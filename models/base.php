<?php

/**
 * EVF-Ranking Base Model
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

 class Base {

    public $table="";
    public $fields=array();
    public $fieldToExport=array();
    public $rules=array();
    public $pk="";
    public $last_id=-1;

    // validation and saving
    public $errors=null;
    public $model=null;

    private $_state="new";
    private $_ori_fields=array();

    public function __construct($id=null,$forceload=false) {
        $this->_state = "new";
        if(!empty($id)) {
            if(is_array($id) || is_object($id)) {
                $this->read($id);
            }
            else {
                $this->{$this->pk} = $id;
                $this->_state="pending";
                if($forceload) $this->load();
            }
        }
        if(sizeof($this->fieldToExport) == 0) {
            foreach($this->fields as $fld) {
                $this->fieldToExport[$fld]=$fld;
            }
        }
    }

    public function getKey() {
        return intval($this->{$this->pk});
    }

    public function setKey($id=null) {
        if($id === null) {
            $id=-1;
        }
        else if($id <=0) {
            $id=-1;
        }
        $this->{$this->pk} = intval($id);
        $this->_state = $id <= 0 ? "new": "pending";
    }

    public function get($id) {
        global $evflogger;
        $evflogger->log("getting by id $id");
        $obj = new static($id);
        $obj->load();
        $pk=$obj->pk;
        if(empty($obj->$pk)) {
            return null;
        }
        return $obj;
    }

    public function exists() {
        $this->load();
        return !($this->isNew());
    }

    public function isNew() {
        return $this->_state == 'new' || $this->getKey() <= 0;
    }

    public function load() {
        //global $evflogger;
        //$evflogger->log("load for ".get_class($this)."->".$this->{$this->pk});
        if($this->_state == "loaded" || $this->_state == "new") { 
            //$evflogger->log("already loaded or new");
            return;
        }

        global $wpdb;

        $pkval = $this->getKey();
        $sql="select * from ".$this->table." where ".$this->pk."=%d";
        $sql = $wpdb->prepare($sql,array($pkval));
        $results = $wpdb->get_results($sql);

        //$evflogger->log("load returns ".json_encode($results));
        if(empty($results) || sizeof($results) != 1) {
            $this->{$this->pk} = -1;
            $this->_state = "new";
        }
        else {
            $this->read($results[0]);
        }
    }

    public function export($result=null) {
        if(empty($result)) {
            $result=$this;
            $this->load();
        }
        $result = (array) $result;
        $retval=array();

        foreach($this->fieldToExport as $fld=>$exp) {
            if(isset($result[$fld])) {
                $retval[$exp] = $result[$fld];
            }
        }
        return $retval;
    }

    public function read($values) {
        $values=(array)$values;
        $this->_state = "reading";
        $values=(array)$values;
        foreach($this->fields as $fld) {
            if(isset($values[$fld])) {
                $this->{$fld} = $values[$fld];
                $this->_ori_fields[$fld]=$values[$fld];
            }
        }
        $this->_state = "loaded";
        if(!isset($this->{$this->pk}) ||  $this->getKey() < 0) {
            $this->_state = "new";
            $this->_ori_fields=array();
        }
    }

    public function save() {
        $fieldstosave=array();
        foreach($this->fields as $f) {
            if($this->differs($f)) {
                $fieldstosave[$f]=$this->$f;
            }
        }
        if(empty($fieldstosave)) {
            global $evflogger;
            $evflogger->log("no fields to save");
        }
        else {
            global $wpdb;
            if($this->isNew()) {
                $wpdb->insert($this->table,$fieldstosave);
                $this->{$this->pk} = $wpdb->insert_id;
                $this->_state = "loaded";
            }
            else {
                $retval=$wpdb->update($this->table, $fieldstosave, array($this->pk => $this->getKey()));
                $this->_state = "loaded";
            }
        }
        // save attached objects
        $this->postSave(!empty($fieldstosave));

        return true;
    }

    public function postSave($wassaved) {
        return true;
    }

    public function identical($other) {
        // if id's match, we're identical
        if(!$this->isNew() && $this->getKey() == $other->getKey()) {
            return true;
        }
        // else, compare all fields
        foreach($this->fields as $field) {
            $v1 = $this->{$field};
            $v2 = $other->{$field};

            if(is_bool($v1)) {
                if(!is_bool($v2) || ($v1 !== $v2)) {
                    return false;
                }
            }
            else if(is_numeric($v1)) {
                $v1 = floatval($v1);
                $v2 = floatval($v2);
                if(abs($v1 - $v2) > 0.000000001) {
                    return false;
                }
            }
            else if(strcmp($v1,$v2)) {
                return false;
            }
        }
        return true;
    }

    private function differs($field) {
        if(!property_exists($this,$field)) {
            return false; // unset fields are never different
        }
        if($field === $this->pk && (!$this->isNew() || $this->{$this->pk} <=0)) {
            return false; // cannot reset the PK
        }
        if(!isset($this->_ori_fields[$field])) {
            return true; // no original found, so always different
        }

        $value=$this->$field;
        $original = $this->_ori_fields[$field];

        if(is_bool($value)) {
            return !is_bool($original) || ($original !== $value);
        }
        if(is_numeric($value)) {
            $value = floatval($value);
            $original=floatval($original);
            return abs($value-$original) > 0.000000001;
        }
        // if we have a null-allowed field and it is filled/cleared, always differs
        if(  ($value === null && $original !== null)
          || ($original === null && $value !== null)) {
            return true;
        }
        return strcmp(strval($value),strval($original)) != 0;
    }

    public function delete($id=null) {
        if($id === null) $id = $this->getKey();
        global $wpdb;
        $retval = $wpdb->delete($this->table, array($this->pk => $id));
        return ($retval !== FALSE || intval($retval) < 1);
    }

    public function __get($key) {
        // this probably doesn't work like this, as values set on the
        // object do not invoke the __get and __set methods...
        if(!isset($this->$key) && $this->_state == "pending") {
            $this->load();
        }
        if(isset($this->$key)) {
            return $this->$key;
        }
        return null;
    }

    public function __set($key,$value) {
        if(!isset($this->$key) && $key != $this->pk && $this->_state == "pending") {
            $this->load();
        }
        $this->$key = $value;
    }

    public function select($p=null) {
        $qb=new QueryBuilder($this);
        return $qb->from($this->table)->select($p);
    }
    public function query($p=null) {
        $qb=new QueryBuilder($this);
        return $qb->from($this->table);
    }
    public function numrows() {
        return $this->select('count(*) as cnt');
    }

    public function first($query,$values) {
        $vals = $this->prepare($query,$values);
        if(!empty($vals) && sizeof($vals)) {
            return $vals[0];
        }
        return null;
    }

    public function prepare($query,$values) {
        global $wpdb;

        if(empty($values)) {
            global $evflogger;
            $evflogger->log("SQL: $query");
            return $wpdb->get_results($query);
        }

        // find all the variables and replace them with proper markers based on the values
        // then prepare the query
        $pattern = "/{[a-f0-9]+}/";
        $matches=array();
        $replvals=array();
        // make sure search terms are not considered parameters
        $query = str_replace("%","%%",$query);
        if(preg_match_all($pattern, $query, $matches)) {
            foreach($matches[0] as $m) {
                $match=trim($m,'{}');
                if(isset($values[$match])) {
                    $v = $values[$match];
                    if(is_float($v)) {
                        $query=str_replace($m,"%f",$query);
                        $replvals[]=$v;
                    }
                    else if(is_int($v)) {
                        $query=str_replace($m,"%d",$query);
                        $replvals[]=$v;
                    }
                    else if(is_null($v)) {
                        $query=str_replace($m,"NULL",$query);
                        $replvals[]=$v;
                    }
                    else if(is_object($v) && method_exists($v,"getKey")) {
                        $query=str_replace($m,"%d",$query);
                        $replvals[]=$v->getKey();
                    }
                    else {
                        $query=str_replace($m,"%s",$query);
                        $replvals[]="$v";
                    }
                }
            }
        }

        global $evflogger;
        $evflogger->log("SQL: $query");
        $evflogger->log("VAL: ".json_encode($replvals));
        $prepared = $wpdb->prepare($query,$replvals);
        return $wpdb->get_results($prepared);
    }

    public function filterData($data, $caps) {
        return $data;
    }

    public function saveFromObject($obj) {
        $validator = new Validator($this);

        if(!$validator->validate($obj)) {
            $this->errors=$validator->errors;
            return false;
        }
        if(!$this->save()) {
            global $wpdb;
            $this->errors = array("Internal database error: ".$wpdb->last_error);
            return false;
        }
        return true;
    }
}
 