<?php

/**
 * EVF-Ranking WPUser Model
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

 class User extends Base {
    public $table = "users";
    public $pk = "ID";
    public $fields = array("ID", "user_nicename", "user_email", "user_login");
    public $fieldToExport = array(
        "ID" => "id",
        "user_nicename" => "name",
        "user_email" => "email",
        "user_login" => "login"
    );

    public function __construct($id=null,$forceload=false) {
        global $wpdb;
        $this->table = $wpdb->base_prefix.$this->table;
        parent::__construct($id,$forceload);
    }

    public function save() {
        return false;
    }
    public function delete($id=null) {
        return false;
    }

    private function sortToOrder($sort) {
        return array("user_nicename asc","ID desc");
    }

    private function addFilter($qb, $filter, $special) {
        if (!empty(trim($filter))) {
            global $wpdb;
            $filter = $wpdb->esc_like($filter);
            $qb->where(function ($qb2) use ($filter) {
                $qb2->where("user_nicename like '%$filter%' or user_login like '%$filter%'");
            });
        }
    }

    public function selectAll($offset, $pagesize, $filter, $sort, $special = null) {
        $qb = $this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb, $filter, $special);
        return $qb->get();
    }

    public function count($filter, $special = null) {
        $qb = $this->numrows();
        $this->addFilter($qb, $filter, $special);
        return $qb->count();
    }
 }
 