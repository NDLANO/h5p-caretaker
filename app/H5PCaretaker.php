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
 * @link     https://github.com/ndlano/H5PCaretaker
 */

namespace H5PCaretaker;

/**
 * Main class.
 *
 * @category Tool
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/H5PCaretaker
 */
class H5PCaretaker
{
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config The configuration.
     */
    public function __construct($config = [])
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . "autoloader.php";

        $config["locale"] = $config["locale"] ?? 'en';

        $language = LocaleUtils::getCompleteLocale($config["locale"]);
        if (isset($language)) {
            putenv("LANG=" . $language);
            putenv("LANGUAGE=" . $language);

            $domain = "h5p_caretaker";
            $bindPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . "locale");
            bindtextdomain($domain, $bindPath);
            textdomain($domain);
        }

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
            $error = _("Something went wrong, but I dunno what, sorry!");
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
        LicenseReport::generateReport($contentTree);
        EfficiencyReport::generateReport($contentTree, $reportRaw);

        $report = [
            "messages" => [],
            "client" => [
                "translations" => LocaleUtils::getKeywordTranslations(
                    $this->config["locale"] === "en"
                ),
                "categories" => [
                    AccessibilityReport::$categoryName => AccessibilityReport::$typeNames,
                    FeatureReport::$categoryName => FeatureReport::$typeNames,
                    LicenseReport::$categoryName => LicenseReport::$typeNames,
                    EfficiencyReport::$categoryName => EfficiencyReport::$typeNames,
                    StatisticsReport::$categoryName => StatisticsReport::$typeNames
                ]
            ]
        ];

        $reports = $contentTree->getReports();
        $report["messages"] = array_merge(...array_values($reports));

        $stats = StatisticsReport::generateReport($contentTree, $reportRaw);
        foreach ($stats as $key => $message) {
            $report["messages"][] = $message;
        }

        // TODO: Ultimately, $report only contain the raw data on request
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
            return _("It seems that no file was provided.");
        }

        $fileSize = filesize($file);
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($fileInfo, $file);

        if ($fileSize === 0) {
            return _("The file is empty.");
        }

        $fileSizeLimit = 1024 * 1024 * 20; // 20 MB
        if ($fileSize > $fileSizeLimit) {
            return sprintf(
                _("The file is larger than the limit of %s bytes."),
                $fileSizeLimit
            );
        }

        if ($fileType !== "application/zip") {
            return _("The file is not a valid H5P file / ZIP archive.");
        }

        try {
            $h5pFileHandler = new H5PFileHandler(
                $file,
                $this->config["uploadsPath"],
                $this->config["cachePath"]
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }

        if (!$h5pFileHandler->isFileOkay()) {
            return _("The file does not seem to follow the H5P specification.");
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
        if (!isset($params["values"])) {
            return $this->done(null, _("No values were provided for writing."));
        }

        $fileCheckResults = $this->checkH5PFile($params["file"]);
        if (getType($fileCheckResults) === "string") {
            return $this->done(null, $fileCheckResults);
        }

        $h5pFileHandler = $fileCheckResults;

        /*
         * TODO: some options
         * - overwrite/add values to content.json
         *
         * Function that takes a semantics path, a value and the content.json and returns the new content.json or null.
         *
         * The frontend will (need to) know the semantics path. E.g.:
         * - the key `foo.bar[8].baz[].yada` and the value `42` should sequentially
         *   - check if foo exists, if not throw an error
         *   - check if foo is an object, if not throw an error
         *   - check if foo.bar exists, if not throw an error
         *   - check if foo.bar is an array, if not throw an error
         *   - check if foo.bar[8] exists, if not throw an error
         *   - check if foo.bar[8].baz exists, if not throw an error
         *   - check if foo.bar[8].baz is an array, if not throw an error
         *   - add an element to foo.bar[8].baz[] and as of now ignore if a field does not exist
         *   - add yada to the newly created element
         *   - set the value of yada to 42
         *
         * - the key `foo.yada` and the value `42` should sequentially
         *   - check if foo exists, if not throw an error
         *   - check if yada exists, if not set it (as it's the final field), but if set,
         *     check it's type to match the value's type
         *   - set the value of yada to 42
         *
         * So, in short:
         * - check each part of the path sequentially
         * - check for existence if not the final field and no array item was added before,
         * - check if type of current field matches (object/array) if not final field.
         * - add array item if the field is an array and has []
         * - add final field if not set yet.
         * - check if type of current field matches the value's type if final field.
         * - set the value of the final field.
         *
         * - overwrite/add values to h5p.json
         *   relevant for metadata at least!
         * - add files to the content folder (?)
         */

        return $this->done(null, _("Writing is not yet implemented."));
    }
}
