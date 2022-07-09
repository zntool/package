<?php

namespace ZnTool\Package\Domain\Helpers;

use ZnCore\Code\Helpers\ComposerHelper;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Collection\Libs\Collection;
use ZnCore\FileSystem\Helpers\FilePathHelper;
use ZnTool\Package\Domain\Entities\ConfigEntity;
use ZnTool\Package\Domain\Entities\GroupEntity;
use ZnTool\Package\Domain\Entities\PackageEntity;

class PackageHelper
{

    /**
     * @return Enumerable | PackageEntity[]
     */
    public static function findAllPackages(): Enumerable
    {
        $packages = ComposerHelper::getInstalled()['packages'];
        $collection = new Collection();
        foreach ($packages as $package) {
            $packageEntity = new PackageEntity();
            $packageEntity->setId($package['name']);
            list($groupName, $packageName) = explode('/', $package['name']);
            $packageEntity->setName($packageName);

            $groupEntity = new GroupEntity();
            $groupEntity->name = $groupName;

            $packageEntity->setGroup($groupEntity);

            $confiEntity = new ConfigEntity();
            $confiEntity->setId($packageEntity->getId());
            $confiEntity->setConfig($package);
            $confiEntity->setPackage($packageEntity);

            $packageEntity->setConfig($confiEntity);
            $collection->add($packageEntity);
        }
        return $collection;
    }

    public static function getPsr4Dictonary()
    {
        $psr4 = include(__DIR__ . '/../../../../../composer/autoload_psr4.php');
        return $psr4;
    }

    public static function pathByNamespace($namespace)
    {
        $nsArray = PackageHelper::findPathByNamespace($namespace);
        if ($nsArray) {
            $partName = mb_substr($namespace, mb_strlen($nsArray['namespace']));
            $partName = trim($partName, '\\');
            $fileName = $nsArray['path'] . '\\' . $partName;
        } else {
            $fileName = FilePathHelper::rootPath() . '\\' . $namespace;
        }
        $fileName = str_replace('\\', '/', $fileName);
        return $fileName;
    }

    public static function findPathByNamespace(string $namespace): ?array
    {
        $namespace = trim($namespace, '\\');
        $namespace .= '\\';
        //dd($namespace);
        $psr4 = self::getPsr4Dictonary();
        foreach ($psr4 as $ns => $path) {
            $path = $path[0];
            if (mb_strpos($namespace, $ns) === 0) {
                return [
                    'namespace' => $ns,
                    'path' => $path,
                ];
            }
        }
        return null;
    }
}
