<?php

/**
 * Tool for helping people to take care of H5P content.
 *
 * PHP version 8
 *
 * @category Tool
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */

namespace Ndlano\H5PCaretaker;

/**
 * Main class.
 *
 * @category Tool
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */
class H5PCaretaker
{
    protected $config;

    /**
     * The maximum age of cached file in seconds.
     *
     * @var int
     */
    private const MAX_CACHE_AGE_S = 60 * 60 * 24; // 1 day

    /**
     * Constructor.
     *
     * @param array $config The configuration.
     */
    public function __construct($config = [])
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . "autoloader.php";

        $config["locale"] = $config["locale"] ?? 'en';
        LocaleUtils::setLocale($config["locale"]);

        $language = LocaleUtils::getCompleteLocale($config["locale"]);

        if (!isset($config["uploadsPath"])) {
            $config["uploadsPath"] =
                __DIR__ .
                DIRECTORY_SEPARATOR .
                ".." .
                DIRECTORY_SEPARATOR .
                "uploads";
        }

        if (!isset($config["cachePath"])) {
            $config["cachePath"] =
                __DIR__ .
                DIRECTORY_SEPARATOR .
                ".." .
                DIRECTORY_SEPARATOR .
                "cache";
        }

        if (!isset($config["cacheTimeout"])) {
            $config["cacheTimeout"] = self::MAX_CACHE_AGE_S;
        }

