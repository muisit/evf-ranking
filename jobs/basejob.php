<?php

/**
 * EVF-Ranking BaseJob clas
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

namespace EVFRanking\Jobs;

class BaseJob
{
    public $queue = null;
    public $_log = array();

    public function __construct(\EVFRanking\Models\Queue $queue = null)
    {
        if (empty($queue)) {
            $queue = new \EVFRanking\Models\Queue();
            $queue->queue = "default";
        }
        $this->queue = $queue;
    }

    public function create()
    {
        $this->queue->state = "new";
        $this->queue->setKey(-1);
        $this->queue->model = get_class($this);
        $this->queue->save();
    }

    public function reset_timer()
    {
        // allow jobs to run for 30 seconds per loop
        set_time_limit(30);
    }

    public function run()
    {
        $this->reset_timer();
        // nothing
    }

    public function log($txt)
    {
        $dt = date('Y-m-d H:i:s');
        $txt = $dt . ": " . $txt;
        global $evflogger;
        if (!empty($evflogger)) {
            $evflogger->log($txt);
        }
        $this->_log[] = $txt;
    }

    protected function fail($msg = null)
    {
        if (!empty($msg)) {
            $this->log($msg);
        }
        $logs = $this->queue->getData("logs", array());
        $this->queue->setData("logs", $logs + $this->_log);
        $this->queue->fail();
    }

    protected function yield()
    {
        $logs = $this->queue->getData("logs", array());
        $this->queue->setData("logs", $logs + $this->_log);
        $this->queue->yield();
        exit();
    }

    protected function timeLeft()
    {
        return $this->queue->timeLeft();
    }
}
