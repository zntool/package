<?php

use ZnTool\Package\Symfony4\Admin\Controllers\ApplicationController;
use ZnTool\Package\Symfony4\Admin\Controllers\EdsController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use ZnTool\Package\Symfony4\Admin\Controllers\ApiKeyController;
use ZnLib\Web\Symfony4\MicroApp\Helpers\RouteHelper;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('package/changed', '/package/changed')
        ->controller([\ZnTool\Package\Symfony4\Admin\Controllers\ChangedController::class, 'index'])
        ->methods(['GET', 'POST']);
    $routes
        ->add('package/changed/view', '/package/changed/view')
        ->controller([\ZnTool\Package\Symfony4\Admin\Controllers\ChangedController::class, 'view'])
        ->methods(['GET', 'POST']);
    
    //    RouteHelper::generateCrud($routes, ApplicationController::class, '/application/application');
};
