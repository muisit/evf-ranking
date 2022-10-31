<?php

namespace {

$wp_current_user=1;
function wp_get_current_user() {
    global $wp_current_user;
    global $verbose;
    global $DB;
    return $DB->get("wp_users",$wp_current_user);
}

function current_user_can($capa) {
    global $DB;
    global $wp_current_user;
    $cando = $DB->get("wp_capabilities",$capa."_".$wp_current_user);
    return !empty($cando);
}

function do_action($hookname, $arg) {
    if($hookname == "extlibraries_hookup" && $arg == "tcpdf") {
        require_once('../../ext-libraries/libraries/tcpdf/tcpdf.php');
    }
}

class MockDBClass {
    public $last_error="no error";
    public $insert_id=-1;

    public function prepare($query,$vals) {
        return vsprintf($query,$vals);
    }
    public function get_results($query) {
        global $DB;
        return $DB->doQuery($query);
    }
    public function query($query) {
        global $DB;
        return $DB->doQuery($query);
    }
    public function delete($table, $id) {
        global $DB;
        $DB->delete($table,$id);
    }
    public function insert($table, $fieldstosave) {
        global $DB;
        $this->insert_id = $DB->save($table,$fieldstosave);
    }
    public function update($table,$fieldstosave,$pk) {
        global $DB;
        $pk = array_values($pk)[0];
        $DB->set($table,$pk,$fieldstosave);
    }
    public function esc_like($text) {
        return str_replace("%","%%",$text);
    }
}
$wpdb = new MockDBClass();

class TestLogger {
    public $logvalues=array();

    function log($txt) {
        $this->logvalues[]=$txt;        
    }
    function clear() {
        $this->logvalues=array();
    }
    function emit() {
        $txt=implode("\r\n",$this->logvalues);
        echo $txt."\r\n";
        $this->clear();
    }
}
$evflogger=new TestLogger();
}

namespace EVFTest {

class TestDatabase {
    var $data=array();
    var $queries=array();

    public function onQuery($query,$cb) {
        $this->queries[$query]=$cb;
    }
    public function doQuery($query) {
        if(isset($this->queries[$query])) {
            return $this->queries[$query]($query,$query);
        }

        foreach($this->queries as $qry=>$cb) {
            $matches=array();
            if(preg_match("/" . $qry . "/i",$query,$matches) === 1) {
                return $cb($qry,$query,$matches);
            }
        }

        global $verbose;
        if($verbose) {
            global $evflogger;
            $evflogger->log("No query found for $query");
        }
        return null;
    }

    public function save($otable,$fields) {
        $table = strtolower($otable);
        if (!isset($this->data[$table])) {
            $this->data[$table] = array();
        }
        $pk="id";
        if(substr($table,0,3) == "td_") {
            $pk=substr($table,3) . "_id";
        }
        $largestid=1;
        foreach($this->data[$table] as $row) {
            if(isset($row[$pk])) {
                if(intval($row[$pk]) > $largestid) $largestid = intval($row[$pk]);
            }
        }
        global $verbose;
        if($verbose) {
            global $evflogger;
            $evflogger->log("Saving entry using PK $pk = $largestid");
        }
        $largestid+=1;
        $fields[$pk]=$largestid;
        $this->set($otable,$largestid,$fields);
        return $largestid;
    }

    public function delete($table,$id) {
        $table = strtolower($table);
        $id = "k$id";
        if (!isset($this->data[$table])) {
            $this->data[$table] = array();
        }
        if(isset($this->data[$table][$id])) {
            unset($this->data[$table][$id]);
        }
    }
    public function set($table, $id, $model) {
        $table=strtolower($table);
        $id="k$id";
        if(!isset($this->data[$table])) {
            $this->data[$table] = array();
        }
        $this->data[$table][$id]=$model;
    }
    public function get($table,$id,$byfield=null) {
        $table=strtolower($table);
        if($byfield===null) {
            $id = "k$id";
            if(isset($this->data[$table]) && isset($this->data[$table][$id])) {
                return $this->data[$table][$id];
            }
        }
        else {
            foreach($this->data[$table] as $el) {
                if(isset($el[$byfield]) && $el[$byfield] == $id) {
                    return $el;
                }
            }
        }
        return null;
    }
    public function loopAll($table, $cb) {
        $table = strtolower($table);
        $retval=[];
        if(isset($this->data[$table])) {
            foreach($this->data[$table] as $item) {
                if($cb($item)) {
                    $retval[] = $item;
                }
            }
        }
        return $retval;
    }
    public function getAll($table,$ids,$byfield=null) {
        $retval=array();
        foreach($ids as $id) {
            $retval[]=$this->get($table,$id,$byfield);
        }
        return $retval;
    }
    public function clear($table) {
        $table = strtolower($table);
        if(isset($this->data[$table])) {
            unset($this->data[$table]);
        }
    }
}

$DB = new TestDatabase();


class BaseTest {
    public $name="Base";
    public $success=0;
    public $fails=0;
    public $count=0;
    public $disabled=false;

