<?php

namespace ZnTool\Package\Domain\Services;

use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Query\Entities\Query;
use ZnCore\Service\Base\BaseCrudService;
use ZnTool\Package\Domain\Interfaces\Repositories\PackageRepositoryInterface;
use ZnTool\Package\Domain\Interfaces\Services\PackageServiceInterface;

class PackageService extends BaseCrudService implements PackageServiceInterface
{

    public function __construct(PackageRepositoryInterface $repository)
    {
        $this->setRepository($repository);
    }

    public function findAllWithOtherAuthors(Query $query = null): Enumerable
    {

    }
}
