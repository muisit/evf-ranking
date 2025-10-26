<?php

/**
 * EVF-Ranking API interface
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

class API extends BaseLib {
    public function createNonceText()
    {
        $user = wp_get_current_user();
        if (!empty($user)) {
            return "evfranking" . $user->ID;
        }
        return "evfranking";
    }

    public function resolve()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $retval = ["error" => "unknown"];
        if (empty($data) || !isset($data['nonce']) || !isset($data['path'])) {
            if (empty($data)) {
                // see if we have the proper GET requests for a download
                if (!empty($this->fromGet("action")) && !empty($this->fromGet("nonce"))) {
                    $retval = $this->doGet($this->fromGet("action"), $this->fromGet("nonce"));
                }
            }

            error_log('die because no path nor nonce');
            die(403);
        }

        if (!isset($retval["error"])) {
            wp_send_json_success($retval);
        } else {
            wp_send_json_error($retval);
        }
        wp_die();
    }

    protected function checkNonce($nonce)
    {
        $result = wp_verify_nonce($nonce, $this->createNonceText());
        if (!($result === 1 || $result === 2)) {
            error_log('die because nonce does not match');
            die(403);
        }
    }

    private function fromGet($var, $def = null)
    {
        if (isset($_GET[$var])) {
            return $_GET[$var];
        }
        return $def;
    }
    private function fromPost($var, $def = null)
    {
        if (isset($_POST[$var])) {
            return $_POST[$var];
        }
        return $def;
    }

    private function doGet($action, $nonce)
    {
        global $evflogger;
        $this->checkNonce($nonce);
        if ($action == "evfranking") {
            $download = $this->fromGet("download");
            if (!empty($download) && $download == 'ranking') {
                (new ExportManager())->download('ranking');
            }
        }
        die(403);
    }
}