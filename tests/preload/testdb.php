<?php

namespace EVFTest;

class TestDatabase
{
    public $data = array();
    public $queries = array();
    public $queryLog = array();

    public function onQuery($query, $cb)
    {
        $this->queries[$query] = $cb;
    }

    public function doQuery($query)
    {
        $this->queryLog[] = $query;

        if (isset($this->queries[$query])) {
            if (is_callable($this->queries[$query])) {
                return $this->queries[$query]($query,$query);
            }
            else {
                return $this->queries[$query];
            }
        }

        foreach ($this->queries as $qry => $cb) {
            if ($qry[0] == '/') {
                $matches = array();
                if (preg_match("/" . $qry . "/i", $query, $matches) === 1) {
                    if (is_callable($cb)) {
                        return $cb($qry, $query, $matches);
                    }
                    else {
                        return $cb;
                    }
                }
            }
        }

        global $evflogger;
        $evflogger->log("No query found for $query");
        return null;
    }

    public function save($otable, $fields)
    {
        $this->queryLog[] = json_encode(array("save", $otable, $fields));
        $table = strtolower($otable);
        if (!isset($this->data[$table])) {
            $this->data[$table] = array();
        }
        $pk = "id";
        if (substr($table, 0, 3) == "td_") {
            $pk = substr($table, 3) . "_id";
        }
        $largestid = 1;
        foreach ($this->data[$table] as $row) {
            if (isset($row[$pk])) {
                if (intval($row[$pk]) > $largestid) {
                    $largestid = intval($row[$pk]);
                }
            }
        }
        global $evflogger;
        $evflogger->log("Saving entry using PK $pk = $largestid");
        $largestid += 1;
        $fields[$pk] = $largestid;
        $this->set($otable, $largestid, $fields);
        return $largestid;
    }

    public function delete($table, $clause)
    {
        $this->queryLog[] = array("delete", $table, $clause);
        $table = strtolower($table);
        $id = 'k' . ($clause[array_keys($clause)[0]]);
        if (!isset($this->data[$table])) {
            $this->data[$table] = array();
        }
        if (isset($this->data[$table][$id])) {
            unset($this->data[$table][$id]);
        }
    }

    public function set($table, $id, $model)
    {
        $this->queryLog[] = json_encode(array("set", $table, $id));
        $table = strtolower($table);
        $id = "k$id";
        if (!isset($this->data[$table])) {
            $this->data[$table] = array();
        }
        $this->data[$table][$id] = $model;
    }

    public function get($table, $id, $byfield = null)
    {
        $table = strtolower($table);
        if ($byfield === null) {
            $id = "k$id";
            if (isset($this->data[$table]) && isset($this->data[$table][$id])) {
                return $this->data[$table][$id];
            }
        } else {
            foreach ($this->data[$table] as $el) {
                if (isset($el[$byfield]) && $el[$byfield] == $id) {
                    return $el;
                }
            }
        }
        return null;
    }

    public function loopAll($table, $cb)
    {
        $table = strtolower($table);
        $retval = [];
        if (isset($this->data[$table])) {
            foreach ($this->data[$table] as $item) {
                if ($cb($item)) {
                    $retval[] = $item;
                }
            }
        }
        return $retval;
    }

    public function getAll($table, $ids, $byfield = null)
    {
        $retval = array();
        foreach ($ids as $id) {
            $retval[] = $this->get($table, $id, $byfield);
        }
        return $retval;
    }

    public function clear($table)
    {
        $table = strtolower($table);
        if (isset($this->data[$table])) {
            unset($this->data[$table]);
        }
    }
}
