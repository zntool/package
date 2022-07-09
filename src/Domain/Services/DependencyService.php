<?php

namespace ZnTool\Package\Domain\Services;

use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Code\Helpers\ComposerHelper;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\FileSystem\Helpers\FilePathHelper;
use ZnCore\Instance\Helpers\ClassHelper;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameParser;
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameStringParser;
use ZnTool\Package\Domain\Libs\Deps\PhpUsesParser;

class DependencyService
{

    public function findUsedClasses($selectedCollection) {
        $classNameStringParser = new PhpClassNameStringParser();
        $classNameParser = new PhpClassNameParser();

        $packageClasses = [];

        foreach ($selectedCollection as $packageEntity) {
            $classes = [];
            $dir = $packageEntity->getDirectory();
            $files = $this->getFiles($dir);
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $filePath = __DIR__ . '/../../../../../znbundle/eav/src/Domain/config/container.php';
                $code = file_get_contents($filePath);

                $classesFromString = $classNameStringParser->parse($code);
                if ($classesFromString) {
                    $classes = ArrayHelper::merge($classes, $classesFromString);
                }

                $classesFromClassNames = $classNameParser->parse($code);
                if ($classesFromClassNames) {
                    $classes = ArrayHelper::merge($classes, $classesFromClassNames);
                }

                /*$classesFromUses = $usesParser->parse($code);
                if ($classesFromUses) {
                    $classes = ArrayHelper::merge($classes, $classesFromUses);
                }*/
            }

            if($classes) {
                $classes = $this->prepareClassList($classes);
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

    private function prepareClassList($classes)
    {
        $new = [];
        foreach ($classes as $class) {
            $class = trim($class, ' \\');

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

            /*$classArr = explode('\\', $class);
            $classArr = array_splice($classArr, 0, 2);
            $class = implode('\\', $classArr);*/

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
        return $classes;
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
