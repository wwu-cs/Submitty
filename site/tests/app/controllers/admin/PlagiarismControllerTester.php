<?php namespace tests\app\controllers\admin;

use app\libraries\Core;
use tests\BaseUnitTest;
use app\controllers\admin\PlagiarismController;

class PlagiarismControllerTester extends BaseUnitTest
{
   /** @var PlagiarismController */
    protected $controller;

    /** @var Core */
    protected $core;

    public function setUp(): void
    {
        parent::setUp();

        $this->core = $this->createMockCore([], [], [
            'getGradeableConfig'  => [],
        ]);

        $this->controller = new PlagiarismController($this->core);
    }

    /** @test */
    public function testQueriesMadeInPlagiarismView()
    {
        $response = $this->controller->editPlagiarismSavedConfig();

        $this->assertMethodCalled('getGradeableConfig');
    }

}
