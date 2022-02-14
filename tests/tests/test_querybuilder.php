<?php

namespace EVFTest;

class Test_QueryBuilder extends BaseTest {
    public $disabled=false;

    public function init() {
        
    }

    public function test_where() {
        $model=new TestQBModel();
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        // returns empty, because no select values set
        $result = $qb->where("a",1)->get();
        $this->expects($model->output(),"","test_where 1: missing select fields returns empty value");

        // returns empty, because no from set
        $result = $qb->select('*')->where('a',1)->get();
        $this->expects($model->output(),"","test_where 2: missing from returns empty value");

        // test that table is taken at construction time
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);
        // returns simple where query
        $result = $qb->select('*')->where('a',1)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1","test_where 3: simple query");

        // returns simple where query
        // this also tests that a get() resets the query values
        $result = $qb->select('*')->from('test2')->where('a',1)->get();
        $this->expects($model->output(),"SELECT * FROM test2 WHERE a = %d1","test_where 4: simple query");

        $result = $qb->select('*')->from('test2')->where('a','1')->get();
        $this->expects($model->output(),"SELECT * FROM test2 WHERE a = %s1","test_where 5: string argument");

        $result = $qb->select()->fields('a1')->fields(array('a','b'))->fields(array('a2'=>true,'b2'=>'1'))->from('test2')->where('a','1')->get();
        $this->expects($model->output(),"SELECT a1,a,b,a2,b2 FROM test2 WHERE a = %s1","test_where 6: using fields method");

