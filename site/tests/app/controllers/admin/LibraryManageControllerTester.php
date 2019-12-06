<?php

namespace tests\app\controllers\admin;

use DateTime;
use app\models\User;
use tests\BaseUnitTest;
use app\libraries\Core;
use app\libraries\Utils;
use app\libraries\FileUtils;
use app\exceptions\NotEnabledException;
use app\libraries\response\WebResponse;
use app\libraries\response\JsonResponse;
use app\exceptions\AuthorizationException;
use app\controllers\admin\LibraryManageController;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Library\InMemoryLibraryGateway;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;
use app\libraries\homework\Gateways\Metadata\InMemoryMetadataGateway;

class LibraryManageControllerTester extends BaseUnitTest {

    /** @var Core */
    protected $core;

    /** @var InMemoryLibraryGateway */
    protected $gateway;

    /** @var InMemoryMetadataGateway */
    protected $metadata;

    /** @var string */
    protected $location;

    /** @var LibraryManageController */
    protected $controller;

    protected function createConfigWithLibrary(bool $enabled = true, string $location = 'library location', bool $allow_access = true) {
        $this->location = $location;
        $this->core = $this->createMockCore([
            'homework_library_enable' => $enabled,
            'homework_library_location' => $this->location
        ], ['can_access' => $allow_access]);
        $this->controller = new LibraryManageController($this->core);
    }

    public function setUp(): void {
        parent::setUp();

        $this->gateway = new InMemoryLibraryGateway();
        $this->metadata = new InMemoryMetadataGateway($this->gateway);

        LibraryGatewayFactory::setInstance($this->gateway);
        MetadataGatewayFactory::setInstance($this->metadata);

        $this->createConfigWithLibrary();
    }

    /** @test */
    public function testItShowsTheLibraryManagePage() {
        $response = $this->controller->showLibraryManagePage()->web_response;

        $this->assertInstanceOf(WebResponse::class, $response);
        $this->assertEquals(
            [
                'admin',
                'LibraryManager',
            ],
            $response->view_class
        );
        $this->assertEquals('showLibraryManager', $response->view_function);
    }

    /** @test */
    public function testAjaxUploadLibraryFromZipFail() {
        $_FILES['zip'] = [
            'invalid' => 'zip file array',
        ];

        $response = $this->controller->ajaxUploadLibraryFromZip()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status'  => 'fail',
                'message' => 'A file must be provided.',
            ],
            $response->json
        );
        $this->assertCount(0, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromZipSuccess() {
        $_FILES['zip'] = [
            'name'     => 'lib.zip',
            'tmp_name' => 'We all make mistakes in the heat of passion, jimbo.',
        ];

        $_POST['name'] = 'a special library';

        $response = $this->controller->ajaxUploadLibraryFromZip()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status' => 'success',
                'data'   => 'Successfully installed new library: a special library',
            ],
            $response->json
        );
        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromGitSuccess() {
        $_POST['git_url'] = 'https://github.com/Submitty/Submitty.git';
        $response = $this->controller->ajaxUploadLibraryFromGit()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status' => 'success',
                'data'   => 'Successfully cloned https://github.com/Submitty/Submitty.git.',
            ],
            $response->json
        );
        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testAjaxUploadLibraryFromGitFail() {
        $_POST['git_url'] = 'my first git url. BABY SHARK DO DO DO DO DO';

        $response = $this->controller->ajaxUploadLibraryFromGit()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status'  => 'fail',
                'message' => 'The git url is not of the right format.',
            ],
            $response->json
        );
        $this->assertCount(0, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testItThrowsNotEnabledException() {
        $this->expectException(NotEnabledException::class);
        $this->createConfigWithLibrary(false);
    }

    /** @test */
    public function testItThrowsAuthorizationException() {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to access this route');
        $this->createConfigWithLibrary(true, 'library location', false);
    }

    /** @test */
    public function testItDeletesAFileAfterHandling() {
        $location = FileUtils::joinPaths(sys_get_temp_dir(), Utils::generateRandomString());
        $this->createConfigWithLibrary(true, $location);
        $this->assertTrue(FileUtils::createDir($this->location));
        $file = $this->createFile('tmp.txt');

        $_FILES['zip'] = [
            'name'     => 'lib.zip',
            'tmp_name' => $file,
        ];

        $this->controller->ajaxUploadLibraryFromZip()->json_response;

        $this->assertFileNotExists($file);
        $this->assertTrue(FileUtils::recursiveRmdir($this->location));
    }

    protected function createFile(string $name): string {
        $name = FileUtils::joinPaths($this->location, $name);
        touch($name);
        $this->assertFileExists($name);
        return $name;
    }

    /** @test */
    public function testItGetsLibrariesWhenThereAreNone() {
        $response = $this->controller->ajaxGetLibraryList()->json_response;

        $this->assertEquals(
            [
                'status' => 'success',
                'data'   => [],
            ],
            $response->json
        );
    }

    /** @test */
    public function testItGetsAllLibraries() {
        $library = new LibraryEntity('key', $this->location);
        $this->gateway->addLibrary($library);
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', '2019-12-06 15:16:17');
        $this->metadata->add(
            new MetadataEntity(
                $library,
                'name',
                'source',
                4,
                $dateTime,
                $dateTime
            )
        );

        $response = $this->controller->ajaxGetLibraryList()->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status' => 'success',
                'data'   => [
                    [
                        'key'                  => 'key',
                        'name'                 => 'name',
                        'source'               => 'source',
                        'number_of_gradeables' => 4,
                        'updated_at'           => '06 Dec, 2019 15:16:17',
                        'created_at'           => '06 Dec, 2019 15:16:17',
                    ],
                ],
            ],
            $response->json
        );
    }

    /** @test */
    public function testItUpdatesALibrary() {
        $this->gateway->addLibrary(new LibraryEntity('imma sleep after this', $this->location));

        $response = $this->controller->ajaxUpdateLibrary('imma sleep after this')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status' => 'success',
                'data'   => "Successfully updated 'imma sleep after this'",
            ],
            $response->json
        );
    }

    /** @test */
    public function testItDoesntUpdateNonExistentLibraries() {
        $response = $this->controller->ajaxUpdateLibrary('Wherever you go, there you will be.')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status'  => 'fail',
                'message' => 'There was a problem updating the metadata: Library does not exist.',
            ],
            $response->json
        );
    }

    /** @test */
    public function testItRemovesALibrary() {
        $this->gateway->addLibrary(new LibraryEntity('name', $this->location));

        $response = $this->controller->ajaxRemoveLibrary('name')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status' => 'success',
                'data'   => 'Successfully removed library \'name\'',
            ],
            $response->json
        );
        $this->assertEquals([], $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testItDoesntRemoveLibrariesItsNotSupposedTo() {
        $this->gateway->addLibrary(new LibraryEntity('name', $this->location));

        $response = $this->controller->ajaxRemoveLibrary('different name')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status' => 'success',
                'data'   => 'Successfully removed library \'different name\'',
            ],
            $response->json
        );
        $this->assertCount(1, $this->gateway->getAllLibraries($this->location));
    }

    /** @test */
    public function testItDoesntRemoveInvalidName() {
        $response = $this->controller->ajaxRemoveLibrary('')->json_response;

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(
            [
                'status'  => 'fail',
                'message' => 'You must specify the library to remove.',
            ],
            $response->json
        );
    }

    public function tearDown(): void {
        parent::tearDown();

        $_FILES = [];
        $_POST = [];
    }
}
