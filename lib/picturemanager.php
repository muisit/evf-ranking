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


namespace EVFRanking\Lib;

class PictureManager extends BaseLib {

    public function display($fencer) {
        if($fencer->fencer_picture != 'N') {
            $filename = $fencer->getPath();
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

    private function extToMime($ext) {
        switch(strtolower($ext)) {
        default:
        case 'jpg': return "image/jpeg";
        case 'png': return "image/png";
        case 'gif': return "image/gif";
        }
    }
    private function mimeToExt($ext) {
        switch(strtolower($ext)) {
        case 'image/jpg':
        case 'image/jpeg': return "jpg";
        case 'image/png': return "png";
        case 'image/gif': return "gif";
        }
        return null;
    }

    public function template($template, $fileid) {
        $templatecontent = json_decode($template->content,true);
        if(isset($templatecontent["pictures"])) {
            foreach($templatecontent["pictures"] as $img) {
                if($img["file_id"] == $fileid) {
                    $upload_dir = wp_upload_dir();
                    $dirname = $upload_dir['basedir'] . '/templates';

                    $filename = $dirname ."/img_". $template->getKey() ."_". $img["file_id"] .".". $img["file_ext"];

                    if (file_exists($filename)) {
                        header('Content-Disposition: inline;');
                        header('Content-Type: '.$this->extToMime($img["file_ext"]));
                        header('Expires: ' . (time() + 2 * 24 * 60 * 60));
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($filename));
                        readfile($filename);
                        exit();
                    }

                }
            }
        }
        die(403);
    }

    public function importTemplate($template) {
        $retval = array();
        if ($template->exists()) {
            // basename of the model upload dir, requires common knowledge of the wp_base_upload_dir in
            // both the picture manager and the accreditation-template model.... not so pretty
            $this->createUploadDir(basename($template->getDir("pictures")));

            foreach ($_FILES as $username => $content) {
                $type = $content["type"];
                $loc = $content["tmp_name"];
                $fname = $content["name"];
                $ext = $this->mimeToExt($type);

                if (!empty($loc) && !empty($ext) && file_exists($loc) && is_readable($loc)) {
                    $id = uniqid();
                    $filename = $template->getPath("pictures", $id, $ext);
                    @move_uploaded_file($loc,  $filename);
                    if (file_exists($filename)) {
                        $size=getimagesize($filename);
                        $tmpl = array("width" => $size[0], "height"=>$size[1], "file_ext" => $ext, "file_id" => $id, "file_name" => basename($fname));
                        $template->addPicture($tmpl);
                        $template->save();
                        $retval["picture"] = $tmpl;
                    }
                } else {
                    $retval["error"] = "Unable to convert image, please upload a valid photo";
                }
            }
        }
        return $retval;
    }

    public function import($fencer) {
        $retval=array();
        if($fencer->exists()) {
            $this->createUploadDir("accreditations");

            foreach($_FILES as $username => $content) {
                $type=$content["type"];
                $loc=$content["tmp_name"];

                $loc = $this->convertToAccreditation($loc,$type);
                if(!empty($loc) && file_exists($loc) && is_readable($loc)) {
                    $filename = $fencer->getPath();
                    @move_uploaded_file( $loc,  $filename);
                    if(file_exists($filename)) {
                        $fencer->fencer_picture='Y'; // new picture
                        $fencer->save();
                    }
                }
                else {
                    $retval["error"]="Unable to convert image, please upload a valid photo";
                }
            }
        }
        return $retval;
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
            if(!imageistruecolor($imageTmp)) {
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
            //error_log("width $w, height $h, ratio $ourratio vs $ratio");

            if($ratio > $ourratio) {
                // image is too high
                $requiredHeight = intval($w / $ratio);
                $offsetY = ($h - $requiredHeight)/2;
                $imageTmp = imagecrop($imageTmp,array(
                    'x' => 0,
                    'y' => $offsetY,
                    'width' => $w,
                    'height' => $requiredHeight
                ));
            }
            else if($ratio < $ourratio) {
                // image is too wide
                $requiredWidth = intval($h * $ratio);
                $offsetX = ($w - $requiredWidth) / 2;
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
            $imageTmp = imagescale($imageTmp, 413,-1, IMG_BICUBIC);
        }

        if(!empty($imageTmp)) {
            // convert to JPEG
            imagejpeg($imageTmp, $filename, 90);
            imagedestroy($imageTmp);
            return $filename;
        }
        return null;
    }
}