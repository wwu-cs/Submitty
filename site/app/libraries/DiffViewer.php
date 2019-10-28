<?php

namespace app\libraries;

/**
 * Class DiffViewer
 *
 * Given an expected, actual, and differences file,
 * will generate the display for them (in either
 * HTML or plain-text)
 */
class DiffViewer {

    private $actual_file;
    private $expected_file;
    private $diff_file;
    private $image_difference;

    private $built = false;

    /**
     * @var bool
     */
    private $has_actual = false;
    private $actual_file_image = "";
    private $actual_file_name = "";

    /**
     * @var bool
     */
    private $display_actual = false;

    /**
     * @var array
     */
    private $actual = array();

    /**
     * @var bool
     */
    private $has_expected = false;
    private $expected_file_image = "";

    private $has_difference = false;
    private $difference_file_image = "";

    /**
     * @var bool
     */
    private $display_expected = false;

    /**
     * @var array
     */
    private $expected = array();

    /**
     * @var array
     */
    private $diff = array();

    /**
     * @var array
     */
    private $add = array();

    /**
     * @var array
     */
    private $link = array();

    /**
     * @var string
     */
    private $id = "id";
    /**
     * @var array
     */
    private $white_spaces = array();

    const SPECIAL_CHARS_ORIGINAL = 'original';
    const SPECIAL_CHARS_ESCAPE = 'escape';
    const SPECIAL_CHARS_UNICODE = 'unicode';

    //The first element of array is used to find the special char, the second is the visual representation, the third is
    // the escape code
    const SPECIAL_CHARS_LIST = array(
                                "space" => [" ", "&nbsp;", " "],
                                "tabs" => ["\t", "↹", "\\t"],
                                "carriage return" => ["\r", "↵<br>", "\\r<br>"],
                                "null characters" => ["\0", "^@", "\\0"],
                                "smart quote1" => ["\xC2\xAB", "\"", "\\xC2\\xAB"],
                                "smart quote2" => ["\xE2\x80\x98", "\"", "\\xE2\\x80\\x98"],
                                "smart quote3" => ["\xE2\x80\x99", "'", "\\xE2\\x80\\x99"],
                                "em dash" => ["\xE2\x80\x94", "—", "\\xE2\\x80\\x94"],
                                "en dash" => ["\xE2\x80\x93", "–", "\\xE2\\x80\\x93"]
                               );

    static function isValidSpecialCharsOption($option) {
        return in_array($option, [
            self::SPECIAL_CHARS_ORIGINAL,
            self::SPECIAL_CHARS_UNICODE,
            self::SPECIAL_CHARS_ESCAPE
        ]);
    }

    const EXPECTED = 'expected';
    const ACTUAL = 'actual';

    static function isValidType($type) {
        return in_array($type, [
            self::EXPECTED,
            self::ACTUAL
        ]);
    }

    /**
     * Reset the DiffViewer to its starting values.
     */
    public function reset() {
        $this->has_actual = false;
        $this->display_actual = false;
        $this->actual = array();
        $this->has_expected = false;
        $this->display_expected = false;
        $this->expected = array();
        $this->diff = array();
        $this->add = array();
        $this->link = array();
    }

    /**
     * Load the actual file, expected file, and diff json, using them to populate the necessary arrays for
     * display them later back to the user
     *
     * @param $actual_file
     * @param $expected_file
     * @param $diff_file
     * @param $image_difference
     * @param $id_prepend
     *
     * @throws \Exception
     */
    public function __construct($actual_file, $expected_file, $diff_file, $image_difference, $id_prepend="id") {
        $this->id = rtrim($id_prepend, "_")."_";
        $this->actual_file = $actual_file;
        $this->expected_file = $expected_file;
        $this->diff_file = $diff_file;
        $this->image_difference = $image_difference;
    }

    public function destroyViewer() {
        $this->reset();
        $this->built = false;
    }

