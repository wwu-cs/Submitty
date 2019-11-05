<?php

namespace tests\app\controllers;

use app\libraries\response\RedirectResponse;
use app\libraries\response\WebResponse;
use app\libraries\response\JsonResponse;
use app\models\User;
use app\libraries\Core;
use tests\BaseUnitTest;
use app\controllers\HomePageController;

class HomePageControllerTester extends BaseUnitTest {

	protected $controller;

	protected $core;

	public function setUp(): void {
		parent::setUp();

		$this->core = $this->createMockCore([
			'getUsernameChangeText' => True
		], [
			'accessGrading' => True
		], [
			'updateUser' => []
		], [], [
			'addSuccessMessage' => true,
			'addErrorMessage' => true,
			'buildUrl' => 'http://192.168.56.111/home'
		]);

		$this->true_controller = new HomePageController($this->core);
	}

	public function testHomePageSearchLibrary() {
		$response = $this->true_controller->searchLibrary();
		
		$this->assertTrue($response->json_response->json['status'] === "success");
		$this->assertInstanceOf(JsonResponse::class, $response->json_response);
	}

}