<?php

namespace ZnTool\Package\Commands;

use Illuminate\Support\Collection;
use ZnCore\Base\Legacy\Yii\Helpers\ArrayHelper;
use ZnCore\Domain\Helpers\EntityHelper;
use ZnTool\Package\Domain\Entities\PackageEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitBranchCheckoutToRootCommand extends BaseCommand
{

    protected static $defaultName = 'package:git:branch-checkout-to-root';

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
        $totalCollection = $this->displayProgress($collection, $input, $output);

        $output->writeln('');
        $output->writeln('<fg=yellow>Total:</>');
        $output->writeln('');
        $output->writeln('<fg=yellow>' . implode("\n", EntityHelper::getColumn($totalCollection, 'id')) . '</>');
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
            $branches = $this->gitService->branches($packageEntity);
            $branch = $this->gitService->branch($packageEntity);
            $rootBranch = $this->gitService->getRootBranch($packageEntity);
            if($branch == $rootBranch) {

            } else {
                $output->write(" checkout $rootBranch ... ");
                $this->gitService->checkout($packageEntity, $rootBranch);
                $totalCollection->add($packageEntity);
            }
            $output->writeln("<fg=green>OK</>");
        }
        return $totalCollection;
    }
}
