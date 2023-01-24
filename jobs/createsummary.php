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

class CreateSummary extends BaseJob
{
    const QUEUENAME = "evfranking_queued_summary";

    // first argument is the document id
    // 2nd argument is the queue key
    public function create()
    {
        $args = func_get_args();
        $docid = sizeof($args) > 0 ? $args[0] : null;
        $key = sizeof($args) > 1 ? $args[1] : null;
        $this->queue->setData("document_id", $docid);
        $this->queue->setData("key", $key);
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

        $document = new \EVFRanking\models\Document($this->queue->getData("document_id"));
        if (!$document->exists()) {
            $this->fail("Invalid document selected for summary: " . $this->queue->getData("document_id"));
        }

        $document->configObject = json_decode($document->config);
        if ($document->configObject === false) {
            $this->fail("Invalid document, no configuration");
        }

        $typeid = intval($document->configObject->model ?? -1);
        $type = $document->configObject->type;
        $eventid = intval($document->configObject->event ?? -1);

        if ($eventid != $event->getKey()) {
            $this->fail("Invalid document, event differs from configuration");
        }
        if (!in_array($type, array('Country', 'Role', 'Template', 'Event'))) {
            $this->fail("Invalid document, unsupported model type");
        }

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
                else if (intval($typeid) == -1) {
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
                $this->fail("Invalid document, type model does not exist");
            }
        }

        $creator = new \EVFRanking\Util\PDFSummary($document, $event, $type, $model);
        $creator->create();

        if (!file_exists($document->getPath())) {
            $this->fail("Could not create PDF at " . $document->getPath());
        }

        $this->log("end of CreateSummary job");
    }

    public function fail($msg = null)
    {
        parent::fail($msg);
    }
}
