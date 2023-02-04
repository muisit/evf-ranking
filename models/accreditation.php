<?php

/**
 * EVF-Ranking Accreditation Model
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

// phpcs:disable PSR12.Classes.ClassInstantiation

namespace EVFRanking\Models;

use EVFRanking\Util\PDFManager;

class Accreditation extends Base {
    public $table = "TD_Accreditation";
    public $pk="id";
    public $fields=array("id","fencer_id","event_id","data","hash","file_hash","template_id","file_id","generated", "is_dirty","fe_id");
    public $fieldToExport=array(
        "id"=>"id",
        "fencer_id" => "fencer_id",
        "event_id" => "event_id",
        "data" => "data",
        "template_id" => "template_id",
        // hash, file_hash, file_id, generated and is_dirty do not need to be exported
        // fe_id is not exported to prevent retrieving it automatically and marking all
        // accreditations as registered
    );
    public $rules=array(
        "id" => "skip",
        "fencer_id"=> "required|model=Fencer",
        "event_id" => "required|model=Event",
        "data" => "trim",
        "hash" => "trim|lte=512",
        "file_hash" => "trim|lte=512",
        "template_id" => "required|model=AccreditationTemplate",
        "file_id" => "trim|lte=255",
        "generated" => "datetime",
        "is_dirty" => "datetime",
        "fe_id" => "trim|lte=10"
    );

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        $qb=$this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        //$this->addFilter($qb, $filter, $special);
        return $qb->get();
    }

    public function count($filter, $special = null) {
        $qb = $this->numrows();
        //$this->addFilter($qb, $filter, $special);
        return $qb->count();
    }

//    public function save() {
//        $wasnew=$this->isNew();
//        if(parent::save()) {
//            if($wasnew) {
//                Audit::Create($this,"created");
//            }
//            else {
//                Audit::Create($this,"updated");
//            }
//            return true;
//        }
//        return false;
//    }

    public function delete($id=null) {
        if ($id === null) {
            $id = $this->getKey();
            $accr=$this;
        }
        else {
            $accr=new Accreditation($id,true); // reload from database
        }
        if(parent::delete($id)) {
            $path=$accr->getPath();
            if(file_exists($path)) {
                @unlink($path);
            }
//            Audit::Clear($this);
        }
    }

    public function createID($tries=0) {
        $id1 = random_int(101, 999);
        $id2 = random_int(101, 999);

        $id = sprintf("%03d-%03d",$id1,$id2);
        $this->fe_id=$id;

        // see if there is an open accreditation with this ID. In that case, we generate a new
        $a = $this->findByID($id);
        if(!empty($a) && $a->exists() && $tries<10) {
            return $this->createID($tries+1);
        }
        if(!empty($a) && $a->exists() && $tries>=10) {
            // this should not happen, but we are catching the theoretical case
            $id="I".$this->getKey();
        }

        return $id;
    }

    private function accreditationIsValid($a,$feid) {
        // check the front-end ID to make sure
        if($a->fe_id != $feid) {
            error_log("stored fe_id does not match passed value " . $feid);
            return false;
        }

        $event=new Event($a->event_id,true);
        if(!$event->exists()) {
            error_log("event does not exist, invalid accreditation");
            return false;
        }

        $caps = $event->eventCaps();
        $enddate = strtotime($event->event_open) + (intval($event->event_duration)+1) * (24*60*60);
        // we cannot accredit anything until registrations opens
        $starttime=strtotime($event->event_registration_open);
        if(in_array($caps,array("system","organisation","accreditation")) && time() < $enddate && time() > $starttime) {
            return true;
        }
        return false;
    }

    public function findByID($aid) {
        // should by %03d-%03d
        $accr=null;
        $matches=array();
        if(preg_match("/(\d\d\d)-(\d\d)(\d)/",$aid,$matches)) {
            $accreditation=$this->select('*')->where("fe_id",$aid)->first();
            if(!empty($accreditation)) {
                $accr = new Accreditation($accreditation);
            }
        }
        else if(preg_match("/I(\d+)/",$aid,$matches)) {
            // error case: ID prefixed with an I
            $accr=new Accreditation($matches[1],true);
        }
        if(!empty($accr)) {
            if ($this->accreditationIsValid($accr, $aid)) {
                return $accr;
            }
        }
        return null;
    }

    public function isDirty() {
        return !empty($this->is_dirty);
    }

    public function makeDirty($fid, $eventid = null)
    {
        if (is_object($eventid) && get_class($eventid) == "EVFRanking\\Models\\Registration") {
            $eventid = $eventid->registration_mainevent;
        }
        if (empty($eventid)) {
            $rows = $this->query()->select('event_id')->from('TD_Event')->where("(ADDDATE(event_open, event_duration + 4 ) > NOW())")->get();
            if (!empty($rows)) {
                $eventid = [];
                foreach ($rows as $row) {
                    $eventid[] = $row->event_id;
                }
            }
            if (empty($eventid)) {
                return; // no pending events to make dirty
            }
        }
        else {
            $eventid = [$eventid];
        }
        $fid = intval($fid);
        $cnt = $this->numrows()->where("fencer_id", $fid)->where_in("event_id", $eventid)->count();

        foreach ($eventid as $eid) {
            if ($cnt == 0) {
                // we create an empty accreditation to signal the queue that this set needs to be reevaluated
                $dt = new Accreditation();
                $dt->fencer_id = $fid;
                $dt->event_id = $eid;
                $dt->data = json_encode(array());

                $tmpl = new AccreditationTemplate();
                $lst = $tmpl->selectAll(0, 10000, null, "");
                if (!empty($lst) && sizeof($lst)) {
                    $dt->template_id = $lst[0]->id;
                }
                $dt->file_id = null;
                $dt->generated = null;
                $dt->is_dirty = strftime('%F %T');
                $dt->save();
            }
            else {
                $qb = $this->query()->set("is_dirty", strftime('%F %T'))->where('fencer_id', $fid)->where("event_id", $eid)->update();
            }
        }
    }

    public function unsetDirty($fid=null, $eventid=null) {
        if($fid===null && $eventid===null && !$this->isNew()) {
            $this->is_dirty=null;
            $this->save();
        }
        else {
            $fid=intval($fid);
            $eventid=intval($eventid);
            $this->query()->set("is_dirty", null)->where('fencer_id', $fid)->where("event_id", $eventid)->update();
        }
    }

    public function checkDirtyAccreditations()
    {
        // only look at accreditations that were made dirty at least 10 minutes ago, to avoid
        // situations where a registration is entered and we generate a new badge half way
        // there should not be a situation where one row is <10 minutes ago and another is >10 minutes,
        // unless it is exactly around this border (so both < 9.9 minutes)
        $notafter = strftime('%F %T', time() - EVFRANKING_RENEW_DIRTY_ACCREDITATONS * 60);
        $res = $this->select('fencer_id, event_id')
            ->from('TD_Accreditation')
            ->where("is_dirty", "<>", null)
            ->where("is_dirty", "<", $notafter)
            ->groupBy(array("fencer_id", "event_id")) // make sure we get one entry per fencer/event
            ->orderBy(array("fencer_id", "event_id"))
            ->get();

        // we usually do not expect 2 events to run at the same time, causing additional queue entries
        if (!empty($res)) {
            // for each fencer, create a new AccreditationDirty job
            // we do not change the is_dirty timestamp. This will cause new jobs to be entered
            // as long as we do not process the queue, but as we'll check and reset the flag
            // before processing, the superfluous queue entries will die quickly
            foreach ($res as $row) {
                $job = new \EVFRanking\Jobs\AccreditationDirty();
                $job->queue->event_id = $row->event_id;
                $job->create($row->fencer_id);
            }
        }
    }

    private function checkDirtyAccreditationsForFencer($fid, $eid, $queueid)
    {
        $res = $this->select('fencer_id, event_id, id')
            ->from('TD_Accreditation')
            ->where("is_dirty", "<>", null)
            ->where("fencer_id", $fid)
            ->where("event_id", $eid)
            ->get();

        if (!empty($res)) {
            $job = new \EVFRanking\Jobs\AccreditationDirty();
            $job->queue->queue = $queueid;
            $job->queue->event_id = $eid;
            $job->create($fid);
        }
    }

    public function selectRecordsByFencer($fid) {
        $res = $this->select('*')->where("fencer_id",$fid)->get();
        $retval=array();
        if(!empty($res)) {
            foreach($res as $row) {
                $retval[]=new Accreditation($row);
            }
        }
        return $retval;
    }

    public function import($adata) {
        // import accreditation data and create a new object
        $this->data = json_encode($adata);
        $this->hash = $this->makeHash($adata);
    }

    public function similar($dataobj) {
        $val = $this->makeHash($dataobj);
        return $this->hash == $val;
    }

    private function makeHash($dataobj) {
        $val = $this->makeString($dataobj);
        return hash('sha256',$val,false);
    }

    private function makeString($dataobj) {
        // sort all array keys to make sure they are in the same order.
        // then concatenate all the values to the keys.
        // Any change in case or value will be detected, but changes in
        // variable order within arrays are corrected
        if(is_array($dataobj)) {
            $keys=array_keys($dataobj);
            sort($keys);
            $retval="";
            foreach($keys as $k) {
                $retval.=$k.":".$this->makeHash($dataobj[$k]);
            }
            return $retval;
        }
        else {
            return strval($dataobj);
        }
    }

    public function getPath()
    {
        $basedir = \EVFRanking\Util\PDFManager::PDFPath($this->event_id);
        return $basedir . "accreditation_" . $this->file_id . ".pdf";
    }

    public function clean($eid)
    {
        $event = new \EVFRanking\Models\Event($eid, true);
        if ($event->exists()) {
            $this->query()->from("TD_Accreditation")->where("event_id", $event->getKey())->delete();
        }
    }

    public function regenerate($eid)
    {
        $retval = array();
        $event = new \EVFRanking\Models\Event($eid, true);
        if ($event->exists()) {
            // make all existing accreditations for this event dirty
            // This is a catch all to make sure we get all accreditations
            $this->query()->set("is_dirty", strftime('%F %T'))->where("event_id", $event->getKey())->update();

            // loop over all different fencers that are registered and make accreditations dirty.
            // Only select fencers that have no accreditations, so we can make new ones.
            $fids = $this->query()->from("TD_Registration")
                ->select("distinct registration_fencer")
                ->where("registration_mainevent", $event->getKey())
                ->where("not exists(select * from TD_Accreditation a where a.fencer_id=TD_Registration.registration_fencer)")
                ->get();

            foreach ($fids as $fid) {
                $id = $fid->registration_fencer;
                $this->makeDirty($id, $event->getKey());
            }

            // The dirty-accreditation check will remove accreditations for fencers that have no registration
        }
        return $retval;
    }

    public function generate($eid, $type, $typeid)
    {
        $event = new \EVFRanking\Models\Event($eid, true);
        if ($event->exists()) {
            // create a summary document for the given selection
            $job = new \EVFRanking\Jobs\SetupSummary();
            $job->queue->event_id = $eid;
            $job->create($eid, $type, $typeid);
        }
        return array();
    }

    public function generateForFencer($eid, $fid) {
        $event = new Event($eid, true);
        $fencer = new Fencer($fid, true);
        if ($event->exists() && $fencer->exists()) {
            $this->makeDirty($fencer->getKey(), $event->getKey());
            // create jobs to recreate the accreditations if needed
            $myqueueid = uniqid();
            $this->checkDirtyAccreditationsForFencer($fencer->getKey(), $event->getKey(), $myqueueid);

            $queue = new Queue();
            $queue->queue = $myqueueid;

            while ($queue->tick(60)) {
                // continue
            }
        }
        return array();
    }

    public function checkSummaryDocuments($eid)
    {
        // check hashes of all summary documents of this event and remove any that our out of sync
        $retval = array();
        $event = new \EVFRanking\Models\Event($eid, true);
        if ($event->exists()) {
            $allsummaries = PDFManager::AllSummaries($eid);
            foreach ($allsummaries as $doc) {
                if (!$doc->fileExists() || !PDFManager::CheckDocument($doc)) {
                    $doc->deleteByName();
                }
            }
        }
        return $retval;
    }

    private function humanFilesize($bytes, $decimals = 1) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    private function isValidQueue($queue)
    {
        if ($queue->exists() && $queue->state == "new") {
            return true;
        }
        return false;
    }

    public function overview($eid)
    {
        $retval = array();
        $event = new \EVFRanking\Models\Event($eid,true);
        if ($event->exists()) {
            // create an overview of total registrations, total accreditations, dirty accreditations and
            // generated accreditations per:
            // - event
            // - country
            // - role
            // - accreditation template

            // We first create a list of all side events that have competitions, so we can easily weed
            // out the (non)athletes
            $ses = SideEvent::SelectCompetitions($event);
            $sids = array();
            foreach ($ses as $sid) {
                $sids[] = $sid->id;
            }
            
            // The mark the templates for athletes, federative roles and other roles
            $rtype = RoleType::FindByType("Country");
            $roles = Role::ListAll();
            $roleById = array();
            $roleByType = array();
            foreach ($roles as $r) {
                $roleById["r" . $r->role_id] = new Role($r);
                if (!isset($roleByType["r" . $r->role_type])) {
                    $roleByType["r" . $r->role_type] = array();
                }
                $roleByType["r" . $r->role_type][] = $r->role_id;
            }

            $templateByType = AccreditationTemplate::TemplateIdsByRoleType($event, $roleById);

            $retval["events"] = $this->overviewForEvents($event, $sids, $templateByType, $roleByType, $rtype);
            $retval["countries"] = $this->overviewForCountries($event, $sids, $templateByType, $roleByType, $rtype);
            $retval["roles"] = $this->overviewForRoles($event, $sids, $templateByType, $roleByType, $rtype);
            $retval["templates"] = $this->overviewForTemplates($event, $sids, $templateByType, $roleByType, $rtype);

            $retval["jobs" ] = $this->listJobs($retval, $event);

            // also provide a queue indication
            $queue = new Queue();
            $retval["queue"] = $queue->count(null, ["waiting" => true]);
        }
        return $retval;
    }

    private function getActiveQueues($event)
    {
        $activequeues = Queue::instance()->selectAll(
            0,
            10000,
            array(
                "event" => $event->getKey(),
                "queue" => "default",
                "model" => [\EVFRanking\Jobs\CreateSummary::class, \EVFRanking\Jobs\SetupSummary::class]
            ),
            '',
            array('pending' => true)
        );
        $activequeue = array();
        // the queue field is used to store the summary queue key
        foreach ($activequeues as $q) {
            $queue = new Queue($q);
            if (!isset($activequeue[$queue->getData('key')])) $activequeue[$queue->getData('key')] = [];
            $activequeue[$queue->getData('key')][] = $queue;
        }
        return $activequeue;
    }

    private function listJobs($retval, $event)
    {
        // create a list of all jobs for this event based on the running jobs in our wordpress queue configuration
        $jobs = array();
        $activequeue = $this->getActiveQueues($event);

        foreach ($retval["events"] as $ev) {
            $key = $event->getKey() . "_Event_" . $ev["event"];
            if (isset($activequeue[$key])) {
                foreach ($activequeue[$key] as $queue) {
                    if ($this->isValidQueue($queue)) {
                        if (!isset($jobs[$key])) $jobs[$key] = [];
                        $jobs[$key][] = array("id" => $ev["event"], "start" => $queue->available_at);
                    }
                }
            }
        }
        foreach ($retval["countries"] as $dat) {
            $key = $event->getKey() . "_Country_" . $dat["country"];
            if (isset($activequeue[$key])) {
                foreach ($activequeue[$key] as $queue) {
                    if ($this->isValidQueue($queue)) {
                        if (!isset($jobs[$key])) $jobs[$key] = [];
                        $jobs[$key][] = array("id" => $dat["country"], "start" => $queue->available_at);
                    }
                }
            }
        }
        foreach ($retval["roles"] as $dat) {
            $key = $event->getKey() . "_Role_" . $dat["role"];
            if (isset($activequeue[$key])) {
                foreach ($activequeue[$key] as $queue) {
                    if ($this->isValidQueue($queue)) {
                        if (!isset($jobs[$key])) $jobs[$key] = [];
                        $jobs[$key][] = array("id" => $dat["role"], "start" => $queue->available_at);
                    }
                }
            }
        }
        foreach ($retval["templates"] as $dat) {
            $key = $event->getKey() . "_Template_" . $dat["template"];
            if (isset($activequeue[$key])) {
                if (isset($activequeue[$key])) {
                    foreach ($activequeue[$key] as $queue) {
                        if ($this->isValidQueue($queue)) {
                            if (!isset($jobs[$key])) $jobs[$key] = [];
                            $jobs[$key][] = array("id" => $dat["template"], "start" => $queue->available_at);
                        }
                    }
                }
            }
        }
        return $jobs;
    }

    private function findDocuments($event, $type, $model)
    {
        $name = PDFManager::SummaryName($event, $type, $model);
        $docModel = new \EVFRanking\models\Document();
        $documents = $docModel->findByName($name);
        return array_map(function ($doc) {
            $doc->configObject = json_decode($doc->config);
            if ($doc->configObject == false) $doc->configObject = (object)array();
            if ($doc->fileExists()) {
                $doc->humanSize = $this->humanFilesize(filesize($doc->getPath()));
            }
            return $doc;
        }, $documents);
    }

    private function overviewForEvents($event, $sids, $templateByType, $roleByType, $rtype)
    {
        // for side-events, we only display the athletes and participants
        // we do that by selecting on the accreditation templates
        $k1 = "r0"; // athlete role
        $acceptabletemplates = $templateByType[$k1];
        $acceptableroles = array("0");

        $results = $this->query()->from("TD_Event_Side")->select(array(
            "TD_Event_Side.id", 
            "TD_Event_Side.title", 
            "r.total as registrations", 
            "a.total as accreditations",
            "d.total as dirty",
            "g.total as generated"
        ))
            ->join(
                function ($qb) use ($event, $acceptableroles) {
                    $qb->from("TD_Registration")
                        ->select("registration_event, count(*) as total")
                        ->where("registration_mainevent", $event->getKey())
                        ->where_in("registration_role", $acceptableroles)
                        ->groupBy("registration_event");
                },
                "r",
                "TD_Event_Side.id=r.registration_event"
            )
            ->join(
                function ($qb) use ($event, $acceptabletemplates) {
                    $qb->from("TD_Registration")
                        ->join("TD_Accreditation", "ar", "ar.fencer_id=TD_Registration.registration_fencer", "inner")
                        ->select("TD_Registration.registration_event, count(*) as total")
                        ->where("TD_Registration.registration_mainevent", $event->getKey())
                        ->where("ar.event_id", $event->getKey())
                        ->where_in("ar.template_id", $acceptabletemplates)
                        ->groupBy("TD_Registration.registration_event");
                },
                "a",
                "TD_Event_Side.id=a.registration_event"
            )
            ->join(
                function ($qb) use ($event, $acceptabletemplates) {
                    $qb->from("TD_Registration")
                        ->join("TD_Accreditation", "ar", "ar.fencer_id=TD_Registration.registration_fencer", "inner")
                        ->select("TD_Registration.registration_event, count(*) as total")
                        ->where("TD_Registration.registration_mainevent", $event->getKey())
                        ->where("ar.event_id", $event->getKey())
                        ->where_in("ar.template_id", $acceptabletemplates)
                        ->where("ar.is_dirty", "<>", null)
                        ->groupBy("TD_Registration.registration_event");
                },
                "d",
                "TD_Event_Side.id=d.registration_event"
            )
            ->join(
                function ($qb) use ($event, $acceptabletemplates) {
                    $qb->from("TD_Registration")
                        ->join("TD_Accreditation", "ar", "ar.fencer_id=TD_Registration.registration_fencer", "inner")
                        ->select("TD_Registration.registration_event, count(*) as total")
                        ->where("TD_Registration.registration_mainevent", $event->getKey())
                        ->where("ar.event_id", $event->getKey())
                        ->where_in("ar.template_id", $acceptabletemplates)
                        ->where("ar.is_dirty", "=", null)
                        ->groupBy("TD_Registration.registration_event");
                },
                "g",
                "TD_Event_Side.id=g.registration_event"
            )
            ->where("TD_Event_Side.competition_id", "<>", null)
            ->where("TD_Event_Side.event_id", $event->getKey())
            ->orderBy("TD_Event_Side.title")
            ->get();

        $retval=array();
        foreach($results as $se) {
            $documents = array_map(function ($d) {
                return [
                    "id" => $d->getKey(),
                    "size" => $d->humanSize,
                    "available" => $d->fileExists()
                ];
            }, $this->findDocuments($event, 'Event', $se->id));

            $retval[]=array(
                "event" => $se->id,
                "title" => $se->title,
                "registrations" => intval($se->registrations),
                "accreditations" => intval($se->accreditations),
                "dirty" => intval($se->dirty),
                "generated" => intval($se->generated),
                "documents" => $documents
            );
        }
        return $retval;
    }

    private function overviewForCountries($event,$sids,$templateByType,$roleByType, $rtype) {
        // for countries, we only display the athletes and federative roles
        // we do that by selecting on the accreditation templates
        $k1="r0"; // athlete role
        $k2="r".$rtype->getKey(); // federative role
        $acceptabletemplates=array_merge($templateByType[$k1],$templateByType[$k2]);
        if(empty($acceptabletemplates)) {
            $acceptabletemplates=arraY(-1);

        }
        $acceptableroles = array_merge(array("0"), $roleByType[$k2]);

        $results = $this->query()->from("TD_Country")->select(array(
            "TD_Country.country_id", 
            "TD_Country.country_name",
            "TD_Country.country_abbr",
            "r.total as registrations", 
            "a.total as accreditations",
            "d.total as dirty",
            "g.total as generated"
        ))
          ->join(function($qb) use ($event,$acceptableroles,$sids) {
            $qb->from("TD_Registration")
                ->join("TD_Fencer","fr","fr.fencer_id=TD_Registration.registration_fencer","inner")
                ->select("fr.fencer_country, count(*) as total")
                ->where("TD_Registration.registration_mainevent",$event->getKey())
                ->where_in("TD_Registration.registration_role",$acceptableroles)
                ->where(function($qb) use($sids) {
                    $qb->where_in("TD_Registration.registration_event",$sids)
                       ->or_where("TD_Registration.registration_event","=",null);
                })
                ->groupBy("fr.fencer_country");
          },"r", "TD_Country.country_id=r.fencer_country")
          ->join(function($qb) use ($event,$acceptabletemplates) {
            $qb->from("TD_Accreditation")
                ->join("TD_Fencer","fr","fr.fencer_id=TD_Accreditation.fencer_id","inner")
                ->select("fr.fencer_country, count(*) as total")
                ->where("TD_Accreditation.event_id",$event->getKey())
                ->where_in("TD_Accreditation.template_id",$acceptabletemplates)
                ->groupBy("fr.fencer_country");
          },"a", "TD_Country.country_id=a.fencer_country")
          ->join(function($qb) use ($event, $acceptabletemplates) {
            $qb->from("TD_Accreditation")
                ->join("TD_Fencer","fr","fr.fencer_id=TD_Accreditation.fencer_id","inner")
                ->select("fr.fencer_country, count(*) as total")
                ->where("TD_Accreditation.event_id",$event->getKey())
                ->where("TD_Accreditation.is_dirty", "<>", null)
                ->where_in("TD_Accreditation.template_id", $acceptabletemplates)
                ->groupBy("fr.fencer_country");
          },"d", "TD_Country.country_id=d.fencer_country")
          ->join(function($qb) use ($event, $acceptabletemplates) {
            $qb->from("TD_Accreditation")
                ->join("TD_Fencer","fr","fr.fencer_id=TD_Accreditation.fencer_id","inner")
                ->select("fr.fencer_country, count(*) as total")
                ->where("TD_Accreditation.event_id",$event->getKey())
                ->where("TD_Accreditation.is_dirty", "=", null)
                ->where_in("TD_Accreditation.template_id", $acceptabletemplates)
                ->groupBy("fr.fencer_country");
          },"g", "TD_Country.country_id=g.fencer_country")->get();


        $retval=array();
        foreach($results as $se) {
            $documents = array_map(function ($d) {
                return [
                    "id" => $d->getKey(),
                    "size" => $d->humanSize,
                    "available" => $d->fileExists()
                ];
            }, $this->findDocuments($event, 'Country', $se->country_id));
            $retval[]=array(
                "country" => $se->country_id,
                "name" => $se->country_name,
                "abbr" => $se->country_abbr,
                "registrations" => intval($se->registrations),
                "accreditations" => intval($se->accreditations),
                "dirty" => intval($se->dirty),
                "generated" => intval($se->generated),
                "documents" => $documents
            );
        }
        return $retval;
    }
    private function overviewForRoles($event,$sids,$templateByType, $roleByType) {
        // create a total for each registered role for this event.
        // For each registration, we need to find accreditations of fencers with that role
        $results = $this->query()
            ->from("TD_Registration")
            ->select(array(
                "TD_Registration.registration_role",
                "r.cnt as registrations",
                "a.cnt as accreditations",
                "d.cnt as dirty",
                "g.cnt as generated"
            ))
            ->join(function($qb) use ($event,$sids) {
                $qb->select("registration_role, count(*) as cnt")
                   ->from("TD_Registration")
                   ->where(function ($qb) use ($sids) {
                        $qb->where_in("registration_event", $sids)
                           ->or_where("registration_event", "=", null);
                    })
                   ->where("registration_mainevent",$event->getKey())
                   ->groupBy("registration_role");
            },"r","r.registration_role=TD_Registration.registration_role")
            ->join(function($qb) use ($event,$sids) {
                $qb->from(function($qb) use($event,$sids) {
                    $qb->select("r1.registration_role, r1.registration_fencer, 1 as cnt2")
                    ->from("TD_Accreditation")
                    ->join("TD_Registration","r1","r1.registration_fencer=TD_Accreditation.fencer_id and r1.registration_mainevent=TD_Accreditation.event_id")
                    ->where("TD_Accreditation.event_id",$event->getKey())
                    ->where(function($qb) use($sids) {
                        $qb->where_in("r1.registration_event", $sids)
                            ->or_where("r1.registration_event","=",null);
                    })
                    ->groupBy("r1.registration_role, r1.registration_fencer");
                },"s1")
                ->select("s1.registration_role, sum(s1.cnt2) as cnt")
                ->groupBy("s1.registration_role");
            },"a", "TD_Registration.registration_role=a.registration_role")
            ->join(function($qb) use ($event,$sids) {
                $qb->from(function($qb) use($event,$sids) {
                    $qb->select("r2.registration_role, r2.registration_fencer, 1 as cnt2")
                    ->from("TD_Accreditation")
                    ->join("TD_Registration","r2","r2.registration_fencer=TD_Accreditation.fencer_id and r2.registration_mainevent=TD_Accreditation.event_id")
                    ->where(function($qb) use($sids) {
                        $qb->where_in("r2.registration_event", $sids)
                            ->or_where("r2.registration_event","=",null);
                    })
                    ->where("TD_Accreditation.event_id",$event->getKey())
                    ->where("TD_Accreditation.is_dirty", "<>", null)
                    ->groupBy("r2.registration_role, r2.registration_fencer");
                },"s2")
                ->select("s2.registration_role, sum(s2.cnt2) as cnt")
                ->groupBy("s2.registration_role");
            },"d", "TD_Registration.registration_role=d.registration_role")
            ->join(function($qb) use ($event,$sids) {
                $qb->from(function($qb) use($event,$sids) {
                    $qb->select("r3.registration_role, r3.registration_fencer, 1 as cnt2")
                    ->from("TD_Accreditation")
                    ->join("TD_Registration","r3","r3.registration_fencer=TD_Accreditation.fencer_id and r3.registration_mainevent=TD_Accreditation.event_id")
                    ->where("TD_Accreditation.event_id",$event->getKey())
                    ->where(function($qb) use($sids) {
                        $qb->where_in("r3.registration_event", $sids)
                            ->or_where("r3.registration_event","=",null);
                    })
                    ->where("TD_Accreditation.is_dirty", "=", null)
                    ->groupBy("r3.registration_role, r3.registration_fencer");
                },"s3")
                ->select("s3.registration_role, sum(s3.cnt2) as cnt")
                ->groupBy("s3.registration_role");
            },"g", "TD_Registration.registration_role=g.registration_role")
            ->groupBy("TD_Registration.registration_role")
            ->get();

        $retval=array();
        foreach($results as $se) {
            $role=$se->registration_role;

            $key="r$role";
            $documents = array_map(function ($d) {
                return [
                    "id" => $d->getKey(),
                    "size" => $d->humanSize,
                    "available" => $d->fileExists()
                ];
            }, $this->findDocuments($event, 'Role', $role));

            $retval[$key]=array(
                "role" => $role,
                "registrations" => intval($se->registrations),
                "accreditations" => intval($se->accreditations),
                "dirty" => intval($se->dirty),
                "generated" => intval($se->generated),
                "documents" => $documents,
            );
        }
        return array_values($retval);
    }
    private function overviewForTemplates($event,$sids,$templateByType, $roleByType) {
        $results = $this->query()->from("TD_Accreditation_Template")->select(array(
            "TD_Accreditation_Template.id",
            "TD_Accreditation_Template.name", 
            "a.total as accreditations",
            "d.total as dirty",
            "g.total as generated"
        ))
          ->where("TD_Accreditation_Template.event_id",$event->getKey())
          ->join(function($qb) use ($event) {
            $qb->from("TD_Accreditation")
                ->select("template_id, count(*) as total")
                ->where("event_id",$event->getKey())
                ->groupBy("template_id");
          },"a","TD_Accreditation_Template.id=a.template_id")
          ->join(function($qb) use ($event) {
            $qb->from("TD_Accreditation")
                ->select("template_id, count(*) as total")
                ->where("event_id",$event->getKey())
                ->where("TD_Accreditation.is_dirty", "<>", null)
                ->groupBy("template_id");
          },"d","TD_Accreditation_Template.id=d.template_id")
          ->join(function($qb) use ($event) {
            $qb->from("TD_Accreditation")
                ->select("template_id, count(*) as total")
                ->where("event_id",$event->getKey())
                ->where("TD_Accreditation.is_dirty", "=", null)
                ->groupBy("template_id");
          },"g","TD_Accreditation_Template.id=g.template_id")->get();

        $retval=array();
        foreach($results as $se) {
            $documents = array_map(function ($d) {
                return [
                    "id" => $d->getKey(),
                    "size" => $d->humanSize,
                    "available" => $d->fileExists()
                ];
            }, $this->findDocuments($event, 'Template', $se->id));

            $retval[]=array(
                "template" => $se->id,
                "name" => $se->name,
                "registrations" => intval($se->accreditations),
                "accreditations" => intval($se->accreditations),
                "dirty" => intval($se->dirty),
                "generated" => intval($se->generated),
                "documents" => $documents
            );
        }
        return $retval;
    }

    public function findAccreditations($eid,$fid) {
        $event=new Event($eid,true);
        $fencer=new Fencer($fid,true);
        $retval=array("list"=>array());
        if($event->exists() && $fencer->exists()) {
            $accreditations = $this->select("TD_Accreditation.*, t.name")
                ->join("TD_Accreditation_Template","t","t.id=TD_Accreditation.template_id")
                ->where("fencer_id",$fencer->getKey())->where("TD_Accreditation.event_id",$event->getKey())->get();
            if(!empty($accreditations)) {
                foreach($accreditations as $a) {
                    $accr=new Accreditation($a);
                    $path=$accr->getPath();
                    $data=array("id"=>$accr->getKey(),"dirty"=>$accr->isDirty(),"has_file"=>false, "title"=>$a->name);
                    if(file_exists($path)) {
                        $data["has_file"]=true;
                    }
                    $retval["list"][]=$data;
                }
            }
        }
        return $retval;
    }

    public function cleanForFencer($fid,$eid) {
        $this->query()->where("fencer_id",intval($fid))->where("event_id",intval($eid))->delete();
    }
}
 