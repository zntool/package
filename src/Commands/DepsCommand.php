<?php

namespace ZnTool\Package\Commands;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Entity\Helpers\CollectionHelper;
use ZnCore\FileSystem\Helpers\FilePathHelper;
use ZnCore\FileSystem\Helpers\FindFileHelper;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;

class DepsCommand extends BaseCommand
{

    protected static $defaultName = 'package:code:deps';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages deps</>');

        /** @var PackageEntity[] $collection */
        $collection = $this->packageService->findAll();
//        dd($collection[0]->getId());

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
            if(in_array($packageEntity->getId(), $selectedPackages)) {
                $selectedCollection->add($packageEntity);
            }
        }
//        dd($selectedCollection);

        $classes = [];

        foreach ($selectedCollection as $packageEntity) {
            $dir = $packageEntity->getDirectory();
            $files = $this->getFiles($dir);
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
//                $filePath = '/home/common/var/www/social/server.soc/vendor/znlib/rpc/src/Domain/Repositories/ConfigManager/MethodRepository.php';
                $code = file_get_contents($filePath);

                preg_match_all('/(\\\Zn[a-z0-9_]+\\\[a-z0-9_\\\]+)/i', $code, $matches);
                $matches = $matches[0];
//                dd($matches);

                /*$tokens = token_get_all($code, TOKEN_PARSE);
                dd($tokens);*/

                if ($matches) {
                    $classes = ArrayHelper::merge($classes, $matches);
                }

                /*$classes111 = $this->extractClasses($tokens);
                if ($classes111) {
                    $classes = ArrayHelper::merge($classes, $classes111);
                }


                $classesUse = $this->extractUse($tokens);
                if ($classesUse) {
                    $classes = ArrayHelper::merge($classes, $classesUse);
                }*/
            }
        }

        $classes = array_unique($classes);
        $classes = array_values($classes);
        sort($classes);

        dd($classes);

        return 0;
    }

    private function extractClasses($tokens) {
        $classes = [];
        $startIndex = false;
        foreach ($tokens as $index => $token) {

            if(isset($token[0]) && $token[0] == T_CLASS) {
//                dd($token);
                $startIndex = $index;
                $useTokens = array_slice($tokens, $startIndex, 10);
                dd($useTokens);
            }



            /*if(isset($token[1]) && $token[1] == 'use') {
                $startIndex = $index;
                //dd(array_slice($tokens, $index, 15));
//                dd($token);
            }
            if($startIndex && $token == ';') {
//                dd($token);
                $useTokens = array_slice($tokens, $startIndex + 2, $index - $startIndex - 2);
                $use = $this->joinTokens($useTokens);
                $classes[] = $use;
//                dd($use);
                $startIndex = false;
            }*/
        }
        return $classes;
    }

    private function extractUse($tokens) {
        $classes = [];
        $startIndex = false;
        foreach ($tokens as $index => $token) {
            if(isset($token[1]) && $token[1] == 'use') {
                $startIndex = $index;
                //dd(array_slice($tokens, $index, 15));
//                dd($token);
            }
            if($startIndex && $token == ';') {
//                dd($token);
                $useTokens = array_slice($tokens, $startIndex + 2, $index - $startIndex - 2);
                $use = $this->joinTokens($useTokens);
                $classes[] = $use;
//                dd($use);
                $startIndex = false;
            }
        }
        return $classes;
    }

    private function joinTokens($useTokens) {
        $res = '';
        foreach ($useTokens as $token) {
            if(is_string($token)) {
                $res .= $token;
            } elseif(is_array($token)) {
                $res .= $token[1];
            }
        }
        return $res;
    }

    /**
     * @param string $directoryPath
     * @param array $exlcudes
     * @return array | \SplFileInfo[]
     */
    private function getFiles(string $directoryPath, array $exlcudes = [])
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
            if($ext != 'php') {
                continue;
            }
            //dd($ext);
            $path = str_replace($directoryPath, '', $path);
            
            /*if ($this->matchExclude($path, $exlcudes)) {
                continue;
            }*/
            $files[] = $info;
        }
        return $files;
    }
}
