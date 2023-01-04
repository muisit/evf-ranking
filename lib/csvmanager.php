<?php

/**
 * EVF-Ranking CSVManager
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

class CSVManager extends BaseLib
{
    public function import()
    {
        $retval = array();
        foreach ($_FILES as $username => $content) {
            $loc = $content["tmp_name"];
            $fname = $content["name"];

            if (!empty($loc) && file_exists($loc) && is_readable($loc)) {
                $this->convertFile($loc);
                $retval = $this->importCSV($loc);
            } else {
                $retval["error"] = "Unable to read file, please upload a valid file";
            }
        }
        return $retval;
    }

    private function convertFile($fname)
    {
        $contents = file_get_contents($fname);
        // see https://stackoverflow.com/questions/32184933/remove-bom-%c3%af-from-imported-csv-file/58647535#58647535
        switch (true) {
            case (substr($contents, 0, 4) == "\xff\xfe\x00\x00"):
                $contents = mb_convert_encoding(substr($contents, 4), "UTF-8", "UTF-32LE");
                break;
            case (substr($contents, 0, 4) == "\x00\x00\xfe\xff"):
                $contents = mb_convert_encoding(substr($contents, 4), "UTF-8", "UTF-32BE");
                break;
            case (substr($contents, 0, 3) == "\xef\xbb\xbf"):
                $string = substr($contents, 3);
                break;
            case (substr($contents, 0, 2) == "\xfe\xff"):
                $string = mb_convert_encoding(substr($contents, 2), "UTF-8", "UTF-16BE");
                break;
            case (substr($contents, 0, 2) == "\xff\xfe"):
                $string = mb_convert_encoding(substr($contents, 2), "UTF-8", "UTF-16LE");
                break;
        };

        if (!mb_check_encoding($contents, 'UTF-8')) {
            if (mb_check_encoding($contents, 'Windows-1252')) {
                $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
            }
            else if (mb_check_encoding($contents, 'ISO-8859-1')) {
                $contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-1');
            }
            else if (mb_check_encoding($contents, 'UTF-16')) {
                $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-16');
            }
            else if (mb_check_encoding($contents, 'UTF-32')) {
                $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-32');
            }
        }
        file_put_contents($fname, $contents);
    }

    private function importCSV($fname)
    {
        $f = fopen($fname, "r");
        if (!empty($f)) {
            $sep = ';';
            $contents = fgetcsv($f, null, $sep);
            fclose($f);

            if (empty($contents) || !is_array($contents) || count($contents) == 0) {
                return ["error" => "No valid content found, please upload a valid file"];
            }
            else if (count($contents) == 1) {
                $sep = ',';
            }

            $f = fopen($fname, "r");
            $contents = [];
            while (($data = fgetcsv($f, null, $sep)) !== false) {
                $contents[] = $data;
            }
            fclose($f);
            // require at least lastname, firstname
            if (empty($contents) || !is_array($contents) || count($contents) == 0 || count($contents[0]) < 2) {
                return ["error" => "No valid content found, please upload a valid file"];
            }
            return ["model" => $contents];
        }
        return ["error" => "Unable to read file, please upload a valid file"];
    }
}
