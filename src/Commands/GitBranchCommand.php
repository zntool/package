<?php

namespace ZnTool\Package\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Base\Arr\Helpers\ArrayHelper;
use ZnCore\Domain\Collection\Interfaces\Enumerable;
use ZnCore\Domain\Collection\Libs\Collection;
use ZnTool\Package\Domain\Entities\PackageEntity;

class GitBranchCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:branch';

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
            $branches = $this->gitService->branches($packageEntity);
            $branch = $this->gitService->branch($packageEntity);
            ArrayHelper::removeValue($branches, $branch);

            $branchesString = $branches ? ' (' . implode(', ', $branches) . ')' : '';
            $output->writeln($branch . $branchesString);
        }
        return $totalCollection;
    }
}
