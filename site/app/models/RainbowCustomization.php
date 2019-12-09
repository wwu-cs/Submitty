<?php

namespace app\models;

use app\exceptions\ValidationException;
use app\libraries\Core;
use app\libraries\database\DatabaseQueries;
use app\libraries\DatabaseUtils;
use app\libraries\FileUtils;
use app\libraries\GradeableType;

/**
 * Class RainbowCustomization
 * @package app\models
 *
 * This class is a RainbowGrades Customization.  It may contain the data found in customization.json but it also
 * contains additional data that is used by the web user interface to aid in generation/customization.
 */
class RainbowCustomization extends AbstractModel {
    /**/
    protected $core;
    private $customization_data = [];
    private $has_error;
    private $error_messages;
    private $used_buckets = [];
    private $available_buckets;
    private $RCJSON;                           // This is the customization.json php object, or null if it wasn't found
    private $sections;                         // Contains section ids mapped to labels

    /*XXX: This is duplicated from AdminGradeableController.php, we really shouldn't have multiple copies lying around.
     * On top of that, Rainbow Grades has its own enum internally. Since that's a separate repo it's probably
     * unavoidable, but the fewer places we can duplicate this, the better.
     */
    /*XXX: It's also going to be annoying to have "none (for practice only)" since in customization it's "none"
     * which is a lot nicer to type.
     */
    //XXX: 'none (for practice only)' we want to truncate to just 'none'.
    const syllabus_buckets = [
        'homework', 'assignment', 'problem-set',
        'quiz', 'test', 'exam',
        'exercise', 'lecture-exercise', 'reading', 'lab', 'recitation', 'worksheet',
        'project',
        'participation', 'note',
        'none'];


    public function __construct(Core $main_core) {
        parent::__construct($main_core);
        $this->has_error = "false";
        $this->error_messages = [];

        $this->sections = (object) [];

        // Attempt to load json from customization file
        // If it fails then set to null, will be used to load defaults later
        $this->RCJSON = new RainbowCustomizationJSON($this->core);

        try {
            $this->RCJSON->loadFromJsonFile();
        }
        catch (\Exception $e) {
            $this->RCJSON = null;
        }
    }

    public function buildCustomization() {

        //This function should examine the DB(?) / a file(?) and if customization settings already exist, use them. Otherwise, populate with defaults.
        foreach (self::syllabus_buckets as $bucket) {
            $this->customization_data[$bucket] = [];
        }

        $gradeables = $this->core->getQueries()->getGradeableConfigs(null);
        foreach ($gradeables as $gradeable) {
            //XXX: 'none (for practice only)' we want to truncate to just 'none', otherwise use full bucket name
            $bucket = $gradeable->getSyllabusBucket() == "none (for practice only)" ? "none" : $gradeable->getSyllabusBucket();
            /*if(!isset($this->customization_data[$bucket])){
                $this->customization_data[$bucket] = [];
            }*/
            /*XXX: Right now we aren't yet worried about pulling in from existing customization.json but if we do, then what happens in the event of a conflict?
             * I'm tempted to say for version 1.0, too bad, we override with the version from the DB (since that's more up to date), if the gradeable exists
             * In a later version we may want to highlight it in red or something, and ask the user which number to use? I'm not sure if there's a use case for using
             * the version in the customization.json, but the warning might be nice. Might even be an error state where action is required, just so the user isn't
             * confused when the grade distribution shifts around.
             */

            $max_score = $gradeable->getTAPoints();
            //If the gradeable has autograding points, load the config and add the non-extra-credit autograder total
            if ($gradeable->hasAutogradingConfig()) {
                $last_index = count($this->customization_data[$bucket]) - 1;
                $max_score += $gradeable->getAutogradingConfig()->getTotalNonExtraCredit();
            }

            $this->customization_data[$bucket][] = [
                "id" => $gradeable->getId(),
                "title" => $gradeable->getTitle(),
                "max_score" => $max_score
            ];
        }

        // Determine which 'buckets' exist in the customization.json
        if (!is_null($this->RCJSON)) {
            $json_gradeables = $this->RCJSON->getGradeables();

            // Place those buckets in $this->used_buckets
            foreach ($json_gradeables as $json_gradeable) {
                $this->used_buckets[] = $json_gradeable->type;
            }
        }

        //XXX: Assuming that the contents of these buckets will be lowercase
        $this->available_buckets = array_diff(self::syllabus_buckets, $this->used_buckets);
    }

    /**
     * Get an array containing what percentage of the grade the bucket counts toward.  The key is the name of the
     * bucket and the value is the percentage which has been cast back to a whole number integer.  This differs
     * from the customization.json in that in the json file the percentage is represented as a decimal between 0 and 1.
     * This value is represented as an integer between 0 and 100.
     *
     * ex.  key => value
     *     'test' => 50
     *     'lab' => 25
     *     'homework' => 25
     *
     * @return array
     */
    public function getBucketPercentages() {
        $retArray = [];

        if (!is_null($this->RCJSON)) {
            $json_gradeables = $this->RCJSON->getGradeables();

            $sum = 0;

            foreach ($json_gradeables as $json_gradeable) {
                // Get percentage, cast back to whole number integer
                $retArray[$json_gradeable->type] = (int) ($json_gradeable->percent * 100);

                // Keep track of the sum
                $sum += $retArray[$json_gradeable->type];
            }

            // Save the sum of used percentages to special key in array
            $retArray['used_percentage'] = $sum;
        }

        return $retArray;
    }