        $result = $qb->select()->select('a1')->select(array('a','b'))->select(array('a2'=>true,'b2'=>'1'))->from('test2')->where('a','1')->get();
        $this->expects($model->output(),"SELECT a1,a,b,a2,b2 FROM test2 WHERE a = %s1","test_where 5: select is alias for fields");
    }

    public function test_where2() {
        $model=new TestQBModel();
        $model->table="testw2";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->where('a',1)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a = %d1","test_where2 1: simple clause");

        $result = $qb->select('*')->where('a','=',1)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a = %d1","test_where2 2: specified comparator");

        $result = $qb->select('*')->where('a','<',1)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a < %d1","test_where2 3: specified comparator");

        $result = $qb->select('*')->where('a','>',1)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a > %d1","test_where2 4: specified comparator");

        $result = $qb->select('*')->where('a','unknownop',1)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a unknownop %d1","test_where2 5: specified comparator (unknown)");

        $result = $qb->select('*')->where('a')->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a is NULL","test_where2 6: test on null");
        $result = $qb->select('*')->where('a',null)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a is NULL","test_where2 7: test on null");
        $result = $qb->select('*')->where('a','=',null)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a is NULL","test_where2 8: test on null");
        $result = $qb->select('*')->where('a','<>',null)->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a is not NULL","test_where2 9: test on null");
        $result = $qb->select('*')->where('a=2')->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a=2","test_where2 10: if field contains =, interpret it as subclause");
        $result = $qb->select('*')->where('a in (SELECT * from test)')->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a in (SELECT * from test)","test_where2 11: if field contains a space, interpret it as subclause");
        $result = $qb->select('*')->where('a<2')->get();
        $this->expects($model->output(),"SELECT * FROM testw2 WHERE a<2 is NULL","test_where2 12: no space or = sign: wrong interpretation");

    }

    public function test_wherein() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->where_in('a','a,b,c,d')->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN (a,b,c,d)","test_wherein 1: direct list");

        $result = $qb->select('*')->where_in('a',array('a','b','c','d'))->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN ('a','b','c','d')","test_wherein 2: array list");

        $result = $qb->select('*')->where_in('a','invalid content')->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN (invalid content)","test_wherein 3: no checks on clause");

        $result = $qb->select('*')->where_in('a',function($qb2) {
            return $qb2->select('*')->from('test2');
        })->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN (SELECT * FROM test2)","test_wherein 4: subclause");

        // invalid (non-string) clause gets converted
        $result = $qb->select('*')->where_in('a',1)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN (1)","test_wherein 5: clause string conversion");

        $result = $qb->select('*')->where_in('a',2.12)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN (2.12)","test_wherein 6: clause string conversion");

        $result = $qb->select('*')->where_in('a',new \stdClass())->get();
        $this->expects($model->output(),"","test_wherein 7: invalid conversion from object breaks query");

        $result = $qb->select('*')->where_in('a',$model)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN (testclause)","test_wherein 9: object with __toString is supported");

        $result = $qb->select('*')->where('a','in',array(1,2,3))->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN ('1','2','3')","test_wherein 10: explicit IN specification");

        $result = $qb->select('*')->where('a','iN',array(1,2,3))->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a IN ('1','2','3')","test_wherein 11: explicit IN specification (case insensitive)");

    }

    public function test_whereand() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->where('a',1)->where('b',2)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 AND b = %d2","test_whereand 1: two simple clauses");

        $result = $qb->select('*')->where('a','>', 1)->where('b','invalid',2)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a > %d1 AND b invalid %d2","test_whereand 2: specific operands");

        $result = $qb->select('*')->where(function($qb2) {
            $qb2->select('*')->from('test3');
        })->where('b',2)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE (SELECT * FROM test3) AND b = %d2","test_whereand 3: subclause");

        $result = $qb->select('*')->where('b',2)->where(function($qb2) {
            $qb2->select('*')->from('test3');
        })->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE b = %d2 AND (SELECT * FROM test3)","test_whereand 4: subclause");

        $result = $qb->select('*')->where('a',1)->where('b','=',2,'AND')->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 AND b = %d2","test_whereand 5: explicit AND");

        $result = $qb->select('*')->where('a',1)->where('b','=',2,'and')->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 and b = %d2","test_whereand 6: explicit AND");

        $result = $qb->select('*')->where('a',1)->where('b','=',2,'or')->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 or b = %d2","test_whereand 7: explicit OR");
    }

    public function test_exists() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->where_exists(function($qb2) {
            $qb2->select('*')->from('test2')->where('b',2);
        })->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE exists(SELECT * FROM test2 WHERE b = %d2)","test_exists 1: method call");

        $result = $qb->select('*')->where_exists('a')->get();
        $this->expects($model->output(),"","test_exists 2: not a callable returns error");

        $result = $qb->select('*')->where_exists($model)->get();
        $this->expects($model->output(),"","test_exists 3: not a callable returns error");
        $result = $qb->select('*')->where_exists(array(1,2))->get();
        $this->expects($model->output(),"","test_exists 4: not a callable returns error");

        // this is not a feature per se
        $result = $qb->select('*')->where(function($qb2) {
            $qb2->select('*')->from('test2')->where('b',2);
        },"exists",'non-null-value')->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE exists(SELECT * FROM test2 WHERE b = %d2)","test_exists 5: direct call");

        $result = $qb->select('*')->where(function($qb2) {
            $qb2->select('*')->from('test2')->where('b',2);
        },"exists")->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE (SELECT * FROM test2 WHERE b = %d2)","test_exists 6: exists interpreted as subclause");
    }

    public function test_whereor() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        // first OR is ignored
        $result = $qb->select('*')->or_where('a',1)->or_where('b',2)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 OR b = %d2","test_whereor 1: two simple clauses");

        $result = $qb->select('*')->where('a',1)->or_where('b',2)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 OR b = %d2","test_whereor 2: two simple clauses");

        $result = $qb->select('*')->where('a','>', 1)->or_where('b','invalid',2)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a > %d1 OR b invalid %d2","test_whereor 3: specific operands");

        $result = $qb->select('*')->where('a',1)->or_where(function($qb2) {
            $qb2->select('*')->from('test3');
        })->or_where('b',2)->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 OR (SELECT * FROM test3) OR b = %d2","test_whereor 4: subclause");

        $result = $qb->select('*')->or_where('b')->or_where('a')->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE b is NULL OR a is NULL","test_whereor 5: test on null");

        $result = $qb->select('*')->where('b')
            ->or_where('(a=1 and b=3)')
            ->where(function($qb2) {
                $qb2->where('a',1)->or_where('b',4);
            })->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE b is NULL OR (a=1 and b=3) AND (a = %d1 OR b = %d4)","test_whereor 6: or/and combinations and subclause");

        $result = $qb->select('*')->where('b')
            ->or_where(function($qb2) {
                $qb2->where('a',1)->or_where(function($qb3) {
                    $qb3->where('b',5)->or_where('c','in',array(1,2,3));
                });
            })->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE b is NULL OR (a = %d1 OR (b = %d5 OR c IN ('1','2','3')))","test_whereor 7: complicated subclauses");

        // specific comparison operators
        $result = $qb->select('*')->where('a',1)->where_in('b',array(1,2,3),"OR")->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 OR b IN ('1','2','3')","test_whereor 8: in clause with OR");

        $result = $qb->select('*')->where('a',1)->where_in('b',array(1,2,3),"NOSUCHOP")->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a = %d1 NOSUCHOP b IN ('1','2','3')","test_whereor 9: in clause with OR");

        $result = $qb->select('*')->where('a','<',1,'OR')->where('b','>',2,"OR")->get();
        $this->expects($model->output(),"SELECT * FROM test WHERE a < %d1 OR b > %d2","test_whereor 10: explicit andor clause");

    }

    public function test_join() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->join("test2","t","t.id=test.id")->get();
        $this->expects($model->output(),"SELECT * FROM test left JOIN test2 t ON t.id=test.id","test_join 1: simple join");

        $result = $qb->select('*')->join("test2","t","t.id=test.id")->join("test3","t3","t3.id=t2.id and t2.id=tr.id")->get();
        $this->expects($model->output(),"SELECT * FROM test left JOIN test2 t ON t.id=test.id left JOIN test3 t3 ON t3.id=t2.id and t2.id=tr.id","test_join 2: two joins");

        $result = $qb->select('*')->join("test2","t","t.id=test.id","inner")->get();
        $this->expects($model->output(),"SELECT * FROM test inner JOIN test2 t ON t.id=test.id","test_join 3: join specification");

        $result = $qb->select('*')->join("test2","t","t.id=test.id","invalid")->get();
        $this->expects($model->output(),"SELECT * FROM test invalid JOIN test2 t ON t.id=test.id","test_join 4: invalid join spec");

        $result = $qb->select('*')->join(function($qb2) {
            $qb2->select('*')->from('test2')->where('a',1)->or_where('b');
        },'t','t.id=test.id','RIGHT')->get();
        $this->expects($model->output(),"SELECT * FROM test RIGHT JOIN (SELECT * FROM test2 WHERE a = %d1 OR b is NULL) t ON t.id=test.id","test_join 5: subclause join");
    }

    public function test_orderby() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->orderBy('a')->get();
        $this->expects($model->output(),"SELECT * FROM test ORDER BY a","test_orderby 1: simple order");

        $result = $qb->select('*')->orderBy(array('a','b','c'))->get();
        $this->expects($model->output(),"SELECT * FROM test ORDER BY a,b,c","test_orderby 2: array order");

        $result = $qb->select('*')->orderBy(array('a'=>'asc','b'=>'DESC','c'))->get();
        $this->expects($model->output(),"SELECT * FROM test ORDER BY a asc,b DESC,c","test_orderby 3: array order");

        $result = $qb->select('*')->orderBy(array('a'=>true, 'b'=>true, 'c'=>false))->get();
        $this->expects($model->output(),"SELECT * FROM test ORDER BY a,b,c","test_orderby 4: array key order");

        $result = $qb->select('*')->orderBy('a','NOSUCHDIR')->get();
        $this->expects($model->output(),"SELECT * FROM test ORDER BY a NOSUCHDIR","test_orderby 5: direction spec");

        $result = $qb->select('*')->orderBy(array('a'=>'asc','b'=>'DESC','c'),"UNKNOWN")->get();
        $this->expects($model->output(),"SELECT * FROM test ORDER BY a asc,b DESC,c UNKNOWN","test_orderby 6: default direction");        

    }

    public function test_groupby() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->groupBy('a')->get();
        $this->expects($model->output(),"SELECT * FROM test GROUP BY a","test_groupby 1: simple order");

        $result = $qb->select('*')->groupBy(array('a','b'))->get();
        $this->expects($model->output(),"SELECT * FROM test GROUP BY a,b","test_groupby 2: array order");

        $result = $qb->select('*')->groupBy(array('a','b'))->groupBy('c')->get();
        $this->expects($model->output(),"SELECT * FROM test GROUP BY a,b,c","test_groupby 3: additional clause");
    }

    public function test_having() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->having('a')->get();
        $this->expects($model->output(),"SELECT * FROM test HAVING a","test_having 1: simple order");

        $result = $qb->select('*')->having(array('a','b'))->get();
        $this->expects($model->output(),"SELECT * FROM test HAVING a,b","test_having 2: array order");

        $result = $qb->select('*')->having(array('a','b'))->having('c')->get();
        $this->expects($model->output(),"SELECT * FROM test HAVING a,b,c","test_having 3: additional clause");
    }

    public function test_offset() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->select('*')->page(1)->get();
        $this->expects($model->output(),"SELECT * FROM test LIMIT 20 OFFSET 20","test_limit 1: page");

        $result = $qb->select('*')->page(2)->get();
        $this->expects($model->output(),"SELECT * FROM test LIMIT 20 OFFSET 40","test_limit 2: page");

        $result = $qb->select('*')->page(2,40)->get();
        $this->expects($model->output(),"SELECT * FROM test LIMIT 40 OFFSET 80","test_limit 3: page");

        $result = $qb->select('*')->page(2,40)->offset(100)->get();
        $this->expects($model->output(),"SELECT * FROM test LIMIT 40 OFFSET 100","test_limit 4: page");

        $result = $qb->select('*')->page(2,40)->limit(100)->get();
        $this->expects($model->output(),"SELECT * FROM test LIMIT 100 OFFSET 80","test_limit 5: page");

        $result = $qb->select('*')->page(null,null)->get();
        $this->expects($model->output(),"SELECT * FROM test","test_limit 6: page");

        $result = $qb->select('*')->page(null,20)->get();
        $this->expects($model->output(),"SELECT * FROM test LIMIT 20","test_limit 7: page");

        $result = $qb->select('*')->page(2,40)->limit(null)->get();
        $this->expects($model->output(),"SELECT * FROM test OFFSET 80","test_limit 8: page");

        $result = $qb->select('*')->page(2,40)->offset(null)->get();
        $this->expects($model->output(),"SELECT * FROM test LIMIT 40","test_limit 9: page");
    }

    public function test_update() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->update();
        $this->expects($model->output(),"","test_update 1: no clauses returns empty value");

        $result = $qb->set('a',1)->update();
        $this->expects($model->output(),"UPDATE test SET a=%d1","test_update 2: simple set");

        $result = $qb->set('a',1)->where('a',2)->update();
        $this->expects($model->output(),"UPDATE test SET a=%d1 WHERE a = %d2","test_update 3: set and where");

        $result = $qb->set('a',1)->where('a',2)->or_where('b','<',3)->update();
        $this->expects($model->output(),"UPDATE test SET a=%d1 WHERE a = %d2 OR b < %d3","test_update 4: set, double where");

        $result = $qb->set('a',1)->where(function($qb2) {
            $qb2->where('a','s2')->or_where('b','<>',null);
        })->or_where('b','<',3)->update();
        $this->expects($model->output(),"UPDATE test SET a=%d1 WHERE (a = %ss2 OR b is not NULL) OR b < %d3","test_update 5: set, complicated where");

        $result = $qb->set('a')->update();
        $this->expects($model->output(),"UPDATE test SET a=NULL","test_update 6: setting to null");

        $result = $qb->set(array('a'=>1,'b'=>null,'c'=>'aa2'))->update();
        $this->expects($model->output(),"UPDATE test SET a=%d1, b=NULL, c=%saa2","test_update 7: set using array");

        $result = $qb->set(array('a'=>1,'b'=>null,'c'=>'aa2'))->join('test2','t','t.id=test.id')->update();
        $this->expects($model->output(),"UPDATE test left JOIN test2 t ON t.id=test.id SET a=%d1, b=NULL, c=%saa2","test_update 8: set with join");

        $qb2 = $qb->sub();
        $result = $qb2->set(array('a'=>1,'b'=>null,'c'=>'aa2'))->join('test2','t','t.id=test.id')->update();
        $this->expects($model->output(),"","test_update 9: update not in subclause");

        $result = $qb->set(array('a'=>1,'b'=>null,'c'=>'aa2'))->where(function($qb3) {
            $qb3->set('a',1)->from('test2')->update();
        })->update();
        $this->expects($model->output(),"UPDATE test SET a=%d1, b=NULL, c=%saa2 WHERE ()","test_update 10: no update in subclause");

        $result = $qb->set('a',1)->offset(10)->page(10,2)->limit(100)->having('a')->groupBy('a')->orderBy('c')->update();
        $this->expects($model->output(),"UPDATE test SET a=%d1","test_update 11: having/groupby/limit/offset/orderby ignored");

    }

    public function test_delete() {
        $model=new TestQBModel();
        $model->table="test";
        $qb=new \EVFRanking\Models\QueryBuilder($model);

        $result = $qb->delete();
        $this->expects($model->output(),"DELETE FROM test","test_delete 1: no clauses deletes entire table");

        $result = $qb->where('a',1)->delete();
        $this->expects($model->output(),"DELETE FROM test WHERE a = %d1","test_delete 2: simple delete");

        $result = $qb->where('a',1)->or_where('b','aa')->delete();
        $this->expects($model->output(),"DELETE FROM test WHERE a = %d1 OR b = %saa","test_delete 3: OR clause");

        $result = $qb->where('a',1)->or_where('b','aa')->offset(10)->page(10,2)->limit(100)->having('a')->groupBy('a')->orderBy('c')->delete();
        $this->expects($model->output(),"DELETE FROM test WHERE a = %d1 OR b = %saa","test_delete 4: having/groupby/limit/offset ignored");

        $qb2 = $qb->sub();
        $result = $qb2->where('a',1)->delete();
        $this->expects($model->output(),"","test_delete 5: delete not in subclause");

        $result = $qb->where(function($qb3) {
            $qb3->where('a',1)->from('test2')->delete();
        })->delete();
        $this->expects($model->output(),"DELETE FROM test WHERE ()","test_delete 6: no delete in subclause");

        $result = $qb->where('a',1)->join('test2','t','t.id=test.id')->delete();
        $this->expects($model->output(),"DELETE FROM test WHERE a = %d1","test_delete 7: with join is ignored");
    }
}

class TestQBModel {
    public function __construct() {
        $this->log=array();
    }

    public function __toString() {
        return "testclause";
    }

    public function prepare($query,$values) {
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

                    // we mimic the replacements here to indicate we get the proper 
                    // value type for SQL preparation
                    if(is_float($v)) $v="%f$v";
                    else if(is_int($v)) $v="%d$v";
                    else if(is_null($v)) $v="cNULL";
                    else if(is_object($v) && method_exists($v,"getKey")) $v="%dKEY".$v->getKey();
                    else $v="%s$v";
                    $query=str_replace($m,$v,$query);
                }
            }
        }        
        $this->log[]=$query;
        return $query;
    }

    public function first($sql, $values) {
        $this->prepare($sql,$values);
        $this->log[]="FIRST";
    }

    public $_where_values=array();
    public $log=array();
    public function output() {
        $retval=implode(",",$this->log);
        $this->log=array();
        return $retval;
    }
}
