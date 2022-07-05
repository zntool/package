<?php

namespace ZnTool\Package\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Domain\Collection\Interfaces\Enumerable;
use ZnCore\Domain\Collection\Libs\Collection;
use ZnTool\Package\Domain\Entities\PackageEntity;

class GitPullCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:pull';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages git pull</>');
        $collection = $this->packageService->findAll();
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

    private function displayProgress(Enumerable $collection, InputInterface $input, OutputInterface $output): Enumerable
    {
        /** @var PackageEntity[] | Enumerable $collection */
        /** @var PackageEntity[] | Enumerable $totalCollection */
        $totalCollection = new Collection;
        foreach ($collection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $output->write(" $packageId ... ");
            $result = $this->gitService->pullPackage($packageEntity);
            if ($result == 'Already up to date.') {
                $result = "<fg=green>{$result}</>";
            } else {
                $totalCollection->add($packageEntity);
            }
            $output->writeln($result);
        }
        return $totalCollection;
    }

    private function displayTotal(Enumerable $totalCollection, InputInterface $input, OutputInterface $output)
    {
        /** @var PackageEntity[] | Enumerable $totalCollection */
        $output->writeln('<fg=yellow>Updated packages!</>');
        $output->writeln('');
        foreach ($totalCollection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $output->writeln("<fg=yellow> {$packageId}</>");
        }
    }
}
