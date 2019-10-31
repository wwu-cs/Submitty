<?php

namespace app\views\admin;

use app\views\AbstractView;

class LibraryManagerView extends AbstractView {
    public function showLibraryManager(String $text) {
        return $this->core->getOutput()->renderTwigTemplate("admin/LibraryManager.twig", [
            "text" => $text
        ]);
    }
}
