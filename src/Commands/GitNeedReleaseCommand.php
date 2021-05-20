<?php

namespace ZnTool\Package\Commands;

use Illuminate\Support\Collection;
use ZnTool\Package\Domain\Entities\PackageEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnTool\Package\Domain\Enums\StatusEnum;
use ZnTool\Package\Domain\Helpers\VersionHelper;

class GitNeedReleaseCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:need-release';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages need release</>');
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
            $output->writeln('<fg=magenta>All packages released!</>');
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
            $isNeedRelease = $this->gitService->isNeedRelease($packageEntity);
            if ($isNeedRelease) {
                $output->writeln("<fg=yellow>Need release</>");
                $totalCollection->add($packageEntity);
            } else {
                $output->writeln("<fg=green>OK</>");
            }
        }
        return $totalCollection;
    }

    private function displayTotal(Collection $totalCollection, InputInterface $input, OutputInterface $output)
    {
        /** @var PackageEntity[] | Collection $totalCollection */
        $output->writeln('<fg=yellow>Need release!</>');
        $output->writeln('');

        $fastCommands = [];
        foreach ($totalCollection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $lastVersion = $this->gitService->lastVersion($packageEntity);

            $version = $lastVersion ? "<fg=blue>{$lastVersion}</>" : "<fg=red>No version</>";
            $output->writeln("<fg=yellow> {$packageId}:{$version}</>");

            $vendorDir = __DIR__ . '/../../../../';
            $dir = realpath($vendorDir) . '/' . $packageId;

            $possibleVersionList = VersionHelper::possibleVersionList($lastVersion);
            $fastCommands[] = "<fg=yellow> {$packageId}:{$version}</>";
            foreach ($possibleVersionList as $value) {
                $fastCommands[] = "cd $dir && git tag 'v$value' && git push origin 'v$value'";
            }
            $fastCommands[] = "";
        }

        $output->writeln('');
        $output->writeln('<fg=yellow>Fast command:</>');
        $output->writeln('');

        foreach ($fastCommands as $fastCommand) {
            $output->writeln($fastCommand);
        }
    }
}
