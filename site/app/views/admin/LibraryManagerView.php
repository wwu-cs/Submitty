<?php

namespace app\views\admin;

use app\libraries\FileUtils;
use app\views\AbstractView;

class LibraryManagerView extends AbstractView {
  
    public function showLibraryManager(string $text, array $libraries) {
        $this->core->getOutput()->addBreadcrumb('Library Manager');
        $this->core->getOutput()->addInternalCss('admin-gradeable.css');
        $this->core->getOutput()->addInternalJs('drag-and-drop.js');
        $this->core->getOutput()->addInternalCss(FileUtils::joinPaths('fileinput.css'));
        return $this->core->getOutput()->renderTwigTemplate("admin/library/LibraryManager.twig", [
            'text' => $text,
            'libraries' => $libraries,
            'git_submit_url' => $this->core->buildUrl(['manage', 'upload', 'git']),
            'zip_submit_url' => $this->core->buildUrl(['manage', 'upload', 'zip']),
            "csrf_token" => $this->core->getCsrfToken()
        ]);
    }
}