    public function init_admin($id=null) {
        global $DB;
        if($id === null) {
            $DB->clear("wp_users");
            $id=1;
        }
        $DB->set("wp_users",$id,(object)array(
            "ID"=>$id,
            "user_nicename"=>"Test $id",
            "user_login" => "test$id",
            "user_email" => "test{$id}@test.org"
        ));
        $DB->clear("wp_capabilities");
        $DB->set("wp_capabilities","manage_ranking_$id",array("1"));
        $DB->set("wp_capabilities", "manage_registration_$id", array("1"));
    }
    public function init_ranking($id=null) {
        global $DB;
        if ($id === null) {
            $DB->clear("wp_users");
            $id = 1;
        }
        $DB->set("wp_users",$id,(object)array(
            "ID"=>$id,
            "user_nicename"=>"Test $id",
            "user_login" => "test$id",
            "user_email" => "test${id}@test.org"
        ));
        $DB->clear("wp_capabilities");
        $DB->set("wp_capabilities", "manage_ranking_$id", array("1"));
    }
    public function init_registrar($id=null) {
        global $DB;
        if ($id === null) {
            $DB->clear("wp_users");
            $id = 1;
        }
        $DB->set("wp_users",$id,(object)array(
            "ID"=>$id,
            "user_nicename"=>"Test $id",
            "user_login" => "test$id",
            "user_email" => "test${id}@test.org"
        ));
        $DB->clear("wp_capabilities");
        $DB->set("wp_capabilities", "manage_registration_$id", array("1"));
    }
    public function init_unpriv($id=null) {
        global $DB;
        if ($id === null) {
            $DB->clear("wp_users");
            $id = 1;
        }
        $DB->set("wp_users",$id,(object)array(
            "ID"=>$id,
            "user_nicename"=>"Test $id",
            "user_login" => "test$id",
            "user_email" => "test${id}@test.org"
        ));
        $DB->clear("wp_capabilities");
    }
    public function init_anonymous() {
        global $DB;
        $DB->clear("wp_users");
        $DB->clear("wp_capabilities");
    }

    public function init() {
        // setup basic database elements
        global $DB;

        // create some basic tables with content
        $DB->set("TD_Event_Type",1, array(
            "event_type_id" => 1,
            "event_type_abbr" => "E",
            "event_type_name" => "European Individual",
            "event_type_group" => "Fencer"
        ));
        $DB->onQuery('^select +\* +from +([a-zA-Z_]*) +where +([a-zA-Z_]*_?id)=([-0-9]+)$', function($pattern, $qry,$matches) {
            global $DB;
            global $evflogger;
            $evflogger->log("SQL $qry");
            $field=$matches[2];
            $value=$matches[3];
            return $DB->loopAll($matches[1],function($item) use($field,$value) {
                global $evflogger;
                //$evflogger->log("testing for field $field and $value on ".json_encode($item));
                return isset($item[$field]) && $item[$field]==$value;
            });
        });
        $DB->set("TD_Category",1,array(
            "category_id" => 1, 
            "category_abbr" => "", 
            "category_name" => "Cat 1", 
            "category_type" => "I", 
            "category_value" => 1
        ));

        $DB->set("TD_Weapon",1,array(
            "weapon_id"=>1, 
            "weapon_abbr" => "MF", 
            "weapon_name" => "Mens Foil", 
            "weapon_gender" => "M"
        ));

        $DB->set("TD_RoleType", 1, array(
            "role_type_id" => 1, 
            "role_type_name" =>"Federation non-fencers", 
            "org_declaration" => "Country"
        ));

        $DB->set("TD_Role", 1, array(
            "role_id" => 1,
            "role_name" => "Fencer",
            "role_type" => 1
        ));

        $DB->set("TD_Country",1,array(
            "country_id" => 1, 
            "country_abbr" => "NED", 
            "country_name" => "Netherlands", 
            "country_registered" => 'Y'
        ));
        $DB->set("TD_Country",2,array(
            "country_id" => 2, 
            "country_abbr" => "GER", 
            "country_name" => "Germany", 
            "country_registered" => 'Y'
        ));
    }

    public function assert($result, $subtest) {
        global $verbose;
        global $evflogger;
        $retval=false;
        if($result) {
            $this->success+=1;
            $evflogger->clear();
            $retval=true;
        }
        else {
            if($verbose) {
                $evflogger->log("$subtest fails");
                $evflogger->emit();
            }
            $this->fails+=1;
            $retval=false;
        }
        $this->count+=1;
        return $retval;
    }

    public function expects($result, $output, $subtest) {
        global $verbose;
        global $evflogger;
        $retval=false;
        if($result === $output) {
            $this->success+=1;
            $evflogger->clear();
            $retval=true;
        }
        else {
            if($verbose) {
                $evflogger->log("expected: ".$output);
                $evflogger->log("received: ".$result);
                $evflogger->log("$subtest fails");
                $evflogger->emit();
            }
            $this->fails+=1;
            $retval=false;
        }
        $this->count+=1;
        return $retval;
    }

    public function run() {
        global $verbose;
        $this->success=0;
        $this->fails=0;
        $this->count=0;

        $prevcount=0;
        $prevfail=0;

        $this->init();

        $methods=get_class_methods($this);
        foreach($methods as $m) {
            if(substr(strtolower($m),0,5) == "test_") {
                if(!$verbose) echo "Running ".substr($m,5)."... ";
                else echo "Test $m\r\n";
                try {
                    $this->$m();
                }
                catch(\Exception $e) {
                    echo " caught exception ".$e->getMessage()."\r\n";
                }

                if($this->fails - $prevfail > 0) {
                    echo "failed: ".($this->fails - $prevfail). " out of ".($this->count-$prevcount);
                }
                else {
                    echo "all good";
                }
                echo "\r\n";

                $prevcount=$this->count;
                $prevfail=$this->fails;
            }
        }
    }

    public function loadPolicy() {
        $obj = new \EVFRanking\Lib\Policy();
        return $obj;
    }

    public function loadModel($cname) {
        $cname="\\EVFRanking\\Models\\$cname";
        $model=new $cname();
        return $model;
    }
}

} // end of namespace EVFTest