<?php

namespace ZnTool\Package\Commands;

use Illuminate\Support\Collection;
use ZnCore\Domain\Helpers\EntityHelper;
use ZnTool\Package\Domain\Entities\PackageEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitBranchByVersionCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:branch-by-version';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages git pull</>');
        $collection = $this->packageService->all();
        $output->writeln('');
        if ($collection->count() == 0) {
            $output->writeln('<fg=magenta>Not found packages!</>');
            $output->writeln('');
            return 0;
        }
        $hasChangesCollection = $this->hasChanges($collection, $input, $output);
        if ($hasChangesCollection->count() > 0) {
            $names = EntityHelper::getColumn($hasChangesCollection, 'id');
            $output->writeln('<fg=magenta>Has changes in packages!</>');
            $output->writeln($names);
            return 0;
        }
        $totalCollection = $this->displayProgress($collection, $input, $output);
        return 0;
    }

    private function hasChanges(Collection $collection): Collection {
        $totalCollection = new Collection;
        foreach ($collection as $packageEntity) {
            $hasChanges = $this->gitService->isHasChanges($packageEntity);
            if($hasChanges) {
                $totalCollection->add($packageEntity);
            }
        }
        return $totalCollection;
    }

    private function displayProgress(Collection $collection, InputInterface $input, OutputInterface $output): Collection
    {
        /** @var PackageEntity[] | Collection $collection */
        /** @var PackageEntity[] | Collection $totalCollection */
        $totalCollection = new Collection;

        $targetVersion = '0.x';

        $fastCommands = [];

        foreach ($collection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $output->writeln(" <fg=white># $packageId");
            $output->writeln("");

            $hasChanges = $this->gitService->isHasChanges($packageEntity);
            if(!$hasChanges) {
                $rootBranch = $this->gitService->getRootBranch($packageEntity);
                $currentBranch = $this->gitService->branch($packageEntity);
                $hasVersionBranch = $this->gitService->isHasBranch($packageEntity, $targetVersion);

                /*try {
                    $output->writeln(" checkout $rootBranch ... ");
                    $this->gitService->checkout($packageEntity, $rootBranch);
                    $output->writeln(" remove $targetVersion ... ");
                    $this->gitService->removeBranch($packageEntity, $targetVersion);
                } catch (\Throwable $e) {

                }
                continue;*/

                if($currentBranch == $targetVersion) {
                    //continue;
                } elseif($hasVersionBranch) {
                    $output->writeln(" checkout $targetVersion ... ");
                    $this->gitService->checkout($packageEntity, $targetVersion);
                } else {
                    /*if($currentBranch != $rootBranch) {
                        $output->writeln(" checkout $rootBranch ... ");
                        $this->gitService->checkout($packageEntity, $rootBranch);
                    }

                    $output->writeln(" createBranch $targetVersion ... ");
                    $createBranch = $this->gitService->createBranch($packageEntity, $targetVersion);

                    $output->writeln(" checkout $targetVersion ... ");
                    $this->gitService->checkout($packageEntity, $targetVersion);*/

//                    $output->writeln(" push $targetVersion ... ");
//                    $this->gitService->push($packageEntity, $targetVersion);

                    $fastCommands[] = "cd {$packageEntity->getDirectory()} && git push --set-upstream origin $targetVersion";
                    $output->writeln("<fg=green> OK</>");
                }

            } else {
                $output->write(" <fg=red>Has changes</> ");
            }



//            $result = $this->gitService->branches($packageEntity);
//            dd($result);
//            $this->gitService->branches($packageEntity);
//            $output->writeln($result);
        }

        $output->writeln('');
        $output->writeln('<fg=yellow>Fast command:</>');
        $output->writeln('');

        foreach ($fastCommands as $fastCommand) {
            $output->writeln($fastCommand);
        }

        return $totalCollection;
    }
}
