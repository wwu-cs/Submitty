<?php

namespace app\models;

use app\controllers\admin\WrapperController;
use app\exceptions\ConfigException;
use app\exceptions\FileNotFoundException;
use app\libraries\Core;
use app\libraries\FileUtils;
use app\libraries\Utils;

/**
 * Class Config
 *
 * This class handles and contains all of the variables necessary for running
 * the application. These variables are loaded from a combination of files and tables from
 * the database. We also allow for using this to write back to the variables within the database
 * (but not the variables in the files).
 *
 * @method string getSemester()
 * @method string getCourse()
 * @method string getBaseUrl()
 * @method string getVcsUrl()
 * @method string getCgiUrl()
 * @method string getSubmittyPath()
 * @method string getCgiTmpPath()
 * @method string getCoursePath()
 * @method string getDatabaseDriver()
 * @method array getSubmittyDatabaseParams()
 * @method array getCourseDatabaseParams()
 * @method string getCourseName()
 * @method string getCourseHomeUrl()
 * @method integer getDefaultHwLateDays()
 * @method integer getDefaultStudentLateDays()
 * @method string getConfigPath()
 * @method string getAuthentication()
 * @method \DateTimeZone getTimezone()
 * @method string getUploadMessage()
 * @method array getHiddenDetails()
 * @method string getCourseJsonPath()
 * @method bool isCourseLoaded()
 * @method string getInstitutionName()
 * @method string getInstitutionHomepage()
 * @method string getCourseCodeRequirements()
 * @method string getUsernameChangeText()
 * @method bool isForumEnabled()
 * @method bool isRegradeEnabled()
 * @method bool isEmailEnabled()
 * @method string getRegradeMessage()
 * @method string getVcsBaseUrl()
 * @method string getCourseEmail()
 * @method string getVcsUser()
 * @method string getVcsType()
 * @method string getPrivateRepository()
 * @method string getRoomSeatingGradeableId()
 * @method bool isSeatingOnlyForInstructor()
 * @method array getCourseJson()
 * @method string getSecretSession()
 * @method string getAutoRainbowGrades()
 * @method bool isQueueEnabled()
 */

class Config extends AbstractModel {

    /**
     * Variable to set the system to debug mode, which allows, among other things
     * easier access to user switching and to always output full exceptions. Never
     * turn on if running server in production environment.
     * @property
     * @var bool
     */
    protected $debug = false;

    /** @property @var string contains the semester to use, generally from the $_REQUEST['semester'] global */
    protected $semester;
    /** @property @var string contains the course to use, generally from the $_REQUEST['course'] global */
    protected $course;

    /** @property @var string path on the filesystem that points to the course data directory */
    protected $config_path;
    /** @property @var string path to the json file that contains all the course specific settings */
    protected $course_json_path;

    /** @property @var array */
    protected $course_json;

    /**
    * Indicates whether a course config has been successfully loaded.
    * @var bool
    * @property
    */
    protected $course_loaded = false;

    /*** MASTER CONFIG ***/
    /** @property @var string */
    protected $base_url;
    /** @property @var string */
    protected $vcs_url;
    /** @property @var string */
    protected $cgi_url;
    /** @property @var string */
    protected $authentication;
    /** @property @var DateTimeZone */
    protected $timezone;
    /** @var string */
    protected $default_timezone = 'America/New_York';
    /** @property @var string */
    protected $submitty_path;
    /** @property @var string */
    protected $course_path;
    /** @property @var string */
    protected $submitty_log_path;
    /** @property @var bool */
    protected $log_exceptions;
    /** @property @var string */
    protected $cgi_tmp_path;

    /** @property @var string */
    protected $database_driver = "pgsql";

    /**
     * The name of the institution that deployed Submitty. Added to the breadcrumb bar if non-empty.
     * @var string
     * @property
     */
    protected $institution_name = "";

    /**
     * The url of the institution's homepage. Linked to from the breadcrumb created with institution_name.
     * @var string
     * @property
     */
    protected $institution_homepage = "";

    /**
     * The text to be shown to a user when they attempt to change their username.
     * @var string
     * @property
     */
    protected $username_change_text = "";

    /**
     * The text to be shown when an instructor enters a course code for a new course.
     * @var string
     * @property
     */
    protected $course_code_requirements = "";

    /** @property @var string Text shown to all users for system announcement */
    protected $system_message = '';

