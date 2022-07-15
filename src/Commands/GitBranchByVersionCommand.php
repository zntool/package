<?php

namespace ZnTool\Package\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Collection\Libs\Collection;
use ZnDomain\Entity\Helpers\CollectionHelper;
use ZnLib\Console\Symfony4\Libs\Command;
use ZnLib\Console\Symfony4\Question\ChoiceQuestion;
use ZnTool\Package\Domain\Entities\PackageEntity;

class GitBranchByVersionCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:branch-by-version';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># Packages branch-by-version</>');
        $collection = $this->packageService->findAll();
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

    private function hasChanges(Enumerable $collection): Enumerable
    {
        $totalCollection = new Collection();
        foreach ($collection as $packageEntity) {
            $hasChanges = $this->gitService->isHasChanges($packageEntity);
            if ($hasChanges) {
                $totalCollection->add($packageEntity);
            }
        }
        return $totalCollection;
    }

    private function displayProgress(Enumerable $collection, InputInterface $input, OutputInterface $output): Enumerable
    {
        /** @var PackageEntity[] | Enumerable $collection */
        /** @var PackageEntity[] | Enumerable $totalCollection */
        $totalCollection = new Collection();

        $targetVersion = $_ENV['ZN_VERSION'] ?? '0.x';
        $fastCommands = [];
        foreach ($collection as $packageEntity) {
            $packageId = $packageEntity->getId();
            $output->write(" <fg=white>$packageId ... ");
            $rootBranch = $this->gitService->getRootBranch($packageEntity);
            $currentBranch = $this->gitService->branch($packageEntity);
            $hasVersionBranch = $this->gitService->isHasBranch($packageEntity, $targetVersion);
            if ($currentBranch == $targetVersion) {
                $output->writeln("<fg=green> OK</>");
            } elseif ($hasVersionBranch) {
                $totalCollection->add($packageEntity);
                $output->writeln("<fg=yellow> Need checkout</>");
            } else {
                $output->writeln("<fg=yellow> No branch</>");
                $totalCollection->add($packageEntity);
            }
        }

        if($totalCollection->isEmpty()) {
            $output->writeln("");
            $output->writeln("<fg=green>All packages already versioned!</>");
            $output->writeln("");
            return $totalCollection;
        }

        $output->writeln('');
        $question = new ChoiceQuestion(
            'Select packages for push:',
            CollectionHelper::getColumn($totalCollection, 'id'),
            'a'
        );
        $question->setMultiselect(true);
        $selectedPackages = $this->getHelper('question')->ask($input, $output, $question);
        $output->writeln('');

        foreach ($totalCollection as $packageEntity) {
            $packageId = $packageEntity->getId();
            if (in_array($packageId, $selectedPackages)) {

                $output->writeln("");
                $output->writeln("<fg=white>$packageId</>");

                $rootBranch = $this->gitService->getRootBranch($packageEntity);
                $currentBranch = $this->gitService->branch($packageEntity);
                $hasVersionBranch = $this->gitService->isHasBranch($packageEntity, $targetVersion);

                if ($currentBranch != $targetVersion) {
                    if (!$hasVersionBranch) {
                        $output->write("  fetch $targetVersion ... ");
                        try {
                            $ee = @$this->gitService->fetch($packageEntity, $targetVersion);
                        } catch (\Throwable $e) {
                            $ee = false;
                        }
                        if ($ee) {
                            $output->writeln("  <fg=green>OK</>");
                        } else {
                            $output->writeln("  <fg=yellow>Not found in remote</>");
                        }
                    }
                }
                $hasVersionBranch = $this->gitService->isHasBranch($packageEntity, $targetVersion);

                if ($currentBranch == $targetVersion) {
                    $output->writeln("  <fg=green>OK</>");
                } elseif ($hasVersionBranch) {
                    $output->write("checkout $targetVersion ... ");
                    $this->gitService->checkout($packageEntity, $targetVersion);
                    $output->writeln("<fg=green>OK</>");
                } else {
                    if ($currentBranch != $rootBranch) {
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

                    $cmd = new Command();
                    $cmd
                        ->add("cd {$packageEntity->getDirectory()}")
                        ->add("git push --set-upstream origin $targetVersion");
                    $fastCommands[] = $cmd->toString();
                }
            }
        }

        if ($fastCommands) {
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
