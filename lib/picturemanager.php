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
        global $evflogger;
        $evflogger->log("picturemanager: display");
        if ($fencer->fencer_picture != 'N') {
            $filename = $fencer->getPath();
            if (file_exists($filename)) {
                header('Content-Disposition: inline;');
                header('Content-Type: image/jpeg');
                header('Expires: ' . (time() + 2*24*60*60));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filename));
                readfile($filename);
                exit();
            }
            else {
                $evflogger->log("picture was not found");
            }
        }
        else {
            $evflogger->log("fencer has no picture stored");
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

    public function import($fencer)
    {
        global $evflogger;
        $retval = array();
        if ($fencer->exists()) {
            $this->createUploadDir("accreditations");
            $evflogger->log("FILES is " . json_encode($_FILES));
            foreach ($_FILES as $username => $content) {
                $loc = $content["tmp_name"];
                if (!empty($loc) && file_exists($loc)) {
                    $type = mime_content_type($loc);

                    $loc = $this->convertToAccreditation($loc, $type);
                    if (!empty($loc) && file_exists($loc) && is_readable($loc)) {
                        $filename = $fencer->getPath();
                        @move_uploaded_file($loc, $filename);
                        if (file_exists($filename)) {
                            $fencer->fencer_picture = 'Y'; // new picture
                            $fencer->save();
                        }
                    }
                    else {
                        $retval["error"] = "Unable to convert image, please upload a valid photo";
                    }
                }
                else {
                    $retval["error"] = "Unable to convert image, please upload a valid file";
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

    private function convertToAccreditation($filename, $type)
    {
        global $evflogger;
        $evflogger->log('converting accreditation picture for ' . json_encode([$filename, $type]));
        $imageTmp = null;
        if (preg_match('/jpg|jpeg/i', $type)) {
            $imageTmp = $this->rotateImage(imagecreatefromjpeg($filename), $filename);
        }
        else if (preg_match('/png/i', $type)) {
            $imageTmp = imagecreatefrompng($filename);
        }
        else if (preg_match('/gif/i', $type)) {
            $imageTmp = imagecreatefromgif($filename);
        }
        else {
            $evflogger->log("mime type " . $type . " not recognised");
            return null;
        }


        if (!empty($imageTmp)) {
            if (!imageistruecolor($imageTmp)) {
                $evflogger->log("converting image to true color");
                $bg = imagecreatetruecolor(imagesx($imageTmp), imagesy($imageTmp));
                imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                imagealphablending($bg, true);
                imagecopy($bg, $imageTmp, 0, 0, 0, 0, imagesx($imageTmp), imagesy($imageTmp));
                imagedestroy($imageTmp);
                $imageTmp = $bg;
            }
        }

        if (!empty($imageTmp)) {
            

            $wh = $this->determineCropDimensions($imageTmp);

            if ($wh !== null) {
                $wh = $this->scaleDimensions($wh);
                $imageTmp = $this->scaleAndCropImage($imageTmp, $wh);
            }
        }

        if (!empty($imageTmp)) {
            $evflogger->log("converting image to JPEG, 90% quality");
            // convert to JPEG
            imagejpeg($imageTmp, $filename, 90);
            imagedestroy($imageTmp);
            return $filename;
        }

        $evflogger->log("returning null for uploaded image");
        return null;
    }

    private function rotateImage($image, $filename)
    {
        $exif = exif_read_data($filename);
            
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
                
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
            }
        }
        return $image;
    }

    private function determineCropDimensions($imageTmp)
    {
        global $evflogger;
        $ratio = 413.0 / 531.0;
        $w = imagesx($imageTmp);
        $h = imagesy($imageTmp);
        if ($h <= 0 || $w <= 0) {
            $evflogger->log("images has incorrect dimensions " . json_encode([$ratio, $w, $h]));
            return null;
        }

        $ourratio = $w / $h;
        if ($ratio > $ourratio) {
            // image is too high
            $requiredHeight = intval($w / $ratio);
            $offsetY = ($h - $requiredHeight)/2;
            $evflogger->log("image is too high, cropping the height from $h to $requiredHeight");
            return [0, $offsetY, $w, $requiredHeight];
        }
        else if ($ratio < $ourratio) {
            // image is too wide
            $requiredWidth = intval($h * $ratio);
            $offsetX = ($w - $requiredWidth) / 2;
            return [$offsetX, 0, $requiredWidth, $h];
        }
        return [0,0,$w,$h];
    }

    private function scaleDimensions($wh)
    {
        $destX = 413;
        $destY = 531;
        $ratio = $destX / $destY;
        $ourratio = $wh[2] / $wh[3];
        if($ratio > $ourratio) {
            $destY = intval($destX / $ratio);
        }
        else {
            $destX = intval($destY * $ratio);
        }
        return [$wh[0], $wh[1], $wh[2], $wh[3], $destX, $destY];
    }

    private function scaleAndCropImage($imageTmp, $wh)
    {
        global $evflogger;
        if (!empty($imageTmp)) {
            $offX = $wh[0];
            $offY = $wh[1];
            $w = ceil($wh[2]);
            $h = ceil($wh[3]);
            $destX = $wh[4];
            $destY = $wh[5];
            $imageTmp2 = imagecreatetruecolor($destX, $destY);
            $evflogger->log("resampling from " . imagesx($imageTmp) . " by " . imagesy($imageTmp) . " to image of size $w by $h from $offX, $offY");
            if (imagecopyresampled($imageTmp2, $imageTmp, 0, 0, $offX, $offY, $destX, $destY, $w, $h)) {
                $imageTmp = $imageTmp2;
            }
            else {
                $imageTmp = null;
                $evflogger->log("copy-resampled fails");
            }
        }

        return $imageTmp;
    }
}
