<?php

namespace ZnTool\Package\Domain\Services;

use Illuminate\Support\Collection;
use ZnCore\Base\Legacy\Yii\Helpers\ArrayHelper;
use ZnCore\Domain\Base\BaseService;
use ZnTool\Package\Domain\Entities\CommitEntity;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Entities\TagEntity;
use ZnTool\Package\Domain\Interfaces\Repositories\GitRepositoryInterface;
use ZnTool\Package\Domain\Interfaces\Services\GitServiceInterface;
use ZnTool\Package\Domain\Interfaces\Services\PackageServiceInterface;
use ZnTool\Package\Domain\Libs\GitShell;

class GitService extends BaseService implements GitServiceInterface
{

    private $packageService;

    public function __construct(GitRepositoryInterface $repository, PackageServiceInterface $packageService)
    {
        $this->repository = $repository;
        $this->packageService = $packageService;
    }

    public function lastVersionCollection(): array
    {
        $collection = $this->packageService->all();
        /** @var PackageEntity[] | Collection $collection */
        $versionArray = [];
        foreach ($collection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $lastVersion = $this->lastVersion($packageEntity);
            $versionArray[$packageId] = $lastVersion ?? 'dev-master';
        }
        return $versionArray;
    }

    public function lastVersion(PackageEntity $packageEntity)
    {
        $tags = $this->repository->allVersion($packageEntity);
        if ($tags) {
            return $tags[0];
        }
        return null;
    }

    public function isNeedRelease(PackageEntity $packageEntity): bool
    {
        $commitCollection = $this->repository->allCommit($packageEntity);
        $tagCollection = $this->repository->allTag($packageEntity);

        if ($commitCollection->count() == 0) {
            return true;
        }
        if (count($tagCollection->all()) == 0) {
            return true;
        }

        $commitShaMap = $commitCollection->map(function (CommitEntity $commitEntity) {
            return $commitEntity->getSha();
        });
        $commitShaMap = array_flip($commitShaMap->toArray());

        $tagShaMap = $tagCollection->map(function (TagEntity $tagEntity) {
            return $tagEntity->getSha();
        });
        $tagShaMap = array_flip($tagShaMap->toArray());

        $commitTagMap = [];
        foreach ($commitShaMap as $commitSha => $commitOrder) {
            $tagOrder = ArrayHelper::getValue($tagShaMap, $commitSha);
            $commitTagMap[$commitOrder] = $tagOrder;
        }

        $isNeedRelease = $commitTagMap[0] === null;

        return $isNeedRelease;
    }

    public function branch(PackageEntity $packageEntity, string $branch = 'master')
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->getCurrentBranchName();
        return $result;
    }

    public function pullPackage(PackageEntity $packageEntity)
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->pullWithInfo();
        if ($result == 'Already up-to-date.') {
            return false;
        } else {
            return $result;
        }
    }

    public function pushPackage(PackageEntity $packageEntity)
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->pushWithInfo();
        if ($result == 'Already up-to-date.') {
            return false;
        } else {
            return $result;
        }
    }

    public function isHasChanges(PackageEntity $packageEntity): bool
    {
        $isHas = $this->repository->isHasChanges($packageEntity);
//        dd($packageEntity->getId());
        if($packageEntity->getName() == 'messenger') {
            //dd($isHas);
        }

        return $isHas;
    }

    public function allChanged()
    {
        return $this->repository->allChanged();
    }

}
