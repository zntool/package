<?php

namespace ZnTool\Package\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Collection\Libs\Collection;
use ZnTool\Package\Domain\Entities\PackageEntity;

class GitVersionCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:version';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages version</>');
        $collection = $this->packageService->findAll();
        $output->writeln('');
        if ($collection->count() == 0) {
            $output->writeln('<fg=magenta>Not found packages!</>');
            $output->writeln('');
            return 0;
        }
        $totalCollection = $this->displayProgress($collection, $input, $output);
        $output->writeln('');
        return 0;
    }

    private function displayProgress(Enumerable $collection, InputInterface $input, OutputInterface $output): Enumerable
    {
        /** @var PackageEntity[] | Enumerable $collection */
        /** @var PackageEntity[] | Enumerable $totalCollection */
        $totalCollection = new Collection();
        foreach ($collection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $output->write(" $packageId ... ");
            $lastVersion = $this->gitService->lastVersion($packageEntity);
            if ($lastVersion) {
                $output->writeln("<fg=green>{$lastVersion}</>");
            } else {
                $output->writeln("<fg=yellow>dev-master</>");
                $totalCollection->add($packageEntity);
            }
        }
        return $totalCollection;
    }
}
