<?php

namespace ZnTool\Package\Domain\Repositories\File;

use ZnCore\Collection\Interfaces\Enumerable;
use ZnDomain\Entity\Helpers\CollectionHelper;
use ZnDomain\Entity\Interfaces\EntityIdInterface;
use ZnDomain\Query\Entities\Query;
use ZnDomain\Repository\Interfaces\ReadRepositoryInterface;
use ZnLib\Components\Store\StoreFile;
use ZnTool\Package\Domain\Entities\GroupEntity;

class GroupRepository implements ReadRepositoryInterface
{

    private $fileName;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function findAll(Query $query = null): Enumerable
    {
        $store = new StoreFile($this->fileName);
        $array = $store->load();
        //$collection = $this->forgeEntityCollection($array);
        //return $collection;

        $entityClass = $this->getEntityClass();
        return CollectionHelper::create($entityClass, $array);
    }

    public function count(Query $query = null): int
    {
        $collection = $this->findAll($query);
        return $collection->count();
    }

    public function findOneById($id, Query $query = null): EntityIdInterface
    {
        // TODO: Implement findOneById() method.
    }

    public function getEntityClass(): string
    {
        return GroupEntity::class;
    }

    /*public function _relations()
    {
        return [];
    }*/

}
