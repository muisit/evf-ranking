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

class AccreditationCreate extends BaseJob {
    // first argument is an accreditation
    public function create() {
        $args= func_get_args();
        $accreditation = sizeof($args) > 0 ? $args[0] : null;
        $this->queue->setData("accreditation_id",is_object($accreditation) ? $accreditation->getKey() : intval($accreditation));
        parent::create();
    }

    public function run()
    {
        $this->log("running AccreditationCreate job");
        parent::run();

        $accreditation = new \EVFRanking\Models\Accreditation($this->queue->getData("accreditation_id"),true);
        if(!$accreditation->exists()) $this->fail("Invalid accreditation record, cannot create PDF");

        $fencer = new \EVFRanking\Models\Fencer($accreditation->fencer_id,true);
        if(!$fencer->exists()) $this->fail("Cannot read fencer record for accreditation");

        $event = new \EVFRanking\Models\Event($accreditation->event_id,true);
        if(!$event->exists()) $this->fail("Cannot read event record for accreditation");

        $template = new \EVFRanking\Models\AccreditationTemplate($accreditation->template_id,true);
        if(!$template->exists()) $this->fail("Missing template for accreditation");

        $country = new \EVFRanking\Models\Country($fencer->fencer_country,true);
        if(!$country->exists()) $this->fail("Fencer without a country");
       
        // clear old data
        $path = $accreditation->getPath();
        if(file_exists($path)) {
            @unlink($path);
        }

        $accreditation->file_id=uniqid();
        // reload the path due to a new file id
        $path = $accreditation->getPath();
        $tries=1;
        while($tries < 10 && file_exists($path)) {
            $tries++;
            $accreditation->file_id=uniqid();
            $path = $accreditation->getPath();
        }

        if(!file_exists($path)) {
            $accreditation->save();
            $creator=new \EVFRanking\Util\PDFCreator();
            $creator->create($fencer, $event, $template, $country, $accreditation, $path);

            if(file_exists($path)) {
                //\EVFRanking\Models\Audit::Create($accreditation, "(re)generated PDF");
                $accreditation->generated = strftime("%F %T");
                $accreditation->file_hash = hash_file('sha256',$path,false);
                $accreditation->save();
            }
            else {
                $accreditation->file_id = null;
                $accreditation->save();
                $this->fail("Error creating PDF, no output file, path $path already exists");
            }    
        }
        else {
            $this->fail("Could not create output PDF");
        }
        $this->log("end of AccreditationCreate job");
    }
}
