<?php

namespace ZnTool\Package\Domain\Repositories\File;

use ZnCore\Base\Libs\Entity\Helpers\EntityHelper;
use ZnCore\Contract\Domain\Interfaces\Entities\EntityIdInterface;
use ZnCore\Domain\Interfaces\Repository\ReadRepositoryInterface;
use ZnCore\Domain\Libs\Query;
use ZnCore\Base\Libs\Store\StoreFile;
use ZnTool\Package\Domain\Entities\GroupEntity;

class GroupRepository implements ReadRepositoryInterface
{

    private $fileName;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function all(Query $query = null)
    {
        $store = new StoreFile($this->fileName);
        $array = $store->load();
        //$collection = $this->forgeEntityCollection($array);
        //return $collection;

        $entityClass = $this->getEntityClass();
        return EntityHelper::createEntityCollection($entityClass, $array);
    }

    public function count(Query $query = null): int
    {
        $collection = $this->all($query);
        return $collection->count();
    }

    public function oneById($id, Query $query = null): EntityIdInterface
    {
        // TODO: Implement oneById() method.
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
