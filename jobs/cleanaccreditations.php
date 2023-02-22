<?php

/**
 * EVF-Ranking CleanAccreditations job class
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

// phpcs:disable PSR12.Classes.ClassInstantiation
// phpcs:disable DONOT:Squiz.ControlStructures.ControlSignature

namespace EVFRanking\Jobs;

use EVFRanking\Models\Category;

class CleanAccreditations extends BaseJob
{
    private $event;

    public function run()
    {
        $this->log("running CleanAccreditations job");
        parent::run();

        $this->event = new \EVFRanking\Models\Event($this->queue->event_id);
        $this->event->config = json_decode($this->event->event_config);
        if (empty($this->event->config)) $this->event->config = (object)array();

        if (   $this->event->exists()
            && ($this->event->isPassed()
               || (isset($this->event->config->no_accreditations) && $this->event->config->no_accreditations)
               )
        ) {
            \EVFRanking\Util\PDFManager::cleanPath($this->queue->event_id);
            $accreditationModel = new \EVFRanking\Models\Accreditation();
            $accreditationModel->clean($this->queue->event_id);
        }

        $this->log("end of CleanAccreditations job");
    }
}
