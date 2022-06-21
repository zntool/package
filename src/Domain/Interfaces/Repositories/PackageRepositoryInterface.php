<?php

namespace ZnTool\Package\Domain\Interfaces\Repositories;

use ZnCore\Base\Libs\Domain\Interfaces\GetEntityClassInterface;
use ZnCore\Base\Libs\Domain\Interfaces\ReadAllInterface;
use ZnCore\Base\Libs\Repository\Interfaces\ReadOneInterface;
//use ZnCore\Base\Libs\Repository\Interfaces\RelationConfigInterface;
use ZnCore\Base\Libs\Repository\Interfaces\RepositoryInterface;

interface PackageRepositoryInterface extends RepositoryInterface, GetEntityClassInterface, ReadAllInterface, ReadOneInterface//, RelationConfigInterface
{

}
