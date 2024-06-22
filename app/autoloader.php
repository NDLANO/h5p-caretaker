<?php

spl_autoload_register(function ($class) {
    static $classmap;
    // DIRECTORY_SEPARATOR is not defined here (?)

    if (!isset($classmap)) {
        $classmap = [
        'H5PCaretaker\H5PFileHandler' => 'H5PFileHandler.php',
        'H5PCaretaker\LibretextData' => 'filehandlers/LibretextData.php',
        'H5PCaretaker\FileUtils' => 'utils/FileUtils.php',
        'H5PCaretaker\GeneralUtils' => 'utils/GeneralUtils.php',
        'H5PCaretaker\JSONUtils' => 'utils/JSONUtils.php',
        'H5PCaretaker\H5PUtils' => 'utils/H5PUtils.php',
        'H5PCaretaker\AccessibilityReport' => 'reports/AccessibilityReport.php',
        ];
    };

    if (isset($classmap[$class])) {
        require_once __DIR__ . '/' . $classmap[$class];
    }
});
