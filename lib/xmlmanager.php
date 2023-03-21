<?php

/**
 * EVF-Ranking ExportManager
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

namespace EVFRanking\Lib;

class XMLManager extends BaseLib
{
    public $sideevent;
    public $sideevents;
    public $event;
    public $category;
    public $competition;
    public $weapon;
    public $dom;
    public $root;
    public $doc;

    public function export($filetype, $sideevent, $event)
    {
        $this->sideevent = $sideevent;
        $this->event = $event;
        $this->filetype = $filetype;
        $this->category = null;
        $this->weapon = null;

        $this->sideevents = $this->event->sides();
        if (!empty($this->sideevent) && !$this->sideevent->isNew()) {
            $data = $this->sideevent->registrations();
            $this->competition = new \EVFRanking\Models\Competition($this->sideevent->competition_id,true);
            if (!$this->competition->exists()) {
                $this->competition = null;
            }
            else {
                $this->category = new \EVFRanking\Models\Category($this->competition->competition_category,true);
                $this->weapon = new \EVFRanking\Models\Weapon($this->competition->competition_weapon,true);
            }
        }
        else {
            $data = $this->event->registrations();
            $this->sideevent = null;
        }
        
        $this->dom = new \DOMDocument();
        $this->dom->encoding = 'UTF-8';
        $this->dom->xmlVersion = '1.0';
        $this->dom->formatOutput = true;
        $xml_file_name = $this->event->event_name;
        if (!empty($this->sideevent)) {
            $xml_file_name .= "." . $this->sideevent->title;
        }
        $xml_file_name .= ".xml";

        $implementation = new \DOMImplementation();
        if (!empty($this->category)) {
            if ($this->category->category_type == 'T') {
                $this->root = $this->dom->createElement('BaseCompetitionParEquipes');
                $this->fillDataEquipe($data);
                $doctype = $implementation->createDocumentType('BaseCompetitionParEquipes');
            }
            else {
                $this->root = $this->dom->createElement('BaseCompetitionIndividuelle');
                $this->fillDataIndividual($data);
                $doctype = $implementation->createDocumentType('BaseCompetitionIndividuelle');
            }
        }
        else {
            $this->root = $this->dom->createElement('BaseCompetitionIndividuelle');
            $doctype = $implementation->createDocumentType('BaseCompetitionIndividuelle');
            $this->fillDataIndividual($data);
        }
        $this->dom->appendChild($doctype);

        $this->root->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xsd", "http://www.w3.org/2001/XMLSchema");
        $this->root->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $this->dom->appendChild($this->root);

        header('Content-Disposition: attachment; filename="' . $xml_file_name . '";');
        header('Content-Type: text/xml; charset=UTF-8');
        echo "\xEF\xBB\xBF"; // echo a BOM for Windows purposes
        echo $this->dom->saveXML();
        ob_flush();
        exit();
    }

    private function fillDataEquipe($data) {
        $this->doc=$this->root;
        $this->setYear()->setWeapon()->setCategory()->setDates()->setEventData();
        $this->doc->setAttribute("TypeCompetition", "S"); // V is for Veterans weelchair

        $this->addFencers($data);
        $this->doc=$this->root;
        $this->addTeams($data);
    }

    private function addTeams($data) {
        // sort all data according to teams
        $teams=array();
        foreach($data as $d) {
            if(isset($d->registration_team)) {
                // team name is a unique value within country
                $key=$d->country_abbr.$d->registration_team;
                if(!isset($teams[$key])) $teams[$key]=array();
                $teams[$key][]=(array)$d;
            }
        }

        $equipes = $this->dom->createElement('Equipes');
        $this->root->appendChild($equipes);
        $this->doc=$equipes;
        foreach($teams as $team=>$regs) {
            $this->addEquipe($team,$regs);
        }
    }

    private function fillDataIndividual($data)
    {
        $this->doc = $this->root;
        $this->setYear()->setWeapon()->setCategory()->setDates()->setEventData();

        $this->addFencers($data);
    }

    private function addFencers($data)
    {
        $currentranking = array();
        if (!empty($this->competition)) {
            $ranking = new \EVFRanking\Models\Ranking();
            $results = $ranking->listResults($this->weapon->getKey(), $this->category);
            foreach ($results as $row) {
                $key = "fid" . $row["id"];
                $currentranking[$key] = $row;
            }
        }
        $tireurs = $this->dom->createElement("Tireurs");
        $this->root->appendChild($tireurs);
        $this->doc = $tireurs;
        foreach ($data as $row) {
            $this->addFencer((array)$row, $currentranking);
        }
    }

    private function addEquipe($team, $regs)
    {
        $equipe = $this->dom->createElement("Equipe");
        $firstreg = $regs[0]; // must be at least 1 registration

        if (!empty($pos)) {
            $tireur->setAttribute("Classement", $pos);
        }
        $equipe->setAttribute("ID", $team);
        $equipe->setAttribute('Nation', $firstreg["country_abbr"]);
        $equipe->setAttribute('Nom', $firstreg["country_name"] . " " . $firstreg["registration_team"]);
        if ($firstreg['fencer_gender'] == 'M') {
            $equipe->setAttribute('Sexe', 'M');
        }
        else {
            $equipe->setAttribute('Sexe', 'F');
        }

        // According to Ophardt, Tireur do not need to be included in Equipe, the Equipe-attribute of
        // the original Tireur is sufficient.
        //$oridoc=$this->doc;
        //$this->doc = $equipe;
        //foreach($regs as $r) {
        //    $this->addFencerRef($r);
        //}
        //$this->doc=$oridoc;

        $this->doc->appendChild($equipe);
    }

    private function addFencerRef($row)
    {
        $tireur = $this->dom->createElement("Tireur");
        $tireur->setAttribute("REF", $row["registration_fencer"]);
        $this->doc->appendChild($tireur);
    }

    private function addFencer($row, $ranking)
    {
        $key = "fid" . $row["registration_fencer"];
        $pos = null;
        $points = null;
        if (isset($ranking[$key])) {
            $pos = $ranking[$key]["pos"];
            $points = $ranking[$key]["points"];
        }
        $tireur = $this->dom->createElement("Tireur");
        $tireur->setAttribute("ID", $row["registration_fencer"]);
        $tireur->setAttribute('Nom', $row["fencer_surname"]);
        $tireur->setAttribute('Prenom', $row["fencer_firstname"]);
        $tireur->setAttribute("DateNaissance", strftime('%d.%m.%Y', strtotime($row["fencer_dob"])));
        $tireur->setAttribute('Nation', $row["country_abbr"]);

        if ($row['fencer_gender'] == 'M') $tireur->setAttribute('Sexe', 'M');
        else $tireur->setAttribute('Sexe', 'F');

        // Lateralite is required, but we do not have it
        $tireur->setAttribute('Lateralite', 'D');

        if (!empty($pos)) $tireur->setAttribute("Classement", $pos);
        if (!empty($pos)) $tireur->setAttribute("Points", $points);

        if (!empty($row["registration_team"])) $tireur->setAttribute("Equipe", $row["country_abbr"] . $row["registration_team"]);

        // skip Arme, used for mixed competitions
        // skip Club
        // skip Dossard... mask number
        // skip Licence
        // skip LicenceNat
        // skip Ligue
        // skip NbMatches
        // skip NbVictoires
        // skip NoDansLaPoule
        // skip PhotoURL, privacy issue
        // skip RangFinal
        // skip RangInitial
        // skip RangPoule
        // skip Score
        // skip Statut
        // skip TD
        // skip TR
        $this->doc->appendChild($tireur);
    }

    private function setEventData() {
        if(!empty($this->event->event_location)) $this->doc->setAttribute("Lieu", $this->event->event_location);
        if(!empty($this->event->event_feed)) $this->doc->setAttribute("LiveURL", $this->event->event_feed);
        $cnt=new \EVFRanking\Models\Country($this->event->event_country,true);
        $this->doc->setAttribute("Organisateur", $cnt->country_name);
        // Sonja Lange requested: 'title should not contain weapon/sex/etc' and suggested a generic short title
        $this->doc->setAttribute("TitreCourt", "EVCH");
        $this->doc->setAttribute("TitreLong", $this->event->event_name);
        if(!empty($this->event->event_web)) $this->doc->setAttribute("URLOrganisateur", $this->event->event_web);
        $this->doc->setAttribute("Championnat", 'EVF');
        $this->doc->setAttribute("Domaine", 'Z'); // EVF is concerned only with the European Zone 
        $this->doc->setAttribute("Federation", $cnt->country_abbr); // country of the organiser
        if(!empty($this->sideevent)) {
            $this->doc->setAttribute("ID", $this->sideevent->getKey());
        }
        else {
            $this->doc->setAttribute("ID", $this->event->getKey());
        }
    }

    private function setDates() {
        if(!empty($this->sideevent)) {
            $this->doc->setAttribute("Date", strftime("%d.%m.%Y",strtotime($this->sideevent->starts)));
        }
        else {
            $this->doc->setAttribute("Date", strftime("%d.%m.%Y",strtotime($this->event->event_open)));
        }
        $this->doc->setAttribute("DateDebut", strftime("%d.%m.%Y",strtotime($this->event->event_open)));
        $this->doc->setAttribute("DateFin", strftime("%d.%m.%Y",strtotime($this->event->event_open) + intval($this->event->event_duration) * 24*60*60));
        $this->doc->setAttribute("DateFichierXML", strftime("%d.%m.%Y %H:%M",time()));
        return $this;
    }

    private function setCategory() {
        switch($this->category->category_abbr) {
        case '1':
        case '2':
        case '3':
        case '4':
        case '5': $this->doc->setAttribute("Categorie", 'V'.$this->category->category_abbr); break;
        case 'T': $this->doc->setAttribute("Categorie", 'V'); break;
        case 'T(G)':$this->doc->setAttribute("Categorie", 'GV'); break;
        }
        return $this;
    }
    private function setYear() {
        $this->doc->setAttribute("Annee", $this->event->event_year);
        return $this;
    }
    private function setWeapon() {
        if(!empty($this->weapon)) {
            switch($this->weapon->weapon_abbr) {
            case 'MF':
            case 'WF': $this->doc->setAttribute("Arme","F"); break;
            case 'ME':
            case 'WE': $this->doc->setAttribute("Arme","E"); break;
            case 'MS':
            case 'WS': $this->doc->setAttribute("Arme","S"); break;
            }

            switch($this->weapon->weapon_abbr) {
            case 'MS':
            case 'ME':
            case 'MF': $this->doc->setAttribute("Sexe","M"); break;
            case 'WE': 
            case 'WS':
            case 'WF': $this->doc->setAttribute("Sexe","F"); break;
            }
        }
        return $this;
    }

}
