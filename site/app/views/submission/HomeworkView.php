<?php

namespace app\views\submission;

use app\libraries\DateUtils;
use app\models\gradeable\AutoGradedTestcase;
use app\models\gradeable\AutoGradedVersion;
use app\models\gradeable\Component;
use app\models\gradeable\Gradeable;
use app\models\gradeable\GradedComponent;
use app\models\gradeable\GradedGradeable;
use app\models\gradeable\LateDays;
use app\models\gradeable\SubmissionTextBox;
use app\models\User;
use app\views\AbstractView;
use app\libraries\FileUtils;
use app\libraries\Utils;
use app\models\gradeable\AbstractGradeableInput;

class HomeworkView extends AbstractView {

    /**
     * @param Gradeable $gradeable
     * @param GradedGradeable|null $graded_gradeable
     * @param int $display_version
     * @param bool $can_inquiry
     * @param bool $show_hidden_testcases
     * @return string
     */
    public function showGradeable(Gradeable $gradeable, $graded_gradeable, int $display_version, bool $can_inquiry, bool $show_hidden_testcases = false) {
        $return = '';

        $this->core->getOutput()->addInternalJs('drag-and-drop.js');

        // The number of days late this gradeable would be if submitted now (including exceptions)
        $late_days_use = 0;
        $version_instance = null;
        if ($graded_gradeable !== null) {
            $version_instance = $graded_gradeable->getAutoGradedGradeable()->getAutoGradedVersions()[$display_version] ?? null;
            $late_days_use = max(0, $gradeable->getWouldBeDaysLate() - $graded_gradeable->getLateDayException($this->core->getUser()));
        }

        $is_admin = $this->core->getAccess()->canI('admin.wrapper', []);
        $on_team = $this->core->getUser()->onTeam($gradeable->getId());

        // Only show the late banner if the submission has a due date
        // Instructors shouldn't see this banner if they're not on a team (they won't have proper information)
        if (LateDays::filterCanView($this->core, $gradeable) && !($is_admin && !$on_team)) {
            $late_days = LateDays::fromUser($this->core, $this->core->getUser());
            $return .= $this->renderLateDayMessage($late_days, $gradeable, $graded_gradeable);
        }

        // showing submission if user is full grader or student can submit
        if ($this->core->getUser()->accessFullGrading()) {
            $return .= $this->renderSubmitBox($gradeable, $graded_gradeable, $version_instance, $late_days_use);
        }
        elseif ($gradeable->isStudentSubmit()) {
            if ($gradeable->canStudentSubmit()) {
                $return .= $this->renderSubmitBox($gradeable, $graded_gradeable, $version_instance, $late_days_use);
            }
            else {
                $return .= $this->renderSubmitNotAllowedBox();
            }
        }
        $all_directories = $gradeable->getSplitPdfFiles();
        if ($this->core->getUser()->accessFullGrading() && count($all_directories) > 0) {
            $return .= $this->renderBulkUploadBox($gradeable);
        }

        /*
          See #1624 and #1967

          if (!$this->core->getOutput()->bufferOutput()) {
              echo $return;
              $return = '';
          }
         */

        $auto_graded_gradeable = $graded_gradeable !== null ? $graded_gradeable->getAutoGradedGradeable() : null;
        $submission_count = $auto_graded_gradeable !== null ? $auto_graded_gradeable->getHighestVersion() : 0;
        $active_version = $auto_graded_gradeable !== null ? $auto_graded_gradeable->getActiveVersion() : 0;
        if ($submission_count === 0) {
            $return .= $this->renderNoSubmissionBox($graded_gradeable);
        }
        else {
            $return .= $this->renderVersionBox($graded_gradeable, $version_instance, $show_hidden_testcases);
        }

        $regrade_available = $this->core->getConfig()->isRegradeEnabled()
            && $gradeable->isTaGradeReleased()
            && $gradeable->isTaGrading()
            && $graded_gradeable !== null
            && $graded_gradeable->isTaGradingComplete()
            && $gradeable->isRegradeOpen()
            && $submission_count !== 0;

        if (
            $gradeable->isTaGradeReleased()
            && $gradeable->isTaGrading()
            && $submission_count !== 0
            && $active_version !== 0
        ) {
            $return .= $this->renderTAResultsBox($graded_gradeable, $regrade_available);
        }
        if ($regrade_available || $graded_gradeable !== null && $graded_gradeable->hasRegradeRequest()) {
            $return .= $this->renderRegradeBox($graded_gradeable, $can_inquiry);
        }
        return $return;
    }

