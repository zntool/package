<?php

namespace ZnTool\Package\Domain\Repositories\File;

use Illuminate\Support\Collection;
use ZnCore\Base\Libs\FileSystem\Helpers\FindFileHelper;
use ZnCore\Contract\Domain\Interfaces\Entities\EntityIdInterface;
use ZnCore\Domain\Libs\Query;
use ZnCore\Base\Legacy\Yii\Helpers\FileHelper;
use ZnCore\Base\Libs\Store\StoreFile;
use ZnTool\Package\Domain\Entities\GroupEntity;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Interfaces\Repositories\PackageRepositoryInterface;

class PackageRepository implements PackageRepositoryInterface
{

    const VENDOR_DIR = __DIR__ . '/../../../../../../';

    protected $tableName = '';
    private $groupRepostory;

    public function __construct(GroupRepository $groupRepostory)
    {
        $this->groupRepostory = $groupRepostory;
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
        $groupCollection = new Collection;

        foreach ($groups as $group) {
            if(is_dir($vendorDir . '/' . $group)) {
                $groupEntity = new GroupEntity;
                $groupEntity->name = $group;
                $groupCollection->add($groupEntity);
            }
        }

        $collection = new Collection;
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

    public function all(Query $query = null)
    {
        $vendorDir = realpath(self::VENDOR_DIR);
        /** @var GroupEntity[] $groupCollection */
        $groupCollection = $this->groupRepostory->all();
        $collection = new Collection;
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

    private function isComposerPackage(PackageEntity $packageEntity): bool {
        $composerConfigFile = $packageEntity->getDirectory() . '/composer.json';
        $isPackage = is_dir($packageEntity->getDirectory()) && is_file($composerConfigFile);
        return $isPackage;
    }

    public function count(Query $query = null): int
    {
        return count($this->all($query));
    }

    public function oneById($id, Query $query = null): EntityIdInterface
    {
        // TODO: Implement oneById() method.
    }

    /*public function _relations()
    {
        // TODO: Implement relations() method.
    }*/
}