        $this->config = $config;
    }

    /**
     * Done.
     *
     * @param string|null $result The result. Should be null if there is an error.
     * @param string|null $error  The error. Should be null if there is no error.
     *
     * @return array The result or error.
     */
    private function done($result, $error = null)
    {
        if (isset($error)) {
            $result = null;
        } elseif (!isset($result)) {
            $error = LocaleUtils::getString("error:unknownError");
        }

        return [
            "result" => $result,
            "error" => $error,
        ];
    }

    /**
     * Analyze the H5P content from the given file.
     *
     * @param array $params The parameters. file (tmp file), format (html, text).
     *
     * @return array The result or error.
     */
    public function analyze($params)
    {
        $fileCheckResults = $this->checkH5PFile($params["file"]);
        if (getType($fileCheckResults) === "string") {
            return $this->done(null, $fileCheckResults);
        }

        $h5pFileHandler = $fileCheckResults;

        $reportRaw = [];
        $reportRaw["h5pJson"] = $h5pFileHandler->getH5PInformation();
        $reportRaw["contentJson"] = $h5pFileHandler->getH5PContentParams();
        $reportRaw["libraries"] = $h5pFileHandler->getLibrariesInformation();
        $reportRaw["media"] = $h5pFileHandler->getMediaInformation();

        $contentTree = new ContentTree($reportRaw);

        AccessibilityReport::generateReport($contentTree);
        FeatureReport::generateReport($contentTree, $reportRaw);
        LicenseReport::generateReport($contentTree, $reportRaw);
        EfficiencyReport::generateReport($contentTree, $reportRaw, $h5pFileHandler);
        ReuseReport::generateReport($contentTree, $reportRaw);

        $report = [
            "messages" => [],
            "client" => [
                "translations" => LocaleUtils::getKeywordTranslations(),
                "categories" => [
                    AccessibilityReport::$categoryName => AccessibilityReport::$typeNames,
                    FeatureReport::$categoryName => FeatureReport::$typeNames,
                    LicenseReport::$categoryName => LicenseReport::$typeNames,
                    EfficiencyReport::$categoryName => EfficiencyReport::$typeNames,
                    StatisticsReport::$categoryName => StatisticsReport::$typeNames,
                    ReuseReport::$categoryName => ReuseReport::$typeNames
                ]
            ]
        ];

        $reports = $contentTree->getReports();
        $report["messages"] = array_merge(...array_values($reports));

        // Sort messages by category and type
        usort(
            $report["messages"],
            function ($a, $b) {
                $categoryComparison = strcmp($a["category"], $b["category"]);
                if ($categoryComparison !== 0) {
                    return $categoryComparison;
                }

                return strcmp($a["type"], $b["type"]);
            }
        );

        $report["contentTree"] = $contentTree->getTreeRepresentation();

        $stats = StatisticsReport::generateReport($contentTree, $reportRaw);
        foreach ($stats as $key => $message) {
            $report["messages"][] = $message;
        }

        $report["raw"] = $reportRaw;
        $h5pFileHandler = null;

        return $this->done(json_encode($report, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Check the H5P file.
     *
     * @param string $file The file.
     *
     * @return string|H5PFileHandler The error message or the file handler.
     */
    public function checkH5PFile($file)
    {
        if (!isset($file)) {
            return LocaleUtils::getString("error:noFile");
        }

        $fileSize = filesize($file);
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($fileInfo, $file);

        if ($fileSize === 0) {
            return LocaleUtils::getString("error:fileEmpty");
        }

        $fileSizeLimit = GeneralUtils::convertToBytes(
            min(ini_get('post_max_size'), ini_get('upload_max_filesize'))
        );

        if ($fileSize > $fileSizeLimit) {
            return sprintf(
                LocaleUtils::getString("error:fileTooLarge"),
                $fileSizeLimit
            );
        }

        if ($fileType !== "application/zip") {
            return LocaleUtils::getString("error:notAnH5Pfile");
        }

        try {
            $h5pFileHandler = new H5PFileHandler(
                $file,
                $this->config["uploadsPath"],
                $this->config["cachePath"],
                $this->config["cacheTimeout"]
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }

        if (!$h5pFileHandler->isFileOkay()) {
            return LocaleUtils::getString("error:notH5PSpecification");
        }

        return $h5pFileHandler;
    }

    /**
     * Write values to an H5P content file.
     *
     * @param array $params The parameters. file (tmp file), values.
     *
     * @return array The result or error.
     */
    public function write($params)
    {
        if (!isset($params["changes"])) {
            return $this->done(null, LocaleUtils::getString("error:noChanges"));
        }

        $fileCheckResults = $this->checkH5PFile($params["file"]);
        if (getType($fileCheckResults) === "string") {
            return $this->done(null, $fileCheckResults);
        }

        $h5pFileHandler = $fileCheckResults;

        $contentJson = $h5pFileHandler->getH5PContentParams();
        $h5pJson = $h5pFileHandler->getH5PInformation();

        $validChanges = $this->filterOutInvalidChanges($params["changes"]);

        list($changesToParams, $changesToFiles) = $this->separateChangesForParamsAndFiles($validChanges);

        $newChanges = [];
        foreach ($changesToFiles as $change) {
            $filePath = $h5pFileHandler->buildExtractPath(['content', $change['filePath']]);
            if (!file_exists($filePath)) {
                continue; // File must exist to be processed
            }

            $jobs = explode(";", $change['value']);
            foreach ($jobs as $job) {
                $job = trim($job);
                $arguments = explode(" ", $job);
                $command = array_shift($arguments);

                if ($command === "scale-down") {
                    list($width, $height) = ImageUtils::scaleDown($arguments, $filePath);
                    if ($width === -1 || $height === -1) {
                        continue;
                    }

                    $newChanges[] = [
                        'semanticsPath' => $change['semanticsPath'] . ".width",
                        'value' => $width,
                    ];
                    $newChanges[] = [
                        'semanticsPath' => $change['semanticsPath'] . ".height",
                        'value' => $height,
                    ];
                } elseif ($command === "convert") {
                    $wasConverted = ImageUtils::convert($arguments, $filePath);
                    if (!$wasConverted) {
                        continue; // Conversion failed, skip this change
                    }

                    $filePathParts = explode('.', $filePath);
                    array_pop($filePathParts);
                    $newFilePath = implode('.', $filePathParts) . '.' . $arguments[0];
                    // Rename file at $filePath to $newFilePath
                    if (!rename($filePath, $newFilePath)) {
                        continue; // Renaming failed, skip this change
                    }

                    $filePathParts = explode('.', $change['filePath']);
                    array_pop($filePathParts);
                    $newFilePath = implode('.', $filePathParts) . '.' . $arguments[0];
                    $newChanges[] = [
                        'semanticsPath' => $change['semanticsPath'] . "." . "path",
                        'value' => $newFilePath
                    ];

                    $newChanges[] = [
                        'semanticsPath' => $change['semanticsPath'] . "." . "mime",
                        'value' => "image/" . $arguments[0]
                    ];
                }
            }
        }
        $changesToParams = array_merge($changesToParams, $newChanges);

        foreach ($changesToParams as $change) {
            $needsToGoIntoContentJson = !str_starts_with($change['semanticsPath'], "root");
            if ($needsToGoIntoContentJson) {
                JSONUtils::setPropertyAtPath($contentJson, $change['semanticsPath'], $change['value']);
            } else {
                $fieldName = str_replace("root.", "", $change['semanticsPath']);
                $this->setH5PJsonFieldValue($h5pJson, $fieldName, $change['value']);
            }
        }

        $h5pFileHandler->setH5PInformation($h5pJson);
        $h5pFileHandler->setH5PContentParams($contentJson);

        try {
            $exportResult = $h5pFileHandler->exportH5PArchive();
        } catch (\Exception $error) {
            return $this->done(null, $error->getMessage());
        }

        return $this->done($exportResult);
    }

    /**
     * Filter out invalid changes.
     *
     * @param array $changes The changes.
     *
     * @return array Valid changes.
     */
    private function filterOutInvalidChanges($changes)
    {
        $changes = array_filter($changes, function ($change) {
            return isset($change->semanticsPath) && isset($change->value);
        });

        // Grouping changes to be able to validate them in groups if required
        $groupedByBasePath = array_reduce($changes, function ($grouped, $current) {
            $semanticsParts = explode('.', $current->semanticsPath);

            $mainFieldName = array_pop($semanticsParts);
            $basePath = implode('.', $semanticsParts);

            if (substr($basePath, -1) === ']') {
                $current->childFieldName = $mainFieldName;
                $current->mainFieldName = explode('[', end($semanticsParts))[0];
            } else {
                $current->mainFieldName = $mainFieldName;
            }

            if (!isset($grouped[$basePath])) {
                $grouped[$basePath] = [];
            }
            $grouped[$basePath][] = $current;

            return $grouped;
        }, []);

        // Validate single field or group fields using regex from ValidationUtils
        foreach ($groupedByBasePath as $basePath => $changes) {
            $isSingleField = count($changes) === 1 && !isset($changes[0]->childFieldName);

            if ($isSingleField) {
                $isValid = ValidationUtils::isValidH5PJsonValue($changes[0]->mainFieldName, $changes[0]->value);
                if (!$isValid) {
                    unset($changes[0]);
                }
            } else {
                $isValid = true;
                foreach ($changes as $change) {
                    if (isset($change->childFieldName)) {
                        $regex = ValidationUtils::getRegex($change->mainFieldName . '.' . $change->childFieldName);
                    } else {
                        $regex = ValidationUtils::getRegex($change->mainFieldName);
                    }

                    if (isset($regex)) {
                        $isValid = $isValid && preg_match($regex, $change->value);
                    }
                }

                if (!$isValid) {
                    unset($groupedByBasePath[$basePath]);
                }
            }
        }

        // Flatten grouped changes back into a single array
        $flattenedArray = array_merge(...array_values($groupedByBasePath));

        return array_map(function ($change) {
            $result = [
                'semanticsPath' => $change->semanticsPath,
                'value' => $change->value,
            ];

            if (isset($change->filePath)) {
                $result['filePath'] = $change->filePath;
            }

            return $result;
        }, $flattenedArray);
    }

    /**
     * Set the value of a field in the H5P JSON.
     *
     * @param array  $h5pJson   The H5P JSON.
     * @param string $fieldName The field name.
     * @param mixed  $value     The value.
     */
    private function setH5PJsonFieldValue(&$h5pJson, $fieldName, $value)
    {
        $containsArray = preg_match('/\[[0-9]+\]/', $fieldName);
        if ($containsArray) {
            $arrayIndex = preg_replace('/[^\d]/', '', $fieldName);
            $arrayFieldName = explode("[", $fieldName)[0];
            $propertyName = explode("].", $fieldName)[1];

            // Ensure the array field exists
            if (!isset($h5pJson[$arrayFieldName])) {
                $h5pJson[$arrayFieldName] = [];
            }

            // Ensure the array index is valid
            $arrayIndex = min($arrayIndex, count($h5pJson[$arrayFieldName]));
            $h5pJson[$arrayFieldName][$arrayIndex][$propertyName] = $value;
        } else {
            $h5pJson[$fieldName] = $value;
        }
    }

    /**
     * Split changes into changes for parameters and files.
     *
     * @param array $validChanges The valid changes.
     *
     * @return array An array containing two arrays: changes to parameters and changes to files.
     */
    private function separateChangesForParamsAndFiles($validChanges)
    {
        $changesToParams = [];
        $changesToFiles = [];
        foreach ($validChanges as $change) {
            if (isset($change['filePath'])) {
                $changesToFiles[] = $change;
            } else {
                $changesToParams[] = $change;
            }
        }
        return [$changesToParams, $changesToFiles];
    }
}
