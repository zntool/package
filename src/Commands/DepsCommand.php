<?php

namespace ZnTool\Package\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Collection\Libs\Collection;
use ZnDomain\Entity\Helpers\CollectionHelper;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;
use ZnTool\Package\Domain\Libs\Deps\DepsExtractor;
use ZnTool\Package\Domain\Services\DependencyService;

class DepsCommand extends BaseCommand
{

    protected static $defaultName = 'package:code:deps';

    protected function configure()
    {
//        $this->addArgument('channel', InputArgument::OPTIONAL);
        $this->addOption(
            'withDetail',
            null,
            InputOption::VALUE_OPTIONAL,
            '',
            false
        );
        $this->addOption(
            'withResolved',
            null,
            InputOption::VALUE_OPTIONAL,
            '',
            false
        );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages deps</>');

        /** @var PackageEntity[] $collection */
        $collection = $this->packageService->findAll();

        $selectedCollection = $this->selectPackages($collection, $input, $output);

        $dependencyService = new DependencyService();
        $packageClasses = $dependencyService->findDependency($selectedCollection);

        $withDetail = $input->getOption('withDetail');
        $withResolved = $input->getOption('withResolved');

        foreach ($packageClasses as $packageId => $classes) {
            $output->writeln('');
            $output->writeln("<fg=blue># {$packageId}</>");
//            $output->writeln('');
            foreach ($classes as $class) {
                if(!$withResolved && !$class['isNeed']) {
                    continue;
                }
                if ($class['isNeed']) {
                    $output->writeln(" - <fg=red>{$class['fullName']}</>");
                } else {
                    $output->writeln(" - <fg=green>{$class['fullName']}</>");
                }
                if($withDetail) {
                    foreach ($class['classes'] as $packageClass) {
                        $output->writeln("    - <fg=white>{$packageClass}</>");
                    }
                }

//                $output->writeln("");
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
