<?php namespace tests\app\controllers\admin;

use app\libraries\Core;
use tests\BaseUnitTest;
use app\controllers\admin\GradeOverrideController;

class GradeOverrideControllerTester extends BaseUnitTest
{
    /** @var GradeOverrideController */
    protected $controller;

    /** @var Core  */
    protected $core;

    public function setUp(): void
    {
        parent::setUp();

        $this->core = $this->createMockCore([], [], [
            'getAllUsers'                   => [],
            'getAllGradeablesIdsAndTitles'  => []
        ]);

        $this->controller = new GradeOverrideController($this->core);
    }

    /** @test */
    public function testQueriesAreCalledInViewOverriddenGrades()
    {
        $this->controller->viewOverriddenGrades();

        $this->assertMethodCalled('getAllUsers');
        $this->assertMethodCalled('getAllGradeablesIdsAndTitles');
    }

}