    /**
     * Render the late days banner
     * @param LateDays $late_days
     * @param Gradeable $gradeable
     * @param GradedGradeable|null $graded_gradeable
     * @return string
     */
    public function renderLateDayMessage(LateDays $late_days, Gradeable $gradeable, $graded_gradeable) {
        $extensions = 0;
        $active_version_instance = null;
        $active_version = 0;
        if ($graded_gradeable !== null) {
            $extensions = $graded_gradeable->getLateDayException($this->core->getUser());
            $active_version_instance = $graded_gradeable->getAutoGradedGradeable()->getActiveVersionInstance();
            $active_version = $graded_gradeable->getAutoGradedGradeable()->getActiveVersion();
        }
        $late_days_remaining = $late_days->getLateDaysRemaining();
        $active_days_late =  $active_version_instance !== null ? $active_version_instance->getDaysLate() : 0;
        $would_be_days_late = $gradeable->getWouldBeDaysLate();
        $late_day_info = $late_days->getLateDayInfoByGradeable($gradeable);
        $late_days_allowed = $gradeable->getLateDays();
        $late_day_budget = $late_day_info->getLateDaysAllowed();

        $error = false;
        $messages = [];

        // ------------------------------------------------------------
        // ALWAYS PRINT DEADLINE EXTENSION (IF ANY)
        if ($extensions > 0) {
            $messages[] = ['type' => 'extension', 'info' => [
                'extensions' => $extensions
            ]];
        }

        // HOW MANY DAYS LATE...  MINUS EXTENSIONS?
        $active_days_charged = max(0, $active_days_late - $extensions);

        // ------------------------------------------------------------
        // IF STUDENT HAS ALREADY SUBMITTED AND THE ACTIVE VERSION IS LATE, PRINT LATE DAY INFORMATION FOR THE ACTIVE VERSION
        if ($active_version >= 1 && $active_days_late > 0) {
            // BAD STATUS - AUTO ZERO BECAUSE INSUFFICIENT LATE DAYS REMAIN
            if ($active_days_charged > $late_day_budget) {
                $error = true;
                $messages[] = ['type' => 'too_few_remain', 'info' => [
                    'late' => $active_days_late,
                    'remaining' => $late_days_remaining
                ]];
            } // BAD STATUS - AUTO ZERO BECAUSE TOO MANY LATE DAYS USED ON THIS ASSIGNMENT
            elseif ($active_days_charged > $late_days_allowed) {
                $error = true;
                $messages[] = ['type' => 'too_many_used', 'info' => [
                    'late' => $active_days_late,
                    'charged' => $active_days_charged,
                    'allowed' => $late_days_allowed
                ]];
            } // LATE STATUS
            else {
                $messages[] = ['type' => 'late', 'info' => [
                    'late' => $active_days_late,
                    'charged' => $active_days_charged,
                    'remaining' => $late_days_remaining
                ]];
            }
            if ($error) {
                //You're going to get a zero
                $messages[] = ['type' => 'getting_zero'];
            }
        }

        // ------------------------------------------------------------
        // (IF LATE) PRINT LATE DAY INFORMATION
        if ($would_be_days_late > 0) {
            // HOW MANY DAYS LATE...  MINUS EXTENSIONS?
            $new_late_charged = max(0, $would_be_days_late - $extensions);

            // if unsubmitted, or submitted but still in the late days allowed window
            if (
                $active_version < 1
                || (
                    $new_late_charged <= $late_days_remaining
                    && $new_late_charged <= $late_days_allowed
                )
            ) {
                // PRINT WOULD BE HOW MANY DAYS LATE
                $messages[] = ['type' => 'would_late', 'info' => [
                    'late' => $would_be_days_late
                ]];

                // SUBMISSION NOW WOULD BE BAD STATUS -- INSUFFICIENT LATE DAYS
                if ($new_late_charged > $late_days_remaining) {
                    $messages[] = ['type' => 'would_too_few_remain', 'info' => [
                        'remaining' => $late_days_remaining
                    ]];
                    $error = true;
                    $messages[] = ['type' => 'would_get_zero'];
                } // SUBMISSION NOW WOULD BE BAD STATUS -- EXCEEDS LIMIT FOR THIS ASSIGNMENT
                elseif ($new_late_charged > $late_days_allowed) {
                    $messages[] = ['type' => 'would_too_many_used', 'info' => [
                        'allowed' => $late_days_allowed
                    ]];
                    $error = true;
                    $messages[] = ['type' => 'would_get_zero'];
                } // SUBMISSION NOW WOULD BE LATE
                else {
                    $new_late_charged = max(0, $would_be_days_late - $active_days_late - $extensions);
                    $new_late_days_remaining = $late_days_remaining - $new_late_charged;
                    $messages[] = ['type' => 'would_allowed', 'info' => [
                        'charged' => $new_late_charged,
                        'remaining' => $new_late_days_remaining
                    ]];
                }
            }
        }

        // ------------------------------------------------------------
        // IN CASE OF AUTOMATIC ZERO, MAKE THE MESSAGE RED
        if ($error == true) {
            $messages[] = ['type' => 'contact_instructor', 'info' => [
                'extensions' => $extensions
            ]];
        }

        return $this->core->getOutput()->renderTwigTemplate('submission/homework/LateDayMessage.twig', [
            'messages' => $messages,
            'error' => $error
        ]);
    }

