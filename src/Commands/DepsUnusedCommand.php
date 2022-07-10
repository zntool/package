<?php

namespace ZnTool\Package\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Collection\Libs\Collection;
use ZnCore\Entity\Helpers\CollectionHelper;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Libs\Deps\DepsExtractor;
use ZnTool\Package\Domain\Services\DependencyService;

class DepsUnusedCommand extends BaseCommand
{

    protected static $defaultName = 'package:code:unused';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages deps</>');

        /** @var PackageEntity[] $collection */
        $collection = $this->packageService->findAll();

        $selectedCollection = $this->selectPackages($collection, $input, $output);

        $dependencyService = new DependencyService();
        $packageClasses = $dependencyService->findUsedClasses($selectedCollection);
        dd($packageClasses);

        foreach ($packageClasses as $packageId => $classes) {
            $output->writeln('');
            $output->writeln("<fg=yellow>{$packageId}</>");
            $output->writeln('');
            foreach ($classes as $class) {
                if ($class['isNeed']) {
                    $output->writeln(" - <fg=red>{$class['fullName']}</>");
                    foreach ($class['classes'] as $packageClass) {
                        $output->writeln("    - <fg=white>{$packageClass}</>");
                    }
                    $output->writeln("");
                } else {
                    $output->writeln(" - <fg=green>{$class['fullName']}</>");
                }
            }
        }

        return Command::SUCCESS;
    }

    private function selectPackages(Enumerable $collection, InputInterface $input, OutputInterface $output): Enumerable
    {
        $output->writeln('');
        $question = new ChoiceQuestion(
            'Select packages:',
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
        return $selectedCollection;
    }
}