    /** @property @var array */
    protected $submitty_database_params = array();

    /** @property @var array */
    protected $course_database_params = array();

    /** @property @var array */
    protected $wrapper_files = array();

    /** @property @var bool */
    protected $email_enabled;

    /** @property @var string */
    protected $latest_tag;
    /** @property @var string */
    protected $latest_commit;

    /** @property @var string */
    protected $course_name;
    /** @property @var string */
    protected $course_home_url;
    /** @property @var int */
    protected $default_hw_late_days;
    /** @property @var int */
    protected $default_student_late_days;
    /** @property @var bool */
    protected $zero_rubric_grades;

    /** @property @var string */
    protected $upload_message;
    /** @property @var bool */
    protected $keep_previous_files;
    /** @property @var bool */
    protected $display_rainbow_grades_summary;
    /** @property @var bool */
    protected $display_custom_message;
    /** @property @var string*/
    protected $course_email;
    /** @property @var string */
    protected $vcs_base_url;
    /** @property @var string */
    protected $vcs_type;
    /** @property @var string */
    protected $private_repository;
    /** @property @var array */
    protected $hidden_details;
    /** @property @var bool */
    protected $forum_enabled;
    /** @property @var bool */
    protected $regrade_enabled;
    /** @property @var string */
    protected $regrade_message;
    /** @property @var bool*/
    protected $seating_only_for_instructor;
    /** @property @var string|null */
    protected $room_seating_gradeable_id;
    /** @property @var bool */
    protected $auto_rainbow_grades;
    /** @property @var string */
    protected $secret_session;
    /** @property @var bool */
    protected $queue_enabled;

    /**
     * Config constructor.
     *
     * @param Core   $core
     */
    public function __construct(Core $core) {
        parent::__construct($core);
        $this->timezone = new \DateTimeZone($this->default_timezone);
    }

