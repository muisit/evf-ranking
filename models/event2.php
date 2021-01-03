<?php

/**
 * EVF-Ranking Country Model
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

 class Event extends Base {

    public function __construct($id=null) {
        $this->table = "";
        $this->pk="event_id";
        $this->fields=array("id","name","start","end", "competitions");
        parent::__construct($id);
    }

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="p.ID asc"; break;
            case 'I': $orderBy[]="p.ID desc"; break;
            case 'n': $orderBy[]="p.post_title asc"; break;
            case 'N': $orderBy[]="p.post_title desc"; break;
            case 'd': $orderBy[]="s.meta_value asc"; break;
            case 'D': $orderBy[]="s.meta_value desc"; break;
            }
        }
        return " order by ".implode(',',$orderBy);
    }

    private function addFilter($filter) {
        if(!empty(trim($filter))) {
            global $wpdb;
            error_log("filter not empty");
            $filter=$wpdb->esc_like($filter);
            return " p.title like '%$filter%' ";
        }
        return "";
    }


    private function rawquery($offset=null, $pagesize=null,$filter=null,$sort=null) {
        $sql= "from wp_posts as p "
            ." left join wp_postmeta s on s.post_id=p.ID and s.meta_key='_EventStartDateUTC' "
            ." left join wp_postmeta e on e.post_id=p.ID and e.meta_key='_EventEndDateUTC' "
            ." where post_type='tribe_events' and post_status='publish' ";
         
        $sql .= $this->addFilter($filter);
        $sql .= $this->sortToOrder($sort);

        if(!empty($pagesize)) {
            $sql .= " LIMIT ".intval($pagesize);
        }
        if(!empty($offset)) {
            $sql .= " OFFSET ".intval($offset);
        }
        return $sql;
    }

    private function normalize($result, $model) {
        // find the competitions belonging to this result
        require_once(__DIR__ . "/competition.php");
        $model = new Competition();
        $dt = $model->listByEvent($result->ID);
        $competitions=array();
        $retval=array("competitions"=>array());
        if(!empty($dt) && is_array($dt)) {
            foreach($dt as $c) {
                $retval["competitions"][]=$model->export($c);
            }
        }
        $retval["id"]=$result->ID;
        $retval['name']=$result->post_title;
        $retval['start']=$result->start;
        $retval['end']=$result->end;
        return (object)$retval;
    }

    public function selectAll($offset=0,$pagesize=0,$filter=null,$sort=null) {
        require_once(__DIR__ . "/competition.php");
        $model = new Competition();
        $sql = "select p.*, s.meta_value as start, e.meta_value as end ".$this->rawquery($offset,$pagesize,$filter,$sort);
        error_log("selecting events using ".$sql);
        global $wpdb;
        $result = $wpdb->get_results($sql);
        $results=array();
        if(!empty($result) && is_array($result)) {
            error_log('received results '.json_encode($result));
            $self=$this;
            array_walk($result,function($v,$k) use (&$results,$self, $model) {
                error_log('normalizing model '.json_encode($v));
                $results[]=$self->normalize($v,$model);
            });
        }
        else {
            error_log("result is empty ".json_encode($result));
        }
        error_log("returning ".json_encode($results));
        return $results;
    }

    public function count($filter=null) {
        global $wpdb;
        $result = $wpdb->query("select count(*) as cnt ".$this->rawquery());
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }
 }
 