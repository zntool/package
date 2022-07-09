<?php

namespace ZnTool\Package\Domain\Services;

use Symfony\Component\Config\Resource\ComposerResource;
use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Code\Helpers\ComposerHelper;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\FileSystem\Helpers\FilePathHelper;
use ZnCore\Instance\Helpers\ClassHelper;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Helpers\PackageHelper;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameParser;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameInQuotedStringParser;
use ZnTool\Package\Domain\Libs\Deps\PhpUsesParser;

class DependencyService
{

    public function findUsedClasses($selectedCollection) {

//        $vendors = (new ComposerResource())->getVendors();
//        dd($vendors);

        $classNameStringParser = new PhpClassNameInQuotedStringParser();
        $classNameParser = new PhpClassNameParser();

        $packageClasses = [];

        foreach ($selectedCollection as $packageEntity) {
            $classes = [];
            $dir = $packageEntity->getDirectory();
            $files = $this->getFiles($dir);
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
//                $filePath = __DIR__ . '/../../../../../znbundle/eav/src/Domain/config/container.php';
                $code = file_get_contents($filePath);

                $classesFromString = $classNameStringParser->parse($code);
                if ($classesFromString) {
                    $classes = ArrayHelper::merge($classes, $classesFromString);
                }

                $classesFromClassNames = $classNameParser->parse($code);
                if ($classesFromClassNames) {
                    $classes = ArrayHelper::merge($classes, $classesFromClassNames);
                }
            }

            if($classes) {
                $classes = $this->prepareClassList($classes, $packageEntity);
                $packageClasses[$packageEntity->getId()] = $classes;
            }
        }
        return $packageClasses;
    }

    /**
     * @param string $directoryPath
     * @return array | \SplFileInfo[]
     */
    private function getFiles(string $directoryPath)
    {
        $directoryIterator = new \RecursiveDirectoryIterator($directoryPath);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = [];
        foreach ($iterator as $info) {
            /** @var $info \SplFileInfo */
            if ($info->isDir()) {
                continue;
            }
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $info->getRealPath());
            $ext = FilePathHelper::fileExt($path);
            if ($ext != 'php') {
                continue;
            }
            $path = str_replace($directoryPath, '', $path);
            $files[] = $info;
        }
        return $files;
    }


    private function getPackageMap()
    {
        $packageCollection = PackageHelper::findAllPackages();
        foreach ($packageCollection as $packageEntity) {
            $autoload = $packageEntity->getConfig()->getAllAutoloadPsr4();
            if($autoload) {
                foreach ($autoload as $namespace => $path) {
                    $namespace = trim($namespace, '\\');
                    $map[$namespace] = $packageEntity;
                }
            }
        }
        return $map;
    }

    private function prepareClassList($classes, PackageEntity $packageEntity)
    {
        $map = $this->getPackageMap();

//        dd($map);
//        dd($packageCollection->first());


//        $packages = ComposerHelper::getInstalledPackages();
//        dd($map);

        $packagesNeedle = [];

        $new = [];
        foreach ($classes as $class) {
            $class = trim($class, ' \\');

            foreach ($map as $namespace => $packageEntity1) {
                if(strpos($class, $namespace) !== false) {
                    if($packageEntity1->getId() != $packageEntity->getId()) {
//                        dump($packageEntity1);
                        $packagesNeedle[] = $packageEntity1->getId() . ':' . $packageEntity1->getConfig()->getVersion();
                    }
                }
            }

            if(strpos($class, '\\') === false) {
                continue;
            }

//           $dir = $this->getPackageDirByClassName($class);
//            dump($dir);

            /*if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
//                $this->noExistsClassList[] = $class;
                continue;
            } elseif(!(new \ReflectionClass($class))->isUserDefined()) {
                $this->noExistsClassList[] = $class;
                continue;
            }*/

            $classArr = explode('\\', $class);
            $classArr = array_splice($classArr, 0, 2);
            $class = implode('\\', $classArr);

            $class = trim($class, ' \\');

            if (empty($class)) {
//                unset($class);
                continue;
            }
            // $class = implode('\\', $classArr);

            $new[] = $class;
        }

        $classes = array_unique($new);
        $classes = array_values($classes);
        sort($classes);

        $packagesNeedle = array_unique($packagesNeedle);
        $packagesNeedle = array_values($packagesNeedle);
        sort($packagesNeedle);

        return $packagesNeedle;
    }


    private function getPackageDirByClassName(string $class): string {
        list($group, $package) = explode('\\', $class);
        $class = $group . '\\' . $package;
        $autoloadPsr4 = ComposerHelper::getComposerVendorClassLoader()->getPrefixesPsr4();
//        dump(ComposerHelper::getPsr4Path($class));
        $dir = $autoloadPsr4[$class . '\\'][0];
        $dir = realpath($dir);
        return $dir;
    }
}
