<?php

namespace app\views\admin;

use app\libraries\FileUtils;
use app\views\AbstractView;

class LibraryManagerView extends AbstractView {
    public function showLibraryManager(String $text) {
        $this->core->getOutput()->addInternalJs("drag-and-drop.js");
        $this->core->getOutput()->addInternalCss(FileUtils::joinPaths('fileinput.css'));
        return $this->core->getOutput()->renderTwigTemplate("admin/LibraryManager.twig", [
            "text" => $text
        ]);
    }
}
