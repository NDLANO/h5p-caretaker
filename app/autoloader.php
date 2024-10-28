<?php

spl_autoload_register(function ($class) {
    static $classmap;
    // DIRECTORY_SEPARATOR is not defined here (?)

    if (!isset($classmap)) {
        $classmap = [
            "Ndlano\H5PCaretaker\H5PFileHandler" => "H5PFileHandler.php",
            "Ndlano\H5PCaretaker\LibretextData" => "filehandlers/LibretextData.php",
            "Ndlano\H5PCaretaker\FileUtils" => "utils/FileUtils.php",
            "Ndlano\H5PCaretaker\GeneralUtils" => "utils/GeneralUtils.php",
            "Ndlano\H5PCaretaker\H5PUtils" => "utils/H5PUtils.php",
            "Ndlano\H5PCaretaker\JSONUtils" => "utils/JSONUtils.php",
            "Ndlano\H5PCaretaker\LocaleUtils" => "utils/LocaleUtils.php",
            "Ndlano\H5PCaretaker\ReportUtils" => "utils/ReportUtils.php",
            "Ndlano\H5PCaretaker\ContentFile" => "models/ContentFile.php",
            "Ndlano\H5PCaretaker\Content" => "models/Content.php",
            "Ndlano\H5PCaretaker\ContentTree" => "models/ContentTree.php",
            "Ndlano\H5PCaretaker\AccessibilityReport" => "reports/AccessibilityReport.php",
            "Ndlano\H5PCaretaker\EfficiencyReport" => "reports/EfficiencyReport.php",
            "Ndlano\H5PCaretaker\FeatureReport" => "reports/FeatureReport.php",
            "Ndlano\H5PCaretaker\LicenseReport" => "reports/LicenseReport.php",
            "Ndlano\H5PCaretaker\StatisticsReport" => "reports/StatisticsReport.php",
        ];
    }

    if (isset($classmap[$class])) {
        require_once __DIR__ . "/" . $classmap[$class];
    }
});
