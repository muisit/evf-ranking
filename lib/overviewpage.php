<?php

namespace EVFRanking\Lib;

class OverviewPage extends VirtualPage
{
    private $competitions;
    private $fencers;
    private $rankings;

    private function findEvent($eid)
    {
        $policy = new Policy();
        $event = $policy->feEventToBeEvent($eid);
        if (empty($event) || !$event->exists()) {
            $event = null;
        }
        if (!empty($event)) {
            $start = strtotime($event->event_open) + 24 * 60 * 60 * (intval($event->event_duration) + 1);
            if (time() > $start) {
                $event = null;
            }
        }
        return $event;
    }

    public function create($eid)
    {
        $event = $this->findEvent($eid);

        if (empty($event)) {
            error_log("no such event " . $eid);
            wp_safe_redirect("/calendar");
            exit;
        }

        $post = $this->virtualPage($event);
        return $post;
    }

    public function virtualPage($model)
    {
        $options = $this->createFakePost();

        $title = "Participants Overview for " . $model->event_name . " at " .
            $model->event_location . " on " . date('j F Y', strtotime($model->event_open));
        $options["post_name"] = "Participants";
        $options["post_title"] = $title;
        $options["post_content"] = $this->createContent($model, $title);

        return $this->createPost($options);
    }

    public function createContent($event, $title)
    {
        $this->createCaches($event);
        usort($this->competitions, fn ($a1, $a2) => $a1->sideEvent->title <=> $a2->sideEvent->title);

        $output = $this->renderOverview($this->competitions, $title);
        foreach ($this->competitions as $competition) {
            $output .= $this->renderCompetition($competition);
        }
        return "<div class='overview-page'>" . $output . "</div>";
    }

    private function createCaches($event)
    {
        $this->competitions = array_map(function ($comp) {
            $comp->sideEvent = $comp->getSideEvent();
            $comp->weapon = $comp->getWeapon();
            $comp->category = $comp->getCategory();
            $comp->registrations = $comp->sideEvent->registrations();
            $comp->_abbreviation = $comp->abbreviation();
            return $comp;
        }, $event->competitions(null, true));

        $ranking = new \EVFRanking\Models\Ranking();

        array_walk($this->competitions, function ($comp) use ($ranking) {
            array_walk($comp->registrations, function ($reg) use ($comp) {
                $fencer = new \EVFRanking\Models\Fencer($reg);
                $country = new \EVFRanking\Models\Country($reg);
                $fencer->country = $country;
                $key = '#' . $fencer->getKey();
                $this->fencers[$key] = $fencer;
            });

            $compRanking = $ranking->listResults($comp->weapon->getKey(), $comp->category);
            if (!empty($compRanking)) {
                array_walk($compRanking, function ($entry) use ($comp) {
                    $key = $comp->weapon->weapon_abbr . '#' . $comp->category->category_abbr . '#' . $entry['id'];
                    $this->ranking[$key] = $entry;
                });
            }
        });
    }

