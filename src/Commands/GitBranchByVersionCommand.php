<?php

namespace ZnTool\Package\Commands;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Domain\Helpers\EntityHelper;
use ZnLib\Console\Symfony4\Libs\Command;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;

class GitBranchByVersionCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:branch-by-version';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages branch-by-version</>');
        $collection = $this->packageService->all();
        $output->writeln('');
        if ($collection->count() == 0) {
            $output->writeln('<fg=magenta>Not found packages!</>');
            $output->writeln('');
            return 0;
        }
        /*$hasChangesCollection = $this->hasChanges($collection, $input, $output);
        if ($hasChangesCollection->count() > 0) {
            $names = EntityHelper::getColumn($hasChangesCollection, 'id');
            $output->writeln('<fg=magenta>Has changes in packages!</>');
            $output->writeln($names);
            return 0;
        }*/
        $totalCollection = $this->displayProgress($collection, $input, $output);
        return 0;
    }

    private function hasChanges(Collection $collection): Collection
    {
        $totalCollection = new Collection;
        foreach ($collection as $packageEntity) {
            $hasChanges = $this->gitService->isHasChanges($packageEntity);
            if ($hasChanges) {
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
            $output->write(" <fg=white>$packageId ... ");
//            $output->writeln("");

            $rootBranch = $this->gitService->getRootBranch($packageEntity);
            $currentBranch = $this->gitService->branch($packageEntity);
            $hasVersionBranch = $this->gitService->isHasBranch($packageEntity, $targetVersion);

            /*if(in_array('1.0.x', $this->gitService->branches($packageEntity))) {
                try {
                    $output->writeln(" checkout $rootBranch ... ");
                    $this->gitService->checkout($packageEntity, $rootBranch);
                    $output->writeln(" remove 1.0.x ... ");
                    $this->gitService->removeBranch($packageEntity, '1.0.x');
                } catch (\Throwable $e) {

                }
            }*/

            if ($currentBranch == $targetVersion) {
                $output->writeln("<fg=green> OK</>");
                //continue;
            } elseif ($hasVersionBranch) {
                $output->writeln(" checkout $targetVersion ... ");
                $this->gitService->checkout($packageEntity, $targetVersion);
                $totalCollection->add($packageEntity);
                $output->writeln("<fg=yellow> Need checkout</>");
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

                /*$cmd = new Command();
                $cmd
                    ->add("cd {$packageEntity->getDirectory()}")
                    ->add("git push --set-upstream origin $targetVersion");
                $fastCommands[] = $cmd->toString();*/

//                $fastCommands[] = "cd {$packageEntity->getDirectory()} && git push --set-upstream origin $targetVersion";
                $output->writeln("<fg=yellow> No branch</>");

                $totalCollection->add($packageEntity);
            }


//            $result = $this->gitService->branches($packageEntity);
//            dd($result);
//            $this->gitService->branches($packageEntity);
//            $output->writeln($result);
        }

        $output->writeln('');
        $question = new ChoiceQuestion(
            'Select packages for push:',
            EntityHelper::getColumn($totalCollection, 'id'),
            'a'
        );
        $question->setMultiselect(true);
        $selectedPackages = $this->getHelper('question')->ask($input, $output, $question);
        $output->writeln('');

        foreach ($totalCollection as $packageEntity) {
            $packageId = $packageEntity->getId();
            if(in_array($packageId, $selectedPackages)) {
                $output->writeln("");
                $output->writeln("<fg=white>$packageId");

                $rootBranch = $this->gitService->getRootBranch($packageEntity);
                $currentBranch = $this->gitService->branch($packageEntity);
                $hasVersionBranch = $this->gitService->isHasBranch($packageEntity, $targetVersion);

                if ($currentBranch == $targetVersion) {
                    $output->writeln("  <fg=green>OK</>");
                } elseif ($hasVersionBranch) {
                    $output->writeln("checkout $targetVersion ... ");
                    $this->gitService->checkout($packageEntity, $targetVersion);
                    $output->writeln("<fg=green>OK</>");
                } else {
                    if($currentBranch != $rootBranch) {
                        $output->write("  checkout $rootBranch ... ");
                        $this->gitService->checkout($packageEntity, $rootBranch);
                        $output->writeln("<fg=green>OK</>");
                    }

                    $output->write("  create branch $targetVersion ... ");
                    $createBranch = $this->gitService->createBranch($packageEntity, $targetVersion);
                    $output->writeln("<fg=green>OK</>");

                    $output->write("  checkout $targetVersion ... ");
                    $this->gitService->checkout($packageEntity, $targetVersion);
                    $output->writeln("<fg=green>OK</>");

//                    $output->write("  push $targetVersion ... ");
//                    $this->gitService->push($packageEntity, $targetVersion);
//                    $output->writeln("<fg=green>OK</>");

                    $cmd = new Command();
                    $cmd
                        ->add("cd {$packageEntity->getDirectory()}")
                        ->add("git push --set-upstream origin $targetVersion");
                    $fastCommands[] = $cmd->toString();

//                $fastCommands[] = "cd {$packageEntity->getDirectory()} && git push --set-upstream origin $targetVersion";
//                    $output->writeln("<fg=yellow> No branch</>");

//                    $totalCollection->add($packageEntity);
//                    $output->writeln("  <fg=green>OK</>");
                }


//            $result = $this->gitService->branches($packageEntity);
//            dd($result);
//            $this->gitService->branches($packageEntity);
//            $output->writeln($result);
            }


        }

        if($fastCommands) {
            $output->writeln('');
            $output->writeln('<fg=yellow>Fast command:</>');
            $output->writeln('');
            foreach ($fastCommands as $fastCommand) {
                $output->writeln($fastCommand);
            }
            $output->writeln('');
        }

        return $totalCollection;
    }
}