    private function renderSubmitNotAllowedBox() {
        return $this->core->getOutput()->renderTwigOutput("submission/homework/SubmitNotAllowedBox.twig");
    }

    /**
     * @param Gradeable $gradeable
     * @param GradedGradeable|null $graded_gradeable
     * @param AutoGradedVersion|null $version_instance
     * @param int $late_days_use
     * @return string
     */
    private function renderSubmitBox(Gradeable $gradeable, $graded_gradeable, $version_instance, int $late_days_use): string {
        $student_page = $gradeable->isStudentPdfUpload();
        $students_full = [];
        $inputs = $gradeable->getAutogradingConfig()->getInputs();
        $notebook = $gradeable->getAutogradingConfig()->getNotebook();
        $would_be_days_late = $gradeable->getWouldBeDaysLate();
        $active_version_instance = null;
        if ($graded_gradeable !== null) {
            $active_version_instance = $graded_gradeable->getAutoGradedGradeable()->getActiveVersionInstance();
        }
        $active_days_late =  $active_version_instance !== null ? $active_version_instance->getDaysLate() : 0;
        $days_to_be_charged = $would_be_days_late - $active_days_late;
        $old_files = [];
        $display_version = 0;

        if ($this->core->getUser()->accessGrading()) {
            $students = $this->core->getQueries()->getAllUsers();
            $student_ids = array();
            foreach ($students as $student) {
                $student_ids[] = $student->getId();
            }

            $students_version = array();
            foreach ($this->core->getQueries()->getGradedGradeables([$gradeable], $student_ids) as $gg) {
                /** @var GradedGradeable $gg */
                $students_version[$gg->getSubmitter()->getId()] = $gg->getAutoGradedGradeable()->getHighestVersion();
            }
            $students_full = json_decode(Utils::getAutoFillData($students, $students_version));
        }

        $github_user_id = '';
        $github_repo_id = '';

        $image_data = [];
        if (!$gradeable->isVcs()) {
            // Prepare notebook image data for displaying
            foreach ($notebook as $cell) {
                if (isset($cell['type']) && $cell['type'] == "image") {
                    $image_name = $cell['image'];
                    $imgPath = FileUtils::joinPaths(
                        $this->core->getConfig()->getCoursePath(),
                        'test_input',
                        $gradeable->getId(),
                        $image_name
                    );
                    $content_type = FileUtils::getContentType($imgPath);
                    if (substr($content_type, 0, 5) === 'image') {
                        // Read image path, convert to base64 encoding
                        $inputImageData = base64_encode(file_get_contents($imgPath));
                        // Format the image SRC:  data:{mime};base64,{data};
                        $inputimagesrc = 'data: ' . mime_content_type($imgPath) . ';charset=utf-8;base64,' . $inputImageData;
                        // insert the sample image data
                        $image_data[$image_name] = $inputimagesrc;
                    }
                }
            }

            if ($version_instance !== null) {
                $display_version = $version_instance->getVersion();
                for ($i = 1; $i <= $gradeable->getAutogradingConfig()->getNumParts(); $i++) {
                    foreach ($version_instance->getPartFiles($i)['submissions'] as $file) {
                        $size = number_format($file['size'] / 1024, 2);
                        // $escape_quote_filename = str_replace('\'','\\\'',$file['name']);
                        if (substr($file['relative_name'], 0, strlen("part{$i}/")) === "part{$i}/") {
                            $escape_quote_filename = str_replace('\'', '\\\'', substr($file['relative_name'], strlen("part{$i}/")));
                        }
                        else {
                            $escape_quote_filename = str_replace('\'', '\\\'', $file['relative_name']);
                        }

                        $old_files[] = ['name' => $escape_quote_filename, 'size' => $size, 'part' => $i];
                    }
                }
            }
        }
        else {
            // Get path to VCS_CHECKOUT
            $gradeable_path = FileUtils::joinPaths($this->core->getConfig()->getCoursePath(), "submissions", $gradeable->getId());
            $who_id = $this->core->getUser()->getId();
            $user_path = FileUtils::joinPaths($gradeable_path, $who_id);
            $highest_version = $graded_gradeable->getAutoGradedGradeable()->getHighestVersion();
            $version_path = FileUtils::joinPaths($user_path, $highest_version);
            $path = FileUtils::joinPaths($version_path, ".submit.VCS_CHECKOUT");

            // Load repo and user id
            if (file_exists($path)) {
                $json = json_decode(file_get_contents($path), true);
                if (!is_null($json)) {
                    if (isset($json["git_user_id"])) {
                        $github_user_id = $json["git_user_id"];
                    }
                    if (isset($json["git_repo_id"])) {
                        $github_repo_id = $json["git_repo_id"];
                    }
                }
            }
        }

        $component_names = array_map(function (Component $component) {
            return $component->getTitle();
        }, $gradeable->getComponents());

        $input_data = array_map(function (AbstractGradeableInput $inp) {
            return $inp->toArray();
        }, $inputs);

        $highest_version = $graded_gradeable !== null ? $graded_gradeable->getAutoGradedGradeable()->getHighestVersion() : 0;

        // instructors can access this page even if they aren't on a team => don't create errors
        $my_team = $graded_gradeable !== null ? $graded_gradeable->getSubmitter()->getTeam() : "";
        $my_repository = $graded_gradeable !== null ? $gradeable->getRepositoryPath($this->core->getUser(), $my_team) : "";
        $notebook_data = $graded_gradeable !== null ? $graded_gradeable->getUpdatedNotebook() : array();
        $testcase_messages = $version_instance !== null ? $version_instance->getTestcaseMessages() : array();

        // Import custom stylesheet to style notebook items
        $this->core->getOutput()->addInternalCss('gradeable-notebook.css');

        // Import custom js for notebook items
        $this->core->getOutput()->addInternalJs('gradeable-notebook.js');

        $this->core->getOutput()->addInternalCss('submitbox.css');
        $this->core->getOutput()->addVendorCss(FileUtils::joinPaths('codemirror', 'codemirror.css'));
        $this->core->getOutput()->addVendorCss(FileUtils::joinPaths('codemirror', 'theme', 'eclipse.css'));
        $this->core->getOutput()->addVendorCss(FileUtils::joinPaths('codemirror', 'theme', 'monokai.css'));
        $this->core->getOutput()->addVendorJs(FileUtils::joinPaths('codemirror', 'codemirror.js'));
        $this->core->getOutput()->addVendorJs(FileUtils::joinPaths('codemirror', 'mode', 'clike', 'clike.js'));
        $this->core->getOutput()->addVendorJs(FileUtils::joinPaths('codemirror', 'mode', 'python', 'python.js'));
        $this->core->getOutput()->addVendorJs(FileUtils::joinPaths('codemirror', 'mode', 'shell', 'shell.js'));

        $DATE_FORMAT = "m/d/Y @ h:i A";

        return $this->core->getOutput()->renderTwigTemplate('submission/homework/SubmitBox.twig', [
            'base_url' => $this->core->getConfig()->getBaseUrl(),
            'gradeable_id' => $gradeable->getId(),
            'gradeable_name' => $gradeable->getTitle(),
            'formatted_due_date' => $gradeable->getSubmissionDueDate()->format($DATE_FORMAT),
            'part_names' => $gradeable->getAutogradingConfig()->getPartNames(),
            'is_vcs' => $gradeable->isVcs(),
            'vcs_subdirectory' => $gradeable->getVcsSubdirectory(),
            'vcs_host_type' => $gradeable->getVcsHostType(),
            'github_user_id' => $github_user_id,
            'github_repo_id' => $github_repo_id,
            'has_due_date' => $gradeable->hasDueDate(),
            'repository_path' => $my_repository,
            'show_no_late_submission_warning' => !$gradeable->isLateSubmissionAllowed() && $gradeable->isSubmissionClosed(),
            // This is only used as a placeholder, so the who loads this page is the 'user' unless the
            //  client overrides the user
            'user_id' => $this->core->getUser()->getId(),
            'has_gradeable_message' => $gradeable->getAutogradingConfig()->getGradeableMessage() !== null
               && $gradeable->getAutogradingConfig()->getGradeableMessage() !== '',
            'gradeable_message' => $gradeable->getAutogradingConfig()->getGradeableMessage(),
            'allowed_late_days' => $gradeable->getLateDays(),
            'num_inputs' => $gradeable->getAutogradingConfig()->getNumInputs(),
            'max_submissions' => $gradeable->getAutogradingConfig()->getMaxSubmissions(),
            'display_version' => $display_version,
            'highest_version' => $highest_version,
            'student_page' => $student_page,
            'students_full' => $students_full,
            'late_days_use' => $late_days_use,
            'old_files' => $old_files,
            'inputs' => $input_data,
            'notebook' => $notebook_data,
            'testcase_messages' => $testcase_messages,
            'image_data' => $image_data,
            'component_names' => $component_names,
            'upload_message' => $this->core->getConfig()->getUploadMessage(),
            "csrf_token" => $this->core->getCsrfToken(),
            'has_overridden_grades' => $graded_gradeable ? $graded_gradeable->hasOverriddenGrades() : false,
            'days_to_be_charged' => $days_to_be_charged,
            'max_file_size' => Utils::returnBytes(ini_get('upload_max_filesize')),
            'max_post_size' => Utils::returnBytes(ini_get('post_max_size'))
        ]);
    }