    public function renderOverview($competitions, $title)
    {
        $title = $this->encode($title);
        $buckets = array();
        $catsAvailable = array();
        $weaponsAvailable = array();
        foreach ($competitions as $competition) {
            $cat = $competition->category->category_name;
            if (!isset($buckets[$cat])) {
                $buckets[$cat] = array();
            }
            if (!in_array($cat, $catsAvailable)) {
                $catsAvailable[] = $cat;
            }

            $wpn = $competition->weapon->weapon_abbr[1];
            if (!isset($buckets[$cat][$wpn])) {
                $buckets[$cat][$wpn] = array();
            }
            if (!in_array($wpn, $weaponsAvailable)) {
                $weaponsAvailable[] = $wpn;
            }

            $name = $competition->weapon->weapon_gender;
            $participants = count($competition->registrations);
            $anchor = $competition->_abbreviation;
            $buckets[$cat][$wpn][$name] = array("anchor" => $anchor, "count" => $participants);
        }

        $header = '<th></th>';
        foreach ($weaponsAvailable as $weapon) {
            switch ($weapon)
            {
                case 'F': $weapon = 'Foil'; break;
                case 'E': $weapon = 'Epee'; break;
                case 'S': $weapon = 'Sabre'; break;
                default: $weapon = 'Unk'; break;
            }
            $header .= "<th colspan='2' class='textcenter'>" . $weapon . "</th>";
        }

        $rows = '';
        $totals=['MF' => 0, 'FF' => 0, 'ME' => 0, 'FE' => 0, 'MS' => 0, 'FS' => 0];
        foreach ($catsAvailable as $category) {
            $rows .= "<tr><td>" . $category . "</td>";
            foreach ($weaponsAvailable as $weapon) {
                if (isset($buckets[$category]) && isset($buckets[$category][$weapon])) {
                    $competitions = $buckets[$category][$weapon];
                    $compW = isset($competitions['F']) ? $competitions['F'] : null;
                    $compM = isset($competitions['M']) ? $competitions['M'] : null;

                    if (isset($competitions['M'])) {
                        $comp = $competitions['M'];
                        $link = '<a href="#' . $comp['anchor'] . '">Men<br/>' . $comp['count'] . '</a>';
                        $rows .= '<td class="textcenter">' . $link . '</td>';
                        $totals['M' . $weapon] += $comp['count'];
                    }
                    if (isset($competitions['F'])) {
                        $comp = $competitions['F'];
                        $link = '<a href="#' . $comp['anchor'] . '">Women<br/>' . $comp['count'] . '</a>';
                        $rows .= '<td class="textcenter">' . $link . '</td>';
                        $totals['F' . $weapon] += $comp['count'];
                    }
                }
            }
            $rows .= "</tr>";
        }
        $me = isset($totals['ME']) ? $totals['ME'] : 0;
        $fe = isset($totals['FE']) ? $totals['FE'] : 0;
        $mf = isset($totals['MF']) ? $totals['MF'] : 0;
        $ff = isset($totals['FF']) ? $totals['FF'] : 0;
        $ms = isset($totals['MS']) ? $totals['MS'] : 0;
        $fs = isset($totals['FS']) ? $totals['FS'] : 0;
        $gt = $me + $fe + $mf + $ff + $ms + $fs;
        $rows .= <<< DOC
        <tr>
          <td>Total</td>
          <td class='textcenter'>$me</td>
          <td class='textcenter'>$fe</td>
          <td class='textcenter'>$mf</td>
          <td class='textcenter'>$ff</td>
          <td class='textcenter'>$ms</td>
          <td class='textcenter'>$fs</td>
          <td class='textcenter'><b>$gt</b></td>
        </tr>
        DOC;

        $output = <<< DOC
        <a name='top'><h3>Overview</h3></a>
        <table style='width: auto;'>
            <thead>
              <tr>
                $header
              </tr>
            </thead>
            <tbody>
                $rows
            </tbody>
        </table>
        DOC;
        return $output;
    }

    public function renderCompetition($competition)
    {
        $se = $competition->sideEvent;
        $title = $this->encode($se->title);
        $subtitle = count($competition->registrations) . ' participants';
        $anchor = $competition->_abbreviation;
        $output = <<<DOC
        <div class='container competition'>
          <div class='row'>
            <div class='col-8'>
              <a name='$anchor'><h3>$title</h3></a>
              <span>$subtitle</span>
            </div>
            <div class='col-4 textright'><a href='#top'><span class='pi pi-icon pi-caret-up' style='margin-top: 2rem;'> back to top</span></a></div>
          </div>
          <table class='list' style='width: auto;'>
            <thead>
              <tr>
                <th style='width: 30px;'>#</th>
                <th style='min-width: 50px'>Country</th>
                <th>Name</th>
                <th style='width: 50px'>Ranking</th>
              </tr>
            </thead>
            <tbody>
        DOC;

        usort($competition->registrations, function ($a1, $a2) {
            if ($a1->country_name != $a2->country_name) {
                return $a1->country_name <=> $a2->country_name;
            }
            if ($a1->fencer_surname != $a2->fencer_surname) {
                return $a1->fencer_surname <=> $a2->fencer_surname;
            }
            return $a1->fencer_firstname <=> $a2->fencer_firstname;
        });

        $index = 1;
        foreach ($competition->registrations as $reg) {
            error_log(json_encode($reg));
            $output .= $this->renderRegistration($index++, $competition, $reg);
        }

        $output .= <<<DOC
            </tbody>
          </table>
        </div>
        DOC;
        return $output;
    }

    private function renderRegistration($index, $competition, $reg)
    {
        $key = $competition->weapon->weapon_abbr . '#' . $competition->category->category_abbr . '#' . $reg->fencer_id;
        $points = null;
        $position = null;
        if (isset($this->ranking[$key])) {
            $points = $this->ranking[$key]['points'];
            $position = $this->ranking[$key]['pos'];
        }

        $surname = $this->encode(strtoupper($reg->fencer_surname));
        $path = '/' . $this->encode($reg->country_flag_path);
        $name = $this->encode($reg->fencer_firstname);
        $country = $this->encode($reg->country_name);
        $position = intval($position);
        if ($position <= 0) {
            $position = '';
        }
        return <<<DOC
              <tr>
                <td class='textright'>$index</td>
                <td class='textleft'><img style='height: 1.1rem;' src='$path'> $country</td>
                <td class='textleft'>$surname, $name</td>
                <td class='textright'>$position</td>
              </tr>
        DOC;
    }
}
