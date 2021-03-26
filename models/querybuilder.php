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
    public function __construct($model, $issub=false) {
        $this->_model=$model;
        $this->_issub=$issub;
    }

    public function sub() {
        $qb=new QueryBuilder($this, true);
        return $qb;
    }

    private $_action="select";
    public function delete() {
        if($this->_issub) return "";
        $sql = "DELETE FROM ".$this->_from;
        $sql .= $this->buildClause("where");
        return $this->_model->prepare($sql,$this->_where_values);
    }

    public function update() {
        if($this->_issub || sizeof($this->_select_fields) == 0) return "";
        $sql = "UPDATE ".$this->_from;
        $sql .= $this->buildClause("join");
        $sql .= $this->buildClause("set");
        $sql .= $this->buildClause("where");
        return $this->_model->prepare($sql,$this->_where_values);
    }

    public function count() {
        $sql = $this->_doget();
        $result = $this->_model->prepare($sql, $this->_where_values);
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function first() {
        if ($this->_issub) return $this->_dosub();
        $sql = $this->_doget();
        return $this->_model->first($sql, $this->_where_values);
    }
    public function get() {
        if($this->_issub) return $this->_dosub();
        $sql = $this->_doget();
        return $this->_model->prepare($sql, $this->_where_values);
    }
    private function _doget() {
        //error_log('query builder for get');
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
                    $retval.= " ".$jc["dir"]." JOIN ".$jc["tab"]." ".$jc['al']." ON ".$jc['cl'];
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
        //error_log("is subclause builder");
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
        // model is a QueryBuilder
        $this->_model->_where_values = array_merge($this->_model->_where_values, $this->_where_values);
        return $sql;
    }

    private $_select_fields=array();
    public function select($f=null) {
        $this->_action="select";
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

    public function set($f,$v) {
        if(empty($f)) {
            return $this;
        }
        if(is_string($f)) {
            $this->_select_fields[$f]=$v;
        }
        else if(is_array($f)) {
            foreach(array_keys($f) as $n=>$v) {
                $this->_select_fields[$n]=$v;
            }
        }
        return $this;
    }

    private $_where_clauses=array();
    private $_where_values=array();
    public function where($field,$comparison=null,$clause=null) {
        return $this->andor_where($field,$comparison,$clause,"AND");
    }

    private function andor_where($field,$comparison,$clause,$andor) {
        if(empty($clause)) {
            // if clause is null, but comparison is = or <>, compare with NULL
            if (empty($comparison) || in_array($comparison, array("=", "<>"))) {
                if(is_array($field)) {
                    foreach($field as $k=>$v) {
                        $this->_where($k,'=',$v,$andor);
                    }
                }
                else {
                    $this->_where($field, $comparison, $clause, $andor);
                }
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

    public function or_where($field,$comparison=null,$clause=null) {
        return $this->andor_where($field, $comparison, $clause, "OR");
    }

    public function where_in($field, $values) {
        $this->_where($field,"in",$values);
        return $this;
    }    

    public function where_exists($callable, $andor="AND") {
        $this->_where($callable, "exists",null,$andor);
        return $this;
    }

    private function _where($field, $comparison, $clause, $andor="AND") {
        if(strtolower($comparison) == "in") {
            if(is_array($clause)) {
                $clause="('".implode("','",$clause)."')";
            }
            $this->_where_clauses[]=array($andor,"$field IN $clause");
        }
        else if(strtolower($comparison) == "exists") {
            if (is_callable($field)) {
                $qb = $this->sub();
                ($field)($qb);
                $sql = $qb->get();
                $this->_where_clauses[] = array($andor, "exists(".$sql.")");
            }
        }
        else if(is_callable($field) && empty($comparison) && empty($clause)) {
            $qb = $this->sub();
            //error_log("calling where clause");
            ($field)($qb);
            $sql = $qb->get();
            $this->_where_clauses[] = array($andor, "(" . $sql . ")");   
        }
        else {
            if($clause === null) {
                // this could be the case where we compare to NULL
                // see if the query contains a space or a = sign. 
                if(strpbrk($field," =") === false) {
                    // regular field, compare to null
                    $this->_where_clauses[] = array($andor, "$field is NULL");
                }
                else {
                    $this->_where_clauses[] = array($andor, $field);
                }
            }
            else if($clause === null && $comparison == "<>") {
                $this->_where_clauses[]=array($andor,"$field is not NULL");
            }
            else if($clause === null && $comparison == "=") {
                $this->_where_clauses[] = array($andor, "$field is NULL");
            }
            else if($clause !== null) {
                $id=uniqid();            
                $this->_where_values[$id]=$clause;
                $this->_where_clauses[]=array($andor,$field.$comparison.'{'.$id.'}');
            }
            // else clause is null does not make sense for any other comparison
        }
    }

    private $_from=null;
    public function from($table) {
        $this->_from=$table;
        return $this;
    }

    private $_joinclause=array();
    public function join($table, $alias, $onclause, $dr=null) {
        if(empty($dr)) {
            $dr="left";
        }
        $this->_joinclause[]=array("tab"=>$table, "al"=>$alias, "cl"=>$onclause, "dir"=>$dr);
        return $this;
    }

    private $_orderbyclause=array();
    public function orderBy($field, $dr=null) {
        if(is_array($field)) {
            //error_log('array of orderBy');
            foreach($field as $k=>$v) {
                if(is_numeric($k)) {
                    //error_log('field is numeric, orderBy is '.$v);
                    $this->_orderbyclause[]=$v;
                }
                else if(in_array(strtolower($v),array("asc","desc"))) {
                    //error_log('value is asc/desc, key is '.$k.' '.$v);
                    $this->_orderbyclause[]="$k $v";
                }
                else {
                    //error_log('full clause in key '.$k);
                    $this->_orderbyclause[]=$k;
                }
            }
        }
        else {
            $this->_orderbyclause[]=trim($field." ".$dr);
        }
        return $this;
    }

    private $_groupbyclause=array();
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

    private $_havingclause=array();
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

    private $_limit=null;
    private $_offset=null;
    public function page($v,$ps=20) {
        $this->_limit=$ps;
        if($v<1) $v=1;
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

 }

