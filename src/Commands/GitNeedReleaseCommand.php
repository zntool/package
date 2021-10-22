<?php

namespace ZnTool\Package\Commands;

use Illuminate\Support\Collection;
use ZnCore\Domain\Helpers\EntityHelper;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnLib\Console\Symfony4\Style\SymfonyStyle;
use ZnSandbox\Sandbox\Bundle\Domain\Entities\BundleEntity;
use ZnSandbox\Sandbox\Bundle\Domain\Entities\DomainEntity;
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

        $choices = $this->selectPackages($input, $output, $totalCollection);

//        dd($choices);

        $this->displayTotal($totalCollection, $input, $output, $choices);
        $output->writeln('');
        return 0;
    }

    private function selectPackages(InputInterface $input, OutputInterface $output, Collection $collection)//: DomainEntity
    {
        $packageNames = EntityHelper::getColumn($collection, 'id');
        $io = new SymfonyStyle($input, $output);
        $choices = $io->choiceMulti('Select packages', $packageNames);
        return $choices;
    }

    private function selectPackageVersion(InputInterface $input, OutputInterface $output, array $versionList)//: DomainEntity
    {
//        $packageNames = EntityHelper::getColumn($collection, 'id');
        $io = new SymfonyStyle($input, $output);
        $choices = $io->choice('Select version', $versionList);
        return $choices;
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

    private function displayTotal(Collection $totalCollection, InputInterface $input, OutputInterface $output, array $choices)
    {
        /** @var PackageEntity[] | Collection $totalCollection */
        $output->writeln('<fg=yellow>Need release!</>');
        $output->writeln('');

        $fastCommands = [];
        foreach ($totalCollection as $packageEntity) {
            $packageId = $packageEntity->getId();
            if(in_array($packageId, $choices)) {



                $lastVersion = $this->gitService->lastVersion($packageEntity);

                $version = $lastVersion ? "<fg=blue>{$lastVersion}</>" : "<fg=red>No version</>";
                $output->writeln("<fg=yellow> {$packageId}:{$version}</>");

                $vendorDir = __DIR__ . '/../../../../';
                $dir = realpath($vendorDir) . '/' . $packageId;

                $possibleVersionList = VersionHelper::possibleVersionList($lastVersion);
                //$fastCommands[] = "<fg=yellow> {$packageId}:{$version}</>";

                $choiceVersion = $this->selectPackageVersion($input, $output, array_values($possibleVersionList));

                $fastCommands[] = "cd $dir && git tag 'v$choiceVersion' && git push origin 'v$choiceVersion'";

                //dd($choiceVersion);

                /*foreach ($possibleVersionList as $value) {
                    $fastCommands[] = "cd $dir && && git tag 'v$value' && git push origin 'v$value'";
                }*/
//                $fastCommands[] = "";
            }

        }

        $output->writeln('');
        $output->writeln('<fg=yellow>Fast command:</>');
        $output->writeln('');

        foreach ($fastCommands as $fastCommand) {
            $output->writeln($fastCommand);
        }
    }
}
