<?php

/**
 * EVF-Ranking Migration Model
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

class Migration extends Base
{
    public $table = "TD_Migration";
    public $pk = "id";
    public $fields = array("id","name","status");
    public $rules = array(
        "id" => "skip",
        "name" => "skip",
        "status" => "int"
    );

    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);

        global $wpdb;
        $sql = "select count(*) as cnt from TD_Migration";
        $result = $wpdb->get_results($sql);
        $results = array();
        if (empty($result) || !is_array($result)) {
            $sql = "CREATE TABLE `TD_Migration` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `name` VARCHAR(255) NOT NULL , 
                `status` INT NOT NULL, 
                PRIMARY KEY (`id`)) ENGINE = InnoDB; ";
            $wpdb->query($sql);
        }
    }

    public function save()
    {
        if (parent::save() && intval($this->status) == 1) {
            try {
                ob_start();
                $this->execute();
                ob_end_clean();
            }
            catch (Exception $e) {
                // pass
            }
        }
        return true;
    }

    public function selectAll($offset, $pagesize, $filter, $sort, $special)
    {
        $this->synchroniseMigrations();
        return $this->select('*')->offset($offset)->limit($pagesize)->orderBy(array("id desc"))->get();
    }

    public function count($filter = null)
    {
        $result = $this->select("count(*) as cnt")->get();
        if (empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function export($result = null)
    {
        if (empty($result)) {
            $result = $this;
        }
        return array(
            "id" => $result->id,
            "name" => $result->name,
            "status" => $result->status
        );
    }

    private function loadClassFile($filename)
    {
        $classes = get_declared_classes();
        require_once($filename);
        $diff = array_diff(get_declared_classes(), $classes);
        $class = reset($diff);
        if (!empty($class)) {
            $model = new $class();
            return $model;
        }
        return null;
    }

    private function synchroniseMigrations()
    {
        // load all the migration objects from the migrations subfolder
        $objects = scandir(dirname(__FILE__) . '/migrations');
        $allmigrations = array();
        foreach ($objects as $filename) {
            $path = dirname(__FILE__) . "/migrations/" . $filename;
            if ($filename != '.' && $filename != '..' && is_file($path)) {
                $model = $this->loadClassFile($path);
                if (!empty($model)) {
                    $model->checkDb();
                    $allmigrations[$model->name] = $model;
                }
            }
        }
        return $allmigrations;
    }

    public function execute()
    {
        $retval = -1;
        ob_start();
        try {
            $allMigrations = $this->synchroniseMigrations();
            if (isset($allMigrations[$this->name])) {
                $model = $allMigrations[$this->name];
            }

            if (!empty($model)) {
                if (intval($model->status) == 0) {
                    if ($model->up()) {
                        $model->status = 1;
                        $model->save();
                        $retval = 1;
                    }
                }
                else {
                    if ($model->down()) {
                        $model->status = 0;
                        $model->save();
                        $retval = 0;
                    }
                }
            }
        }
        catch (Exception $e) {
            error_log("caught exception on migration: " . $e->getMessage());
        }
        ob_end_clean();
        return $retval;
    }
}
