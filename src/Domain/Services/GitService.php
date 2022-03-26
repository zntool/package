<?php

namespace ZnTool\Package\Domain\Services;

use Illuminate\Support\Arr;
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
        $this->setRepository($repository);
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
        $tags = $this->getRepository()->allVersion($packageEntity);
        if ($tags) {
            return $tags[0];
        }
        return null;
    }

    public function isNeedRelease(PackageEntity $packageEntity): bool
    {
        $commitCollection = $this->getRepository()->allCommit($packageEntity);
        $tagCollection = $this->getRepository()->allTag($packageEntity);

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

    public function getRootBranch(PackageEntity $packageEntity)
    {
        $branches = $this->branches($packageEntity);
        $branches = array_intersect($branches, ['master', 'main']);
        return Arr::first($branches);
    }

    public function checkout(PackageEntity $packageEntity, string $branch)
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->checkout($branch);
        return $result;
    }

    public function fetch(PackageEntity $packageEntity, string $branch = 'master')
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->fetch('' . $branch);
        return $result;
    }

    public function isHasBranch(PackageEntity $packageEntity, string $branch): bool
    {
        $git = new GitShell($packageEntity->getDirectory());
        $branches = $git->getBranches();
        foreach ($branches as &$branchItem) {
            $branchItem = str_replace('remotes/origin/', '', $branchItem);
        }
        $branches = array_unique($branches);
        return in_array($branch, $branches);
    }

    public function status(PackageEntity $packageEntity): array
    {
        $git = new GitShell($packageEntity->getDirectory());
        $currentBranch = $this->branch($packageEntity);
        $status = $git->status();
        $info = [
            'branch' => null,
            'flags' => [
                'hasUntracked' => false,
                'hasModified' => false,
                'needPush' => false,
                'needCommit' => false,
            ],
            'push' => [

            ],
        ];

        if($matches = $git->matchText($status, 'On branch ([\S]+)')) {
            $info['branch'] = $matches[0][1];
        }

        //'Your branch is ahead of \'origin/(.+)\' by (\d+) commit'

        if($matches = $git->matchText($status, 'Your branch is ahead of \'origin\/(.+)\' by (\d+) commit')) {
            $info['push']['isUpToDateWith'] = $matches[0][1];
            $info['push']['aheadCommitCount'] = $matches[0][2];
            $info['flags']['needPush'] = true;
        } elseif($matches = $git->matchText($status, 'Your branch is up to date with \'origin\/(.+)\'\.')) {
            $info['push']['isUpToDateWith'] = $matches[0][1];
            $info['flags']['needPush'] = $matches[0][1] != $info['branch'];
        }

        if($matches = $git->matchText($status, 'Untracked files:')) {
            $info['flags']['hasUntracked'] = true;
        }

        if($matches = $git->matchText($status, 'Changes not staged for commit:')) {
            $matches2 = $git->matchTextAll($status, '(modified|deleted):\s+(.+)');
            $hasModified  = false;
            foreach ($matches2 as $item) {
                $action = $item[1][0];
                $file = $item[2][0];
                $info[$action][] = $file;
                $hasModified  = true;
            }
            $info['flags']['hasModified'] = $hasModified;
        }

        if($git->searchText2($status, 'nothing to commit, working tree clean')) {
            $info['flags']['needCommit'] = false;
        } else {
            $info['flags']['needCommit'] = $info['flags']['hasUntracked'] || $info['flags']['hasModified'];
        }

//        $info['flags']['needCommit'] = $info['flags']['hasUntracked'] || $info['flags']['hasModified'];

        return $info;
    }

    public function createBranch(PackageEntity $packageEntity, string $branch)
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->createBranch($branch);
        return $result;
    }

    public function removeBranch(PackageEntity $packageEntity, string $branch)
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->removeBranch($branch);
        return $result;
    }

    public function branch(PackageEntity $packageEntity, string $branch = 'master')
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->getCurrentBranchName();
        return $result;
    }

    public function branches(PackageEntity $packageEntity)
    {
        $git = new GitShell($packageEntity->getDirectory());
        $result = $git->getBranches();
        return $result;
    }

    public function tags(PackageEntity $packageEntity)
    {
        $tagCollection = $this->getRepository()->allTag($packageEntity);
        return $tagCollection;
//        $git = new GitShell($packageEntity->getDirectory());
//        $result = $git->getTags();
//        return $result;
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

    public function push(PackageEntity $packageEntity, $remote = null)
    {
        $git = new GitShell($packageEntity->getDirectory());

        if(!$remote) {
            $remote = $this->branch($packageEntity);
        }
        $result = $git->pushWithInfo("--set-upstream origin $remote");

        //$result = $git->pushWithInfo($remote);
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
        $isHas = $this->getRepository()->isHasChanges($packageEntity);
//        dd($packageEntity->getId());
        if($packageEntity->getName() == 'messenger') {
            //dd($isHas);
        }

        return $isHas;
    }

    public function allChanged()
    {
        return $this->getRepository()->allChanged();
    }

}
