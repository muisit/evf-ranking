<?php

/**
 * EVF-Ranking QueryBuilder Model
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

 class QueryBuilder {
    private $_model=null;
    private $_issub=false;
    private $_action="select";
    private $_select_fields=array();
    private $_where_clauses=array();
    private $_where_values=array();
    private $_from=null;
    private $_joinclause=array();
    private $_orderbyclause=array();
    private $_groupbyclause=array();
    private $_havingclause=array();
    private $_limit=null;
    private $_offset=null;
    private $_error=false;

    private function _reset_values() {
        $this->_action="select";
        $this->_select_fields=array();
        $this->_where_clauses=array();
        $this->_where_values=array();
        $this->_from=null;
        $this->_joinclause=array();
        $this->_orderbyclause=array();
        $this->_groupbyclause=array();
        $this->_havingclause=array();
        $this->_limit=null;
        $this->_offset=null;
        $this->_error=false;

        if(!empty($this->_model) && isset($this->_model->table)) {
            $this->_from=$this->_model->table;
        }
    }

    public function __construct($model, $issub=false) {
        $this->_model=$model;
        $this->_issub=$issub;
        if(!empty($model) && isset($model->table)) {
            $this->_from=$model->table;
        }
    }

    public function sub() {
        $qb=new QueryBuilder($this, true);
        return $qb;
    }

    public function delete() {
        // delete as subclause not supported
        if($this->_issub || $this->_error || empty($this->_model) || !method_exists($this->_model,"prepare")) {
            $this->_reset_values();
            return "";
        }
        $sql = "DELETE FROM ".$this->_from;
        $sql .= $this->buildClause("where");
        $qry = $this->_model->prepare($sql,$this->_where_values);
        $this->_reset_values();
        return $qry;
    }

    public function update() {
        // update not supported as subclause
        if($this->_issub || sizeof($this->_select_fields) == 0 || $this->_error || empty($this->_model) || !method_exists($this->_model,"prepare")) {
            $this->_reset_values();
            return "";
        }
        $sql = "UPDATE ".$this->_from;
        $sql .= $this->buildClause("join");
        $sql .= $this->buildClause("set");
        $sql .= $this->buildClause("where");
        $qry = $this->_model->prepare($sql,$this->_where_values);
        $this->_reset_values();
        return $qry;
    }

    public function count() {
        if($this->_error) {
            $this->_reset_values();
            return 0;
        }
        $sql = $this->_doget();
        $result = $this->_model->prepare($sql, $this->_where_values);
        $this->_reset_values();
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function first() {
        if($this->_error) {
            $this->_reset_values();
            return null;
        }
        if ($this->_issub) return $this->_dosub();
        $sql = $this->_doget();
        $qry = $this->_model->first($sql, $this->_where_values);
        $this->_reset_values();
        return $qry;
    }
    public function get() {
        if($this->_error) {
            $this->_reset_values();
            return "";
        }
        if($this->_issub) return $this->_dosub();
        $sql = $this->_doget();
        $qry=$this->_model->prepare($sql, $this->_where_values);
        $this->_reset_values();
        return $qry;
    }
    private function _doget() {
        if(empty($this->_from) || empty($this->_select_fields) || $this->_error) {
            return "";
        }
        $sql = strtoupper($this->_action)." "
            .implode(',', array_keys($this->_select_fields))
            ." FROM ".$this->_from;

        $sql .= $this->buildClause("join");
        $sql .= $this->buildClause("where");
        $sql .= $this->buildClause("groupby");
        $sql .= $this->buildClause("having");
        $sql .= $this->buildClause("orderby");
        $sql .= $this->buildClause("limit");
        return $sql;
    }

    private function buildClause($clausename, $skipSyntax=false) {
        $retval="";
        switch($clausename) {
        case 'set':
            $first=true;
            foreach($this->_select_fields as $f=>$n) {
                $id=uniqid();
                if($first) {
                    $retval = " SET ";
                }
                else {
                    $retval.=", ";
                }
                if($n === null) {
                    $retval.="$f=NULL";
                }
                else {
                    $retval.=$f."={".$id."}";
                    $this->_where_values[$id]=$n;
                }
                $first = false;
            }
            break;
        case 'join':
            if(sizeof($this->_joinclause)) {
                foreach($this->_joinclause as $jc) {
                    if(strpos($jc["tab"]," ") !== FALSE) {
                        // add brackets around the subquery
                        $retval .= " " . $jc["dir"] . " JOIN (" . $jc["tab"] . ") " . $jc['al'] . " ON " . $jc['cl'];
                    }
                    else {
                        $retval.= " ".$jc["dir"]." JOIN ".$jc["tab"]." ".$jc['al']." ON ".$jc['cl'];
                    }
                }
            }
            break;
        case 'where':
            if(sizeof($this->_where_clauses)) {
                $first=true;
                foreach($this->_where_clauses as $c) {
                    if($first) {
                        $first=false;
                        if(!$skipSyntax) $retval.=" WHERE ";
                        $retval.=$c[1];
                    }
                    else {
                        $retval .= ' '.$c[0] . ' '.$c[1];
                    }
                }
            }
            break;
        case 'groupby':
            if(sizeof($this->_groupbyclause)) {
                $retval = " GROUP BY ".implode(',',$this->_groupbyclause);
            }
            break;
        case 'having':
            if (sizeof($this->_havingclause)) {
                $retval = " HAVING ".implode(',',$this->_havingclause);
            }
            break;
        case 'orderby':
            if (sizeof($this->_orderbyclause)) {
                $retval= " ORDER BY ".implode(',',$this->_orderbyclause);
            }
            break;
        case 'limit':
            if(!empty($this->_limit) && intval($this->_limit) > 0) {
                $retval .= " LIMIT ".intval($this->_limit);
            }
            if(!empty($this->_offset)) {
                $retval .= " OFFSET ".intval($this->_offset);
            }
            break;
        }
        return $retval;
    }

    private function _dosub() {
        $sql="";

        // allow SELECT in case of exists() clause
        if(!empty($this->_from)) {
            $sql = "SELECT "
                . implode(',', array_keys($this->_select_fields))
                . " FROM " . $this->_from;

            $sql .= $this->buildClause("join");
        }

        // regular WHERE subclause, but without the keyword if we don't have a from
        $sql .= $this->buildClause("where",empty($this->_from));

        // in case of complicated subclauses, support group by and having
        $sql .= $this->buildClause("groupby");
        $sql .= $this->buildClause("having");
        // model is a QueryBuilder
        $this->_model->_where_values = $this->_model->_where_values + $this->_where_values;
        return $sql;
    }

    public function select($f=null, $reset = false) {
        $this->_action="select";
        if ($reset) {
            $this->_select_fields = array();
        }
        if(empty($f)) {
            return $this;
        }
        return $this->fields($f);
    }

    public function fields($f) {
        if(empty($f)) {
            return $this;
        }
        if(is_string($f)) {
            $this->_select_fields[$f]=true;
        }
        else if(is_array($f)) {
            foreach(array_keys($f) as $n) {
                if(is_numeric($n)) {
                    $this->_select_fields[$f[$n]]=true;
                }
                else {
                    $this->_select_fields[$n]=true;
                }
            }
        }
        return $this;
    }

    public function set($f,$v=null) {
        if(empty($f)) {
            return $this;
        }
        if(is_string($f)) {
            $this->_select_fields[$f]=$v;
        }
        else if(is_array($f)) {
            foreach($f as $n=>$v) {
                if(!is_numeric($n)) {
                    $this->_select_fields[$n]=$v;
                }
            }
        }
        return $this;
    }

    public function where($field,$comparison=null,$clause=null,$andor="AND") {
        return $this->andor_where($field,$comparison,$clause,$andor);
    }

    private function andor_where($field,$comparison,$clause,$andor) {
        if($clause === null) {
            // use strict comparison mode to avoid having in_array(0,array('=','<>')) return true
            if(in_array($comparison, array("=", "<>"),true)) {
                // if clause is null, but comparison is = or <>, compare with NULL
                $this->_where($field, $comparison, $clause, $andor);
            }            
            else {
                // where(field,value) => where(field,=,value)
                $this->_where($field,'=',$comparison,$andor);
            }
        }
        else {
            $this->_where($field,$comparison,$clause,$andor);
        }
        return $this;
    }

    public function or_where($field, $comparison=null, $clause=null)
    {
        return $this->andor_where($field, $comparison, $clause, "OR");
    }

    public function where_in($field, $values, $andor="AND")
    {
        $this->_where($field, "in", $values, $andor);
        return $this;
    }

    public function where_exists($callable, $andor="AND") {
        $this->_where($callable, "exists",null,$andor);
        return $this;
    }

    private function _where($field, $comparison, $clause, $andor="AND") {
        if(!empty($comparison) && strtolower($comparison) == "in") {
            if(is_array($clause)) {
                $clause="'" . implode("','", array_map(fn ($c) => $this->escape($c), $clause)) . "'";
            }
            else if(is_callable($clause)) {
                $qb = $this->sub();
                ($clause)($qb);
                $sql = $qb->get();
                $clause = $sql;
            }
            else if(is_object($clause) && !method_exists($clause,"__toString")) {
                $this->_error=true;
                $clause="";
            }
            else {
                $clause=strval($clause);
            }
            $this->_where_clauses[]=array($andor,"$field IN ($clause)");
        }
        else if(!empty($comparison) && strtolower($comparison) == "exists") {
            if (is_callable($field)) {
                $qb = $this->sub();
                ($field)($qb);
                $sql = $qb->get();
                $this->_where_clauses[] = array($andor, "exists(".$sql.")");
            }
            else {
                $this->_error=true;
            }
        }
        else if(is_callable($field)) {
            $qb = $this->sub();
            ($field)($qb);
            $sql = $qb->get();
            $this->_where_clauses[] = array($andor, "(" . $sql . ")");   
        }
        else {
            if($clause === null) {
                // this could be the case where we compare to NULL
                // see if the query contains a space or a = sign. 
                if(strpbrk($field," =") !== false) {
                    // field is a subquery, this is ->where(<subquery>)
                    $this->_where_clauses[] = array($andor, $field);
                }
                else if($comparison == "<>") {
                    $this->_where_clauses[]=array($andor,"$field is not NULL");
                }
                else {
                    // default case, comparision should be '=' or empty
                    $this->_where_clauses[] = array($andor, "$field is NULL");
                }
            }
            else {
                $id=uniqid();            
                $this->_where_values[$id]=$clause;
                // make sure there are spaces between field, comparison ('like') and value
                $this->_where_clauses[]=array($andor,$field.' '.$comparison.' {'.$id.'}');
            }
            // else clause is null does not make sense for any other comparison
        }
    }

    public function from($table, $alias=null) {
        if(is_callable($table) && $alias !== null) {
            $qb=$this->sub();
            ($table)($qb);
            $this->_from="(".$qb->get().") as $alias";
        }
        else {
            $this->_from=$table;
        }
        return $this;
    }

    public function join($table, $alias, $onclause, $dr=null) {
        if(empty($dr)) {
            $dr="left";
        }
        if(is_callable($table)) {
            $qb = $this->sub();
            ($table)($qb);
            $table = $qb->get();
        }
        $this->_joinclause[]=array("tab"=>$table, "al"=>$alias, "cl"=>$onclause, "dir"=>$dr);
        return $this;
    }

    public function orderBy($field, $dr=null, $reset = false) {
        if ($reset) {
            $this->_orderbyclause = array();
        }
        if(is_array($field)) {
            foreach($field as $k=>$v) {
                if(is_numeric($k)) {
                    $this->_orderbyclause[]=trim($v." ".$dr);
                }
                else if(in_array(strtolower($v),array("asc","desc"))) {
                    $this->_orderbyclause[]="$k $v";
                }
                else {
                    $this->_orderbyclause[]=trim($k." ".$dr);
                }
            }
        }
        else {
            $this->_orderbyclause[]=trim($field." ".$dr);
        }
        return $this;
    }

    public function groupBy($field) {
         if(is_array($field)) {
            foreach($field as $v) {
                $this->_groupbyclause[]=$v;
            }
         }
         else {
            $this->_groupbyclause[]=$field;
        }
        return $this;
    }

    public function having($field) {
         if(is_array($field)) {
            foreach($field as $v) {
                $this->_havingclause[]=$v;
            }
         }
         else {
            $this->_havingclause[]=$field;
        }
        return $this;
    }

    public function page($v,$ps=20) {
        $this->_limit=$ps;
        if($v<0) $v=0;
        $this->_offset = $v * $ps;
        return $this;
    }
    public function limit($v) {
        $this->_limit=$v;
        return $this;
    }
    public function offset($v) {
        $this->_offset=$v;
        return $this;
    }

    public function escape($v)
    {
        return str_replace(['\\', '%'], ['\\\\', '%%'], $v);
    }
 }

