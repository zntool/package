<?php

namespace ZnTool\Package\Domain\Repositories\File;

use Illuminate\Support\Collection;
use ZnCore\Base\Legacy\Yii\Helpers\FileHelper;
use php7extension\yii\web\NotFoundHttpException;
use php7rails\domain\interfaces\repositories\ReadInterface;
use php7rails\domain\repositories\BaseRepository;
use php7tool\vendor\domain\entities\RepoEntity;
use php7tool\vendor\domain\entities\RequiredEntity;
use php7tool\vendor\domain\filters\IsIgnoreFilter;
use php7tool\vendor\domain\filters\IsPackageFilter;
use php7tool\vendor\domain\helpers\RepositoryHelper;
use php7tool\vendor\domain\helpers\UseHelper;
use ZnCore\Domain\Libs\Query;
use ZnCore\Domain\Helpers\EntityHelper;
use ZnCore\Base\Exceptions\InvalidArgumentException;
use ZnCore\Base\Libs\ArrayTools\Helpers\ArrayIterator;
use ZnCore\Base\Libs\Scenario\Collections\ScenarioCollection;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Interfaces\Repositories\PackageRepositoryInterface;
use ZnTool\Package\Domain\Libs\GitShell;
use ZnTool\Package\Domain\Services\GroupService;

/**
 * Class InfoRepository
 *
 * @package php7tool\vendor\domain\repositories\file
 *
 * @property-read \php7tool\vendor\domain\Domain $domain
 */
class InfoRepository extends BaseRepository implements ReadInterface
{

    protected $withList = ['group', 'branch', 'has_changes', 'has_readme', 'has_changelog', 'has_guide', 'has_license', 'has_test', 'version', 'need_release', 'head_commit', 'remote_url'];
    private $groupService;
    private $packageRepository;

    public function __construct(GroupService $groupService, PackageRepositoryInterface $packageRepository)
    {
        $this->groupService = $groupService;
        $this->packageRepository = $packageRepository;
    }

    public function isExistsById($id)
    {
        try {
            $this->oneById($id);
            return true;
        } catch (NotFoundHttpException $e) {
            return false;
        }
    }

    public function isExists($condition)
    {
        /** @var Query $query */
        $query = Query::forge();
        if (is_array($condition)) {
            $query->whereFromCondition($condition);
        } else {
            $query->where($this->primaryKey, $condition);
        }
        try {
            $this->one($query);
            return true;
        } catch (NotFoundHttpException $e) {
            return false;
        }
    }

    public function oneById($id, Query $query = null)
    {
        $query = Query::forge($query);
        $query->where('id', $id);
        return $this->one($query);
    }

    public function one($query = null)
    {
        $query = Query::forge($query);
        $collection = $this->all($query);
        if (empty($collection)) {
            throw new NotFoundHttpException(__METHOD__ . ': ' . __LINE__);
        }
        return $collection[0];
    }

    public function all(Query $query = null)
    {
        $query = Query::forge($query);
        $query->with(['group']);
        $queryClone = $this->removeRelationWhere($query);
        $groupCollection = $this->groupService->all();

        /** @var PackageEntity[] $packageCollection */
        $packageCollection = $this->packageRepository->all();

        $vendorDir = realpath(__DIR__ . '/../../../../../../');

        foreach ($packageCollection as $packageEntity) {
            $dir = $vendorDir . DIRECTORY_SEPARATOR . $packageEntity->getId();
            $shell = new GitShell($dir);
            $hasChanges = $shell->hasChanges();

            dd($hasChanges);
        }


        $ownerNames = EntityHelper::getColumn($groupCollection, 'name');

        //$ownerNames = \App::$domain->package->group->allNames();
        $list = RepositoryHelper::allByOwners($ownerNames);
        $list = $this->separateCollection($list);
        $filteredList = ArrayIterator::allFromArray($queryClone, $list);
        dd($ownerNames);
        $listWithRelation = [];
        foreach ($filteredList as $item) {
            $listWithRelation[] = $this->loadRelations($item, $query);
        }
        $collection = $this->forgeEntity($listWithRelation, RepoEntity::class);
        return ArrayIterator::allFromArray($query, $collection);
    }

    public function count(Query $query = null)
    {
        $query = Query::forge($query);
        $queryCount = Query::cloneForCount($query);
        $collection = $this->all($queryCount);
        return count($collection);
    }

