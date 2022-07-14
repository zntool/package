<?php

namespace ZnTool\Package\Domain\Interfaces\Repositories;

use ZnDomain\Domain\Interfaces\GetEntityClassInterface;
use ZnDomain\Domain\Interfaces\ReadAllInterface;
use ZnDomain\Repository\Interfaces\FindOneInterface;
//use ZnDomain\Repository\Interfaces\RelationConfigInterface;
use ZnDomain\Repository\Interfaces\RepositoryInterface;

interface PackageRepositoryInterface extends RepositoryInterface, GetEntityClassInterface, ReadAllInterface, FindOneInterface//, RelationConfigInterface
{

}
