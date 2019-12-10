<?php

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