    /**
     * @param Gradeable $gradeable
     * @return string
     */
    private function renderBulkUploadBox(Gradeable $gradeable): string {
        $all_directories = $gradeable->getSplitPdfFiles();

        $files = [];
        $cover_images = [];
        $count = 1;
        $count_array = array();
        $bulk_upload_data = [];
        foreach ($all_directories as $timestamp => $content) {
            $dir_files = $content['files'];
            foreach ($dir_files as $filename => $details) {
                if ($filename === 'decoded.json') {
                    $bulk_upload_data +=  FileUtils::readJsonFile($details['path']);
                }
                $clean_timestamp = str_replace('_', ' ', $timestamp);
                $path = rawurlencode(htmlspecialchars($details['path']));
                //get the cover image if it exists
                if (strpos($filename, '_cover.jpg') && pathinfo($filename)['extension'] === 'jpg') {
                    $corrected_filename = rawurlencode(htmlspecialchars($filename));
                    $url = $this->core->buildCourseUrl(['display_file']) . '?' . http_build_query([
                        'dir' => 'split_pdf',
                        'file' => $corrected_filename,
                        'path' => $path,
                        'ta_grading' => 'false'
                    ]);
                    $cover_images[] = [
                        'filename' => $corrected_filename,
                        'url' => $url,
                    ];
                }
                if (
                    strpos($filename, 'cover') === false
                    || pathinfo($filename)['extension'] === 'json'
                    || pathinfo($filename)['extension'] === "jpg"
                ) {
                    continue;
                }
                // get the full filename for PDF popout
                // add 'timestamp / full filename' to count_array so that path to each filename is to the full PDF, not the cover
                $filename = rawurlencode(htmlspecialchars($filename));
                $url = $this->core->buildCourseUrl(['display_file']) . '?' . http_build_query([
                    'dir' => 'split_pdf',
                    'file' => $filename,
                    'path' => $path,
                    'ta_grading' => 'false'
                ]);
                $filename_full = str_replace('_cover.pdf', '.pdf', $filename);
                $path_full = str_replace('_cover.pdf', '.pdf', $path);
                $url_full = $this->core->buildCourseUrl(['display_file']) . '?' . http_build_query([
                    'dir' => 'uploads',
                    'file' => $filename_full,
                    'path' => $path_full,
                    'ta_grading' => 'false'
                ]);
                $count_array[$count] = FileUtils::joinPaths($timestamp, rawurlencode($filename_full));
                //decode the filename after to display correctly for users
                $filename_full = rawurldecode($filename_full);
                $cover_image_name = substr($filename, 0, -3) . "jpg";
                $cover_image = [];
                foreach ($cover_images as $img) {
                    if ($img['filename'] === $cover_image_name) {
                        $cover_image = $img;
                    }
                }
                $files[] = [
                    'clean_timestamp' => $clean_timestamp,
                    'filename_full' => $filename_full,
                    'filename' => $filename,
                    'url' => $url,
                    'url_full' => $url_full,
                    'cover_image' => $cover_image
                ];
                $count++;
            }
        }

        for ($i = 0; $i < count($files); $i++) {
            if (array_key_exists('is_qr', $bulk_upload_data) && $bulk_upload_data['is_qr'] && !array_key_exists($files[$i]['filename_full'], $bulk_upload_data)) {
                continue;
            }
            elseif (array_key_exists('is_qr', $bulk_upload_data) && $bulk_upload_data['is_qr']) {
                $data = $bulk_upload_data[ $files[$i]['filename_full'] ];
            }

            $page_count = 0;
            $is_valid = true;
            $id = '';

            //decoded.json may be read before the assoicated data is written, check if key exists first
            if (array_key_exists('is_qr', $bulk_upload_data) && $bulk_upload_data['is_qr']) {
                if (array_key_exists('id', $data)) {
                    $id = $data['id'];
                    $is_valid = null !== $this->core->getQueries()->getUserByIdOrNumericId($id);
                }
                else {
                    //set the blank id as invalid for now, after a page refresh it will recorrect
                    $id = '';
                    $is_valid = false;
                }
                if (array_key_exists('page_count', $data)) {
                    $page_count = $data['page_count'];
                }
            }
            else {
                $is_valid = true;
                $id = '';
                if (array_key_exists('page_count', $bulk_upload_data)) {
                    $page_count = $bulk_upload_data['page_count'];
                }
            }

            $files[$i] += ['page_count' => $page_count,
                           'id' => $id,
                           'valid' => $is_valid ];
        }

        $semester = $this->core->getConfig()->getSemester();
        $course = $this->core->getConfig()->getCourse();
        $gradeable_id = $_REQUEST['gradeable_id'] ?? '';
        return $this->core->getOutput()->renderTwigTemplate('submission/homework/BulkUploadBox.twig', [
            'gradeable_id' => $gradeable->getId(),
            'team_assignment' => $gradeable->isTeamAssignment(),
            'max_team_size' => $gradeable->getTeamSizeMax(),
            'count_array' => $count_array,
            'files' => $files,
            'csrf_token' => $this->core->getCsrfToken()
        ]);
    }

