<?php

namespace ZnTool\Package\Domain\Repositories\File;

use ZnCore\FileSystem\Helpers\FindFileHelper;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Collection\Libs\Collection;
use ZnCore\Entity\Interfaces\EntityIdInterface;
use ZnCore\Domain\Query\Entities\Query;
use ZnTool\Package\Domain\Entities\GroupEntity;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Interfaces\Repositories\PackageRepositoryInterface;

class PackageRepository implements PackageRepositoryInterface
{

    const VENDOR_DIR = __DIR__ . '/../../../../../../';

    protected $tableName = '';
    private $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function getEntityClass(): string
    {
        return PackageEntity::class;
    }

    public function allWithThirdParty(Query $query = null)
    {
        $vendorDir = realpath(self::VENDOR_DIR);

        $groups = FindFileHelper::scanDir($vendorDir);
        /** @var GroupEntity[] $groupCollection */
        $groupCollection = new Collection();

        foreach ($groups as $group) {
            if (is_dir($vendorDir . '/' . $group)) {
                $groupEntity = new GroupEntity;
                $groupEntity->name = $group;
                $groupCollection->add($groupEntity);
            }
        }

        $collection = new Collection();
        foreach ($groupCollection as $groupEntity) {
            $dir = $vendorDir . DIRECTORY_SEPARATOR . $groupEntity->name;

            $names = FindFileHelper::scanDir($dir);

            foreach ($names as $name) {
                $packageEntity = new PackageEntity;
                $packageEntity->setName($name);
                $packageEntity->setGroup($groupEntity);

                if ($this->isComposerPackage($packageEntity)) {
                    $collection->add($packageEntity);
                }

            }

        }

        return $collection;
    }

    public function findAll(Query $query = null): Enumerable
    {
        $vendorDir = realpath(self::VENDOR_DIR);
        /** @var GroupEntity[] $groupCollection */
        $groupCollection = $this->groupRepository->findAll();
        $collection = new Collection();
        foreach ($groupCollection as $groupEntity) {
            $dir = $vendorDir . DIRECTORY_SEPARATOR . $groupEntity->name;
            $names = FindFileHelper::scanDir($dir);
            foreach ($names as $name) {
                $packageEntity = new PackageEntity;
                $packageEntity->setName($name);
                $packageEntity->setGroup($groupEntity);
                if ($this->isComposerPackage($packageEntity)) {
                    $collection->add($packageEntity);
                }
            }
        }
        return $collection;
    }

    private function isComposerPackage(PackageEntity $packageEntity): bool
    {
        $composerConfigFile = $packageEntity->getDirectory() . '/composer.json';
        $isPackage = is_dir($packageEntity->getDirectory()) && is_file($composerConfigFile);
        return $isPackage;
    }

    public function count(Query $query = null): int
    {
        return count($this->findAll($query));
    }

    public function findOneById($id, Query $query = null): EntityIdInterface
    {
        // TODO: Implement findOneById() method.
    }

    /*public function findOneById($id, Query $query = null): EntityIdInterface
    {
        // TODO: Implement findOneById() method.
    }*/

    /*public function _relations()
    {
        // TODO: Implement relations() method.
    }*/
}
