<?php

namespace ZnTool\Package\Domain\Services;

use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Code\Helpers\ComposerHelper;
use ZnCore\FileSystem\Helpers\FileHelper;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Helpers\PackageHelper;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameInQuotedStringParser;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameParser;
use ZnTool\Package\Domain\Libs\Deps\PhpUsesParser;

class DependencyService
{

    public function findUsedClasses($selectedCollection)
    {
        $classNameStringParser = new PhpClassNameInQuotedStringParser();
        $classNameParser = new PhpClassNameParser();
        $classUsesParser = new PhpUsesParser();

        $packageClasses = [];
        $unused = [];

        foreach ($selectedCollection as $packageEntity) {
            $classes = [];
            $dir = $packageEntity->getDirectory();

            $options['only'][] = '*.php';
            $files = FileHelper::findFiles($dir, $options);

//            $files = $this->getFiles($dir);
            foreach ($files as $filePath) {
//                $filePath = $file->getRealPath();
//                $filePath = __DIR__ . '/../../../../../znbundle/eav/src/Domain/config/container.php';
                $code = file_get_contents($filePath);

                $classesFromUses = $classUsesParser->parse($code);
                $code = $classUsesParser->removeUses($code);
//                dd($code);
                if ($classesFromUses) {
                    foreach ($classesFromUses as $alias => $use) {
//                        dd($code, $alias);
                        $has = strpos($code, $alias) !== false;
                        if(!$has) {
                            $unused[$packageEntity->getId()][$filePath][] = $use;
                        }
                    }
                }
            }
        }
        return $unused;
    }

    public function findDependency($selectedCollection)
    {
        $classNameStringParser = new PhpClassNameInQuotedStringParser();
        $classNameParser = new PhpClassNameParser();

        $packageClasses = [];

        foreach ($selectedCollection as $packageEntity) {
            $classes = [];
            $dir = $packageEntity->getDirectory();

            $options['only'][] = '*.php';
            $files = FileHelper::findFiles($dir, $options);

//            $files = $this->getFiles($dir);
            foreach ($files as $filePath) {
//                $filePath = $file->getRealPath();
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
     * @return PackageEntity[]
     */
    private function getPackageMap()
    {
        $packageCollection = PackageHelper::findAll();
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

        $packageCollection = PackageHelper::findAll();
        foreach ($classes as $class) {
            $class = trim($class, ' \\');

            foreach ($map as $namespace => $packageEntity1) {

                $pid = $packageEntity1->getId();

                if (strpos($class, $namespace) !== false) {
                    if ($pid != $packageEntity->getId()) {
                        $requires = $packageEntity->getConfig()->getAllRequire();

                        $isNeed = empty($requires) || !isset($requires[$pid]);
                        if ($isNeed) {
                            $status = 'need';
                        } else {

                        }

                        $pckageItem = $packagesNeedle[$pid] ?? [];
                        $pckageItemClasses = $pckageItem['classes'] ?? [];
                        $pckageItemClasses[] = $class;


                        $packagesNeedle[$pid] = [
                            'id' => $pid,
                            'version' => $packageEntity1->getConfig()->getVersion(),
                            'fullName' => $pid,
                            'isNeed' => $isNeed,
                            'classes' => $pckageItemClasses,
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
