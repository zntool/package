<?php

namespace ZnTool\Package\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Collection\Libs\Collection;
use ZnCore\Entity\Helpers\CollectionHelper;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Libs\Deps\DepsExtractor;
use ZnTool\Package\Domain\Services\DependencyService;

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

        $selectedCollection = new Collection();
        foreach ($collection as $packageEntity) {
            if (in_array($packageEntity->getId(), $selectedPackages)) {
                $selectedCollection->add($packageEntity);
            }
        }

        $dependencyService = new DependencyService();
        $packageClasses = $dependencyService->findUsedClasses($selectedCollection);

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

}