    /**
     * @throws \Exception
     */
    public function buildViewer() {
        if ($this->built) {
            return;
        }

        //TODO: Implement a better way to deal with large files
        //.25MB (TEMP VALUE)
        $size_limit = 262144;

        $actual_file = $this->actual_file;
        $expected_file = $this->expected_file;
        $diff_file = $this->diff_file;
        $can_diff = true;
        $image_difference = $this->image_difference;
        if (!file_exists($actual_file) && $actual_file != "") {
            throw new \Exception("'{$actual_file}' could not be found.");
        }
        else if ($actual_file != "") {
            // TODO: fix this hacky way to deal with images
            if (Utils::isImage($actual_file)) {
                $this->actual_file_image = $actual_file;
            }
            else {
                if(filesize($actual_file) < $size_limit){
                    $this->actual_file_name = $actual_file;
                    $this->actual = file_get_contents($actual_file);
                    $this->has_actual = trim($this->actual) !== "" ? true: false;
                    $this->actual = explode("\n", $this->actual);
                    $this->display_actual = true;
                }
                else{
                    $this->actual_file_name = $actual_file;
                    $can_diff = false;
                    //load in the first sizelimit characters of the file (TEMP VALUE)
                    $this->actual = file_get_contents($actual_file, null, null, 0, $size_limit);
                    $this->has_actual = trim($this->actual) !== "" ? true: false;
                    $this->actual = explode("\n", $this->actual);
                    $this->display_actual = true;
                }
            }
        }

        if (!file_exists($expected_file) && $expected_file != "") {
            throw new \Exception("'{$expected_file}' could not be found.");
        }
        else if ($expected_file != "") {
            if (Utils::isImage($expected_file)) {
                $this->expected_file_image = $expected_file;
            }
            else{
                if(filesize($expected_file) < $size_limit){
                    $this->expected = file_get_contents($expected_file);
                    $this->has_expected = trim($this->expected) !== "" ? true : false;
                    $this->expected = explode("\n", $this->expected);
                    $this->display_expected = true;
                }
                else{
                    $can_diff = false;
                    //load in the first sizelimit characters of the file (TEMP VALUE)
                    $this->expected = file_get_contents($expected_file, null, null, 0, $size_limit);
                    $this->has_expected = trim($this->expected) !== "" ? true : false;
                    $this->expected = explode("\n", $this->expected);
                    $this->display_expected = true;
                }
            }
        }

        if (!file_exists($image_difference) && $image_difference != "") {
            throw new \Exception("'{$expected_file}' could not be found.");
        }
        else if ($image_difference != "") {
            if (Utils::isImage($image_difference)) {
                $this->difference_file_image = $image_difference;
            }
        }

        if (!file_exists($diff_file) && $diff_file != "") {
            throw new \Exception("'{$diff_file}' could not be found.");
        }
        else if ($diff_file != "") {
            $diff = FileUtils::readJsonFile($diff_file);
        }

        $this->diff = array(self::EXPECTED => array(), self::ACTUAL => array());
        $this->add = array(self::EXPECTED => array(), self::ACTUAL => array());

        if (isset($diff['differences']) && $can_diff) {
            $diffs = $diff['differences'];
            /*
             * Types of things we need to worry about:
             * lines are highlighted
             * lines are highlighted with character sequence
             * need to insert lines into other diff while some lines are highlighted
             */
            foreach ($diffs as $diff) {
                $act_ins = 0;
                $exp_ins = 0;
                $act_start = $diff[self::ACTUAL]['start'];
                $act_final = $act_start;
                if (isset($diff[self::ACTUAL]['line'])) {
                    $act_ins = count($diff[self::ACTUAL]['line']);
                    foreach ($diff[self::ACTUAL]['line'] as $line) {
                        $line_num = $line['line_number'];
                        if (isset($line['char_number'])) {
                            $this->diff[self::ACTUAL][$line_num] = $this->compressRange($line['char_number']);
                        } else {
                            $this->diff[self::ACTUAL][$line_num] = array();
                        }
                        $act_final = $line_num;
                    }
                }

                $exp_start = $diff[self::EXPECTED]['start'];
                $exp_final = $exp_start;
                if (isset($diff[self::EXPECTED]['line'])) {
                    $exp_ins = count($diff[self::EXPECTED]['line']);
                    foreach ($diff[self::EXPECTED]['line'] as $line) {
                        $line_num = $line['line_number'];
                        if (isset($line['char_number'])) {
                            $this->diff[self::EXPECTED][$line_num] = $this->compressRange($line['char_number']);
                        } else {
                            $this->diff[self::EXPECTED][$line_num] = array();
                        }
                        $exp_final = $line_num;
                    }
                }

                $this->link[self::ACTUAL][($act_start)] = (isset($this->link[self::ACTUAL])) ? count($this->link[self::ACTUAL]) : 0;
                $this->link[self::EXPECTED][($exp_start)] = (isset($this->link[self::EXPECTED])) ? count($this->link[self::EXPECTED]) : 0;

                // Do we need to insert blank lines into actual?
                if ($act_ins < $exp_ins) {
                    $this->add[self::ACTUAL][($act_final)] = $exp_ins - $act_ins;
                } // Or into expected?
                else if ($act_ins > $exp_ins) {
                    $this->add[self::EXPECTED][($exp_final)] = $act_ins - $exp_ins;
                }
            }
        }
        $this->built = true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function hasDisplayActual() {
        $this->buildViewer();
        return $this->display_actual;
    }

    /**
     * Boolean flag to indicate whether or not the actual file had any contents to display (or was
     * blank/empty lines). Assuming we do not have a difference file, we can use this flag to indicate
     * if we should actually print out the actual file or not, such as an error file (which ideally is
     * empty in most cases).
     *
     * @return bool
     * @throws \Exception
     */
    public function hasActualOutput() {
        $this->buildViewer();
        return $this->has_actual;
    }

    /**
     * Was there a given expected file and were we able to successfully read from it
     * @return bool
     * @throws \Exception
     */
    public function hasDisplayExpected() {
        $this->buildViewer();
        return $this->display_expected;
    }

    /**
     * Returns boolean indicating whether or not there is any input in the expected.
     * @return bool
     * @throws \Exception
     */
    public function hasExpectedOutput() {
        $this->buildViewer();
        return $this->has_expected;
    }

    /**
     * Return the output HTML for the actual display
     * @param string Option for displaying. Currently only supports show empty space
     *
     * @return string actual html
     * @throws \Exception
     */
    public function getDisplayActual($option = self::SPECIAL_CHARS_ORIGINAL) {
        $this->buildViewer();
        if ($this->display_actual) {
            return $this->getDisplay($this->actual, self::ACTUAL, $option);
        }
        else {
            return "";
        }

    }

    /**
     * @return string the file name for a non-image.
     * @throws \Exception
     */
    public function getActualFilename() {
        $this->buildViewer();
        return $this->actual_file_name;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getActualImageFilename() {
        $this->buildViewer();
        return $this->actual_file_image;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getExpectedImageFilename() {
        $this->buildViewer();
        return $this->expected_file_image;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getDifferenceFilename() {
        $this->buildViewer();
        return $this->difference_file_image;
    }

    /**
     * Return the HTML for the expected display
     * @param string Option for displaying. Currently only supports show empty space
     *
     * @return string expected html
     * @throws \Exception
     */
    public function getDisplayExpected($option = self::SPECIAL_CHARS_ORIGINAL) {
        $this->buildViewer();
        if ($this->display_expected) {
            return $this->getDisplay($this->expected, self::EXPECTED, $option);
        }
        else {
            return "";
        }
    }

    /**
     * Prints out the $lines parameter
     *
     * Prints out the actual codebox with diff view applied
     * using the $this->diff global based off which
     * type we're interested in
     *
     * @param array $lines array of strings (each line)
     * @param string $type which diff we use while printing
     *
     * @return string html to be displayed to user
     * @throws \Exception
     */
    private function getDisplay($lines, $type = self::EXPECTED, $option = self::SPECIAL_CHARS_ORIGINAL) {
        $this->buildViewer();
        $start = null;
        $html = "<div class='diff-container'><div class='diff-code'>\n";

        if (isset($this->add[$type]) && count($this->add[$type]) > 0) {
            if (array_key_exists(-1, $this->add[$type])) {
                $html .= "\t<div class='highlight' id='{$this->id}{$type}_{$this->link[$type][-1]}'>\n";
                for ($k = 0; $k < $this->add[$type][-1]; $k++) {
                    $html .= "\t<div class='row bad'><div class='empty_line'>&nbsp;</div></div>\n";
                }
                $html .= "\t</div>\n";
            }
        }
        /*
         * Run through every line, starting a highlight around any group of mismatched lines that exist (whether
         * there's a difference on that line or that the line doesn't exist.
         */
        $max_digits = strlen((string)count($lines));
        for ($i = 0; $i < count($lines); $i++) {
            $j = $i + 1;
            if ($start === null && isset($this->diff[$type][$i])) {
                $start = $i;
                $html .= "\t<div class='highlight' id='{$this->id}{$type}_{$this->link[$type][$start]}'>\n";
            }
            if (isset($this->diff[$type][$i])) {
                $html .= "\t<div class='bad'>";
            }
            else {
                $html .= "\t<div>";
            }
            $html .= "<span class='line_number'>";
            $digits_at_line = strlen((string)$j);
            for ($counter = ($max_digits - $digits_at_line); $counter > 0; $counter--) {
                $html .= "&nbsp;";
            }
            $html .= "{$j}</span>";
            $html .= "<span class='line_code'>";
            if (isset($this->diff[$type][$i])) {
                // highlight the line
                $current = 0;
                // character highlighting
                foreach ($this->diff[$type][$i] as $diff) {
                    $html_orig = htmlentities(substr($lines[$i], $current, ($diff[0] - $current)));
                    $test = str_replace("\0", "null", $html_orig);
                    $html_orig_error = htmlentities(substr($lines[$i], $diff[0], ($diff[1] - $diff[0] + 1)));
                    $test2 = str_replace("\0", "null", $html_orig_error);
                    if($option == self::SPECIAL_CHARS_ORIGINAL){
                        $html .= $html_orig;
                        $html .= "<span class='highlight-char'>".$html_orig_error."</span>";
                    } else if($option == self::SPECIAL_CHARS_UNICODE) {
                        $html_no_empty = $this->replaceEmptyChar($html_orig, false);
                        $html_no_empty_error = $this->replaceEmptyChar($html_orig_error, false);
                        $html .= $html_no_empty;
                        $html .= "<span class='highlight-char'>".$html_no_empty_error."</span>";
                    } else if($option == self::SPECIAL_CHARS_ESCAPE) {
                        $html_no_empty = $this->replaceEmptyChar($html_orig, true);
                        $html_no_empty_error = $this->replaceEmptyChar($html_orig_error, true);
                        $html .= $html_no_empty;
                        $html .= "<span class='highlight-char'>".$html_no_empty_error."</span>";
                    }
                    $current = $diff[1]+1;
                }
                $html .= "<span class='line_code_inner'>";
                $inner = htmlentities(substr($lines[$i], $current));
                if ($option === self::SPECIAL_CHARS_UNICODE) {
                    $inner = $this->replaceEmptyChar($inner, false);
                }
                elseif ($option === self::SPECIAL_CHARS_ESCAPE) {
                    $inner = $this->replaceEmptyChar($inner, true);
                }
                $html .= "{$inner}</span>";
            }
            else {
                if (isset($lines[$i])) {
                    if($option == self::SPECIAL_CHARS_ORIGINAL){
                        $html .= htmlentities($lines[$i]);
                    } else if($option == self::SPECIAL_CHARS_UNICODE){
                        $html .= $this->replaceEmptyChar(htmlentities($lines[$i]), false);
                    } else if($option == self::SPECIAL_CHARS_ESCAPE){
                        $html .= $this->replaceEmptyChar(htmlentities($lines[$i]), true);
                    }
                }
            }
            if($option == self::SPECIAL_CHARS_UNICODE) {
                $html .= '<span class="whitespace">&#9166;</span>';
            } else if($option == self::SPECIAL_CHARS_ESCAPE) {
                $html .= '<span class="whitespace">\\n</span>';
            }
            $html .= "</span></div>\n";

            if (isset($this->add[$type][$i])) {
                if ($start === null) {
                    $html .= "\t<div class='highlight' id='{$this->id}{$type}_{$this->link[$type][$i]}'>\n";
                }
                for ($k = 0; $k < $this->add[$type][$i]; $k++) {
                    $html .= "\t<div class='bad'><td class='empty_line' colspan='2'>&nbsp;</td></div>\n";
                }
                if ($start === null) {
                    $html .= "\t</div>\n";
                }
            }

            if ($start !== null && !isset($this->diff[$type][($i+1)])) {
                $start = null;
                $html .= "\t</div>\n";
            }
        }
        $html .= "</div></div>\n";
        return $html;
    }

    public function getWhiteSpaces(){
        $return = "";
        foreach($this->white_spaces as $key => $value){
            $return .= "$value" . " = " . "$key". " ";
        }
        return $this->white_spaces;
    }

    /**
     * @param $html the original HTML before any text transformation
     * @param $with_escape Show escape characters instead of character representations
     *
     * Add to this function (Or the one below it) in the future for any other special characters that needs to be replaced.
     *
     * @return string HTML after white spaces replaced with visuals
     */
    private function replaceEmptyChar($html, $with_escape){
        $return = $html;
        if($with_escape){
            foreach(self::SPECIAL_CHARS_LIST as $name => $representations){
                $this->replaceUTF($representations[0], $representations[2], $return, $name);
            }
        } else {
            foreach(self::SPECIAL_CHARS_LIST as $name => $representations){
                $this->replaceUTF($representations[0], $representations[1], $return, $name);
            }
        }
        return $return;
    }

    /**
     * @param $text String
     * @param $what String
     * @param $which String(Reference)
     * @param $description (What is the description of this character)
     * @return string (The newly formed string)
     *
     * This function replaces string $text with string $what in string $which.
     */
    private function replaceUTF($text, $what, &$which, $description){
        $count = 0;
        $what = '<span class="whitespace">'.$what.'</span>';
        $which = str_replace($text, $what, $which,$count);
        if($count > 0) $this->white_spaces[$description] = strip_tags($what);
        return $what;
    }

    /**
     * Compress an array of numbers into ranges
     *
     * Given some array of numbers, it sorts the array, then condenses
     * adjacent numbers into a range.
     *
     * Ex: Given [0,1,2,5,6,9,100] -> [[0,2],[5,6],[9,9],[100,100]]
     *
     * @param array $range original flat array
     *
     * @return array A condensed array with ranges
     */
    private function compressRange($range) {
        sort($range);
        $range[] = -100;
        $last = -100;
        $return = array();
        $temp = array();
        foreach ($range as $number) {
            if ($number != $last+1) {
                if (count($temp) > 0) {
                    $return[] = array($temp[0], end($temp));
                    $temp = array();
                }
            }
            $temp[] = $number;
            $last = $number;
        }
        return $return;
    }

    /**
     * Returns true if there's an actual difference between actual and expected, else will
     * return false
     *
     * @return bool
     * @throws \Exception
     */
    public function existsDifference() {
        $this->buildViewer();
        $return = false;
        foreach(array(self::EXPECTED, self::ACTUAL) as $key) {
            if(count($this->diff[$key]) > 0 || count($this->add[$key]) > 0) {
                $return = true;
            }
        }
        return $return;
    }
}
