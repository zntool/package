<?php

namespace ZnTool\Package\Commands;

use ZnTool\Package\Domain\Interfaces\Services\GitServiceInterface;
use ZnTool\Package\Domain\Interfaces\Services\PackageServiceInterface;
use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command
{

    protected $packageService;
    protected $gitService;

    public function __construct(?string $name = null, PackageServiceInterface $packageService, GitServiceInterface $gitService)
    {
        parent::__construct($name);
        $this->packageService = $packageService;
        $this->gitService = $gitService;
    }

}
