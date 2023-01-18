<?php

/**
 * EVF-Ranking MigrationObject Model
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

class MigrationObject extends Migration
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = strtolower(get_class($this));
        if (strpos($this->name, '\\') !== false) {
            $this->name = substr($this->name, strrpos($this->name, '\\') + 1);
        }
        if (strpos($this->name, 'migration') === 0) {
            $this->name = substr($this->name, 9);
        }
    }

    public function save()
    {
        error_log("saving migration object");
        // when we save the MigrationObject, it is always new and unexecuted
        $this->{$this->pk} = null;
        $this->state = 0;
        parent::save();
    }

    public function existsByName()
    {
        error_log("existsByName $this->name");
        $results = $this->numrows()->where('name', $this->name)->count();
        error_log("results is ".json_encode($results));
        return $results > 0;
    }

    public function checkDb()
    {
        if (!$this->existsByName()) {
            // this migrates filename and classname to the database
            $this->save();
        }
    }

    public function find()
    {
        $res = $this->select("*")->where("name", $this->name)->get();
        if (sizeof($res) > 0) {
            return new Migration($res[0]);
        }
        return new Migration();
    }

    public function rawQuery($txt)
    {
        global $wpdb;
        return $wpdb->query($txt);
    }

    public function up()
    {
        error_log("abstract parent UP");
    }

    public function down()
    {
        error_log("abstract parent DOWN");
    }

    public function tableName($name)
    {
        global $wpdb;
        return $wpdb->base_prefix . $name;
    }

    public function tableExists($tablename)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
        return $wpdb->get_var($query) == $table_name;
    }

    public function columnExists($tablename, $columnname)
    {
        global $wpdb;
        $query = $wpdb->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = %s', $wpdb->esc_like($this->tableName($tablename)), $wpdb->esc_like($columnname));
        return $wpdb->get_var($query) == $columnname;
    }

    public function createTable($tablename, $content)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        return $wpdb->query("CREATE TABLE $table_name $content;");
    }

    public function dropTable($tablename)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        return $wpdb->query("DROP TABLE $table_name;");
    }
}
