<?php

namespace tests\app\controllers;

use app\libraries\homework\UseCases\LibraryAddUseCase;
use app\libraries\response\RedirectResponse;
use app\libraries\response\WebResponse;
use app\libraries\response\JsonResponse;
use app\models\User;
use app\libraries\Core;
use tests\BaseUnitTest;
use app\controllers\HomePageController;
use app\libraries\FileUtils;

class HomePageControllerTester extends BaseUnitTest {

	protected $controller;

	protected $core;

	protected $tmp_dirs = ["DAEMON_CONTAINED_WITHIN", "LIVE_UPDATES", "THERE_SHALL_BE_LIGHT", "THIS_IS_ALSO_IMPORTANT", "WE_WANT_TO_SEE_THIS"];

	protected $library_path;

	public function tearDown(): void {
		FileUtils::recursiveRmdir($this->library_path);
	}

	public function setUp(): void {
		parent::setUp();

		$this->library_path = FileUtils::joinPaths(sys_get_temp_dir(), "library");

		$this->core = $this->createMockCore([
			'getUsernameChangeText' => True,
            'homework_library_enable' => True,
            'homework_library_location' => '/tmp/library'
		], [
			'accessGrading' => True
		], [
			'updateUser' => []
		], [], [
			'addSuccessMessage' => true,
			'addErrorMessage' => true,
			'buildUrl' => 'http://192.168.56.111/home'
		]);

		$this->controller = new HomePageController($this->core);

		// mock folder structure in system temp folder
		FileUtils::createDir($this->library_path);
		foreach ($this->tmp_dirs as $dir_name) {
			$dir_full_path = FileUtils::joinPaths($this->library_path, $dir_name);
			FileUtils::createDir($dir_full_path);
		}
	}

	public function testGitLibrary() {
        $useCase = new LibraryAddUseCase($this->core);

        $testRepoUrl = 'https://github.com/Submitty/Tutorial.git';
        $useCase->addGitLibrary($testRepoUrl);

	    $response = $this->controller->searchLibraryGradeableWithQuery('Tutorial', $this->library_path);
        $this->assertTrue($response->json_response->json['status'] === "success");
        $this->assertTrue($response->json_response->json['data'] === '{"path":"\/tmp\/library\/Tutorial\/examples\/07_loop_depth\/config\/config.json","title":"Python - Determine Loop Depth"}');
    }

	public function testHomePageSearchLibrary() {
		$response = $this->controller->searchLibrary($this->library_path);
		
		$this->assertTrue($response->json_response->json['status'] === "success");
		$this->assertTrue($response->json_response->json['data'] === $this->tmp_dirs);
		$this->assertTrue(count($response->json_response->json['data']) === 5);
		$this->assertInstanceOf(JsonResponse::class, $response->json_response);
	}

	public function testHomePageSearchLibraryWithQueryThatSucceeds() {
		$query = "LIVE";
		$response = $this->controller->searchLibraryWithQuery($query, $this->library_path);
		
		$this->assertTrue($response->json_response->json['status'] === "success");
		$this->assertTrue($response->json_response->json['data'] === ["LIVE_UPDATES"]);
		$this->assertTrue(count($response->json_response->json['data']) === 1);
		$this->assertInstanceOf(JsonResponse::class, $response->json_response);
	}

	public function testHomePageSearchLibraryWithQueryThatFails() {
		$query = "BLATANT NONEXISTENCE BLASPHEME AGAINST MAHAPARINIRVANA";
		$response = $this->controller->searchLibraryWithQuery($query, $this->library_path);
		
		$this->assertEquals($response->json_response->json['status'], "success");
		$this->assertEmpty($response->json_response->json['data'], "Test array is not empty");
		$this->assertInstanceOf(JsonResponse::class, $response->json_response);
	}

}
