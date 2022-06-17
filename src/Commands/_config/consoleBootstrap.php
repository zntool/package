<?php

use ZnCore\Base\Libs\App\Kernel;
use ZnCore\Base\Libs\App\Loaders\BundleLoader;
use ZnCore\Base\Libs\DotEnv\DotEnv;

DotEnv::init();

\ZnCore\Base\Helpers\DeprecateHelper::hardThrow();

$kernel = new Kernel('console');
$container = $kernel->getContainer();

/*$container->singleton(EntityManagerInterface::class, function (ContainerInterface $container) {
    return EntityManager::getInstance($container);
});
$container->singleton(Manager::class, function () {
    return ManagerFactory::createManagerFromEnv();
});*/

$bundleLoader = new BundleLoader([], ['i18next', 'container', 'console', 'migration']);
$bundleLoader->addBundles(include __DIR__ . '/bundle.php');
$kernel->setLoader($bundleLoader);

$config = $kernel->loadAppConfig();
