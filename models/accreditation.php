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


namespace EVFRanking\Models;


class Accreditation extends Base {
    public $table = "TD_Accreditation";
    public $pk="id";
    public $fields=array("id","fencer_id","event_id","data","hash","template_id","file_id","generated", "is_dirty");
    public $fieldToExport=array(
        "id"=>"id",
        "fencer_id" => "fencer_id",
        "event_id" => "event_id",
        "data" => "data",
        "hash" => "hash",
        "template_id" => "template_id",
        "file_id" => "file_id",
        "generated" => "generated",
        "is_dirty"=>"is_dirty"
    );
    public $rules=array(
        "id" => "skip",
        "fencer_id"=> "required|model=Fencer",
        "event_id" => "required|model=Event",
        "data" => "trim",
        "hash" => "trim|lte=512",
        "template_id" => "required|model=AccreditationTemplate",
        "file_id" => "trim|lte=255",
        "generated" => "datetime",
        "is_dirty" => "datetime"
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

    public function makeDirty($fid,$eventid) {
        error_log("class of eventid is ".get_class($eventid));
        if(is_object($eventid) && get_class($eventid) == "EVFRanking\\Models\\Registration") {
            $eventid = $eventid->registration_mainevent;
        }        

        $cnt=$this->numrows()->where("fencer_id",$fid)->count();
        if($cnt == 0) {
            // we create an empty accreditation to signal the queue that this set needs to be reevaluated
            $dt=new Accreditation();
            $dt->fencer_id=$fid;
            $dt->event_id=$eventid;
            $dt->data=json_encode(array());

            $tmpl=new AccreditationTemplate();
            $lst = $tmpl->selectAll(0,10000,null,"");
            if(!empty($lst) && sizeof($lst)) {
                $dt->template_id = $lst[0]->id;
            }
            $dt->file_id=null;
            $dt->generated=null;
            $dt->is_dirty=strftime('%F %T');
            $dt->save();
        }
        else {
            $this->query()->set("is_dirty",strftime('%F %T'))->where('fencer_id',$fid)->where("event_id",$eventid)->update();
        }
    }

    public function unsetDirty($fid=null, $eventid=null) {
        if($fid===null && $eventid===null && !$this->isNew()) {
            $this->is_dirty=null;
            $this->save();
        }
        else {
            $this->query()->set("is_dirty", null)->where('fencer_id', $fid)->where("event_id", $eventid)->update();
        }
    }

    public function checkDirtyAccreditations() {
        // only look at accreditations that were made dirty at least 10 minutes ago, to avoid
        // situations where a registration is entered and we generate a new badge half way
        // there should not be a situation where one row is <10 minutes ago and another is >10 minutes,
        // unless it is exactly around this border (so both < 9.9 minutes)
        $notafter = strftime('%F %T', time() - 10 * 60);
        $res = $this->select('TD_Fencer.fencer_id, a.event_id, a.id')
            ->from('TD_Fencer')
            ->join("TD_Accreditation","a","a.fencer_id=TD_Fencer.fencer_id")
            ->where("not a.is_dirty is NULL")
            ->where("a.is_dirty","<",$notafter)
            ->groupBy(array("TD_Fencer.fencer_id", "a.event_id"))
            ->orderBy(array("TD_Fencer.fencer_id", "a.event_id"))
            ->get();

        // we usually do not expect 2 events to run 
        if(!empty($res)) {
            // for each fencer, create a new AccreditationDirty job
            // we do not change the is_dirty timestamp. This will cause new jobs to be entered
            // as long as we do not process the queue, but as we'll check and reset the flag
            // before processing, the superfluous queue entries will die quickly
            foreach($res as $row) {
                error_log("creating accreditationdirty for ".json_encode($row));
                $job = new \EVFRanking\Jobs\AccreditationDirty();
                error_log("creating queue job");
                $job->create($row->fencer_id, $row->event_id, $row->id);
            }
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
        $val = $this->makeString($dataobj);
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
}
 