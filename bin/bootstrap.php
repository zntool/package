<?php


use Illuminate\Container\Container;
use Symfony\Component\Console\Application;
use ZnLib\Console\Symfony4\Helpers\CommandHelper;

/**
 * @var Application $application
 * @var Container $container
 */

//$container = Container::getInstance();

// --- Application ---

//$container->bind(Application::class, Application::class, true);

// --- Generator ---

$container->bind(\ZnTool\Dev\Generator\Domain\Interfaces\Services\DomainServiceInterface::class, \ZnTool\Dev\Generator\Domain\Services\DomainService::class);
$container->bind(\ZnTool\Dev\Generator\Domain\Interfaces\Services\ModuleServiceInterface::class, \ZnTool\Dev\Generator\Domain\Services\ModuleService::class);

// --- Composer ---


$container->bind(\ZnTool\Dev\Composer\Domain\Interfaces\Repositories\ConfigRepositoryInterface::class, \ZnTool\Dev\Composer\Domain\Repositories\File\ConfigRepository::class);
$container->bind(\ZnTool\Dev\Composer\Domain\Interfaces\Services\ConfigServiceInterface::class, \ZnTool\Dev\Composer\Domain\Services\ConfigService::class);

// --- Package ---

$container->bind(\ZnTool\Package\Domain\Interfaces\Services\GitServiceInterface::class, \ZnTool\Package\Domain\Services\GitService::class);
$container->bind(\ZnTool\Package\Domain\Interfaces\Services\PackageServiceInterface::class, \ZnTool\Package\Domain\Services\PackageService::class);
$container->bind(\ZnTool\Package\Domain\Repositories\File\GroupRepository::class, function () {
    $fileName = ! empty($_ENV['PACKAGE_GROUP_CONFIG']) ? __DIR__ . '/../../../../' . $_ENV['PACKAGE_GROUP_CONFIG'] : __DIR__ . '/../src/Package/Domain/Data/package_group.php';
    $repo = new \ZnTool\Package\Domain\Repositories\File\GroupRepository($fileName);
    return $repo;
});
$container->bind(\ZnTool\Package\Domain\Interfaces\Repositories\PackageRepositoryInterface::class, \ZnTool\Package\Domain\Repositories\File\PackageRepository::class);
$container->bind(\ZnTool\Package\Domain\Interfaces\Repositories\GitRepositoryInterface::class, \ZnTool\Package\Domain\Repositories\File\GitRepository::class);

CommandHelper::registerFromNamespaceList([
    'ZnTool\Dev\Generator\Commands',
    'ZnTool\Package\Commands',
    'ZnTool\Dev\Composer\Commands',
], $container);
