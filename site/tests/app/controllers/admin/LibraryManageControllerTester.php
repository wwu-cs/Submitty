<?php namespace tests\app\controllers\admin;


use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\response\Response;
use app\libraries\response\WebResponse;
use tests\BaseUnitTest;
use app\libraries\Core;
use app\exceptions\NotEnabledException;
use app\controllers\admin\LibraryManageController;
use app\libraries\homework\Gateways\Library\InMemoryLibraryGateway;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;

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
        $response = $this->controller->showLibraryManagePage();

        $this->assertInstanceOf(Response::class, $response);
        $webResponse = $response->web_response;
        $this->assertInstanceOf(WebResponse::class, $response->web_response);
        $this->assertEquals([
            'admin', 'LibraryManager'
        ], $webResponse->view_class);
        $this->assertEquals('showLibraryManager', $webResponse->view_function);
    }

    /** @test */
    public function testAjaxUploadLibraryFromZipFail() {
        $_FILES['zip'] = [
            'invalid' => 'zip file array'
        ];

        $response = $this->controller->ajaxUploadLibraryFromZip();
        $this->assertEquals([
            'status' => 'fail',
            'message' => 'A file must be provided.'
        ], $response);
        $this->assertCount(0, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromZipSuccess() {
        $_FILES['zip'] = [
            'name' => 'lib.zip',
            'tmp_name' => 'We all make mistakes in the heat of passion, jimbo.'
        ];

        $response = $this->controller->ajaxUploadLibraryFromZip();

        $this->assertEquals([
            'status' => 'success',
            'data' => 'Successfully installed new library: lib'
        ], $response);
        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromGitSuccess() {
        $_POST['git_url'] = 'https://github.com/Submitty/Submitty.git';
        $response = $this->controller->ajaxUploadLibraryFromGit();

        $this->assertEquals([
            'status' => 'success',
            'data' => 'Successfully cloned https://github.com/Submitty/Submitty.git.'
        ], $response);

        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromGitFail() {
        $_POST['git_url'] = 'my first git url. BABY SHARK DO DO DO DO DO';
        $response = $this->controller->ajaxUploadLibraryFromGit();

        $this->assertEquals([
            'status' => 'fail',
            'message' => 'The git url is not of the right format.'
        ], $response);
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
        $response = $this->controller->ajaxGetLibraryList();

        $this->assertEquals([
            'status' => 'success',
            'data' => []
        ], $response);
    }

    /** @test */
    public function testItGetsAllLibraries() {
        $this->gateway->addLibrary(new LibraryEntity('name', $this->location));

        $response = $this->controller->ajaxGetLibraryList();

        $this->assertEquals([
            'status' => 'success',
            'data' => ['name']
        ], $response);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $_FILES = [];
        $_POST = [];
    }

}
