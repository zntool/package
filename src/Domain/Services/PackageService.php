<?php

namespace ZnTool\Package\Domain\Services;

use ZnCore\Domain\Base\BaseCrudService;
use ZnTool\Package\Domain\Interfaces\Repositories\PackageRepositoryInterface;
use ZnTool\Package\Domain\Interfaces\Services\PackageServiceInterface;

class PackageService extends BaseCrudService implements PackageServiceInterface
{

    public function __construct(PackageRepositoryInterface $repository)
    {
        $this->setRepository($repository);
    }

}
