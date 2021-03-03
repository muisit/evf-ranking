<?php

/**
 * EVF-Ranking WP Frontend Events Model
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


 namespace EVFRanking;

 class Posts extends Base {
    public $table = "wp_posts";
    public $pk = "ID";
    public $fields = array("ID", "post_title", "post_status", "post_type","post_content");
    public $fieldToExport = array(
        "ID" => "id",
        "post_title" => "title",
        "post_status" => "status",
        "post_type" => "type",
        "post_content" => "content"
    );

    public function __construct($id=null) {
        parent::__construct($id);
    }

    public function save() {
        return false;
    }
    public function delete($id=null) {
        return false;
    }

    private function sortToOrder($sort) {
        return array("post_title asc","ID desc");
    }

    private function addFilter($qb, $filter, $special) {
        if (!empty(trim($filter))) {
            global $wpdb;
            $filter = $wpdb->esc_like($filter);
            $qb->where("post_title like '%$filter%'");
        }
        error_log("special is ".json_encode($special));
        if ($special) {
            $doc = json_decode($special);
            if (is_object($doc)) {
                if (isset($doc->events)) {
                    $qb->where("post_type", "tribe_events");
                }
            }
        }
        $qb->where("post_status", "publish");
    }

    public function selectAll($offset, $pagesize, $filter, $sort, $special = null) {
        error_log("selecting all posts");
        $qb = $this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb, $filter, $special);
        return $qb->get();
    }

    public function count($filter, $special = null) {
        $qb = $this->select("count(*) as cnt");
        $this->addFilter($qb, $filter, $special);
        $result = $qb->get();

        if (empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }
 }
 