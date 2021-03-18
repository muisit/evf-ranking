<?php

/**
 * EVF-Ranking PictureManager
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


namespace EVFRanking;

require_once(__DIR__.'/baselib.php');
class PictureManager extends BaseLib {

    public function display($fencer) {
        if($fencer->fencer_picture != 'N') {
            $upload_dir = wp_upload_dir();
            $dirname = $upload_dir['basedir'] . '/accreditations';

            $filename = $dirname."/fencer_".$fencer->getKey().".jpg";

            if(file_exists($filename)) {
                header('Content-Disposition: inline;');
                header('Content-Type: image/jpeg');
                header('Expires: ' . (time() + 2*24*60*60));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filename));
                readfile($filename);
                exit();
            }
        }
        die(403);
    }

    public function import($fid) {
        $fencer=$this->loadModel('Fencer');
        $fencer=$fencer->get($fid);

        $retval=array();
        if(!empty($fencer)) {
            error_log("fencer found, storing and replacing data");
            $this->createUploadDir("accreditations");

            foreach($_FILES as $username => $content) {
                $size=$content["size"];
                $type=$content["type"];
                $fname=$content["name"];
                $loc=$content["tmp_name"];
                $error=$content["error"];
                error_log("file $username was called $fname and is located at $loc. It has size $size and type $type. Error: $error");

                $loc = $this->convertToAccreditation($loc,$type);
                if(!empty($loc) && file_exists($loc) && is_readable($loc)) {
                    $upload_dir = wp_upload_dir();
                    $filename = $upload_dir['basedir'] . "/accreditations/fencer_" . $fencer->getKey() . ".jpg";

                    @move_uploaded_file( $loc,  $filename);
                    if(file_exists($filename)) {
                        $fencer->fencer_picture='Y'; // new picture
                        $fencer->save();
                        $retval['model']=$fencer->export();
                    }
                }
                else {
                    $retval["error"]="Unable to convert image, please upload a valid photo";
                }
            }
        }

        if (!isset($retval["error"])) {
            wp_send_json_success($retval);
        } else {
            wp_send_json_error($retval);
        }
        wp_die();
    }

    private function createUploadDir($dirname) {
        $upload_dir = wp_upload_dir();
        $dirname = $upload_dir['basedir'] . '/' . $dirname;
        if (!file_exists($dirname)) wp_mkdir_p($dirname);

        // make sure there is a .htaccess file inside to prevent file access
        $filename = $dirname.'/.htaccess';
        if(!file_exists($filename)) {
            $contents = <<< DEMARK
order deny,allow
deny from all
DEMARK;
            @file_put_contents($filename, $contents); 
        }
    }

    private function convertToAccreditation($filename, $type) {
        error_log("converting image at $filename to accreditation photo");
        $imageTmp=null;
        if (preg_match('/jpg|jpeg/i', $type)) {
            $imageTmp = imagecreatefromjpeg($filename);
        }
        else if (preg_match('/png/i', $type)) {
            $imageTmp = imagecreatefrompng($filename);
        }
        else if (preg_match('/gif/i', $type)) {
            $imageTmp = imagecreatefromgif($filename);
        }
        else {
            return null;
        }


        if(!empty($imageTmp)) {
            error_log("succesfully loaded image");
            if(!imageistruecolor($imageTmp)) {
                error_log("converting to true color");
                $bg = imagecreatetruecolor(imagesx($imageTmp), imagesy($imageTmp));
                imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                imagealphablending($bg, TRUE);
                imagecopy($bg, $imageTmp, 0, 0, 0, 0, imagesx($imageTmp), imagesy($imageTmp));
                imagedestroy($imageTmp);
                $imageTmp = $bg;
            }
        }

        if(!empty($imageTmp)) {
            // crop and scale the image to 413 by 531
            $ratio = 413.0/531.0;
            $w = imagesx($imageTmp);
            $h = imagesy($imageTmp);
            if($h <=0 || $w <=0) return null;

            $ourratio = floatval($w) / $h;
            error_log("width $w, height $h, ratio $ourratio vs $ratio");

            if($ratio > $ourratio) {
                // image is too high
                $requiredHeight = intval($w / $ratio);
                $offsetY = ($h - $requiredHeight)/2;
                error_log("cropping height to using $w/$ratio = $requiredHeight from offset $offsetY");
                $imageTmp = imagecrop($imageTmp,array(
                    'x' => 0,
                    'y' => $offsetY,
                    'width' => $w,
                    'height' => $requiredHeight
                ));
            }
            else if($ratio < $ourratio) {
                // image is too wide
                error_log("cropping width");
                $requiredWidth = intval($h * $ratio);
                $offsetX = ($w - $requiredWidth) / 2;
                error_log("cropping width using $h*$ratio = $requiredWidth from offset $offsetX");
                $imageTmp = imagecrop($imageTmp, array(
                    'x' => $offsetX,
                    'y' => 0,
                    'width' => $requiredWidth,
                    'height' => $h
                ));
            }
        }

        if(!empty($imageTmp)) {
            // scale the image to 413x531
            error_log("scaling image to 413x531");
            $imageTmp = imagescale($imageTmp, 413,-1, IMG_BICUBIC);
        }

        if(!empty($imageTmp)) {
            // convert to JPEG
            error_log("storing image");
            imagejpeg($imageTmp, $filename, 90);
            imagedestroy($imageTmp);
            return $filename;
        }
        return null;
    }
}