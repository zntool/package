<?php

namespace ZnTool\Package\Domain\Helpers;

use Illuminate\Support\Collection;
use ZnCore\Base\Legacy\Yii\Helpers\ArrayHelper;
use ZnCore\Base\Legacy\Yii\Helpers\FileHelper;
use ZnTool\Package\Domain\Entities\ConfigEntity;
use ZnTool\Package\Domain\Entities\PackageEntity;

class ComposerConfigHelper
{

    public static function getWanted(ConfigEntity $configEntity, $requirePackage)
    {
        $wanted = [];
        if(!empty($requirePackage)) {
            foreach ($requirePackage as $packageId) {
                $allRequire = $configEntity->getAllRequire();
                $isDeclared = isset($allRequire[$packageId]);
                if( ! $isDeclared) {
                    $wanted[] = $packageId;
                }
            }
            $wanted = array_unique($wanted);
            $wanted = array_values($wanted);
        }
        return $wanted;
    }

    public static function getUses(string $dir)
    {
        $options['only'][] = '*.php';
        $phpScripts = FileHelper::findFiles($dir, $options);
        $depss = [];
        foreach ($phpScripts as $phpScriptFileName) {
            $code = FileHelper::load($phpScriptFileName);
            preg_match_all('#use\s+(.+);#iu', $code, $matches);
            if(!empty($matches[1])) {
                $depss = array_merge($depss, $matches[1]);
            }
        }
        $depss = array_unique($depss);
        $depss = array_values($depss);
        return $depss;
    }

    public static function extractPsr4Autoload(Collection $collection)
    {
        /** @var ConfigEntity[] | Collection $collection */
        $namespaces = [];
        foreach ($collection as $configEntity) {
            $psr4autoloads = $configEntity->getAllAutoloadPsr4();
            if($psr4autoloads) {
                foreach ($psr4autoloads as $autoloadNamespace => $path) {
                    $autoloadNamespace = trim($autoloadNamespace, '\\');
                    $path = 'vendor' . DIRECTORY_SEPARATOR . $configEntity->getPackage()->getId() . DIRECTORY_SEPARATOR . $path;
                    $path = str_replace('\\', '/', $path);
                    $path = trim($path, '/');
                    //$path = realpath($path);
                    $namespaces[$autoloadNamespace] = $path;
                }
            }
        }
        return $namespaces;
    }

    public static function extractPsr4AutoloadPackages(Collection $collection)
    {
        /** @var ConfigEntity[] | Collection $collection */
        $namespaces = [];
        foreach ($collection as $configEntity) {
            $psr4autoloads = $configEntity->getAllAutoloadPsr4();
            if($psr4autoloads) {
                foreach ($psr4autoloads as $autoloadNamespace => $path) {
                    $autoloadNamespace = trim($autoloadNamespace, '\\');
                    $namespaces[$autoloadNamespace] = $configEntity->getPackage();
                }
            }
        }
        return $namespaces;
    }

}
