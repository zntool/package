<?php

namespace ZnTool\Package\Commands;

use Doctrine\Common\Collections\ArrayCollection;
use PhpParser\ParserFactory;
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
use ZnTool\Package\Domain\Libs\Deps\PhpClassNameParser;
use ZnTool\Package\Domain\Libs\Deps\PhpUsesParser;

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

        $usesParser = new PhpUsesParser();
        $classNameParser = new PhpClassNameParser();

        $packageClasses = [];
        
        foreach ($selectedCollection as $packageEntity) {
            $classes = [];
            $dir = $packageEntity->getDirectory();
            $files = $this->getFiles($dir);
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
//                $filePath = __DIR__ . '/../../../../znbundle/reference/src/Domain/Entities/ItemEntity.php';
                $code = file_get_contents($filePath);

//                $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
//                $stmts = $parser->parse($code);
//                dd(json_encode($stmts, JSON_PRETTY_PRINT));

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

        foreach ($packageClasses as $packageId => $classes) {
            $output->writeln('');
            $output->writeln("<fg=yellow>{$packageId}</>");
            $output->writeln('');
            foreach ($classes as $class) {
                $output->writeln(" - <fg=blue>{$class}</>");
            }
            
            /*$noExistsClassList = array_unique($this->noExistsClassList);
            $noExistsClassList = array_values($noExistsClassList);
            sort($noExistsClassList);
            
            foreach ($noExistsClassList as $class) {
                $output->writeln(" - <fg=red>{$class}</>");
            }*/
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
                continue;
            } elseif(!(new \ReflectionClass($class))->isUserDefined()) {
                $this->noExistsClassList[] = $class;
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
