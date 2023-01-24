<?php

/**
 * EVF-Ranking Queue Model
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

class YieldException extends \Exception
{
}

// phpcs:disable PSR1.Classes.ClassDeclaration
class FailException extends \Exception
{
}

class Queue extends Base
{
    public $table = "TD_Queue";
    public $pk = "id";
    public $fields = array("id", "state", "payload", "attempts", "started_at", 
        "finished_at", "created_at", "available_at", "queue", 'event_id', 'model');
    public $fieldToExport = array(
        "id" => "id",
        "state" => "state",
        "payload" => "payload",
        "attempts" => "attempts",
        "started_at" => "started_at",
        "finished_at" => "finished_at",
        "created_at" => "created_at",
        "available_at" => "available_at",
        "queue" => "queue",
        "event_id" => "event",
        "model" => "model"
    );
    public $rules = array(
        "id" => "skip",
        "state" => "required|trim|lte=20",
        "payload" => "trim",
        "attempts" => "int|default=0",
        "started_at" => "datetime",
        "finished_at" => "datetime",
        "created_at" => "datetime",
        "available_at" => "datetime",
        "queue" => "required|trim|lte=20",
        "event_id" => array("rules" => "model=Event", "message" => "Please select a valid event"),
        "model" => "trim"
    );

    private $_cache_data = false;

    private function sortToOrder($sort)
    {
        if (empty($sort)) {
            $sort = "i";
        }
        $orderBy = array();
        for ($i = 0; $i < strlen($sort); $i++) {
            $c = $sort[$i];
            switch ($c) {
                default:
                case 'i':
                    $orderBy[] = "id asc";
                    break;
                case 'I':
                    $orderBy[] = "id desc";
                    break;
                case 'q':
                    $orderBy[] = "queue asc";
                    break;
                case 'Q':
                    $orderBy[] = "queue desc";
                    break;
                case 'a':
                    $orderBy[] = "attempts asc";
                    break;
                case 'A':
                    $orderBy[] = "attempts desc";
                    break;
                case 's':
                    $orderBy[] = "started_at asc";
                    break;
                case 'S':
                    $orderBy[] = "started_at desc";
                    break;
                case 'f':
                    $orderBy[] = "finished_at asc";
                    break;
                case 'F':
                    $orderBy[] = "finished_at desc";
                    break;
                case 'r':
                    $orderBy[] = "available_at asc";
                    break;
                case 'R':
                    $orderBy[] = "available_at desc";
                    break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter, $special) {
        //if (is_string($filter)) $filter = json_decode($filter, true);
        if (is_string($special)) {
            $special = json_decode($special, true);
        }

        if (!empty($filter)) {
            if (is_string($filter)) {
                $filter = array("name" => $filter);
            }
            if (is_object($filter)) {
                $filter = (array)$filter;
            }
            if (isset($filter["event"]) && !empty(trim($filter["event"]))) {
                $qb->where("event_id", $filter["event"]);
            }
            if (isset($filter["queue"]) && !empty(trim($filter["queue"]))) {
                $qb->where("queue", $filter["queue"]);
            }
            if (isset($filter["model"]) && !empty($filter["model"])) {
                if (is_array($filter['model'])) {
                    $qb->where_in("model", $filter["model"]);
                }
                else if (!empty(trim($filter['model']))) {
                    $qb->where("model", trim($filter["model"]));
                }
            }
        }

        if (!empty($special)) {
            if (isset($special["open"])) {
                $qb->where("state", "new");
                $qb->where("available_at", ">", strftime('%Y-%m-%d %H:%M:%S'));
            }
            if (isset($special["waiting"])) {
                $qb->where("state", "new");
                $qb->where("available_at", "<", strftime('%Y-%m-%d %H:%M:%S'));
            }
            if (isset($special["running"])) {
                $qb->where("state", "running");
            }
            if (isset($special["error"])) {
                $qb->where("state", "error");
            }
            if (isset($special["finished"])) {
                $qb->where("state", "finished");
            }
            if (isset($special["pending"])) {
                $qb->where("state", "in", array("new", "running"));
            }
        }
    }

    public function selectAll($offset, $pagesize, $filter, $sort, $special = null) {
        $qb = $this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb, $filter, $special);
        return $qb->get();
    }

    public function count($filter, $special = null) {
        $qb = $this->select("count(*) as cnt");
        $this->addFilter($qb, $filter, $special);
        return $qb->count();
    }

    public function save() {
        if($this->isNew()) {
            $this->created_at = strftime('%Y-%m-%d %H:%M:%S');
            $this->started_at = null;
            $this->finished_at = null;
            $this->attempts=0;
            if(empty($this->available_at)) {
                $this->available_at = $this->created_at;
            }
            $this->state="new";
        }
        if(!empty($this->_cache_data)) {
            $this->payload=json_encode($this->_cache_data);
        }
        parent::save();
    }

    public function run($timelimit) {
        $is_available = empty($this->available_at) || (strtotime($this->available_at) <= time());
        if ($this->state == "new" && $is_available) {
            $self = $this;
            set_exception_handler(function ($ex) use ($self) {
                error_log("exception caught " . $ex->getMessage());
                $self->state = "error";
                $self->setData("error", $ex->getMessage());
                $self->setData("backtrace", debug_backtrace());
                $self->save();
            });
            try {
                $this->state = "running";
                $this->started_at = strftime('%Y-%m-%d %H:%M:%S');
                $this->attempts += 1;
                $this->save();

                $this->doRun($timelimit);

                $this->state = "finished";
                $this->finished_at = strftime('%Y-%m-%d %H:%M:%S');
                $this->save();

                return true;
            }
            catch (YieldException $e) {
                // yielding means we save the object and retry again later
                $this->state = "new";
                $this->started_at = null;
                // do not correct the attempts. Larger attempts value will sort
                // the entry later in the pending-queue, allowing other entries
                // to go first
                //
                // wait at least one second to allow other queue entries to go first
                $this->available_at = strftime('%Y-%m-%d %H:%M:%S', time() + 1);
                $yields = $this->getData("yields", array());
                $yields[] = strftime('%Y-%m-%d %H:%M:%S');
                $this->setData("yields", $yields);
                $this->save();
                return true;
            }
            catch (FailException $e) {
                // explicit fail, should have a regular log message
                $this->state = "error";
                error_log("caught fail-queue exception " . $e->getMessage());
                $this->save();
            }
            catch (\Exception $e) {
                $this->state = "error";
                $this->setData("error", $e->getMessage());
                $this->setData("backtrace", debug_backtrace());
                error_log("caught generic queue exception " . $e->getMessage());
                $this->save();
            }
            set_exception_handler(null);
        }
        return false;
    }

    public function yield()
    {
        throw new YieldException();
    }

    public function fail()
    {
        throw new FailException();
    }

    public function timeLeft()
    {
        return $this->end_time - time();
    }

    private function doRun($timelimit)
    {
        $this->end_time = time() + intval($timelimit);
        $model = $this->model;
        $method = $this->getData("method", "run");
        if (!empty($model)) {
            $obj = new $model($this);
        }
        if (method_exists($obj, $method)) {
            ob_start();
            $obj->$method();
            ob_end_clean();
        }
        else {
            throw new Exception("Method $method does not exist on model $model");
        }
    }

    public function getData($key, $def = null)
    {
        if (empty($this->_cache_data)) {
            $this->_cache_data = json_decode($this->payload, true);
        }
        if (empty($this->_cache_data)) {
            $this->_cache_data = array();
        }
        if(isset($this->_cache_data[$key])) {
            return $this->_cache_data[$key];
        }
        else {
            return $def;
        }
    }

    public function setData($key,$value) {
        if(empty($this->_cache_data)) {
            $this->_cache_data = json_decode($this->payload,true);
        }
        if(empty($this->_cache_data)) {
            $this->_cache_data=array();
        }
        $this->_cache_data[$key]=$value;
    }

    public function tick($timelimit) {
        $res=$this->select('*')
            ->where('available_at','<=',strftime('%F %T'))
            ->where('started_at',null)
            ->where("state","new")
            ->where("queue",$this->queue)
            ->orderBy("available_at") // sorts NULL values first
            ->orderBy("attempts desc") // sort least attempted first
            ->first();
        $queue = new Queue($res);
        if($queue->exists()) {
            $queue->run($timelimit);
            return true;
        }
        return false;
    }

    public function cleanup() {
        // delete all finished jobs that are older than 2 days
        $this->query()->where("state","finished")->where("started_at","<",strftime("%F %T",time()-48*60*60))->delete();
        // delete all error jobs that are older than a month
        $this->query()->where("state", "error")->where("started_at", "<", strftime("%F %T", time() - 31*24 * 60 * 60))->delete();
    }
}
 