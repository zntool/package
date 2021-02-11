<?php

namespace ZnTool\Package\Domain\Services;

use ZnCore\Domain\Base\BaseCrudService;
use ZnTool\Package\Domain\Repositories\File\GroupRepository;

class GroupService extends BaseCrudService
{

    public function __construct(GroupRepository $repository)
    {
        $this->setRepository($repository);
    }

}
