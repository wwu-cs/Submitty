<?php

namespace app\controllers;

use app\libraries\response\RedirectResponse;
use app\models\Course;
use app\models\User;
use app\libraries\Core;
use app\libraries\response\Response;
use app\libraries\response\WebResponse;
use app\libraries\response\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomePageController
 *
 * Controller to deal with the submitty home page. Once the user has been authenticated, but before they have
 * selected which course they want to access, they are forwarded to the home page.
 */
class HomePageController extends AbstractController {
    /**
     * HomePageController constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core) {
        parent::__construct($core);
    }

    /**
     * @Route("/current_user/change_password", methods={"POST"})
     * @return Response
     */
    public function changePassword() {
        $user = $this->core->getUser();
        if (
            !empty($_POST['new_password'])
            && !empty($_POST['confirm_new_password'])
            && $_POST['new_password'] == $_POST['confirm_new_password']
        ) {
            $user->setPassword($_POST['new_password']);
            $this->core->getQueries()->updateUser($user);
            $this->core->addSuccessMessage("Updated password");
        }
        else {
            $this->core->addErrorMessage("Must put same password in both boxes.");
        }
        return Response::RedirectOnlyResponse(
            new RedirectResponse($this->core->buildUrl(['home']))
        );
    }

    /**
     * @Route("/current_user/change_username", methods={"POST"})
     * @return Response
     */
    public function changeUserName() {
        $user = $this->core->getUser();
        if (isset($_POST['user_firstname_change']) && isset($_POST['user_lastname_change'])) {
            $newFirstName = trim($_POST['user_firstname_change']);
            $newLastName = trim($_POST['user_lastname_change']);
            // validateUserData() checks both for length (not to exceed 30) and for valid characters.
            if ($user->validateUserData('user_preferred_firstname', $newFirstName) === true && $user->validateUserData('user_preferred_lastname', $newLastName) === true) {
                $user->setPreferredFirstName($newFirstName);
                $user->setPreferredLastName($newLastName);
                //User updated flag tells auto feed to not clobber some of the user's data.
                $user->setUserUpdated(true);
                $this->core->getQueries()->updateUser($user);
            }
            else {
                $this->core->addErrorMessage("Preferred names must not exceed 30 chars.  Letters, spaces, hyphens, apostrophes, periods, parentheses, and backquotes permitted.");
            }
        }
        return Response::RedirectOnlyResponse(
            new RedirectResponse($this->core->buildUrl(['home']))
        );
    }

    /**
     * @param $user_id
     * @param $as_instructor
     * @Route("/api/courses", methods={"GET"})
     * @Route("/home/courses", methods={"GET"})
     * @return Response
     */
    public function getCourses($user_id = null, $as_instructor = false) {
        if ($as_instructor === 'true') {
            $as_instructor = true;
        }

        $user = $this->core->getUser();
        if (is_null($user_id) || $user->getAccessLevel() !== User::LEVEL_SUPERUSER) {
            $user_id = $user->getId();
        }

        $unarchived_courses = $this->core->getQueries()->getUnarchivedCoursesById($user_id);
        $archived_courses = $this->core->getQueries()->getArchivedCoursesById($user_id);

        // Filter out any courses a student has dropped so they do not appear on the homepage.
        // Do not filter courses for non-students.

        $unarchived_courses = array_filter($unarchived_courses, function (Course $course) use ($user_id, $as_instructor) {
            return $as_instructor ?
                $this->core->getQueries()->checkIsInstructorInCourse($user_id, $course->getTitle(), $course->getSemester())
                :
                $this->core->getQueries()->checkStudentActiveInCourse($user_id, $course->getTitle(), $course->getSemester());
        });

        $archived_courses = array_filter($archived_courses, function (Course $course) use ($user_id, $as_instructor) {
            return $as_instructor ?
                $this->core->getQueries()->checkIsInstructorInCourse($user_id, $course->getTitle(), $course->getSemester())
                :
                $this->core->getQueries()->checkStudentActiveInCourse($user_id, $course->getTitle(), $course->getSemester());
        });

        return Response::JsonOnlyResponse(
            JsonResponse::getSuccessResponse([
                "unarchived_courses" => array_map(
                    function (Course $course) {
                        return $course->getCourseInfo();
                    },
                    $unarchived_courses
                ),
                "archived_courses" => array_map(
                    function (Course $course) {
                        return $course->getCourseInfo();
                    },
                    $archived_courses
                )
            ])
        );
    }

    /**
     * Display the HomePageView to the student.
     *
     * @Route("/home")
     * @return Response
     */
    public function showHomepage() {
        $courses = $this->getCourses()->json_response->json;

        return new Response(
            null,
            new WebResponse(
                ['HomePage'],
                'showHomePage',
                $user = $this->core->getUser(),
                $courses["data"]["unarchived_courses"],
                $courses["data"]["archived_courses"],
                $this->core->getConfig()->getUsernameChangeText()
            )
        );
    }

