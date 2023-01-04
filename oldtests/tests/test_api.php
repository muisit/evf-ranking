<?php

namespace EVFTest;

require_once(__DIR__.'../../support/test_api_mocks.php');

class Test_Api extends BaseTest {
    public $disabled=false;

    public function init() {
    }

    public function do_one($path,$data,$expected,$msg) {
        $api=new TestApi();
        $api->testPost(array(
            "nonce" => "test",
            "path" => $path,
            "model" => $data
        ));
        $this->expects($api->output(),$expected,$msg);
    }

    public function do_test_api($name, $basepath, $data, $expected, $specials) {
        $this->do_one($basepath,$data["list"],$expected["list"],"api_$name 1: list call");
        $this->do_one($basepath."/list",$data["list"],$expected["list"],"api_$name 2: list call");
        $this->do_one($basepath."/bogus",$data["list"],$expected["list"],"api_$name 3: list call");

        $this->do_one($basepath."/save",$data["save"],$expected["save"],"api_$name 4: save call");
        $this->do_one($basepath."/view",$data["save"],$expected["view"],"api_$name 5: view call");
        $this->do_one($basepath."/delete",$data["save"],$expected["delete"],"api_$name 6: delete call");

        foreach($specials as $spname => $spdata) {
            $this->do_one("$basepath/$spname",$data[$spdata],$expected[$spname],"api_$name 7: $spname call");
        }

        $otherpaths = array("competitions","sides","roles","importcheck","import",
            "recalculate","clear","delpic","example","default","defaults","loaddefault",
            "presavecheck","merge","reset","detail","overview","regenerate","check",
            "fencer","generate","generateone","nonexistingpath");
        foreach($otherpaths as $path) {
            // skip the ones that are actually implemented
            if(in_array($path,array_keys($specials))) continue;
            $this->do_one("$basepath/$path",$data["other"],$expected["other"],"api_$name 8: $path call");
        }
    }