    public function loadMasterConfigs($config_path) {
        if (!is_dir($config_path)) {
            throw new ConfigException("Could not find config directory: " . $config_path, true);
        }
        $this->config_path = $config_path;
        // Load config details from the master config file
        $database_json = FileUtils::readJsonFile(FileUtils::joinPaths($this->config_path, 'database.json'));

        if (!$database_json) {
            throw new ConfigException("Could not find database config: {$this->config_path}/database.json");
        }

        $this->submitty_database_params = [
            'dbname' => 'submitty',
            'host' => $database_json['database_host'],
            'username' => $database_json['database_user'],
            'password' => $database_json['database_password']
        ];

        if (isset($database_json['driver'])) {
            $this->database_driver = $database_json['driver'];
        }

        $this->authentication = $database_json['authentication_method'];
        $this->debug = $database_json['debugging_enabled'] === true;

        $submitty_json = FileUtils::readJsonFile(FileUtils::joinPaths($this->config_path, 'submitty.json'));
        if (!$submitty_json) {
            throw new ConfigException("Could not find submitty config: {$this->config_path}/submitty.json");
        }

        $this->submitty_log_path = $submitty_json['site_log_path'];
        $this->log_exceptions = true;

        $this->base_url = $submitty_json['submission_url'];
        $this->submitty_path = $submitty_json['submitty_data_dir'];

        if (isset($submitty_json['timezone'])) {
            if (!in_array($submitty_json['timezone'], \DateTimeZone::listIdentifiers())) {
                throw new ConfigException("Invalid Timezone identifier: {$submitty_json['timezone']}");
            }
            $this->timezone = new \DateTimeZone($submitty_json['timezone']);
        }

        if (isset($submitty_json['institution_name'])) {
            $this->institution_name = $submitty_json['institution_name'];
        }

        if (isset($submitty_json['institution_homepage'])) {
            $this->institution_homepage = $submitty_json['institution_homepage'];
        }

        if (isset($submitty_json['username_change_text'])) {
            $this->username_change_text = $submitty_json['username_change_text'];
        }

        if (isset($submitty_json['course_code_requirements'])) {
            $this->course_code_requirements = $submitty_json['course_code_requirements'];
        }

        if (isset($submitty_json['system_message'])) {
            $this->system_message = strval($submitty_json['system_message']);
        }

        $this->base_url = rtrim($this->base_url, "/") . "/";

        if (!empty($submitty_json['cgi_url'])) {
            $this->cgi_url = rtrim($submitty_json['cgi_url'], "/") . "/";
        }
        else {
            $this->cgi_url = $this->base_url . "cgi-bin/";
        }

        if (empty($submitty_json['vcs_url'])) {
            $this->vcs_url = $this->base_url . '{$vcs_type}/';
        }
        else {
            $this->vcs_url = rtrim($submitty_json['vcs_url'], '/') . '/';
        }

        $this->cgi_tmp_path = FileUtils::joinPaths($this->submitty_path, "tmp", "cgi");

        // Check that the paths from the config file are valid
        foreach (array('submitty_path', 'submitty_log_path') as $path) {
            if (!is_dir($this->$path)) {
                throw new ConfigException("Invalid path for setting {$path}: {$this->$path}");
            }
            $this->$path = rtrim($this->$path, "/");
        }

        foreach (array('autograding', 'access', 'site_errors', 'ta_grading') as $path) {
            if (!is_dir(FileUtils::joinPaths($this->submitty_log_path, $path))) {
                throw new ConfigException("Missing log folder: {$path}");
            }
        }

        $secrets_json = FileUtils::readJsonFile(FileUtils::joinPaths($this->config_path, 'secrets_submitty_php.json'));
        if (!$secrets_json) {
            throw new ConfigException("Could not find secrets config: {$this->config_path}/secrets_submitty_php.json");
        }

        foreach (['session'] as $key) {
            $var = "secret_{$key}";
            $secrets_json[$key] = trim($secrets_json[$key]) ?? '';
            if (empty($secrets_json[$key])) {
                throw new ConfigException("Missing secret var: {$key}");
            }
            elseif (strlen($secrets_json[$key]) < 32) {
                // enforce a minimum 32 bytes for the secrets
                throw new ConfigException("Secret {$key} is too weak. It should be at least 32 bytes.");
            }
            $this->$var = $secrets_json[$key];
        }

        $email_json = FileUtils::readJsonFile(FileUtils::joinPaths($this->config_path, 'email.json'));
        if (!$email_json) {
            throw new ConfigException("Could not find email config: {$this->config_path}/email.json");
        }
        $this->email_enabled = $email_json['email_enabled'];


        $version_json = FileUtils::readJsonFile(FileUtils::joinPaths($this->config_path, 'version.json'));
        if (!$version_json) {
            throw new ConfigException("Could not find version file: {$this->config_path}/version.json");
        }
        if (
            !isset($version_json['most_recent_git_tag'])
            || !isset($version_json['short_installed_commit'])
        ) {
            throw new ConfigException("Error parsing version information: {$this->config_path}/version.json");
        }
        $this->latest_tag = $version_json['most_recent_git_tag'];
        $this->latest_commit = $version_json['short_installed_commit'];
    }

    public function loadCourseJson($semester, $course, $course_json_path) {
        $this->semester = $semester;
        $this->course = $course;
        $this->course_path = FileUtils::joinPaths($this->getSubmittyPath(), "courses", $semester, $course);

        if (!file_exists($course_json_path)) {
            throw new ConfigException("Could not find course config file: " . $course_json_path, true);
        }
        $this->course_json_path = $course_json_path;
        $this->course_json = json_decode(file_get_contents($course_json_path), true);
        if ($this->course_json === null) {
            throw new ConfigException("Error parsing the config file: " . json_last_error_msg());
        }

        if (!isset($this->course_json['database_details']) || !is_array($this->course_json['database_details'])) {
            throw new ConfigException("Missing config section 'database_details' in json file");
        }

        $this->course_database_params = array_merge($this->submitty_database_params, $this->course_json['database_details']);

        $array = [
            'course_name', 'course_home_url', 'default_hw_late_days', 'default_student_late_days',
            'zero_rubric_grades', 'upload_message', 'keep_previous_files', 'display_rainbow_grades_summary',
            'display_custom_message', 'room_seating_gradeable_id', 'course_email', 'vcs_base_url', 'vcs_type',
            'private_repository', 'forum_enabled', 'regrade_enabled', 'seating_only_for_instructor', 'regrade_message',
            'auto_rainbow_grades', 'queue_enabled'
        ];
        $this->setConfigValues($this->course_json, 'course_details', $array);

        if (empty($this->vcs_base_url)) {
            $this->vcs_base_url = $this->vcs_url . $this->semester . '/' . $this->course;
        }

        $this->vcs_base_url = rtrim($this->vcs_base_url, "/") . "/";

        if (isset($this->course_json['hidden_details'])) {
            $this->hidden_details = $this->course_json['hidden_details'];
            if (isset($this->course_json['hidden_details']['course_url'])) {
                $this->base_url = rtrim($this->course_json['hidden_details']['course_url'], "/") . "/";
            }
        }

        foreach (array('default_hw_late_days', 'default_student_late_days') as $key) {
            $this->$key = intval($this->$key);
        }

        $array = array('zero_rubric_grades', 'keep_previous_files', 'display_rainbow_grades_summary',
            'display_custom_message', 'forum_enabled', 'regrade_enabled', 'seating_only_for_instructor', "queue_enabled");
        foreach ($array as $key) {
            $this->$key = ($this->$key == true) ? true : false;
        }

        $wrapper_files_path = FileUtils::joinPaths($this->getCoursePath(), 'site');
        foreach (WrapperController::WRAPPER_FILES as $file) {
            $path = FileUtils::joinPaths($wrapper_files_path, $file);
            if (file_exists($path)) {
                $this->wrapper_files[$file] = $path;
            }
        }

        $this->course_loaded = true;
    }