    /**
     * @Route("/home/courses/new", methods={"POST"})
     * @Route("/api/courses", methods={"POST"})
     */
    public function createCourse() {
        $user = $this->core->getUser();
        if (is_null($user) || !$user->accessFaculty()) {
            return new Response(
                JsonResponse::getFailResponse("You don't have access to this endpoint."),
                new WebResponse("Error", "errorPage", "You don't have access to this page.")
            );
        }

        if (
            !isset($_POST['course_semester'])
            || !isset($_POST['course_title'])
            || !isset($_POST['head_instructor'])
        ) {
            $error = "Semester, course title or head instructor not set.";
            $this->core->addErrorMessage($error);
            return new Response(
                JsonResponse::getFailResponse($error),
                null,
                new RedirectResponse($this->core->buildUrl(['home']))
            );
        }

        $semester = $_POST['course_semester'];
        $course_title = strtolower($_POST['course_title']);
        $head_instructor = $_POST['head_instructor'];

        if ($user->getAccessLevel() === User::LEVEL_FACULTY && $head_instructor !== $user->getId()) {
            $error = "You can only create course for yourself.";
            $this->core->addErrorMessage($error);
            return new Response(
                JsonResponse::getFailResponse($error),
                null,
                new RedirectResponse($this->core->buildUrl(['home']))
            );
        }

        if (empty($this->core->getQueries()->getSubmittyUser($head_instructor))) {
            $error = "Head instructor doesn't exist.";
            $this->core->addErrorMessage($error);
            return new Response(
                JsonResponse::getFailResponse($error),
                null,
                new RedirectResponse($this->core->buildUrl(['home']))
            );
        }

        $base_course_semester = '';
        $base_course_title = '';

        if (isset($_POST['base_course'])) {
            $exploded_course = explode('|', $_POST['base_course']);
            $base_course_semester = $exploded_course[0];
            $base_course_title = $exploded_course[1];
        }
        elseif (isset($_POST['base_course_semester']) && isset($_POST['base_course_title'])) {
            $base_course_semester = $_POST['base_course_semester'];
            $base_course_title = $_POST['base_course_title'];
        }

        try {
            $group_check = $this->core->curlRequest(
                $this->core->getConfig()->getCgiUrl() . "group_check.cgi" . "?" . http_build_query(
                    [
                        'head_instructor' => $head_instructor,
                        'base_path' => "/var/local/submitty/courses/" . $base_course_semester . "/" . $base_course_title
                    ]
                )
            );

            if (empty($group_check) || empty($base_course_semester) || empty($base_course_title)) {
                $error = "Invalid base course.";
                $this->core->addErrorMessage($error);
                return new Response(
                    JsonResponse::getFailResponse($error),
                    null,
                    new RedirectResponse($this->core->buildUrl(['home']))
                );
            }

            if (json_decode($group_check, true)['status'] === 'fail') {
                $error = "The instructor is not in the correct Linux group.\n Please contact sysadmin for more information.";
                $this->core->addErrorMessage($error);
                return new Response(
                    JsonResponse::getFailResponse($error),
                    null,
                    new RedirectResponse($this->core->buildUrl(['home']))
                );
            }
        }
        catch (\Exception $e) {
            $error = "Server error.";
            $this->core->addErrorMessage($error);
            return new Response(
                JsonResponse::getErrorResponse($error),
                null,
                new RedirectResponse($this->core->buildUrl(['home']))
            );
        }

        $json = [
            "job" => 'CreateCourse',
            'semester' => $semester,
            'course' => $course_title,
            'head_instructor' => $head_instructor,
            'base_course_semester' => $base_course_semester,
            'base_course_title' => $base_course_title
        ];

        $json = json_encode($json, JSON_PRETTY_PRINT);
        file_put_contents('/var/local/submitty/daemon_job_queue/create_' . $semester . '_' . $course_title . '.json', $json);

        $this->core->addSuccessMessage("Course creation request successfully sent.\n Please refresh the page later.");
        return new Response(
            JsonResponse::getSuccessResponse(null),
            null,
            new RedirectResponse($this->core->buildUrl(['home']))
        );
    }

    /**
     * @Route("/home/courses/new", methods={"GET"})
     */
    public function createCoursePage() {
        $user = $this->core->getUser();
        if (is_null($user) || !$user->accessFaculty()) {
            return new Response(
                JsonResponse::getFailResponse("You don't have access to this endpoint."),
                new WebResponse("Error", "errorPage", "You don't have access to this page.")
            );
        }

        if ($user->getAccessLevel() === User::LEVEL_SUPERUSER) {
            $faculty = $this->core->getQueries()->getAllFaculty();
        }

        return new Response(
            null,
            new WebResponse(
                ['HomePage'],
                'showCourseCreationPage',
                $faculty ?? null,
                $this->core->getUser()->getId(),
                $this->core->getQueries()->getAllUnarchivedSemester()
            )
        );
    }
}
