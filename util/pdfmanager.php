<?php

namespace EVFRanking\util;

class PDFManager
{
    public static function PDFPath($eid, $doRelative = false)
    {
        $path = "/pdfs/event" . intval($eid) . '/';
        if (!$doRelative) {
            $upload_dir = wp_upload_dir();
            $path = $upload_dir['basedir'] . $path;
        }
        return $path;
    }

    public static function CleanPath($eid)
    {
        // see https://stackoverflow.com/questions/3349753/delete-directory-with-files-in-it
        $path = self::PDFPath($eid);
        if (file_exists($path) && is_dir($path)) {
            $it = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getRealPath());
                } else {
                    @unlink($file->getRealPath());
                }
            }
            @rmdir($path);
        }
    }

    public static function SummaryName($event, $type, $model)
    {
        $eid = is_object($event) ? $event->getKey() : intval($event);
        $mid = is_object($model) ? $model->getKey() : intval($model);
        return "summary_" . $eid . "_" . $type . "_" . $mid . "\$";
    }

    public static function AllSummaries($eid)
    {
        return self::FindDocuments("summary_" . $eid . "_");
    }

    public static function MakeHash($accreditations)
    {
        $files = [];
        foreach ($accreditations as $a) {
            $hash = $a->file_hash;
            $fencer = new \EVFRanking\Models\Fencer($a->fencer_id,true);
            $key = $fencer->getFullName() . "~" . $a->getKey();
            $files[$key] = array("file" => $a->getPath(), "hash" => $hash, "fencer" => $fencer, "accreditation" => $a);
        }

        // sort the files by fencer name
        // Sorting makes it easier for the end user to find missing accreditations
        // Also, sorting is vital to make sure the overall hash is created in the
        // same way
        ksort($files, SORT_NATURAL);

        // accumulate all hashes to get at an overall hash
        $acchash = "";
        foreach ($files as $k => $v) {
            $acchash .= $v["hash"];
        }
        $overallhash = hash('sha256', $acchash);
        return array($overallhash, $files);
    }
    
    public static function FindDocuments($name)
    {
        $docModel = new \EVFRanking\models\Document();
        $documents = $docModel->findByName($name);
        return array_map(function ($doc) {
            $doc->configObject = json_decode($doc->config);
            if ($doc->configObject == false) $doc->configObject = (object)array();
            return $doc;
        }, $documents);
    }

    public static function CheckDocument($doc)
    {
        if (isset($doc->configObject) && is_object($doc->configObject) && isset($doc->configObject->accreditations)) {
            $aids = $doc->configObject->accreditations;
            if (is_array($aids)) {
                $accreditations = array_map(fn ($aid) => new \EVFRanking\Models\Accreditation($aid,true), $aids);

                list($hash, $files) = self::MakeHash($accreditations);
                return $doc->hash == $hash;
            }
        }
        return false;
    }
}
