<?php

namespace ZnTool\Package\Domain\Repositories\File;

use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Collection\Libs\Collection;
use ZnCore\Entity\Helpers\CollectionHelper;
use ZnTool\Package\Domain\Entities\CommitEntity;
use ZnTool\Package\Domain\Entities\GitEntity;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Entities\TagEntity;
use ZnTool\Package\Domain\Interfaces\Repositories\GitRepositoryInterface;
use ZnTool\Package\Domain\Libs\GitShell;

class GitRepository implements GitRepositoryInterface
{

    const VENDOR_DIR = __DIR__ . '/../../../../../..';

    protected $tableName = '';
    private $packageRepository;

    public function __construct(PackageRepository $packageRepository)
    {
        $this->packageRepository = $packageRepository;
    }

    public function getEntityClass(): string
    {
        return GitEntity::class;
    }

    public function isHasChanges(PackageEntity $packageEntity): bool
    {
        $vendorDir = realpath(self::VENDOR_DIR);
        $dir = $vendorDir . DIRECTORY_SEPARATOR . $packageEntity->getId();
        $shell = new GitShell($dir);
        $hasChanges = $shell->hasChanges();
        return $hasChanges;
    }

    public function allChanged()
    {
        /** @var PackageEntity[] $packageCollection */
        $packageCollection = $this->packageRepository->findAll();
        $changedCollection = new Collection();
        foreach ($packageCollection as $packageEntity) {
            $hasChanges = $this->isHasChanges($packageEntity);
            if ($hasChanges) {
                $changedCollection->add($packageEntity);
            }
        }
        return $changedCollection;
    }

    public function allVersion(PackageEntity $packageEntity)
    {
        $tagCollection = $this->allTag($packageEntity);
        if ($tagCollection->count()) {
            $tags = $tagCollection->map(function (TagEntity $tagEntity) {
                preg_match('/v?(\d+\.\d+\.\d+)/i', $tagEntity->getName(), $matches);
                return $matches[1] ?? null;
            })->toArray();
            usort($tags, function ($first, $second) {
                if (version_compare($first, $second, '=')) {
                    return 0;
                }
                return version_compare($first, $second, '<') ? 1 : -1;
            });
            $tags = array_values($tags);
            return $tags;
        }
    }

    public function allCommit(PackageEntity $packageEntity): Enumerable
    {
        $git = new GitShell($packageEntity->getDirectory());
        $commits = $git->getCommits();
        $fieldsOnly = [
            "sha",
            "merge",
            "author",
            "date",
            "message",
        ];
        $commitCollection = CollectionHelper::create(CommitEntity::class, $commits, $fieldsOnly);
        return $commitCollection;
    }

    public function allTag(PackageEntity $packageEntity): Enumerable
    {
        $git = new GitShell($packageEntity->getDirectory());
        $tags = $git->getTagsSha();
        $tagCollection = CollectionHelper::create(TagEntity::class, $tags);
        return $tagCollection;
    }
}
