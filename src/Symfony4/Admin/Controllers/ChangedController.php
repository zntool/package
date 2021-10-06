<?php

namespace ZnTool\Package\Symfony4\Admin\Controllers;

use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use ZnCore\Domain\Exceptions\UnprocessibleEntityException;
use ZnCore\Domain\Helpers\EntityHelper;
use ZnLib\Rpc\Domain\Enums\RpcErrorCodeEnum;
use ZnLib\Web\Symfony4\MicroApp\BaseWebController;
use ZnLib\Web\Symfony4\MicroApp\Interfaces\ControllerAccessInterface;
use ZnLib\Web\Symfony4\MicroApp\Libs\FormManager;
use ZnLib\Web\Symfony4\MicroApp\Libs\layoutManager;
use ZnSandbox\Sandbox\Bundle\Domain\Interfaces\Services\BundleServiceInterface;
use ZnTool\Package\Domain\Helpers\TableMapperHelper;
use ZnTool\Package\Domain\Interfaces\Services\GitServiceInterface;
use ZnTool\Package\Domain\Interfaces\Services\PackageServiceInterface;
use ZnTool\Package\Domain\Repositories\Eloquent\SchemaRepository;
use ZnSandbox\Sandbox\Rpc\Domain\Entities\MethodEntity;
use ZnSandbox\Sandbox\Rpc\Domain\Interfaces\Services\MethodServiceInterface;
use ZnTool\Package\Domain\Entities\FavoriteEntity;
use ZnTool\Package\Domain\Helpers\FavoriteHelper;
use ZnTool\Package\Domain\Interfaces\Services\ClientServiceInterface;
use ZnTool\Package\Domain\Interfaces\Services\FavoriteServiceInterface;
use ZnTool\Package\Symfony4\Admin\Forms\ImportForm;
use ZnTool\Package\Symfony4\Admin\Forms\RequestForm;
use ZnUser\Rbac\Domain\Enums\Rbac\ExtraPermissionEnum;

class ChangedController extends BaseWebController implements ControllerAccessInterface
{

    protected $viewsDir = __DIR__ . '/../views/changed';
    protected $baseUri = '/package/changed';
//    protected $formClass = RequestForm::class;
    private $layoutManager;
    private $packageService;
    private $gitService;

    public function __construct(
        FormManager $formManager,
        layoutManager $layoutManager,
        UrlGeneratorInterface $urlGenerator,
        PackageServiceInterface $packageService,
        GitServiceInterface $gitService
    )
    {
        $this->setFormManager($formManager);
        $this->setLayoutManager($layoutManager);
        $this->setUrlGenerator($urlGenerator);
        $this->setBaseRoute('package/changed');

        $this->packageService = $packageService;
        $this->gitService = $gitService;

        $this->getLayoutManager()->addBreadcrumb('Changed', 'package/changed');
    }

    /*public function with(): array
    {
        return [
            'application',
        ];
    }*/

    public function access(): array
    {
        return [
            'index' => [
                ExtraPermissionEnum::ADMIN_ONLY,
            ],
            'view' => [
                ExtraPermissionEnum::ADMIN_ONLY,
            ],
        ];
    }

    public function index(Request $request): Response
    {
        //$bundleCollection = $this->->all();
        $packageCollection = $this->packageService->all();
        //dd($packageCollection);
        return $this->render('index', [
            'packageCollection' => $packageCollection,
        ]);
    }

    public function view(Request $request): Response
    {
        $id = $request->query->get('id');
        $bundleEntity = $this->bundleService->oneById($id);
//dd($bundleEntity);

        if($bundleEntity->getDomain()) {

        }
        $tableCollection = $this->schemaRepository->allTables();
        $tableList = EntityHelper::getColumn($tableCollection, 'name');
        $entityNames = [];
        foreach ($tableList as $tableName) {
            $bundleName = TableMapperHelper::extractDomainNameFromTable($tableName);
            if ($bundleEntity->getDomain()->getName() == $bundleName) {
                $entityNames[] = TableMapperHelper::extractEntityNameFromTable($tableName);
            }
        }
        dd($entityNames);

    }
}
