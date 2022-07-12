<?php

return [
    'definitions' => [],
    'singletons' => [
        \ZnTool\Dev\Composer\Domain\Interfaces\Repositories\ConfigRepositoryInterface::class => \ZnTool\Dev\Composer\Domain\Repositories\File\ConfigRepository::class,
        \ZnTool\Dev\Composer\Domain\Interfaces\Services\ConfigServiceInterface::class => \ZnTool\Dev\Composer\Domain\Services\ConfigService::class,
        \ZnTool\Package\Domain\Interfaces\Services\GitServiceInterface::class => \ZnTool\Package\Domain\Services\GitService::class,
        \ZnTool\Package\Domain\Interfaces\Services\PackageServiceInterface::class => \ZnTool\Package\Domain\Services\PackageService::class,
        \ZnTool\Package\Domain\Repositories\File\GroupRepository::class => function () {
            $fileName = ! empty($_ENV['PACKAGE_GROUP_CONFIG']) ? $_ENV['PACKAGE_GROUP_CONFIG'] : __DIR__ . '/../../../src/Domain/Data/package_group.php';
            $repo = new \ZnTool\Package\Domain\Repositories\File\GroupRepository($fileName);
            return $repo;
        },
        \ZnTool\Package\Domain\Interfaces\Repositories\PackageRepositoryInterface::class => \ZnTool\Package\Domain\Repositories\File\PackageRepository::class,
        \ZnTool\Package\Domain\Interfaces\Repositories\GitRepositoryInterface::class => \ZnTool\Package\Domain\Repositories\File\GitRepository::class,
    ],
];
