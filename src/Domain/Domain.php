<?php

namespace ZnTool\Package\Domain;

use ZnCore\Domain\Interfaces\DomainInterface;

class Domain implements DomainInterface
{

    public function getName()
    {
        return 'package';
    }

}