    public function test_api_accreditation() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
            "event" => array("event"=>'13'),
            "fencer" => array("event"=>'14','fencer'=>'12'),
            "generate"=>array("event"=>'15','type'=>'BlaBla','type_id'=>'1221')
        );
        $specials=array(
            "overview"=>"event",
            "regenerate"=>"event",
            "check"=>"event",
            "fencer"=>"fencer",
            "generate"=>"generate",
            "generateone"=> "fencer"
        );
        $expected=array(
            "list" => 'nonce check test//policy check accreditation view {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//{"error":"invalid action"}//12',
            "save" => 'nonce check test//policy check accreditation view {"filter":"","model":{"special":"data"}}//{"error":"invalid action"}//12',
            "view" => 'nonce check test//policy check accreditation view {"filter":"","model":{"special":"data"}}//{"error":"invalid action"}//12',
            "delete" => 'nonce check test//policy check accreditation view {"filter":"","model":{"special":"data"}}//{"error":"invalid action"}//12',
            "other" => 'nonce check test//policy check accreditation view {"filter":"","model":{"special":"data"}}//{"error":"invalid action"}//12',
            "overview"=>'nonce check test//policy check accreditation view {"filter":"","model":{"event":"13"}}//{"a":"overview","0":13}//12,overview([13])',
            "regenerate"=>'nonce check test//policy check accreditation view {"filter":"","model":{"event":"13"}}//{"a":"regenerate","0":13}//12,regenerate([13])',
            "check"=>'nonce check test//policy check accreditation view {"filter":"","model":{"event":"13"}}//{"a":"checkSummaryDocuments","0":13}//12,checkSummaryDocuments([13])',
            "fencer"=>'nonce check test//policy check accreditation view {"filter":"","model":{"event":"14","fencer":"12"}}//{"a":"findAccreditations","0":14,"1":12}//12,findAccreditations([14,12])',
            "generate"=>'nonce check test//policy check accreditation view {"filter":"","model":{"event":"15","type":"BlaBla","type_id":"1221"}}//{"a":"generate","0":15,"1":"BlaBla","2":1221}//12,generate([15,"BlaBla",1221])',
            "generateone"=> 'nonce check test//policy check accreditation view {"filter":"","model":{"event":"14","fencer":"12"}}//{"a":"generateForFencer","0":14,"1":12}//12,generateForFencer([14,12])'
        );

        $this->do_test_api("accreditation","accreditation",$data, $expected, $specials);
    }    

    public function test_api_registration() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
            "overview"=>array("event"=>'21')
        );
        $specials=array(
            "overview"=>"overview"
        );
        $expected=array(
            "list" => 'nonce check test//policy check registration list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Registration 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check registration save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Registration {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Registration"}//12',
            "view" => 'nonce check test//policy check registration list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Registration 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "delete" => 'nonce check test//policy check registration delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\Registration {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\Registration"}//12',
            "other" => 'nonce check test//policy check registration list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Registration 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "overview"=>'nonce check test//policy check registration list {"filter":"","model":{"event":"21"}}//{"a":"overview","0":21}//12,overview([21])'
        );

        $this->do_test_api("registration","registration",$data, $expected, $specials);
    }    
    public function test_api_ranking() {
        $data=array(
            "list1" => array(
                "category_id" => '19',
                "weapon_id" => '22'
            ),
            "list2" => array(
                "category_id" => 'aaaa',
                "weapon_id" => '22'
            ),
            "list3" => array(
                "category_id" => '19',
                "weapon_id" => 'bbbb'
            ),
            "list4" => array(
                "category_id" => '19',
                "weapon_id" => '-1'
            ),
            "list6" => array(
                "weapon_id" => '1'
            ),
            "list7" => array(
                "category_id" => '1'
            ),
            "reset" => array("special"=>"data"),
            "detail1" => array(
                "category_id"=>'1',
                "weapon_id"=>'1',
                'id'=>'1'
            ),
            "detail2" => array(
                "category_id"=>'-1',
                "weapon_id"=>'1',
                'id'=>'1'
            ),
            "detail3" => array(
                "category_id"=>'1',
                "weapon_id"=>'-1',
                'id'=>'1'
            ),
            "detail4" => array(
                "category_id"=>'1',
                "weapon_id"=>'1',
                'id'=>'-1'
            ),
            "detail5" => array(
                "category_id"=>'aaa',
                "weapon_id"=>'1',
                'id'=>'1'
            ),
            "detail6" => array(
                "category_id"=>'1',
                'id'=>'1'
            ),
            "detail7" => array(
                "weapon_id"=>'1',
                'id'=>'1'
            ),
            "detail8" => array(
                "category_id"=>'1',
                "weapon_id"=>'1',
            ),
            'other'=> array('special'=>'save')
        );
        $expected=array(
            "list1" => 'nonce check test//policy check ranking list {"filter":"","model":{"category_id":"19","weapon_id":"22"}}//{"success":true,"results":{"a":"listResults","0":22,"1":{"log":{"id":12,"0":"exists([])"},"data":[]}}}//12,exists([])',
            "list2" => 'nonce check test//policy check ranking list {"filter":"","model":{"category_id":"aaaa","weapon_id":"22"}}//{"success":true,"results":{"a":"listResults","0":22,"1":{"log":{"id":12,"0":"exists([])"},"data":[]}}}//12,exists([])',
            "list3" => 'nonce check test//policy check ranking list {"filter":"","model":{"category_id":"19","weapon_id":"bbbb"}}//{"error":"No category or weapon selected"}//12,exists([])',
            "list4" => 'nonce check test//policy check ranking list {"filter":"","model":{"category_id":"19","weapon_id":"-1"}}//{"error":"No category or weapon selected"}//12,exists([])',
            "list5" => 'nonce check test//policy check ranking list {"filter":"","model":{"category_id":"19","weapon_id":"-1"}}//{"error":"No category or weapon selected"}//12,exists([])',
            "list6" => 'nonce check test//policy check ranking list {"filter":"","model":{"weapon_id":"1"}}//{"success":true,"results":{"a":"listResults","0":1,"1":{"log":{"id":12,"0":"exists([])"},"data":[]}}}//12,exists([])',
            "list7" => 'nonce check test//policy check ranking list {"filter":"","model":{"category_id":"1"}}//{"error":"No category or weapon selected"}//12,exists([])',
            "reset" => 'nonce check test//policy check ranking misc {"filter":"","model":{"special":"data"}}//{"success":true,"total":{"a":"calculateRankings"}}//12,calculateRankings([])',
            "detail1" => 'nonce check test//policy check ranking view {"filter":"","model":{"category_id":"1","weapon_id":"1","id":"1"}}//{"a":"listDetail","0":1,"1":1,"2":1}//12,listDetail([1,1,1])',
            "detail2" => 'nonce check test//policy check ranking view {"filter":"","model":{"category_id":"-1","weapon_id":"1","id":"1"}}//{"error":"No category or weapon selected"}//12',
            "detail3" => 'nonce check test//policy check ranking view {"filter":"","model":{"category_id":"1","weapon_id":"-1","id":"1"}}//{"error":"No category or weapon selected"}//12',
            "detail4" => 'nonce check test//policy check ranking view {"filter":"","model":{"category_id":"1","weapon_id":"1","id":"-1"}}//{"error":"No category or weapon selected"}//12',
            "detail5" => 'nonce check test//policy check ranking view {"filter":"","model":{"category_id":"aaa","weapon_id":"1","id":"1"}}//{"error":"No category or weapon selected"}//12',
            "detail6" => 'nonce check test//policy check ranking view {"filter":"","model":{"category_id":"1","id":"1"}}//{"error":"No category or weapon selected"}//12',
            "detail7" => 'nonce check test//policy check ranking view {"filter":"","model":{"weapon_id":"1","id":"1"}}//{"error":"No category or weapon selected"}//12',
            "detail8" => 'nonce check test//policy check ranking view {"filter":"","model":{"category_id":"1","weapon_id":"1"}}//{"error":"No category or weapon selected"}//12',
            'other' => 'nonce check test//{"error":"invalid action"}',
            'none' => 'nonce check test//{"error":"invalid action"}'
        );
        $paths=array(
            "list1" => 'list',
            "list2" => 'list',
            "list3" => 'list',
            "list4" => 'list',
            "list5" => 'list',
            "list6" => 'list',
            "list7" => 'list',
            "reset" => 'reset',
            "detail1" => 'detail',
            "detail2" => 'detail',
            "detail3" => 'detail',
            "detail4" => 'detail',
            "detail5" => 'detail',
            "detail6" => 'detail',
            "detail7" => 'detail',
            "detail8" => 'detail',
            'other' => 'delete'
        );

        foreach($data as $key=>$pdata) {
            $exp=$expected[$key];
            $path=$paths[$key];
            $this->do_one("ranking/$path",$pdata,$exp,"api_ranking $key: $path call");
        }
        $this->do_one("ranking",array("special/save"),$expected['none'],"api_ranking none: generic call");
    }    

    public function test_api_posts() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check posts list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Posts 10 false//{"listresults":10}//12,selectAll([0,null,"","i",["special1","special2"]]),count("",["special1","special2"])',
            "save" => 'nonce check test//policy check posts list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Posts 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "view" => 'nonce check test//policy check posts list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Posts 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "delete" => 'nonce check test//policy check posts list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Posts 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "other" => 'nonce check test//policy check posts list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Posts 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
        );

        $this->do_test_api("posts","posts",$data, $expected, $specials);
    }    
    public function test_api_users() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check users list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\User 10 false//{"listresults":10}//12,selectAll([0,null,"","i",["special1","special2"]]),count("",["special1","special2"])',
            "save" => 'nonce check test//policy check users list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\User 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "view" => 'nonce check test//policy check users list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\User 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "delete" => 'nonce check test//policy check users list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\User 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "other" => 'nonce check test//policy check users list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\User 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
        );

        $this->do_test_api("users","users",$data, $expected, $specials);
    }    
    public function test_api_types() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check types list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\EventType 10 false//{"listresults":10}//12,selectAll([0,null,"","i",["special1","special2"]]),count("",["special1","special2"])',
            "save" => 'nonce check test//policy check types list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\EventType 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "view" => 'nonce check test//policy check types list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\EventType 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "delete" => 'nonce check test//policy check types list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\EventType 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "other" => 'nonce check test//policy check types list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\EventType 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
        );

        $this->do_test_api("types","types",$data, $expected, $specials);
    }    
    public function test_api_categories() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check categories list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Category 10 false//{"listresults":10}//12,selectAll([0,null,"","i",["special1","special2"]]),count("",["special1","special2"])',
            "save" => 'nonce check test//policy check categories list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Category 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "view" => 'nonce check test//policy check categories list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Category 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "delete" => 'nonce check test//policy check categories list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Category 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "other" => 'nonce check test//policy check categories list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Category 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
        );

        $this->do_test_api("categories","categories",$data, $expected, $specials);
    }    
    public function test_api_weapons() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check weapons list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Weapon 10 false//{"listresults":10}//12,selectAll([0,null,"","i",["special1","special2"]]),count("",["special1","special2"])',
            "save" => 'nonce check test//policy check weapons list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Weapon 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "view" => 'nonce check test//policy check weapons list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Weapon 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "delete" => 'nonce check test//policy check weapons list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Weapon 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
            "other" => 'nonce check test//policy check weapons list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Weapon 10 false//{"listresults":10}//12,selectAll([0,null,"","i","data"]),count("","data")',
        );

        $this->do_test_api("weapons","weapons",$data, $expected, $specials);
    }    

    public function test_api_migrations() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check migrations list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Migration 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check migrations save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Migration {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Migration"}//12',
            "view" => 'nonce check test//policy check migrations list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Migration 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "delete" => 'nonce check test//policy check migrations list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Migration 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "other" => 'nonce check test//policy check migrations list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Migration 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
        );

        $this->do_test_api("migrations","migrations",$data, $expected, $specials);
    }    

    public function test_api_templates() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
            "delpic" => array("file_id"=>"testfile","template_id"=>'12'),
        );
        $specials=array(
            "delpic" => "delpic",
            "example" => "save",
            "default"=>"save",
            "defaults" =>"save",
            "loaddefaults" =>"save"
        );
        $expected=array(
            "list" => 'nonce check test//policy check templates list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\AccreditationTemplate 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check templates save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\AccreditationTemplate {"special":"data"}//{"save":"EVFTest\\\\Models\\\\AccreditationTemplate"}//12',
            "view" => 'nonce check test//policy check templates view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\AccreditationTemplate {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check templates delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\AccreditationTemplate {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\AccreditationTemplate"}//12',
            "other" => 'nonce check test//policy check templates list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\AccreditationTemplate 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "delpic" =>'nonce check test//policy check templates delete {"filter":"","model":{"file_id":"testfile","template_id":"12"}}//[]//12,deletePicture(["testfile",12])',
            "example" =>'nonce check test//policy check templates save {"filter":"","model":{"special":"data"}}//[]//12,createExample([{"special":"data"}])',
            "default"=>'nonce check test//policy check templates misc {"filter":"","model":{"special":"data"}}//[]//12,setAsDefault([{"special":"data"}])',
            "defaults" =>'nonce check test//policy check templates misc {"filter":"","model":{"special":"data"}}//{"a":"listDefaults"}//12,listDefaults([])',
            "loaddefaults" =>'nonce check test//policy check templates save {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\AccreditationTemplate 10 false//{"listresults":10}//12,addDefaults([{"special":"data"}]),selectAll([0,20,"","","data"]),count("","data")'
        );

        $this->do_test_api("templates","templates",$data, $expected, $specials);
    }

    public function test_api_registrars() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check registrars list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Registrar 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check registrars save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Registrar {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Registrar"}//12',
            "view" => 'nonce check test//policy check registrars view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\Registrar {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check registrars delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\Registrar {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\Registrar"}//12',
            "other" => 'nonce check test//policy check registrars list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Registrar 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
        );

        $this->do_test_api("registrars","registrars",$data, $expected, $specials);
    }


    public function test_api_roles() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check roles list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Role 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check roles save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Role {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Role"}//12',
            "view" => 'nonce check test//policy check roles view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\Role {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check roles delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\Role {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\Role"}//12',
            "other" => 'nonce check test//policy check roles list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Role 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
        );

        $this->do_test_api("roles","roles",$data, $expected, $specials);
    }


    public function test_api_roletypes() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check roletypes list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\RoleType 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check roletypes save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\RoleType {"special":"data"}//{"save":"EVFTest\\\\Models\\\\RoleType"}//12',
            "view" => 'nonce check test//policy check roletypes view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\RoleType {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check roletypes delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\RoleType {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\RoleType"}//12',
            "other" => 'nonce check test//policy check roletypes list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\RoleType 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
        );

        $this->do_test_api("roletypes","roletypes",$data, $expected, $specials);
    }


    public function test_api_events() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
            "special" => array("id"=>'12'),
        );
        $specials=array(
            "competitions"=>"special",
            "sides"=>"special",
            "roles" => "special",
        );
        $expected=array(
            "list" => 'nonce check test//policy check events list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Event 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check events save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Event {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Event"}//12',
            "view" => 'nonce check test//policy check events view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\Event {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check events delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\Event {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\Event"}//12',
            "other" => 'nonce check test//policy check events list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Event 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "competitions" => 'nonce check test//policy check competitions list {"filter":"","model":{"id":"12"}}//listresults EVFTest\Models\Event null true//{"listresults":null}//12,competitions([12])',
            "sides" => 'nonce check test//policy check sides list {"filter":"","model":{"id":"12"}}//listresults EVFTest\Models\Event null true//{"listresults":null}//12,sides([12])',
            "roles" => 'nonce check test//policy check eventroles list {"filter":"","model":{"id":"12"}}//listresults EVFTest\Models\Event null true//{"listresults":null}//12,roles([12])',
        );

        $this->do_test_api("events","events",$data, $expected, $specials);
    }


    public function test_api_results() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
            "importcheck" => array(
                "competition" => "12",
                "ranking" => array(1,2,3,4)
            ),
            "import" => array("import"=>array(2,3,4,5)),
            "recalculate" => array("competition_id"=>'13'),
            "clear" => array("competition_id"=>'14')
        );
        $specials=array(
            "importcheck"=>"importcheck",
            "import"=>"import",
            "recalculate" => "recalculate",
            "clear" => "clear"
        );
        $expected=array(
            "list" => 'nonce check test//policy check results list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Result 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check results save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Result {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Result"}//12',
            "view" => 'nonce check test//policy check results view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\Result {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check results delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\Result {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\Result"}//12',
            "other" => 'nonce check test//policy check results list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Result 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "importcheck" => 'nonce check test//policy check results misc {"filter":"","model":{"competition":"12","ranking":[1,2,3,4]}}//{"a":"doImportCheck","0":[1,2,3,4],"1":12}//12,doImportCheck([[1,2,3,4],12])',
            "import" => 'nonce check test//policy check results misc {"filter":"","model":{"import":[2,3,4,5]}}//{"a":"doImport","0":[2,3,4,5]}//12,doImport([[2,3,4,5]])',
            "recalculate" => 'nonce check test//policy check results misc {"filter":"","model":{"competition_id":"13"}}//{"a":"recalculate","0":13}//12,recalculate([13])',
            "clear" =>'nonce check test//policy check results misc {"filter":"","model":{"competition_id":"14"}}//{"a":"clear","0":14}//12,clear([14])'
        );

        $this->do_test_api("results","results",$data, $expected, $specials);
    }


    public function test_api_countries() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data")
        );
        $specials=array();
        $expected=array(
            "list" => 'nonce check test//policy check countries list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Country 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check countries save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Country {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Country"}//12',
            "view" => 'nonce check test//policy check countries view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\Country {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check countries delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\Country {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\Country"}//12',
            "other" => 'nonce check test//policy check countries list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Country 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
        );

        $this->do_test_api("countries","countries",$data, $expected, $specials);
    }


    public function test_api_fencer() {
        $data=array(
            "list" => array(
                "offset" => 10,
                "pagesize" => 50,
                "filter" => array("a"=>1,"b"=>"b"),
                "sort" => "abcdef",
                "special" => array("special1","special2")
            ),
            "other" => array("special"=>"data"),
            "save" => array("special"=>"data"),
            "importcheck" => array(
                "country" => 1,
                "fencers" => array(
                    array("name" => "test", "firstname" => "test2", "gender" => "M")
                )
                ),
        );
        $specials=array(
            "presavecheck"=>"save",
            "merge"=>"save",
            "importcheck" => "importcheck"
        );
        $expected=array(
            "list" => 'nonce check test//policy check fencers list {"filter":{"a":1,"b":"b"},"model":{"offset":10,"pagesize":50,"filter":{"a":1,"b":"b"},"sort":"abcdef","special":["special1","special2"]}}//listresults EVFTest\Models\Fencer 10 false//{"listresults":10}//12,selectAll([10,50,{"a":1,"b":"b"},"abcdef",["special1","special2"]]),count({"a":1,"b":"b"},["special1","special2"])',
            "save" => 'nonce check test//policy check fencers save {"filter":"","model":{"special":"data"}}//save EVFTest\Models\Fencer {"special":"data"}//{"save":"EVFTest\\\\Models\\\\Fencer"}//12',
            "view" => 'nonce check test//policy check fencers view {"filter":"","model":{"special":"data"}}//create EVFTest\Models\Fencer {"special":"data"}//{"item":["export"]}//12',
            "delete" => 'nonce check test//policy check fencers delete {"filter":"","model":{"special":"data"}}//delete EVFTest\Models\Fencer {"special":"data"}//{"delete":"EVFTest\\\\Models\\\\Fencer"}//12',
            "other" => 'nonce check test//policy check fencers list {"filter":"","model":{"special":"data"}}//listresults EVFTest\Models\Fencer 10 false//{"listresults":10}//12,selectAll([0,20,"","","data"]),count("","data")',
            "presavecheck" => 'nonce check test//policy check fencers save {"filter":"","model":{"special":"data"}}//{"a":"preSaveCheck","0":{"special":"data"}}//12,preSaveCheck([{"special":"data"}])',
            "merge" => 'nonce check test//policy check fencers save {"filter":"","model":{"special":"data"}}//{"a":"merge","0":{"special":"data"}}//12,merge([{"special":"data"}])',
            "importcheck" => 'nonce check test//policy check fencers save {"filter":"","model":{"country":1,"fencers":[{"name":"test","firstname":"test2","gender":"M"}]}}//{"a":"doImportCheck","0":[{"name":"test","firstname":"test2","gender":"M"}],"1":1}//12,doImportCheck([[{"name":"test","firstname":"test2","gender":"M"}],1])',
        );

        $this->do_test_api("fencers","fencers",$data, $expected, $specials);
    }
}

