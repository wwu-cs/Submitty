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
            'homework_library_location' => $this->library_path
		], [
			'accessGrading' => True
		], [
			'updateUser' => []
		], [], []);

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

		$response = $this->controller->searchLibraryGradeableWithQuery('16_docker_network_python', $this->library_path);

        $this->assertTrue($response->json_response->json['status'] === "success");
        $this->assertTrue(preg_match("/.*16_docker_network_python\/config\/config\.json$/i", $response->json_response->json['data']['path']) === 1);
    }

	public function testHomePageSearchLibrary() {
        $useCase = new LibraryAddUseCase($this->core);

        $testRepoUrl = 'https://github.com/Submitty/Tutorial.git';
		$useCase->addGitLibrary($testRepoUrl);
		
		$response = $this->controller->searchLibrary($this->library_path);
		
		$this->assertTrue($response->json_response->json['status'] === "success");
		$this->assertTrue(count($response->json_response->json['data']) === 17);
		$this->assertInstanceOf(JsonResponse::class, $response->json_response);
	}

	public function testHomePageSearchLibraryWithQueryThatSucceeds() {
		$query = "LIVE";
		$response = $this->controller->searchLibraryWithQuery($query, $this->library_path);
		
		$this->assertTrue($response->json_response->json['status'] === "success");
		$this->assertTrue(count($response->json_response->json['data']) === 2);
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