    private function setConfigValues($config, $section, $keys) {
        if (!isset($config[$section]) || !is_array($config[$section])) {
            throw new ConfigException("Missing config section '{$section}' in json file");
        }

        foreach ($keys as $key) {
            if (!isset($config[$section][$key])) {
                throw new ConfigException("Missing config setting '{$section}.{$key}' in configuration json file");
            }
            $this->$key = $config[$section][$key];
        }
    }

    /**
     * Determine if automatic rainbow grades is fully configured
     * For some features to be available to the instructors, the submitty-admin user must be configured
     * at the system level and also must be a member of the course in question.
     */

    public function getSubmittyAdminUser() {
        // grab the name of the submitty_admin user (only if 'verified',
        // that is, password successfully used to grab an API token.
        $users_file = FileUtils::joinPaths(
            '/',
            'usr',
            'local',
            'submitty',
            'config',
            'submitty_users.json'
        );
        if (!is_file($users_file)) {
            throw new FileNotFoundException('Unable to locate the submity_users.json file');
        }
        $users_file_contents = json_decode(file_get_contents($users_file));
        $submitty_admin_user = "";
        if (property_exists($users_file_contents, "verified_submitty_admin_user")) {
            $submitty_admin_user = $users_file_contents->verified_submitty_admin_user;
        }
        return $submitty_admin_user;
    }

    public function isSubmittyAdminUserVerified() {
        return $this->getSubmittyAdminUser() !== "";
    }

    public function isSubmittyAdminUserInCourse() {
        $submitty_admin_user = $this->getSubmittyAdminUser();
        if ($submitty_admin_user === "") {
            return false;
        }
        $course = $this->getCourse();
        $semester = $this->getSemester();
        return $this->core->getQueries()->checkIsInstructorInCourse(
            $submitty_admin_user,
            $course,
            $semester
        );
    }


    /**
     * @return boolean
     */
    public function isDebug() {
        return $this->debug;
    }

    /**
     * @return bool
     */
    public function shouldLogExceptions() {
        return $this->log_exceptions;
    }

    /**
     * @return bool
     */
    public function shouldZeroRubricGrades() {
        return $this->zero_rubric_grades;
    }

    /**
     * @return bool
     */
    public function displayCustomMessage() {
        return $this->display_custom_message;
    }

    /**
     * @return bool
     */
    public function keepPreviousFiles() {
        return $this->keep_previous_files;
    }

    /**
     * @return bool
     */
    public function displayRainbowGradesSummary() {
        return $this->display_rainbow_grades_summary;
    }

    /**
     * @return bool
     */
    public function displayRoomSeating() {
        return $this->room_seating_gradeable_id !== "";
    }


    public function getLogPath() {
        return $this->submitty_log_path;
    }

    public function saveCourseJson($save) {
        FileUtils::writeJsonFile($this->course_json_path, array_merge($this->course_json, $save));
    }

    public function wrapperEnabled() {
        return $this->course_loaded
            && (count($this->wrapper_files) > 0);
    }

    public function getWrapperFiles() {
        //Return empty if not logged in because we can't access them
        return ($this->core->getUser() === null ? [] : $this->wrapper_files);
    }
}
