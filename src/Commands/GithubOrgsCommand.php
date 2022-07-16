<?php

namespace ZnTool\Package\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Collection\Helpers\CollectionHelper;
use ZnCore\Collection\Libs\Collection;
use ZnLib\Components\Http\Enums\HttpMethodEnum;
use ZnLib\Components\Store\Helpers\StoreHelper;
use ZnTool\Package\Domain\Entities\GroupEntity;
use ZnTool\Package\Domain\Entities\PackageEntity;

class GithubOrgsCommand extends BaseCommand
{

    protected static $defaultName = 'package:github:orgs';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white># github orgs</>');

        $url = 'https://api.github.com/user/orgs?access_token=' . $_ENV['GITHUB_TOKEN'];
        $output->writeln('getting groups');
        $collection = $this->sendRequest(HttpMethodEnum::GET, $url);
        $orgs = ArrayHelper::getColumn($collection, 'login');
        $repoCollection = new Collection();
        foreach ($orgs as $orgName) {
            if (strpos($orgName, 'zn') === 0) {
                $url = "https://api.github.com/orgs/{$orgName}/repos";
                $output->writeln('getting packages from: ' . $orgName);
                $repos = $this->sendRequest(HttpMethodEnum::GET, $url);
                $reposList = ArrayHelper::getColumn($repos, 'name');
                $groupEntity = new GroupEntity();
                $groupEntity->name = $orgName;
                $groupEntity->providerName = 'github';
                $orgArr = [
                    'name' => $orgName
                ];
                foreach ($reposList as $repoName) {
                    $packageEntity = new PackageEntity();
                    $packageEntity->setName($repoName);
                    $packageEntity->setGroup($groupEntity);
                    $repoCollection->add($packageEntity);
                }
            }
        }
        $fileName = 'vendor/zntool/dev/src/Package/Domain/Data/package_origin.php';
        $array = CollectionHelper::toArray($repoCollection);
        $array = ArrayHelper::extractItemsWithAttributes($array, ['id', 'name', 'group']);

        StoreHelper::save($fileName, $array);

        $output->writeln('');
        return 0;
    }

    public function sendRequest(string $method, string $url, array $options = []): array
    {
        $client = new Client();
        try {
            $response = $client->request($method, $url, $options);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }
        return json_decode($response->getBody()->getContents());
    }
}