    /**
     * @param GradedGradeable $graded_gradeable
     * @return string
     */
    private function renderNoSubmissionBox($graded_gradeable): string {
        $team_assignment = $graded_gradeable === null ? true : $graded_gradeable->getGradeable()->isTeamAssignment();
        $member_list = $graded_gradeable !== null && $team_assignment
                    ? $graded_gradeable->getSubmitter()->getTeam()->getMemberList() : '';
        return $this->core->getOutput()->renderTwigTemplate('submission/homework/NoSubmissionBox.twig', [
            'team_assignment' => $team_assignment,
            'member_list' => $member_list
        ]);
    }

    /**
     * @param GradedGradeable $graded_gradeable
     * @param AutoGradedVersion|null $version_instance
     * @param bool $show_hidden
     * @return string
     */

    /**
     * @param GradedGradeable $graded_gradeable
     * @param AutoGradedVersion|null $version_instance
     * @param bool $show_hidden
     * @return string
     */
    private function renderVersionBox(GradedGradeable $graded_gradeable, $version_instance, bool $show_hidden): string {
        $gradeable = $graded_gradeable->getGradeable();
        $autograding_config = $gradeable->getAutogradingConfig();
        $auto_graded_gradeable = $graded_gradeable->getAutoGradedGradeable();
        $active_version_number = $auto_graded_gradeable->getActiveVersion();
        $display_version = 0;

        $version_data = array_map(function (AutoGradedVersion $version) use ($gradeable) {
            return [
                'points' => $version->getNonHiddenPoints(),
                'days_late' => $gradeable->isStudentSubmit() && $gradeable->hasDueDate() ? $version->getDaysLate() : 0
            ];
        }, $auto_graded_gradeable->getAutoGradedVersions());

        //sort array by version number after values have been mapped
        ksort($version_data);

        $param = [];
        $show_testcases = false;
        $show_incentive_message = false;
        $history = null;

        if ($version_instance !== null) {
            $display_version = $version_instance->getVersion();

            if ($version_instance->isAutogradingComplete()) {
                $history = $version_instance->getLatestHistory();

                foreach ($version_instance->getTestcases() as $testcase) {
                    if ($testcase->canView()) {
                        $show_testcases = true;
                        break;
                    }
                }
            }

            $show_incentive_message = $autograding_config->hasEarlySubmissionIncentive()
                && $active_version_number > 0
                && $version_instance->getEarlyIncentivePoints() >= $autograding_config->getEarlySubmissionMinimumPoints()
                && $version_instance->getDaysEarly() > $autograding_config->getEarlySubmissionMinimumDaysEarly();

            $files = $version_instance->getFiles();

            $param = array_merge($param, [
                'in_queue' => $version_instance->isQueued(),
                'grading' => $version_instance->isGrading(),
                'submission_time' => DateUtils::dateTimeToString($version_instance->getSubmissionTime()),
                'days_late' => $version_instance->getDaysLate(),
                'num_autogrades' => $version_instance->getHistoryCount(),
                'files' => array_merge($files['submissions'], $files['checkout']),
                'display_version_days_late' => $version_instance->getDaysLate(),
                'result_text' => $this->core->getOutput()->renderTemplate('AutoGrading', 'showResults', $version_instance, $show_hidden)
            ]);

            if ($history !== null) {
                $param = array_merge($param, [
                    'results' => 0,
                    'grade_time' => $history->getGradeTime(),
                    'grading_finished' => DateUtils::dateTimeToString($history->getGradingFinished()),
                    'wait_time' => $history->getWaitTime(),
                    'revision' => $history->getVcsRevision(),
                ]);
            }

            if ($version_instance->isQueued()) {
                $param = array_merge($param, [
                    'queue_pos' => $version_instance->getQueuePosition(),
                    'queue_total' => $this->core->getGradingQueue()->getQueueCount()
                ]);
            }
        }

        // If its not git checkout
        $can_download = !$gradeable->isVcs();

        $active_same_as_graded = true;
        if ($active_version_number !== 0 || $display_version !== 0) {
            if ($graded_gradeable->hasTaGradingInfo()) {
                $active_same_as_graded = $graded_gradeable->getTaGradedGradeable()->getGradedVersion() === $active_version_number;
            }
        }

        $cancel_url = $this->core->buildCourseUrl(['gradeable', $gradeable->getId(), 'version' ,'0']);

        $change_version_url = $this->core->buildCourseUrl(['gradeable', $gradeable->getId(), 'version', $display_version]);

        $view_version_url = $this->core->buildCourseUrl(['gradeable', $gradeable->getId()]) . '/';

        $check_refresh_submission_url = $this->core->buildCourseUrl(['gradeable', $gradeable->getId(), $display_version, 'check_refresh']);

        $this->core->getOutput()->addVendorJs(FileUtils::joinPaths('mermaid', 'mermaid.min.js'));

        $param = array_merge($param, [
            'gradeable_id' => $gradeable->getId(),
            'hide_submitted_files' => $gradeable->getAutogradingConfig()->getHideSubmittedFiles(),
            'hide_version_and_test_details' => $gradeable->getAutogradingConfig()->getHideVersionAndTestDetails(),
            'has_manual_grading' => $gradeable->isTaGrading(),
            // TODO: change this to submitter ID when the MiscController uses new model
            'user_id' => $this->core->getUser()->getId(),
            'team_assignment' => $gradeable->isTeamAssignment(),
            'team_members' => $gradeable->isTeamAssignment() ? $graded_gradeable->getSubmitter()->getTeam()->getMemberList() : [],
            'display_version' => $display_version,
            'active_version' => $active_version_number,
            'cancel_url' => $cancel_url,
            'change_version_url' => $change_version_url,
            'view_version_url' => $view_version_url,
            'check_refresh_submission_url' => $check_refresh_submission_url,
            'versions' => $version_data,
            'total_points' => $autograding_config->getTotalNonHiddenNonExtraCredit(),
            'allowed_late_days' => $gradeable->getLateDays(),
            'ta_grades_released' => $gradeable->isTaGradeReleased(),
            'is_vcs' => $gradeable->isVcs(),
            'can_download' => $can_download,
            'can_change_submissions' => $this->core->getUser()->accessGrading() || $gradeable->isStudentSubmit(),
            'can_see_all_versions' => $this->core->getUser()->accessGrading() || $gradeable->isStudentSubmit(),
            'show_testcases' => $show_testcases,
            'active_same_as_graded' => $active_same_as_graded,
            'show_incentive_message' => $show_incentive_message,
            "csrf_token" => $this->core->getCsrfToken()
        ]);

        $this->core->getOutput()->addInternalJs('confetti.js');
        return $this->core->getOutput()->renderTwigTemplate('submission/homework/CurrentVersionBox.twig', $param);
    }

