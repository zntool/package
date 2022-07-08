<?php

namespace ZnTool\Package\Commands;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Code\Entities\PhpTokenEntity;
use ZnCore\Code\Helpers\PhpTokenHelper;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Entity\Helpers\CollectionHelper;
use ZnCore\FileSystem\Helpers\FilePathHelper;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Libs\Deps\DepsExtractor;

class DepsCommand extends BaseCommand
{

    protected static $defaultName = 'package:code:deps';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages deps</>');

        /** @var PackageEntity[] $collection */
        $collection = $this->packageService->findAll();

        $output->writeln('');
        $question = new ChoiceQuestion(
            'Select packages for push:',
            CollectionHelper::getColumn($collection, 'id'),
            'a'
        );
        $question->setMultiselect(true);
        $selectedPackages = $this->getHelper('question')->ask($input, $output, $question);

        $selectedCollection = new ArrayCollection();
        foreach ($collection as $packageEntity) {
            if (in_array($packageEntity->getId(), $selectedPackages)) {
                $selectedCollection->add($packageEntity);
            }
        }

        $depsExtractor = new DepsExtractor();

        /*$filePath = __DIR__ . '/../../../../znlib/rpc/src/Domain/Repositories/ConfigManager/MethodRepository.php';
        $code = file_get_contents($filePath);
        $classes111 = $depsExtractor->extractUses($code);
        dd($classes111);*/

        $packageClasses = [];
        
        foreach ($selectedCollection as $packageEntity) {
            $classes = [];
            $dir = $packageEntity->getDirectory();
            $files = $this->getFiles($dir);
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $filePath = __DIR__ . '/../../../../znlib/rpc/src/Domain/Repositories/ConfigManager/MethodRepository.php';
                $code = file_get_contents($filePath);

                /*$tokenCollection = PhpTokenHelper::getTokens($code);
                $classes111 = $this->extractClasses($tokenCollection);
                if ($classes111) {
                    $classes = ArrayHelper::merge($classes, $classes111);
                }*/
                
//                $tokenCollection = PhpTokenHelper::getTokens($code);
//                $classes111 = $this->extractUse($tokenCollection);
                $classes111 = $depsExtractor->extractUses($code);
                dd($classes111);
                if ($classes111) {
                    $classes = ArrayHelper::merge($classes, $classes111);
                }
            }

            if($classes) {
                $classes = $this->prepareClassList($classes);
                $packageClasses[$packageEntity->getId()] = $classes;
            }
        }

        foreach ($packageClasses as $packageId => $classes) {
            $output->writeln('');
            $output->writeln("<fg=yellow>{$packageId}</>");
            $output->writeln('');
            foreach ($classes as $class) {
                $output->writeln(" - <fg=blue>{$class}</>");
            }
            
            $noExistsClassList = array_unique($this->noExistsClassList);
            $noExistsClassList = array_values($noExistsClassList);
            sort($noExistsClassList);
            
            foreach ($noExistsClassList as $class) {
                $output->writeln(" - <fg=red>{$class}</>");
            }
        }

        return 0;
    }

    private $noExistsClassList = [];
    
    private function prepareClassList($classes)
    {
        $new = [];
        foreach ($classes as $class) {
            $class = trim($class, ' \\');

            if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
//                $this->noExistsClassList[] = $class;
//                unset($class);
                continue;
            } elseif(!(new \ReflectionClass($class))->isUserDefined()) {
                $this->noExistsClassList[] = $class;
//                unset($class);
                continue;
            }

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

    private function extractClasses(Enumerable $tokenCollection)
    {
        $classes = [];
        $startIndex = null;
        foreach ($tokenCollection as $index => $tokenEntity) {
            if (!$startIndex && $tokenEntity->getName() == 'T_NS_SEPARATOR') {
                $startIndex = $index;
            }
            if ($startIndex) {
                if ($tokenEntity->getName() == 'T_NS_SEPARATOR' || $tokenEntity->getName() == 'T_STRING') {

                } else {
                    $className = '';
                    for ($i = $startIndex - 1; $i < $index; $i++) {
                        if ($tokenCollection[$i]->getName() != 'UNKNOWN') {
                            $className .= $tokenCollection[$i]->getData();
                        }
                    }
                    $classes[] = $className;
                    $startIndex = null;
                }
            }
        }
        return $classes;
    }

    private function isInClass(PhpTokenEntity $tokenEntity)
    {
        static $isClass = false;
        if (!$isClass && $tokenEntity->getName() == 'T_CLASS') {
            $isClass = 1;
        }
        if ($isClass && trim($tokenEntity->getData()) == '{') {
            $isClass++;
        }
        if ($isClass && trim($tokenEntity->getData()) == '}') {
            $isClass--;
            if ($isClass === 1) {
                $isClass = false;
            }
        }
        return $isClass !== false;
    }

    private function extractUse(Enumerable $tokenCollection)
    {
        $classes = [];
        $startIndex = null;
        foreach ($tokenCollection as $index => $tokenEntity) {
            $isClass = $this->isInClass($tokenEntity);
            if (!$isClass) {
                if (!$startIndex && $tokenEntity->getName() == 'T_USE') {
                    $startIndex = $index;
                }
                if ($startIndex) {
                    if ($tokenEntity->getName() == 'UNKNOWN' || $tokenEntity->getName() == 'T_AS') {
                        $className = '';
                        for ($i = $startIndex + 1; $i < $index; $i++) {
                            $className .= $tokenCollection[$i]->getData();
                        }
                        $classes[] = $className;
                        $startIndex = null;
                    }
                }
            }
        }
        return $classes;
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
}
