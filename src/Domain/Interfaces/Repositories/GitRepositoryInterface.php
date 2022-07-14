<?php

namespace ZnTool\Package\Domain\Interfaces\Repositories;

use ZnCore\Collection\Interfaces\Enumerable;
use ZnDomain\Domain\Interfaces\GetEntityClassInterface;
use ZnTool\Package\Domain\Entities\PackageEntity;

interface GitRepositoryInterface extends GetEntityClassInterface
{

    public function isHasChanges(PackageEntity $packageEntity): bool;

    public function allChanged();

    public function allVersion(PackageEntity $packageEntity);

    public function allCommit(PackageEntity $packageEntity): Enumerable;

    public function allTag(PackageEntity $packageEntity): Enumerable;
}
