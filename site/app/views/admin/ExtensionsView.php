<?php

namespace app\views\admin;

use app\views\AbstractView;
use app\libraries\Utils;
use app\libraries\FileUtils;

class ExtensionsView extends AbstractView {
    public function displayExtensions($gradeables, $students, $users, $current_gid) {
        $this->core->getOutput()->addInternalCss('exceptionforms.css');
        $this->core->getOutput()->addInternalCss('table.css');
        $this->core->getOutput()->addInternalJs('extensions.js');
        $this->core->getOutput()->addVendorJs(FileUtils::joinPaths('flatpickr', 'flatpickr.min.js'));
        $this->core->getOutput()->addVendorCss(FileUtils::joinPaths('flatpickr', 'flatpickr.min.css'));
        $this->core->getOutput()->addVendorJs(FileUtils::joinPaths('flatpickr', 'plugins', 'shortcutButtons', 'shortcut-buttons-flatpickr.min.js'));
        $this->core->getOutput()->addVendorCss(FileUtils::joinPaths('flatpickr', 'plugins', 'shortcutButtons', 'themes', 'light.min.css'));
        $this->core->getOutput()->addBreadcrumb('Excused Absence Extensions');

        $student_full = Utils::getAutoFillData($students);
        // get gradeable with matching gid
        $g_key = array_search($current_gid, array_column($gradeables, 'g_id'));
        $current_gradeable = $g_key === false ? null : $gradeables[$g_key]; 

        $current_exceptions = array();
        foreach($users as $user) {
            $current_exceptions[] = array('user_id' => $user->getId(),
                                          'user_firstname' => $user->getDisplayedFirstName(), 
                                          'user_lastname' => $user->getDisplayedLastName(), 
                                          'late_day_exceptions' => $user->getLateDayExceptions());
        }
        if (empty($current_exceptions)) $current_exceptions = null;

        return $this->core->getOutput()->renderTwigTemplate("admin/Extensions.twig", [
            "gradeables" => $gradeables,
            "student_full" => $student_full,
            "current_gradeable" => $current_gradeable,
            "current_exceptions" => $current_exceptions,
            "csrf_token" => $this->core->getCsrfToken()
        ]);
    }
}
