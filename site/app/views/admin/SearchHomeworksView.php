<?php

/**
 * Purpose of this file was to allow the data being pulled from the API
 * to be viewed in a neat format in the details section of AdminGradeableHomeworkLibrary.twg
 */

namespace app\view\admin;

use app\views\AbstractView;

class SearchHomeworkView extends AbstractView {

    public function showSearch(array $homework_list, string $gradeable_id) {
        $this->core->getOutput()->renderTwigOutput('admin/admin_gradeable/AdminGradeableHomeworkLibrary.twig', [
        'homework_list' => $homework_list,
        'homework_library_url' => $this->core->buildCourseUrl(['gradeable', $gradeable_id, 'homework_library']),
        ]);
    }
}
