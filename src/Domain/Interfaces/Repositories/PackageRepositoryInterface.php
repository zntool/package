<?php

namespace ZnTool\Package\Domain\Interfaces\Repositories;

use ZnCore\Domain\Domain\Interfaces\GetEntityClassInterface;
use ZnCore\Domain\Domain\Interfaces\ReadAllInterface;
use ZnCore\Domain\Repository\Interfaces\FindOneInterface;
//use ZnCore\Domain\Repository\Interfaces\RelationConfigInterface;
use ZnCore\Domain\Repository\Interfaces\RepositoryInterface;

interface PackageRepositoryInterface extends RepositoryInterface, GetEntityClassInterface, ReadAllInterface, FindOneInterface//, RelationConfigInterface
{

}
