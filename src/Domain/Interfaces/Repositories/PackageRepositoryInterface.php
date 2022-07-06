<?php

namespace ZnTool\Package\Domain\Interfaces\Repositories;

use ZnCore\Domain\Interfaces\GetEntityClassInterface;
use ZnCore\Domain\Interfaces\ReadAllInterface;
use ZnCore\Repository\Interfaces\FindOneInterface;
//use ZnCore\Repository\Interfaces\RelationConfigInterface;
use ZnCore\Repository\Interfaces\RepositoryInterface;

interface PackageRepositoryInterface extends RepositoryInterface, GetEntityClassInterface, ReadAllInterface, FindOneInterface//, RelationConfigInterface
{

}
