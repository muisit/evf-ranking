<?php 
namespace EVFTest\Models;

class BaseMock {
    public $log=array("id"=>12);
    public $data=array();
    public static $lastobject;

    public function __construct($args,$doload) {
        BaseMock::$lastobject = $this;
    }
    public function __call($name, $args) {
        $this->log[]="$name(".json_encode($args).")";
        return array_merge(array("a"=>$name),$args);
    }

    public function export() {
        return array_merge(array("export"),$this->data);
    }
    public function getKey() {
        return $this->data["id"];
    }

    public function selectAll($offset, $pagesize, $filter, $sort, $special) {
        $this->log[]="selectAll(".json_encode(array($offset,$pagesize,$filter,$sort,$special)).")";
        return array(
            array("entry"),
            array("entry"),
            array("entry"),
            array("entry"),
            array("entry"),
            array("entry"),
            array("entry"),
            array("entry"),
            array("entry"),
            array("entry"),
        );
    }

    public function count($filter, $special) {
        $this->log[]="count(".json_encode($filter).",".json_encode($special).")";
        return 10;
    }
}

class Fencer extends BaseMock {};
class Result extends BaseMock {};
class Ranking extends BaseMock{};
class Event extends BaseMock{};
class SideEvent extends BaseMock{};
class Accreditation extends BaseMock{};
class AccreditationTemplate extends BaseMock{};
class Category extends BaseMock{};
class Competition extends BaseMock{};
class Country extends BaseMock {};
class EventRole extends BaseMock{};
class EventType extends BaseMock{};
class Migration extends BaseMock{};
class Posts extends BaseMock{};
class Queue extends BaseMock{};
class Registrar extends BaseMock{};
class Registration extends BaseMock{};
class Role extends BaseMock{};
class RoleType extends BaseMock{};
class User extends BaseMock{};
class Weapon extends BaseMock{};