class TestApi extends \EVFRanking\Lib\Api {
    public $log=array();

    public function output() {
        if(isset(\EVFTest\Models\BaseMock::$lastobject)) {
            $this->log[] = implode(",",\EVFTest\Models\BaseMock::$lastobject->log);
            \EVFTest\Models\BaseMock::$lastobject=null;
        }
        $retval=implode("//",$this->log);
        $this->log=array();
        return $retval;
    }
    public function testPost($data) {
        $retval=$this->doPost($data);
        $this->log[]=json_encode($retval);
    }

    protected function checkNonce($nonce) {
        $this->log[]="nonce check $nonce";
    }

    protected function checkPolicy($model, $action, $obj=null) {
        $this->log[]="policy check $model $action ".json_encode($obj);
    }

    protected function save($model, $data) {
        $this->log[]="save ".get_class($model)." ".json_encode($data);
        //$this->log[]=implode(",",$model->log);
        return array("save"=>get_class($model));
    }

    protected function delete($model, $data) {
        $this->log[]="delete ".get_class($model)." ".json_encode($data);
        //$this->log[]=implode(",",$model->log);
        return array("delete"=>get_class($model));
    }

    protected function listResults($model, $lst,$total=null, $noexport=FALSE) {
        $this->log[]="listresults ".get_class($model)." ".json_encode($total)." ".json_encode($noexport);
        //$this->log[]=implode(",",$model->log);
        return array('listresults'=>$total);
    }    

    protected function createModel($model, $data) {
        $this->log[]="create ".get_class($model)." ".json_encode($data);
        //$this->log[]=implode(",",$model->log);
        return $model;
    }

    protected function loadModel($name, $args=null) {
        $name="\\EVFTest\\Models\\$name";
        return new $name($args,true);
    }
}