    /**
     * @param GradedGradeable $graded_gradeable
     * @param bool $regrade_available
     * @return string
     */
    private function renderTAResultsBox(GradedGradeable $graded_gradeable, bool $regrade_available): string {

        $rendered_ta_results = '';
        $been_ta_graded = false;
        if ($graded_gradeable->isTaGradingComplete()) {
            $been_ta_graded = true;
            $rendered_ta_results = $this->core->getOutput()->renderTemplate(
                'AutoGrading',
                'showTAResults',
                $graded_gradeable->getTaGradedGradeable(),
                $regrade_available,
                $graded_gradeable->getAutoGradedGradeable()->getActiveVersionInstance()->getFiles()
            );
        }

        return $this->core->getOutput()->renderTwigTemplate('submission/homework/TAResultsBox.twig', [
            'been_ta_graded' => $been_ta_graded,
            'rendered_ta_results' => $rendered_ta_results]);
    }

    /**
     * @param GradedGradeable $graded_gradeable
     * @param bool $can_inquiry
     * @return string
     */
    private function renderRegradeBox(GradedGradeable $graded_gradeable, bool $can_inquiry): string {
        return $this->core->getOutput()->renderTwigTemplate('submission/homework/RegradeBox.twig', [
            'graded_gradeable' => $graded_gradeable,
            'can_inquiry' => $can_inquiry
        ]);
    }

