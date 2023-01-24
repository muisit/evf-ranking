<?php

namespace EVFRanking\Util;

class PDFSummarySplit
{
    public $event;
    public $type;
    public $model;

    const ACCREDITATIONS_PER_DOC = 100;

    public function __construct($event, $type, $model)
    {
        $this->event = $event;
        $this->type = $type;
        $this->model = $model;
    }

    private function createName()
    {
        return PDFManager::summaryName($this->event, $this->type, $this->model);
    }

    private function getPath()
    {
        return "/pdfs/event" . $this->event->getKey() . "/";
    }

    private function findDocuments()
    {
        $name = $this->createName();
        return PDFManager::FindDocuments($name);
    }

    public function accreditations()
    {
        switch ($this->type) {
            case 'Country':
            case 'Event':
            case 'Template':
            case 'Role':
                return $this->model->selectAccreditations($this->event);
                break;
        }
        return array();
    }

    private function splitAccreditations($accreditations)
    {
        if (count($accreditations) < self::ACCREDITATIONS_PER_DOC) {
            return [0 => $accreditations];
        }
        $accreditations = array_map(function ($acc) {
            $fencer = new \EVFRanking\models\Fencer($acc->fencer_id);
            $acc->fencer = $fencer;
            return $acc;
        }, $accreditations);

        usort($accreditations, fn($a1, $a2) => $a1->fencer->getFullName() <=> $a2->fencer->getFullName());
        $accreditations = array_values($accreditations); // is this necessary?

        $pages = ceil(count($accreditations) / self::ACCREDITATIONS_PER_DOC);
        $docsPerPage = ceil(count($accreditations) / $pages);

        $docs = array();
        for ($i = 0; $i < count($accreditations); $i++) {
            $pageIndex = floor($i / $docsPerPage);
            if (!isset($docs[$pageIndex])) {
                $docs[$pageIndex] = array();
            }
            $docs[$pageIndex][] = $accreditations[$i];
        }
        return $docs;
    }

    public function createSummaryDocuments($batches, $oldDocuments)
    {
        $newDocs = array();
        foreach ($batches as $batch) {
            // see if we can find an oldDocument with the same selection of docs
            $found = null;
            foreach ($oldDocuments as $oldDoc) {
                $accreditations = $oldDoc->configObject->accreditations ?? [];
                if (count($accreditations) == count($batch)) {
                    $batchIds = array_map(fn ($a) => $a->getKey(), array_values($batch));
                    $diff = array_diff(array_values($accreditations), $batchIds);
                    if (empty($diff)) {
                        $found = $oldDoc;
                        break;
                    }
                }
            }

            if ($found) {
                $newDocs[] = $found;
                $oldDocuments = array_filter($oldDocuments, fn($doc) => $doc->getKey() !== $found->getKey());
            }
            else {
                $doc = new \EVFRanking\models\Document();
                $doc->configObject = (object)array();
                $doc->configObject->accreditations = array_map(fn ($accr) => $accr->getKey(), array_values($batch));
                $newDocs[] = $doc;
            }
        }
        return [$newDocs, $oldDocuments];
    }

    public function create()
    {
        $accreditations = $this->accreditations();
        if (count($accreditations) == 0) {
            $evflogger->log("PDFSummarySplit::create no accreditations");
            foreach ($documents as $doc) {
                $doc->delete();
            }
            return;
        }

        $batches = $this->splitAccreditations($accreditations);
        list($documents, $oldDocuments) = $this->createSummaryDocuments($batches, $this->findDocuments());

        foreach ($oldDocuments as $doc) {
            $doc->delete();
        }

        $name = $this->createName();
        foreach ($documents as $doc) {
            $doc->name = $name;
            $doc->configObject->event = $this->event->getKey();
            $doc->configObject->type = $this->type;
            $doc->configObject->model = $this->model->getKey();
            $doc->config = json_encode($doc->configObject);
            $doc->save();

            $doc->path = $this->getPath() . $doc->name . "_" . $doc->getKey() . ".pdf";
            $doc->save();
        }
        return $documents;
    }
}
