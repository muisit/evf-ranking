<?php

/**
 * EVF-Ranking Document Model
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


namespace EVFRanking\Models;

class Document extends Base
{
    public $table = "TD_Document";
    public $pk = "id";
    public $fields = array("id","name", "path","hash","config");
    public $fieldToExport = array(
        "id" => "id",
        "name" => "name",
//        "path" => "path",  // internal field
//        "hash" => "hash",  // internal field
//        "config" => "config"  // internal field
    );
    public $rules = array(
        "id" => "skip",
        "name" => "trim|gte=3|required",
        "path" => "skip",
        "hash" => "skip",
        "config" => "skip",
    );

    private function sortToOrder($sort)
    {
        if (empty($sort)) $sort = "i";
        $orderBy = array();
        for ($i = 0; $i < strlen($sort); $i++) {
            $c = $sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="id asc"; break;
            case 'I': $orderBy[]="id desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter, $special)
    {
        if (!empty(trim($filter))) {
            $filter = str_replace("%", "%%", $filter);
            $qb->where("name", "like", "$filter%");
        }
    }

    public function selectAll($offset, $pagesize, $filter, $sort, $special = null)
    {
        $qb = $this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb, $filter, $special);
        return $qb->get();
    }

    public function findByName($name)
    {
        return array_map(fn ($row) => new Document($row), $this->selectAll(null, null, $name, null, null));
    }

    public function count($filter, $special = null)
    {
        $qb = $this->select("count(*) as cnt");
        $this->addFilter($qb, $filter, $special);
        $result = $qb->get();
 
        if (empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function deleteByName()
    {
        // make sure we delete all summary documents for a set if one of them becomes dirty
        $docs = $this->findByName($this->name);
        foreach ($docs as $doc) {
            $doc->delete();
        }
    }

    public function delete($id = null)
    {
        if ($id === null) $id = $this->getKey();
        $id = intval($id);
        $model = $this->get($id);

        if ($model->exists()) {
            if ($model->fileExists()) {
                $basepath = wp_upload_dir();
                unlink($basepath['basedir'] . $model->path);
            }
        }
        return parent::delete($id);
    }

    public function fileExists()
    {
        return file_exists($this->getPath());
    }

    public function getPath()
    {
        $basepath = wp_upload_dir();
        return $basepath['basedir'] . $this->path;
    }

}
