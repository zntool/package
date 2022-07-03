<?php

namespace ZnTool\Package\Commands;

use ZnCore\Domain\Collection\Libs\Collection;
use ZnCore\Base\Arr\Helpers\ArrayHelper;
use ZnCore\Domain\Entity\Helpers\CollectionHelper;
use ZnCore\Domain\Entity\Helpers\EntityHelper;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitPushCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:push';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages git push</>');
        $collection = $this->packageService->findAll();
        $output->writeln('');
        if ($collection->count() == 0) {
            $output->writeln('<fg=magenta>Not found packages!</>');
            $output->writeln('');
            return 0;
        }
        $totalCollection = $this->displayProgress($collection, $input, $output);

        if($totalCollection->isEmpty()) {
            $output->writeln('');
            $output->writeln('<fg=yellow>No changes</>');
            $output->writeln('');
            return 0;
        }

        $totalArray = CollectionHelper::indexing($totalCollection, 'id');

        $output->writeln('');
        $question = new ChoiceQuestion(
            'Select packages for push:',
            CollectionHelper::getColumn($totalCollection, 'id'),
            'a'
        );
        $question->setMultiselect(true);
        $selectedPackages = $this->getHelper('question')->ask($input, $output, $question);

        foreach ($selectedPackages as $packageId) {
            $packageEntity = $totalArray[$packageId];
            $output->write("$packageId ... ");
            $result = $this->gitService->pushPackage($packageEntity);
            if ($result == 'Already up to date.') {
                $result = "<fg=green>{$result}</>";
            }
            $output->writeln($result);
        }

        return 0;
    }

    private function displayProgress(Collection $collection, InputInterface $input, OutputInterface $output): Collection
    {
        /** @var PackageEntity[] | Collection $collection */
        /** @var PackageEntity[] | Collection $totalCollection */
        $totalCollection = new Collection;
        foreach ($collection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $output->write(" $packageId ... ");
            $status = $this->gitService->status($packageEntity);
            $flags = $status['flags'];

            if($flags['needPush'] || $flags['needCommit']) {
                $actions = [];
                if($flags['needPush']) {
                    $actions[] = "<fg=yellow>PUSH {$status['push']['aheadCommitCount']}</>";
                    $totalCollection->add($packageEntity);
                }
                if($flags['needCommit']) {
                    $actions[] = "<fg=yellow>MODIFY</>";
                }
                $output->write(implode(' <fg=white>|</> ', $actions));
            } else {
                $output->write("<fg=green>OK</> ");
            }
            $output->writeln("");
        }
        return $totalCollection;
    }

    private function displayTotal(Collection $totalCollection, InputInterface $input, OutputInterface $output)
    {
        /** @var PackageEntity[] | Collection $totalCollection */
        $output->writeln('<fg=yellow>Updated packages!</>');
        $output->writeln('');
        foreach ($totalCollection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $output->writeln("<fg=yellow> {$packageId}</>");
        }
    }
}
