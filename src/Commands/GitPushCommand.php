<?php

namespace ZnTool\Package\Commands;

use Illuminate\Support\Collection;
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
        $output->writeln('');
        if ($totalCollection->count() == 0) {
            $output->writeln('<fg=magenta>All packages already up-to-date!</>');
            $output->writeln('');
            return 0;
        }
        $this->displayTotal($totalCollection, $input, $output);
        $output->writeln('');
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

            if($flags['hasChangesForPush'] || $flags['hasChangesForCommit']) {
                if($flags['hasChangesForPush']) {
                    $output->write("<fg=yellow>PUSH</> ");
                    $totalCollection->add($packageEntity);
                }
                if($flags['hasChangesForCommit']) {
                    $output->write("<fg=yellow>MODIFY</> ");
                }
            } else {
                $output->write("<fg=green>OK</> ");
            }
            $output->writeln("");

            /*dd($packageEntity->getId(), $isActual);

            $output->write(" $packageId ... ");
            $result = $this->gitService->pushPackage($packageEntity);
            if ($result == 'Already up to date.') {
                $result = "<fg=green>{$result}</>";
            } else {
                $totalCollection->add($packageEntity);
            }
            $output->writeln($result);*/
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