    public function allChanged(Query $query = null)
    {
        $query = Query::forge($query);
        $query->with(['group']);
        $queryClone = $this->removeRelationWhere($query);
        $groupCollection = $this->groupService->all();

        /** @var PackageEntity[] $packageCollection */
        $packageCollection = $this->packageRepository->all();
        $vendorDir = realpath(__DIR__ . '/../../../../../../');
        $changedCollection = new Collection;
        foreach ($packageCollection as $packageEntity) {
            $dir = $vendorDir . DIRECTORY_SEPARATOR . $packageEntity->getId();
            $shell = new GitShell($dir);
            $hasChanges = $shell->hasChanges();
            if ($hasChanges) {
                $changedCollection->add($packageEntity);
            }
        }
        return $changedCollection;
    }

    public function allWithTagAndCommit($query = null)
    {
        $query = Query::forge($query);
        $query->with(['tags', 'commits']);
        return $this->all($query);
    }

    public function allWithTag($query = null)
    {
        $query = Query::forge($query);
        $query->with(['tags']);
        return $this->all($query);
    }

    public function shortNamesByOwner($owner)
    {
        $pathList = RepositoryHelper::namesByOwner($owner);
        foreach ($pathList as &$name) {
            $name = strpos($name, 'yii2-') === 0 ? substr($name, 5) : $name;
        }
        return $pathList;
    }

    public function usesById($id)
    {
        $entity = $this->oneById($id);
        $uses = UseHelper::find($entity->directory);
        $res = UseHelper::listToMap($uses, $entity);
        $res['required_packages'] = $this->forgeRequiredPackages($res['required_packages']);
        return $res['required_packages'];
    }

    private function forgeRequiredPackages($collection)
    {
        $packages = [];
        foreach ($collection as $package) {
            try {
                $package = FileHelper::getAlias('@' . $package);
                $package = str_replace(__DIR__ . '/../../../../../..' . DIRECTORY_SEPARATOR, '', $package);
                $packageArr = explode('\\', $package);
                $package = $packageArr[0] . '\\' . $packageArr[1];
                $package = str_replace('\\', '/', $package);
                $version = \Yii::$app->extensions[$package];
                $packages[] = new RequiredEntity($version);
            } catch (InvalidArgumentException $e) {
            }
        }
        return $packages;
    }

    /**
     * @param $collection
     *
     * @return \php7rails\domain\values\BaseValue
     */
    private function separateCollection($collection)
    {
        $filters = [
            [
                'class' => IsPackageFilter::class,
            ],
            [
                'class' => IsIgnoreFilter::class,
                //'ignore' => $this->domain->info->ignore,
            ],
        ];
        $filterCollection = new ScenarioCollection($filters);
        $collection = $filterCollection->runAll($collection);
        return $collection;
    }

    private function removeRelationWhere(Query $query = null)
    {
        $queryClone = clone $query;
        if ($query->getParam('where')) {
            $queryClone->removeParam('where');
            foreach ($query->getParam('where') as $whereField => $whereValue) {
                if ( ! in_array($whereField, $this->withList)) {
                    $queryClone->where($whereField, $whereValue);
                }
            }
        }
        return $queryClone;
    }

    private function mergeWhereToWith(Query $query)
    {
        $with = $query->getParam('with');
        $with = $with ?: [];
        $where = $query->getParam('where');
        if (empty($where)) {
            return $with;
        }
        foreach ($where as $field => $value) {
            if (in_array($field, $this->withList)) {
                $with[] = $field;
            }
        }
        return $with;
    }

    private function loadRelations($item, Query $query)
    {
        $with = $this->mergeWhereToWith($query);
        $where = $query->getParam('where');
        $where = $where ?: [];
        if (empty($with)) {
            return $item;
        }
        $repo = RepositoryHelper::gitInstance($item['package']);
        if ($repo) {
            if (in_array('tags', $with) || isset($where['version']) || isset($where['need_release'])) {
                $item['tags'] = $repo->getTagsSha();
            }
            if (in_array('commits', $with) || isset($where['need_release']) || isset($where['head_commit'])) {
                $item['commits'] = $repo->getCommits();
            }
            if (in_array('branch', $with)) {
                $item['branch'] = $repo->getCurrentBranchName();
            }
            if (in_array('has_changes', $with)) {
                $item['has_changes'] = $repo->hasChanges();
            }
            if (in_array('required_packages', $with)) {
                $item['required_packages'] = $this->usesById($item['id']);
            }
            if (in_array('remote_url', $with)) {
                $item['remote_url'] = $repo->showRemote();
            }
            if (in_array('group', $with)) {
                $item['group'] = $this->groupService->oneByName($item['owner']);
            }
        }
        $item = RepositoryHelper::getHasInfo($item, $with);
        return $item;
    }

}
