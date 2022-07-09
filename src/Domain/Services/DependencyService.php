<?php

namespace ZnTool\Package\Domain\Services;

use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Code\Helpers\ComposerHelper;
use ZnCore\Entity\Helpers\CollectionHelper;
use ZnCore\FileSystem\Helpers\FilePathHelper;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Helpers\PackageHelper;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameInQuotedStringParser;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameParser;

class DependencyService
{

    public function findUsedClasses($selectedCollection)
    {

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

            if ($classes) {
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


    /**
     * @return PackageEntity[]
     */
    private function getPackageMap()
    {
        $packageCollection = PackageHelper::findAllPackages();
        foreach ($packageCollection as $packageEntity) {
            $autoload = $packageEntity->getConfig()->getAllAutoloadPsr4();
            if ($autoload) {
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
        $packagesNeedle = [];

        $new = [];

        $packageCollection = PackageHelper::findAllPackages();
        $packageMap = CollectionHelper::indexing($packageCollection, 'id');
        foreach ($classes as $class) {
            $class = trim($class, ' \\');

            foreach ($map as $namespace => $packageEntity1) {
                if (strpos($class, $namespace) !== false) {
                    if ($packageEntity1->getId() != $packageEntity->getId()) {
                        $requires = $packageEntity->getConfig()->getAllRequire();
//                        dump($requires);

                        $isNeed = empty($requires) || !isset($requires[$packageEntity1->getId()]);
                        if ($isNeed) {
                            $status = 'need';
                        } else {

                        }

                        $packagesNeedle[$packageEntity1->getId()] = [
                            'id' => $packageEntity1->getId(),
                            'version' => $packageEntity1->getConfig()->getVersion(),
                            'fullName' => $packageEntity1->getId() /*. ':' . $packageEntity1->getConfig()->getVersion()*/,
                            'isNeed' => $isNeed,
                        ];
                    }
                }
            }

            if (strpos($class, '\\') === false) {
                continue;
            }

            $classArr = explode('\\', $class);
            $classArr = array_splice($classArr, 0, 2);
            $class = implode('\\', $classArr);

            $class = trim($class, ' \\');

            if (empty($class)) {
                continue;
            }

            $new[] = $class;
        }

        $classes = array_unique($new);
        $classes = array_values($classes);
        sort($classes);

//        $packagesNeedle = array_unique($packagesNeedle);
//        $packagesNeedle = array_values($packagesNeedle);
        ksort($packagesNeedle);

        return $packagesNeedle;
    }


    private function getPackageDirByClassName(string $class): string
    {
        list($group, $package) = explode('\\', $class);
        $class = $group . '\\' . $package;
        $autoloadPsr4 = ComposerHelper::getComposerVendorClassLoader()->getPrefixesPsr4();
        $dir = $autoloadPsr4[$class . '\\'][0];
        $dir = realpath($dir);
        return $dir;
    }
}