    /**
     * @param GradedGradeable $graded_gradeable
     * @param bool $can_inquirye
     * @return string
     */
    public function showRegradeDiscussion(GradedGradeable $graded_gradeable, bool $can_inquiry): string {
        $grade_inquiry_per_component_allowed = $graded_gradeable->getGradeable()->isGradeInquiryPerComponentAllowed();
        $regrade_message = $this->core->getConfig()->getRegradeMessage();
        $request_regrade_url = $this->core->buildCourseUrl([
            'gradeable',
            $graded_gradeable->getGradeable()->getId(),
            'grade_inquiry',
            'new'
        ]);
        $change_request_status_url = $this->core->buildCourseUrl([
            'gradeable',
            $graded_gradeable->getGradeable()->getId(),
            'grade_inquiry',
            'toggle_status'
        ]);
        $make_regrade_post_url = $this->core->buildCourseUrl([
            'gradeable',
            $graded_gradeable->getGradeable()->getId(),
            'grade_inquiry',
            'post'
        ]);

        $grade_inquiries = $graded_gradeable->getRegradeRequests();
        $gradeable_components = $graded_gradeable->getGradeable()->getComponents();

        // initialize grade inquiries array with all posts grade inquiry to aggregate all posts
        $grade_inquiries_twig_array = [];
        if (!empty($grade_inquiries)) {
            $grade_inquiries_twig_array[0] = ['posts' => []];
            $grade_inquiry_posts = $this->core->getQueries()->getRegradeDiscussions($grade_inquiries);
            foreach ($grade_inquiries as $grade_inquiry) {
                $gc_id = $grade_inquiry->getGcId() ?? 0;
                $gc_title = '';
                if ($gc_id != 0) {
                    $component = $graded_gradeable->getGradeable()->getComponent($gc_id);
                    $gc_title = $component->getTitle();
                }

                // format posts
                $posts = [];
                foreach ($grade_inquiry_posts[$grade_inquiry->getId()] as $post) {
                    if (empty($post)) {
                        break;
                    }
                    $is_staff = $this->core->getQueries()->isStaffPost($post['user_id']);
                    $name = $this->core->getQueries()->getUserById($post['user_id'])->getDisplayedFirstName();
                    $date = DateUtils::parseDateTime($post['timestamp'], $this->core->getConfig()->getTimezone());
                    $content = $post['content'];
                    $posts[] = [
                        'is_staff' => $is_staff,
                        'date' => date_format($date, 'm/d/Y g:i A'),
                        'date_sort' => $date,
                        'name' => $name,
                        'content' => $content,
                        'gc_title' => $gc_title
                    ];
                }

                if ($gc_id != 0) {
                    // grade inquiry object
                    $grade_inquiry_twig_object = [
                        'id' => $grade_inquiry->getId(),
                        'gc_id' => $gc_id,
                        'status' => $grade_inquiry->getStatus(),
                        'posts' => $posts
                    ];
                    // add grade inquiry to grade inquiries array
                    $grade_inquiries_twig_array[$gc_id] = $grade_inquiry_twig_object;
                }
                else {
                    $grade_inquiries_twig_array[0]['id'] = $grade_inquiry->getId();
                    $grade_inquiries_twig_array[0]['gc_id'] = $gc_id;
                    $grade_inquiries_twig_array[0]['status'] = $grade_inquiry->getStatus();
                }
                $grade_inquiries_twig_array[0]['posts'] = array_merge($grade_inquiries_twig_array[0]['posts'], $posts);
            }
        }
        // sort by most recent posts
        if (!empty($grade_inquiries_twig_array)) {
            usort($grade_inquiries_twig_array[0]['posts'], function ($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
        }



        // construct components array for tabs
        $components_twig_array = [];
        if ($grade_inquiry_per_component_allowed) {
            foreach ($gradeable_components as $component) {
                $component_object = [
                    'id' => $component->getId(),
                    'title' => $component->getTitle(),
                ];
                $components_twig_array[] = $component_object;
            }
        }
        $components_twig_array[] = ['id' => 0, 'title' => 'All'];

        return $this->core->getOutput()->renderTwigTemplate('submission/regrade/Discussion.twig', [
            'grade_inquiries' => $grade_inquiries_twig_array,
            'request_regrade_url' => $request_regrade_url,
            'change_request_status_url' => $change_request_status_url,
            'make_request_post_url' => $make_regrade_post_url,
            'has_submission' => $graded_gradeable->hasSubmission(),
            'submitter_id' => $graded_gradeable->getSubmitter()->getId(),
            'g_id' => $graded_gradeable->getGradeable()->getId(),
            'regrade_message' => $regrade_message,
            'can_inquiry' => $can_inquiry,
            'is_grading' => $this->core->getUser()->accessGrading(),
            'grade_inquiry_per_component_allowed' => $grade_inquiry_per_component_allowed,
            'gradeable_components' => $components_twig_array,
            "csrf_token" => $this->core->getCsrfToken()
        ]);
    }
}
