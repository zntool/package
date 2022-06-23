<?php

namespace ZnTool\Package\Domain\Helpers;

use ZnCore\Base\FileSystem\Helpers\FilePathHelper;

class PackageHelper
{

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
