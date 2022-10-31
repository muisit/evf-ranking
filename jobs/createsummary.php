<?php

/**
 * EVF-Ranking AccreditationCreate job clas
 *
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020-2021 Michiel Uitdehaag for muis IT
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

namespace EVFRanking\Jobs;

class CreateSummary extends BaseJob {
    const QUEUENAME = "evfranking_queued_summary";

    private function createSummaryKey($type, $tid)
    {
        $eid = intval($this->queue->event_id);
        return $eid . "_" . $type . "_" . $tid;
    }

    public function isForType($type, $typeId)
    {
        $key = $this->createSummaryKey($type, $typeId);
        $ourKey = $this->queue->getData('key');
        return $key == $ourKey;
    }

    // first argument is an event id, then the selection type and the selection type ID
    public function create()
    {
        $args = func_get_args();
        $type = sizeof($args) > 1 ? $args[1] : null;
        $typeid = sizeof($args) > 2 ? $args[2] : null;
        $this->queue->setData("type", $type);
        $this->queue->setData("type_id", intval($typeid));
        $this->queue->setData("key", $this->createSummaryKey($type, $typeid));
        parent::create();
    }

    public function run()
    {
        $this->log("running CreateSummary job");
        parent::run();

        $event = new \EVFRanking\Models\Event($this->queue->event_id, true);
        if (!$event->exists()) {
            $this->fail("Invalid event record, cannot create PDF summary");
        }

        $type = $this->queue->getData("type");
        if (!in_array($type, array("Country","Role","Template","Event"))) {
            $this->fail("Invalid summary type set");
        }

        $typeid = intval($this->queue->getData("type_id"));
        $model = null;
        switch ($type) {
        case 'Country':
            $model = new \EVFRanking\Models\Country($typeid,true);
            break;
        case 'Role':
            $model = new \EVFRanking\Models\Role($typeid,true);
            if (intval($typeid) == 0) {
                $model->role_name = "Athlete";
                $model->role_id = 0;
            }
            else if(intval($typeid) == -1) {
                $model->role_name = "Participant";
                $model->role_id = -1;
            }
            break;
        case 'Template':
            $model = new \EVFRanking\Models\AccreditationTemplate($typeid,true);
            break;
        case 'Event':
            $model = new \EVFRanking\Models\SideEvent($typeid,true);
            break;
        }

        if (!$model->exists()) {
            if ($type == "Role" && in_array($model->role_id, array(0,-1), true)) {
                // pass these two non-existing role models
            }
            else {
                $this->fail("Invalid type model, cannot create PDF summary for $type/$typeid");
            }
        }

        $creator = new \EVFRanking\Util\PDFSummary($event,$type,$model);
        $creator->create();

        $path = isset($creator->path) ? $creator->path : null;
        if (!file_exists($path)) {
            $this->fail("Could not create PDF at " . $path);
        }
        $this->log("end of CreateSummary job");
    }

    public function fail($msg = null)
    {
        parent::fail($msg);
    }
}
