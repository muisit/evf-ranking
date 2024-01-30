<?php

/**
 * EVF-Ranking ExportManager Interface
 *
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020 Michiel Uitdehaag for muis IT
 * @licenses            GPL-3.0-or-later
 *
 * This file is part of evf-ranking.
 *
 * evf-ranking is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * evf-ranking is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with evf-ranking.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace EVFRanking\Lib;

use EVFRanking\Models\Category;
use EVFRanking\Models\Weapon;
use EVFRanking\Models\Ranking;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportManager
{
    public function download($type)
    {
        if (Display::$policy === null) {
            Display::$policy = new Policy();
        }
        $user = Display::$policy->findUser();
        $retval = null;
        
        switch ($type) {
            case 'ranking':
                $withDetails = isset($user['download']) && $user['download'];
                return $this->downloadRankings($withDetails);
        }

        die(404);
    }

    private function createSpreadSheet($name, $sheets)
    {
        $index = 0;
        $spreadsheet = new Spreadsheet();
        foreach ($sheets as $sheet) {
            $sheetName = $sheet['name'];
            $sheetContent = $sheet['content'];
            $index += 1;
            if ($index > 1) {
                $spreadsheet->createSheet();
            }
            $activeWorksheet = $spreadsheet->getSheet($index - 1);
            $activeWorksheet->setTitle($sheetName);
            $this->fillSheet($activeWorksheet, $sheetContent);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        $writer->save('php://output');
        exit(0);
    }

    private function fillSheet($sheet, $sheetContent)
    {
        $colNames = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $rownum = 0;
        foreach ($sheetContent as $row) {
            $rownum += 1;
            $colnum = 0;
            foreach ($row as $cell) {
                $colName = $colNames[$colnum];
                $colnum += 1;
                $sheet->setCellValue($colName . $rownum, $cell);
            }
        }
        $sheet->setAutoFilter('A1:' . $colName . $rownum);
    }


    private function downloadRankings($withDetails = false)
    {
        $allweapons = Weapon::ExportAll(false);
        $allcategories = Category::ExportAll(false);
        $ranking = new Ranking();
        $headers = ['Position', 'Lastname', 'Firstname', 'Country', 'Points'];
        if ($withDetails) {
            $headers[] = 'DOB';
        }

        $spreadsheet = [];
        foreach ($allweapons as $weapon) {
            foreach ($allcategories as $category) {
                if ($category->category_type == 'I' && $category->category_value < 5) {
                    $results = $ranking->listResults($weapon->weapon_id, $category, $withDetails);
                    $sheet = [
                        'name' => $weapon->weapon_name . ' ' . $category->category_name,
                        'content' => [$headers]
                    ];
                    foreach ($results as $result) {
                        $row = [];
                        foreach ($headers as $header) {
                            switch ($header) {
                                case 'Position':
                                    $row[] = $result['pos'] ?? '-';
                                    break;
                                case 'Lastname':
                                    $row[] = strtoupper($result['name'] ?? '');
                                    break;
                                case 'Firstname':
                                    $row[] = $result['firstname'] ?? '';
                                    break;
                                case 'Country':
                                    $row[] = strtoupper($result['country'] ?? 'OTH');
                                    break;
                                case 'Points':
                                    $row[] = $result['points'] ?? '0.00';
                                    break;
                                case 'DOB':
                                    $row[] = $result['dob'] ?? '';
                                    break;
                                default:
                                    $row[] = '';
                            }
                        }
                        $sheet['content'][] = $row;
                    }
                    $spreadsheet[] = $sheet;
                }
            }
        }
        return $this->createSpreadSheet("EVFRanking_" . date('Ymd') . '.xlsx', $spreadsheet);
    }
}
