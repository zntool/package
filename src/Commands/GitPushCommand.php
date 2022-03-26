<?php

namespace ZnTool\Package\Commands;

use Illuminate\Support\Collection;
use ZnCore\Base\Legacy\Yii\Helpers\ArrayHelper;
use ZnCore\Domain\Helpers\EntityHelper;
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
        $collection = $this->packageService->all();
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

        $totalArray = EntityHelper::indexingCollection($totalCollection, 'id');

        $output->writeln('');
        $question = new ChoiceQuestion(
            'Select packages for push:',
            EntityHelper::getColumn($totalCollection, 'id'),
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
                if($flags['needPush']) {
                    $output->write("<fg=yellow>PUSH {$flags['aheadCommitCount']}</> ");
                    $totalCollection->add($packageEntity);
                }
                if($flags['needCommit']) {
                    $output->write("<fg=yellow>MODIFY</> ");
                }
//                dump($status);
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
