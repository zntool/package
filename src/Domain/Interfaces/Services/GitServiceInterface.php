<?php

namespace ZnTool\Package\Domain\Interfaces\Services;

use Illuminate\Support\Collection;
use ZnTool\Package\Domain\Entities\PackageEntity;

interface GitServiceInterface
{

    public function lastVersionCollection(): array;

    public function lastVersion(PackageEntity $packageEntity);

    public function pullPackage(PackageEntity $packageEntity);

    public function branch(PackageEntity $packageEntity, string $branch = 'master');

    public function branches(PackageEntity $packageEntity);

    public function pushPackage(PackageEntity $packageEntity);

    public function isNeedRelease(PackageEntity $packageEntity): bool;

    public function isHasChanges(PackageEntity $packageEntity): bool;

    public function allChanged();

}
