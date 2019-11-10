<?php namespace tests\app\controllers\admin;

use tests\BaseUnitTest;
use app\libraries\Core;
use app\libraries\response\WebResponse;
use app\exceptions\NotEnabledException;
use app\libraries\response\JsonResponse;
use app\controllers\admin\LibraryManageController;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Library\InMemoryLibraryGateway;

class LibraryManageControllerTester extends BaseUnitTest {

    /** @var Core */
    protected $core;

    /** @var InMemoryLibraryGateway */
    protected $gateway;

    /** @var string */
    protected $location;

    /** @var LibraryManageController */
    protected $controller;

    protected function createConfigWithLibrary(bool $enabled = true) {
        $this->location = 'library location';
        $this->core = $this->createMockCore([
            'homework_library_enable' => $enabled,
            'homework_library_location' => $this->location
        ]);
    }

    public function setUp(): void {
        parent::setUp();

        $this->gateway = new InMemoryLibraryGateway();

        LibraryGatewayFactory::setInstance($this->gateway);

        $this->createConfigWithLibrary();

        $this->controller = new LibraryManageController($this->core);
    }

    /** @test */
    public function testItShowsTheLibraryManagePage() {
        $response = $this->controller->showLibraryManagePage()->web_response;

        $this->assertInstanceOf(WebResponse::class, $response);
        $this->assertEquals([
            'admin', 'LibraryManager'
        ], $response->view_class);
        $this->assertEquals('showLibraryManager', $response->view_function);
    }

    /** @test */
    public function testAjaxUploadLibraryFromZipFail() {
        $_FILES['zip'] = [
            'invalid' => 'zip file array'
        ];

        $response = $this->controller->ajaxUploadLibraryFromZip()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'fail',
            'message' => 'A file must be provided.'
        ], $response->json);
        $this->assertCount(0, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromZipSuccess() {
        $_FILES['zip'] = [
            'name' => 'lib.zip',
            'tmp_name' => 'We all make mistakes in the heat of passion, jimbo.'
        ];

        $response = $this->controller->ajaxUploadLibraryFromZip()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'success',
            'data' => 'Successfully installed new library: lib'
        ], $response->json);
        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromGitSuccess() {
        $_POST['git_url'] = 'https://github.com/Submitty/Submitty.git';
        $response = $this->controller->ajaxUploadLibraryFromGit()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'success',
            'data' => 'Successfully cloned https://github.com/Submitty/Submitty.git.'
        ], $response->json);
        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromGitFail() {
        $_POST['git_url'] = 'my first git url. BABY SHARK DO DO DO DO DO';

        $response = $this->controller->ajaxUploadLibraryFromGit()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'fail',
            'message' => 'The git url is not of the right format.'
        ], $response->json);
        $this->assertCount(0, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testItThrowsNotEnabledException() {
        $this->expectException(NotEnabledException::class);
        $this->createConfigWithLibrary(false);
        $this->controller = new LibraryManageController($this->core);
    }

    /** @test */
    public function testItGetsLibrariesWhenThereAreNone() {
        $response = $this->controller->ajaxGetLibraryList()->json_response;

        $this->assertEquals([
            'status' => 'success',
            'data' => []
        ], $response->json);
    }

    /** @test */
    public function testItGetsAllLibraries() {
        $this->gateway->addLibrary(new LibraryEntity('name', $this->location));

        $response = $this->controller->ajaxGetLibraryList()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'success',
            'data' => ['name']
        ], $response->json);
    }

    /** @test */
    public function testItRemovesALibrary() {
        $this->gateway->addLibrary(new LibraryEntity('name', $this->location));

        $response = $this->controller->ajaxRemoveLibrary('name')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'success',
            'data' => 'Successfully removed library \'name\''
        ], $response->json);
        $this->assertEquals([], $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testItDoesntRemoveLibrariesItsNotSupposedTo() {
        $this->gateway->addLibrary(new LibraryEntity('name', $this->location));

        $response = $this->controller->ajaxRemoveLibrary('different name')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'success',
            'data' => 'Successfully removed library \'different name\''
        ], $response->json);
        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testItDoesntRemoveInvalidName() {
        $response = $this->controller->ajaxRemoveLibrary('')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'status' => 'fail',
            'message' => 'You must specify the library to remove.'
        ], $response->json);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $_FILES = [];
        $_POST = [];
    }
}
