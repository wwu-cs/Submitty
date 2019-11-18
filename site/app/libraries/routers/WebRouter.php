<?php

namespace app\libraries\routers;

use app\libraries\response\RedirectResponse;
use app\libraries\response\Response;
use app\libraries\response\JsonResponse;
use app\libraries\response\WebResponse;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use app\libraries\Utils;
use app\libraries\Core;

class WebRouter {
    /** @var Core  */
    protected $core;

    /** @var Request  */
    protected $request;

    /** @var AnnotationReader */
    protected $reader;

    /** @var array */
    protected $parameters;

    /** @var string the controller to call */
    protected $controller_name;

    /** @var string the method to call */
    protected $method_name;

    private function __construct(Request $request, Core $core) {
        $this->core = $core;
        $this->request = $request;

        $fileLocator = new FileLocator();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->reader = new AnnotationReader();
        $annotationLoader = new AnnotatedRouteLoader($this->reader);
        $loader = new AnnotationDirectoryLoader($fileLocator, $annotationLoader);
        $collection = $loader->load(realpath(__DIR__ . "/../../controllers"));
        $context = new RequestContext();
        $matcher = new UrlMatcher($collection, $context->fromRequest($this->request));
        $this->parameters = $matcher->matchRequest($this->request);
    }

    /**
     * @param Request $request
     * @param Core $core
     * @return Response|mixed should be of type Response only in the future
     */
    public static function getApiResponse(Request $request, Core $core) {
        try {
            $router = new self($request, $core);
            $router->loadCourse();

            $logged_in = $core->isApiLoggedIn($request);

            // prevent user that is not logged in from going anywhere except AuthenticationController
            if (
                !$logged_in
                && !Utils::endsWith($router->parameters['_controller'], 'AuthenticationController')
            ) {
                return new Response(JsonResponse::getFailResponse("Unauthenticated access. Please log in."));
            }

            if ($logged_in && !$core->getUser()->accessFaculty()) {
                return new Response(JsonResponse::getFailResponse("API is open to faculty only."));
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            if (!$router->accessCheck()) {
                return Response::JsonOnlyResponse(
                    JsonResponse::getFailResponse("You don't have access to this endpoint.")
                );
            }
        }
        catch (ResourceNotFoundException $e) {
            return new Response(JsonResponse::getFailResponse("Endpoint not found."));
        }
        catch (MethodNotAllowedException $e) {
            return new Response(JsonResponse::getFailResponse("Method not allowed."));
        }
        catch (\Exception $e) {
            return new Response(JsonResponse::getErrorResponse($e->getMessage()));
        }

        $core->getOutput()->disableRender();
        $core->disableRedirects();
        return $router->run();
    }

    /**
     * @param Request $request
     * @param Core $core
     * @return Response|mixed should be of type Response only in the future
     * @throws \ReflectionException|\Exception
     */
    public static function getWebResponse(Request $request, Core $core) {
        $logged_in = false;
        try {
            $router = new self($request, $core);
            $router->loadCourse();

            $logged_in = $core->isWebLoggedIn();

            $login_check_response = $router->loginRedirectCheck($logged_in);
            if ($login_check_response instanceof Response) {
                return $login_check_response;
            }

            $csrf_check_response = $router->csrfCheck();
            if ($csrf_check_response instanceof Response) {
                return $csrf_check_response;
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            if (!$router->accessCheck()) {
                return new Response(
                    JsonResponse::getFailResponse("You don't have access to this endpoint."),
                    new WebResponse("Error", "errorPage", "You don't have access to this page.")
                );
            }
        }
        catch (ResourceNotFoundException | MethodNotAllowedException $e) {
            // redirect to login page or home page
            if (!$logged_in) {
                return Response::RedirectOnlyResponse(
                    new RedirectResponse($core->buildUrl(['authentication', 'login']))
                );
            }
            else {
                return Response::RedirectOnlyResponse(
                    new RedirectResponse($core->buildUrl(['home']))
                );
            }
        }

        return $router->run();
    }

    private function run() {
        $this->controller_name = $this->parameters['_controller'];
        $this->method_name = $this->parameters['_method'];
        $controller = new $this->controller_name($this->core);

        $arguments = array();
        /** @noinspection PhpUnhandledExceptionInspection */
        $method = new \ReflectionMethod($this->controller_name, $this->method_name);
        foreach ($method->getParameters() as $param) {
            $param_name = $param->getName();
            $arguments[$param_name] = $this->parameters[$param_name] ?? null;
            if (!isset($arguments[$param_name])) {
                $arguments[$param_name] = $this->request->query->get($param_name);
            }
            if (!isset($arguments[$param_name])) {
                $arguments[$param_name] = $param->getDefaultValue();
            }
        }

        return call_user_func_array([$controller, $this->method_name], $arguments);
    }

    /**
     * Loads course config if they exist in the requested URL.
     * @throws \Exception
     */
    private function loadCourse() {
        if (
            array_key_exists('_semester', $this->parameters)
            && array_key_exists('_course', $this->parameters)
        ) {
            $semester = $this->parameters['_semester'];
            $course = $this->parameters['_course'];

            /** @noinspection PhpUnhandledExceptionInspection */
            $this->core->loadCourseConfig($semester, $course);
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->core->loadGradingQueue();

            if ($this->core->getConfig()->isCourseLoaded()) {
                $this->core->getOutput()->addBreadcrumb(
                    $this->core->getDisplayedCourseName(),
                    $this->core->buildCourseUrl(),
                    $this->core->getConfig()->getCourseHomeUrl()
                );
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            $this->core->loadCourseDatabase();

            if ($this->core->getConfig()->isCourseLoaded() && $this->core->getConfig()->isForumEnabled()) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $this->core->loadForum();
            }
        }
    }

    /**
     * Check if the user needs a redirection depending on their login status.
     * @param $logged_in
     * @return Response|bool
     */
    private function loginRedirectCheck($logged_in) {
        if (!$logged_in && !Utils::endsWith($this->parameters['_controller'], 'AuthenticationController')) {
            $old_request_url = $this->request->getUriForPath($this->request->getPathInfo());
            return Response::RedirectOnlyResponse(
                new RedirectResponse(
                    $this->core->buildUrl(['authentication', 'login']) . '?old=' . urlencode($old_request_url)
                )
            );
        }
        elseif (
            $this->core->getConfig()->isCourseLoaded()
            && !$this->core->getAccess()->canI("course.view", ["semester" => $this->core->getConfig()->getSemester(), "course" => $this->core->getConfig()->getCourse()])
            && !Utils::endsWith($this->parameters['_controller'], 'AuthenticationController')
            && $this->parameters['_method'] !== 'noAccess'
        ) {
            return Response::RedirectOnlyResponse(
                new RedirectResponse($this->core->buildCourseUrl(['no_access']))
            );
        }

        if (!$this->core->getConfig()->isCourseLoaded() && !Utils::endsWith($this->parameters['_controller'], 'MiscController')) {
            if ($logged_in) {
                if (
                    $this->parameters['_method'] !== 'logout'
                    && !Utils::endsWith($this->parameters['_controller'], 'HomePageController')
                ) {
                    return Response::RedirectOnlyResponse(
                        new RedirectResponse($this->core->buildUrl(['home']))
                    );
                }
            }
            elseif (!Utils::endsWith($this->parameters['_controller'], 'AuthenticationController')) {
                return Response::RedirectOnlyResponse(
                    new RedirectResponse($this->core->buildUrl(['authentication', 'login']))
                );
            }
        }

        return true;
    }

    /**
     * Check if the request carries a valid CSRF token for all POST requests.
     * @return Response|bool
     */
    private function csrfCheck() {
        if (
            $this->request->isMethod('POST')
            && !Utils::endsWith($this->parameters['_controller'], 'AuthenticationController')
            && !$this->core->checkCsrfToken()
        ) {
            $msg = "Invalid CSRF token.";
            $this->core->addErrorMessage($msg);
            return new Response(
                JsonResponse::getFailResponse($msg),
                null,
                new RedirectResponse($this->core->buildUrl())
            );
        }

        return true;
    }

    /**
     * Check if the call passes access control defined
     * in @AccessControl() annotation.
     *
     * @return bool
     * @throws \ReflectionException
     */
    private function accessCheck() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $access_control = $this->reader->getMethodAnnotation(
            new \ReflectionMethod($this->parameters['_controller'], $this->parameters['_method']),
            AccessControl::class
        );

        if (is_null($access_control)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $access_control = $this->reader->getClassAnnotation(
                new \ReflectionClass($this->parameters['_controller']),
                AccessControl::class
            );
        }

        if (is_null($access_control)) {
            return true;
        }

        $user = $this->core->getUser();
        $access = true;

        if ($access_control->getRole()) {
            switch ($access_control->getRole()) {
                case 'INSTRUCTOR':
                    $access = $user->accessAdmin();
                    break;
                case 'FULL_ACCESS_GRADER':
                    $access = $user->accessFullGrading();
                    break;
                case 'LIMITED_ACCESS_GRADER':
                    $access = $user->accessGrading();
                    break;
                case 'STUDENT':
                default:
                    $access = $user !== null;
                    break;
            }
        }

        if ($access_control->getPermission()) {
            $access = $this->core->getAccess()->canI($access_control->getPermission());
        }

        return $access;
    }
}
