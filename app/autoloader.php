<?php

spl_autoload_register(function ($class) {
    static $classmap;
    // DIRECTORY_SEPARATOR is not defined here (?)

    if (!isset($classmap)) {
        $classmap = [
            "H5PCaretaker\H5PFileHandler" => "H5PFileHandler.php",
            "H5PCaretaker\LibretextData" => "filehandlers/LibretextData.php",
            "H5PCaretaker\FileUtils" => "utils/FileUtils.php",
            "H5PCaretaker\GeneralUtils" => "utils/GeneralUtils.php",
            "H5PCaretaker\H5PUtils" => "utils/H5PUtils.php",
            "H5PCaretaker\JSONUtils" => "utils/JSONUtils.php",
            "H5PCaretaker\LocaleUtils" => "utils/LocaleUtils.php",
            "H5PCaretaker\ReportUtils" => "utils/ReportUtils.php",
            "H5PCaretaker\ContentFile" => "models/ContentFile.php",
            "H5PCaretaker\Content" => "models/Content.php",
            "H5PCaretaker\ContentTree" => "models/ContentTree.php",
            "H5PCaretaker\AccessibilityReport" => "reports/AccessibilityReport.php",
            "H5PCaretaker\EfficiencyReport" => "reports/EfficiencyReport.php",
            "H5PCaretaker\FeatureReport" => "reports/FeatureReport.php",
            "H5PCaretaker\LicenseReport" => "reports/LicenseReport.php",
            "H5PCaretaker\StatisticsReport" => "reports/StatisticsReport.php",
        ];
    }

    if (isset($classmap[$class])) {
        require_once __DIR__ . "/" . $classmap[$class];
    }
});
