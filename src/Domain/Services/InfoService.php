<?php

namespace ZnTool\Package\Domain\Services;

use ZnTool\Package\Domain\Repositories\File\InfoRepository;

class InfoService
{

    public $ignore;
    private $infoRepository;

    public function __construct(InfoRepository $infoRepository)
    {
        $this->infoRepository = $infoRepository;
    }

    public function allForRelease($query = null)
    {
        $collection = $this->infoRepository->allWithTagAndCommit($query);
        $newCollection = [];
        foreach ($collection as $entity) {
            if ($entity->need_release) {
                $newCollection[] = $entity;
            }
        }
        return $newCollection;
    }

    /*public function allChanged($query = null) {
        $query = Query::forge($query);
        //$query->with('has_changes');
        return $this->infoRepository->allChanged($query);
    }*/

    public function allVersion($query = null)
    {
        return $this->infoRepository->allWithTag($query);
    }

    public function shortNamesByOwner($owner)
    {
        return $this->infoRepository->shortNamesByOwner($owner);
    }

    public function usesById($id)
    {
        return $this->infoRepository->usesById($id);
    }

    /*public function allWithGuide() {
        $query = Query::forge();
        $query->where('has_guide', true);
        return $this->infoRepository->all($query);
    }

    public function allWithHasTest() {
        $query = Query::forge();
        $query->where('has_test', true);
        return $this->infoRepository->all($query);
    }*/

}
