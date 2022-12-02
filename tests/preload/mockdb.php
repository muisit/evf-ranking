<?php

namespace EVFTest;

class MockDBClass
{
    public $last_error = "no error";
    public $insert_id = -1;
    public $base_prefix = 'wppref_';

    public function prepare($query, $vals)
    {
        return vsprintf($query, $vals);
    }

    public function get_results($query)
    {
        global $DB;
        return $DB->doQuery($query);
    }

    public function query($query)
    {
        global $DB;
        return $DB->doQuery($query);
    }

    public function delete($table, $id)
    {
        global $DB;
        $DB->delete($table, $id);
    }

    public function insert($table, $fieldstosave)
    {
        global $DB;
        $this->insert_id = $DB->save($table, $fieldstosave);
    }

    public function update($table, $fieldstosave, $pk)
    {
        global $DB;
        $pk = array_values($pk)[0];
        $DB->set($table, $pk, $fieldstosave);
    }

    public function esc_like($text)
    {
        return str_replace("%", "%%", $text);
    }
}