    public function getCustomizationData() {
        return $this->customization_data;
    }

    public function getAvailableBuckets() {
        return $this->available_buckets;
    }

    public function getUsedBuckets() {
        return $this->used_buckets;
    }

    public function getMessages() {
        return !is_null($this->RCJSON) ? $this->RCJSON->getMessages() : [];
    }

    /**
     * Get display benchmarks
     *
     * Get a multidimensional array that contains not only a list of usable display benchmarks but also which ones
     * are in use (in the customization.json)
     *
     * @return array multidimensional array of display benchmark data
     */
    public function getDisplayBenchmarks() {
        // Get allowed benchmarks
        $displayBenchmarks = RainbowCustomizationJSON::allowed_display_benchmarks;
        $retArray = [];

        // If json file available then collect used benchmarks from that, else get empty array
        !is_null($this->RCJSON) ?
            $usedDisplayBenchmarks = $this->RCJSON->getDisplayBenchmarks() :
            $usedDisplayBenchmarks = [];

        // Add data into retArray
        foreach ($displayBenchmarks as $displayBenchmark) {
            in_array($displayBenchmark, $usedDisplayBenchmarks) ? $isUsed = true : $isUsed = false;

            // Add benchmark to return array
            $retArray[] = ['id' => $displayBenchmark, 'isUsed' => $isUsed];
        }

        return $retArray;
    }

    /**
     * Get section ids and labels
     *
     * If no customization.json file exists then this function will generate defaults
     * by examining what sections are registered in the database.  If a file does exist then sections and labels will
     * be read out of that.  If it turns out that new sections have been registered in the database that
     * dont yet exist in the file, those new sections will be added with default values.
     *
     * @return object The object mapping section ids to labels
     */
    public function getSectionsAndLabels() {
        // Get sections from db
        $db = new DatabaseQueries($this->core);
        $db_sections = $db->getRegistrationSections();

        $sections = [];

        // Configure sections
        foreach ($db_sections as $section) {
            $key = $section['sections_registration_id'];

            $sections[$key] = $key;
        }

        // If RCJSON is not null then load it
        if (!is_null($this->RCJSON)) {
            // Get sections from the file
            $sectionsFromFile = (array) $this->RCJSON->getSection();

            // If sections from database is larger than sections from file then there must be a new section in
            // in the database, add new fields into sections from file with defaults
            $sectionsFromFileCount = count($sectionsFromFile);
            $sectionsCount = count($sections);

            if ($sectionsFromFileCount != $sectionsCount) {
                for ($i = $sectionsFromFileCount + 1; $i <= $sectionsCount; $i++) {
                    $sectionsFromFile[$i] = (string) $i;
                }
            }

            return (object) $sectionsFromFile;
        }
        else {
            // RCJSON was null so return database sections as default
            // Collect sections out of the database
            return (object) $sections;
        }
    }

    // This function handles processing the incoming post data
    public function processForm() {

        // Get a new customization file
        $this->RCJSON = new RainbowCustomizationJSON($this->core);

        $form_json = $_POST['json_string'];
        $form_json = json_decode($form_json);

        if (isset($form_json->display_benchmark)) {
            foreach ($form_json->display_benchmark as $benchmark) {
                $this->RCJSON->addDisplayBenchmarks($benchmark);
            }
        }

        if (isset($form_json->section)) {
            foreach ($form_json->section as $key => $value) {
                $this->RCJSON->addSection((string) $key, $value);
            }
        }

        if (isset($form_json->gradeables)) {
            foreach ($form_json->gradeables as $gradeable) {
                $this->RCJSON->addGradeable($gradeable);
            }
        }

        if (isset($form_json->messages)) {
            foreach ($form_json->messages as $message) {
                $this->RCJSON->addMessage($message);
            }
        }

        // Write to customization file
        $this->RCJSON->saveToJsonFile();

        // Configure json to go into jobs queue
        $job_json = (object) [];
        $job_json->job = 'RunAutoRainbowGrades';
        $job_json->semester = $this->core->getConfig()->getSemester();
        $job_json->course = $this->core->getConfig()->getCourse();

        // Encode
        $job_json = json_encode($job_json, JSON_PRETTY_PRINT);

        // Create path to new jobs queue json
        $path = '/var/local/submitty/daemon_job_queue/auto_rainbow_' .
            $this->core->getConfig()->getSemester() .
            '_' .
            $this->core->getConfig()->getCourse() .
            '.json';

        // Place in queue
        file_put_contents($path, $job_json);

//        $this->has_error = "true";
//        foreach($_POST as $field => $value){
//            $this->error_messages[] = "$field: $value";
//        }
//        throw new ValidationException('Debug Rainbow Grades error', $this->error_messages);
    }

    public function error() {
        return $this->has_error;
    }

    public function getErrorMessages() {
        return $this->error_messages;
    }
}
