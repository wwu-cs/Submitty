<?php

namespace tests\app\controllers;

use app\libraries\response\RedirectResponse;
use app\libraries\response\WebResponse;
use app\models\User;
use app\libraries\Core;
use tests\BaseUnitTest;
use app\controllers\HomePageController;

class HomePageControllerTester extends BaseUnitTest {

	protected $controller;

	protected $core;

	public function setUp(): void {
		parent::setUp();

		$this->core = $this->createMockCore([], [
			'accessGrading' => True,
		], [
			'updateUser' => []
		]);

		$this->core->method("addSuccessMessage")->willReturn(true);
		$this->core->method("addErrorMessage")->willReturn(true);
		$this->core->method("buildUrl")->willReturn("http://192.168.56.111/home");

		$this->controller = new HomePageController($this->core);
	}

	public function testHomePageViewChangePasswordSuccess() {
		$_POST = [
			"new_password" => "123",
			"confirm_new_password" => "123"
		];
		$response = $this->controller->changePassword();

		$this->assertMethodCalled("getUser");
		$this->assertMethodCalled("updateUser");
		$this->assertMethodCalled("addSuccessMessage");
		$this->assertMethodCalled("buildUrl");
		$this->assertInstanceOf(RedirectResponse::class, $response->redirect_response);
	}

	public function testHomePageViewChangePasswordFail() {
		$_POST = [
			"new_password" => "123",
			"confirm_new_password" => "321"
		];
		$response = $this->controller->changePassword();

		$this->assertMethodCalled("addErrorMessage");
		$this->assertMethodCalled("buildUrl");
		$this->assertInstanceOf(RedirectResponse::class, $response->redirect_response);
	}

	public function testHomePageViewShowHomepage() {
		$courses = (object) [
			"json_response" => (object) [
				"json" => $this->getCoursesResponse()
			]
		];
		$controller = $this->getMockBuilder(HomePageController::class)
			->setConstructorArgs([$this->core])
			->setMethods(['getCourses'])
			->getMock();
		$controller->method('getCourses')
			->willReturn($courses);

		$this->core->getConfig()->method("getUsernameChangeText")->willReturn(True);
		
		$response = $controller->showHomepage();

		$this->assertMethodCalled("getUser");
		$this->assertMethodCalled("getConfig");
		$this->assertMethodCalled("getUsernameChangeText");
		$this->assertInstanceOf(WebResponse::class, $response->web_response);
	}

	public function getCoursesResponse() {
		$json = '{"status":"success","data":{"unarchived_courses":[{"semester":"f19","title":"blank","display_name":"","display_semester":"Fall 2019","user_group":1},{"semester":"f19","title":"development","display_name":"","display_semester":"Fall 2019","user_group":1},{"semester":"f19","title":"sample","display_name":"","display_semester":"Fall 2019","user_group":1},{"semester":"f19","title":"tutorial","display_name":"","display_semester":"Fall 2019","user_group":1}],"archived_courses":[]}}';	
		return json_decode($json, True);
	}

}