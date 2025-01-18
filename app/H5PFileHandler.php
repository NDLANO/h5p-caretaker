<?php

/**
 * Tool for helping people to take Caretaker of H5P content.
 *
 * PHP version 8
 *
 * @category Tool
 * @package  H5PCaretaker
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */

namespace Ndlano\H5PCaretaker;

/**
 * Class for generating HTML for H5P content.
 *
 * @category Tool
 * @package  H5PCaretaker
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */
class H5PFileHandler
{
    protected $uploadsDirectory;
    protected $cacheDirectory;
    protected $filesDirectory;
    protected $h5pInfo;

    /**
     * The maximum age of cached file in seconds.
     *
     * @var int
     */
    private const MAX_CACHE_AGE_S = 60 * 60 * 24; // 1 day

    /**
     * Constructor.
     *
     * @param string $file        The H5P file to handle.
     * @param string $uploadsPath The path to the uploads directory.
     *                            Will default to "uploads" in current directory.
     * @param string $cachePath   The path to the cache directory.
     *                            Will default to "cache" in current directory.
     * @param int    $cacheTimeout The maximum age of cached file in seconds.
     */
    public function __construct($file, $uploadsPath, $cachePath, $cacheTimeout = self::MAX_CACHE_AGE_S)
    {
        $this->uploadsDirectory = $uploadsPath;
        $this->cacheDirectory = $cachePath;
        $this->cacheTimeout = self::MAX_CACHE_AGE_S;

        try {
            $this->filesDirectory = $this->extractContent($file);
        } catch (\Exception $error) {
            throw new \Exception($error->getMessage());
        }

        try {
            $this->h5pInfo = $this->extractH5PInformation();
        } catch (\Exception $error) {
            throw new \Exception($error->getMessage());
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (!isset($this->filesDirectory)) {
            return;
        }

        $this->collectGarbage();
        $this->deleteDirectory($this->filesDirectory);
    }

    /**
     * Get the media information.
     *
     * @return object The media information.
     */
    public function getMediaInformation()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        $contentDir = $extractDir . DIRECTORY_SEPARATOR . "content";

        if (!is_dir($extractDir) || !is_dir($contentDir)) {
            return (object) [];
        }

        $dirs = $this->getMediaDirNames();

        $results = array_reduce(
            $dirs,
            function ($results, $type) use ($contentDir) {
                $mediaDir = $contentDir . DIRECTORY_SEPARATOR . $type;

                $mediaFiles = array_filter(scandir($mediaDir), function (
                    $file
                ) {
                    return preg_match('/.+\.\w{3,4}$/', $file);
                });

                if (!empty($mediaFiles)) {
                    $files = [];
                    foreach ($mediaFiles as $fileName) {
                        $size = filesize(
                            $mediaDir . DIRECTORY_SEPARATOR . $fileName
                        );
                        $files[$fileName] = ["size" => $size];

                        $resolution = getimagesize(
                            $mediaDir . DIRECTORY_SEPARATOR . $fileName
                        );
                        if ($resolution !== false) {
                            $files[$fileName]["width"] = $resolution[0];
                            $files[$fileName]["height"] = $resolution[1];
                        }

                        $extension = strtolower(
                            pathinfo($fileName, PATHINFO_EXTENSION)
                        );
                        $allowedExtensions = [
                            "png",
                            "jpeg",
                            "jpg",
                            "gif",
                            "svg",
                            "bmp",
                            "tiff",
                            "tif",
                        ];

                        if (in_array($extension, $allowedExtensions)) {
                            $files[$fileName][
                                "base64"
                            ] = FileUtils::fileToBase64(
                                $mediaDir . DIRECTORY_SEPARATOR . $fileName
                            );
                        }
                    }

                    $results->$type = (object) $files;
                }

                return $results;
            },
            (object) []
        );

        return $results;
    }

    /**
     * Get the libraries information.
     *
     * @return array The libraries information.
     */
    public function getLibrariesInformation()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return [];
        }

        $dirNames = $this->getLibrariesDirNames();
        $libraryDetails = array_map([$this, "getLibraryDetails"], $dirNames);

        return array_reduce(
            $libraryDetails,
            function ($results, $details) {
                if (isset($details->machineName)) {
                    $results[$details->machineName] = $details;
                }
                return $results;
            },
            []
        );
    }

    /**
     * Check if the H5P file is okay.
     *
     * @return bool True if the file is okay, false otherwise.
     */
    public function isFileOkay()
    {
        return isset($this->filesDirectory) && $this->filesDirectory !== false;
    }

    /**
     * Get the uploads directory for the H5P files.
     *
     * @return string The upload directory for the H5P files.
     */
    public function getUploadsDirectory()
    {
        return $this->uploadsDirectory;
    }

    /**
     * Get the file directory for the H5P files.
     *
     * @return string The file directory for the H5P files.
     */
    public function getFilesDirectory()
    {
        return $this->filesDirectory;
    }

    /**
     * Get the H5P content informaton from h5p.json.
     *
     * @param string $property The property to get or null to get full information.
     *
     * @return string|array|null  H5P content type CSS, null if not available.
     */
    public function getH5PInformation($property = null)
    {
        if (!isset($this->h5pInfo)) {
            return null;
        }

        return isset($property)
            ? $this->h5pInfo[$property] ?? null
            : $this->h5pInfo;
    }

    /**
     * Get the icon of the main H5P content.
     *
     * @param string $machineName The machine name of the content type to get icon for.
     *
     * @return string Icon file path or false if not found.
     */
    public function getIconPath($machineName = null)
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return false;
        }

        if (!isset($machineName)) {
            if (empty($this->h5pInfo["mainLibrary"])) {
                return false;
            }
            $machineName = $this->h5pInfo["mainLibrary"];
        }

        // We're operating on the file system, so we cannot use spaces
        $machineName = str_replace(" ", "-", $machineName);

        $pattern = $extractDir . DIRECTORY_SEPARATOR . $machineName . "*";

        $contentDirs = glob($pattern, GLOB_ONLYDIR);
        if (empty($contentDirs)) {
            return false;
        }

        $iconFile = $contentDirs[0] . DIRECTORY_SEPARATOR . "icon.svg";
        if (!file_exists($iconFile)) {
            return false;
        }

        return $iconFile;
    }

    /**
     * Get the H5P content parameters from the content.json file.
     *
     * @return array|bool Content parameters if file exists, false otherwise.
     */
    public function getH5PContentParams()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return false;
        }

        $contentDir = $extractDir . DIRECTORY_SEPARATOR . "content";
        if (!is_dir($contentDir)) {
            return false;
        }

        $contentJsonFile = $contentDir . DIRECTORY_SEPARATOR . "content.json";
        if (!file_exists($contentJsonFile)) {
            return false;
        }

        $contentContents = file_get_contents($contentJsonFile);
        $jsonData = json_decode($contentContents, true);

        if ($jsonData === null) {
            return false;
        }

        return $jsonData;
    }

    /**
     * Get the directories of the libraries.
     *
     * @return array The directories of the libraries or false.
     */
    private function getMediaDirNames()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return [];
        }

        $contentDir = $extractDir . DIRECTORY_SEPARATOR . "content";
        if (!is_dir($contentDir)) {
            return [];
        }

        $entries = scandir($contentDir);
        $dirs = array_filter($entries, function ($entry) use ($contentDir) {
            return $entry !== "." &&
                $entry !== ".." &&
                is_dir($contentDir . DIRECTORY_SEPARATOR . $entry);
        });

        return array_values($dirs);
    }

    /**
     * Get the directories of the libraries.
     *
     * @return array The directories of the libraries or false.
     */
    private function getLibrariesDirNames()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;
        if (!is_dir($extractDir)) {
            return [];
        }

        $entries = scandir($extractDir);
        $dirs = array_filter($entries, function ($entry) use ($extractDir) {
            return $entry !== "." &&
                $entry !== ".." &&
                $entry !== "content" &&
                is_dir($extractDir . DIRECTORY_SEPARATOR . $entry);
        });

        return array_values($dirs);
    }

    /**
     * Get the features of the JavaScript file.
     *
     * @param string $fileName The name of the file.
     *
     * @return object The features of the JavaScript file.
     */
    private function getJavaScriptFeatures($fileName)
    {
        $fileContents = file_get_contents($fileName);

        $functionNames = [
            "getAnswerGiven",
            "getScore",
            "getMaxScore",
            "showSolutions",
            "resetTask",
            "getXAPIData",
            "getCurrentState",
            "enableSolutionsButton",
            "enableRetry",
        ];

        return array_reduce(
            $functionNames,
            function ($results, $functionName) use ($fileContents) {
                $results[$functionName] =
                    strpos($fileContents, $functionName) !== false;
                return $results;
            },
            []
        );
    }

    /**
     * Get the details of a library.
     *
     * @param string $dirName The name of the directory.
     *
     * @return array The details of the library.
     */
    private function getLibraryDetails($dirName)
    {
        $results = (object) [];

        // Preliminarily determine library information from directory name
        $libraryInformation = H5PUtils::getLibraryFromString($dirName, "-");
        if ($libraryInformation !== false) {
            $results->machineName = $libraryInformation["machineName"];
            $results->majorVersion = $libraryInformation["majorVersion"];
            $results->minorVersion = $libraryInformation["minorVersion"];
        }

        $libraryDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory .
            DIRECTORY_SEPARATOR .
            $dirName;

        if (!is_dir($libraryDir)) {
            return $results;
        }

        // library.json
        $libraryJsonData = FileUtils::getJSONData(
            $libraryDir . DIRECTORY_SEPARATOR . "library.json"
        );
        if ($libraryJsonData !== null) {
            $results->libraryJson = $libraryJsonData;

            $keys = [
                "machineName",
                "majorVersion",
                "minorVersion",
                "patchVersion",
            ];

            foreach ($keys as $key) {
                if (isset($jsonData[$key])) {
                    $results->$key = $jsonData[$key];
                }
            }
        }

        // Detect Question Type contract features
        if (
            ($results->libraryJson["runnable"] ?? 0) === 1 &&
            isset($results->libraryJson["preloadedJs"])
        ) {
            $questionTypeFeatures = $this->getQuestionTypeFeatures(
                $results->libraryJson["preloadedJs"],
                $libraryDir
            );

            if (isset($questionTypeFeatures)) {
                $results->questionTypeFeatures = $questionTypeFeatures;
            }
        }

        // a11y report from libretexts
        if (($results->libraryJson["runnable"] ?? 0) === 1) {
            $libretextData = LibretextData::fetch(
                $results->libraryJson["title"],
                $this->cacheDirectory,
                $this->cacheTimeout
            );

            if ($libretextData !== false) {
                $results->libreTextA11y = $libretextData[0];
            }
        }

        // semantics.json
        $semanticsJsonData = FileUtils::getJSONData(
            $libraryDir . DIRECTORY_SEPARATOR . "semantics.json"
        );
        if ($semanticsJsonData !== null) {
            $results->semanticsJson = $semanticsJsonData;
        }

        // Language Files
        $languageData = $this->getLanguageData(
            $libraryDir . DIRECTORY_SEPARATOR . "language"
        );
        if ($languageData !== null) {
            $results->languages = $languageData;
        }

        return $results;
    }

    /**
     * Get the features of the question type.
     *
     * @param array  $preloadedJsFiles The preloaded JavaScript files.
     * @param string $libraryDir       The directory of the library.
     *
     * @return array|null The features of the question type or null if none found.
     */
    private function getQuestionTypeFeatures($preloadedJsFiles, $libraryDir)
    {
        if (empty($preloadedJsFiles) || !is_dir($libraryDir)) {
            return null;
        }

        $results = [
            "getAnswerGiven" => false,
            "getScore" => false,
            "getMaxScore" => false,
            "showSolutions" => false,
            "resetTask" => false,
            "getXAPIData" => false,
            "getCurrentState" => false,
            "enableSolutionsButton" => false,
            "enableRetry" => false,
        ];

        foreach ($preloadedJsFiles as $fileName) {
            $fullName = $libraryDir . DIRECTORY_SEPARATOR . $fileName["path"];
            $featuresFound = $this->getJavaScriptFeatures($fullName);
            $results = array_merge($results, $featuresFound);

            if (
                array_reduce(
                    $results,
                    fn($carry, $item) => $carry && $item,
                    true
                )
            ) {
                break; // All features found already
            }
        }

        return $results;
    }

    /**
     * Get the language data.
     *
     * @param string $languageDir The directory of the language files.
     *
     * @return array|null The language data or null if none found.
     */
    private function getLanguageData($languageDir)
    {
        if (!is_dir($languageDir)) {
            return null;
        }

        $languageFiles = array_filter(scandir($languageDir), function ($file) {
            return preg_match('/\.json$/', $file);
        });

        if (empty($languageFiles)) {
            return null;
        }

        $results = [];
        foreach ($languageFiles as $languageFile) {
            $languageJsonData = FileUtils::getJSONData(
                $languageDir . DIRECTORY_SEPARATOR . $languageFile
            );

            if ($languageJsonData === null) {
                continue;
            }

            if ($languageFile === ".en.json") {
                $languageFile = "en.json";
            }
            $languageCode = explode(".", $languageFile)[0];

            $results[$languageCode] = $languageJsonData;
        }

        return $results;
    }

    /**
     * Extract the content of the H5P file to a temporary directory.
     *
     * @param string $file The H5P file to extract.
     *
     * @return string|false Name of temporary directory or false.
     */
    private function extractContent($file)
    {
        // Create temporary directory with time stamp+uuid for garbage collection
        $directoryName = time() . "-" . GeneralUtils::createUUID();

        $extractDir =
            $this->uploadsDirectory . DIRECTORY_SEPARATOR . $directoryName;
        if (!is_dir($extractDir)) {
            if (!is_writable($this->uploadsDirectory)) {
                throw new \Exception(
                    sprintf(
                        LocaleUtils::getString("error:uploadDirectoryNotWritable"),
                        $extractDir
                    )
                );
            }

            if (!mkdir($extractDir, 0777, true) && !is_dir($extractDir)) {
                throw new \Exception(
                    sprintf(
                        LocaleUtils::getString("error:couldNotCreateUploadDirectory"),
                        $extractDir
                    )
                );
            }
        }

        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            throw new \Exception(LocaleUtils::getString("error:unzipFailed"));
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $zip->extractTo($extractDir, $filename);
        }
        $zip->close();

        return $directoryName;
    }

    /**
     * Get the H5P content informaton from h5p.json.
     *
     * @return string|null The H5P content type CSS if it exists, null otherwise.
     */
    private function extractH5PInformation()
    {
        $extractDir =
            $this->uploadsDirectory .
            DIRECTORY_SEPARATOR .
            $this->filesDirectory;

        if (!is_dir($extractDir)) {
            throw new \Exception(
                LocaleUtils::getString("error:H5PFileDirectoryDoesNotExist")
            );
        }

        $h5pJsonFile = $extractDir . DIRECTORY_SEPARATOR . "h5p.json";

        if (!file_exists($h5pJsonFile)) {
            throw new \Exception(
                LocaleUtils::getString("error:noH5PJSON")
            );
        }

        $jsonContents = file_get_contents($h5pJsonFile);
        $jsonData = json_decode($jsonContents, true);

        if ($jsonData === null) {
            throw new \Exception(LocaleUtils::getString("error:decodingH5PJSON"));
        }

        return $jsonData;
    }

    /**
     * Delete a directory and its contents.
     *
     * @param string $dir The directory to delete.
     *
     * @return void
     */
    private function deleteDirectory($dir)
    {
        $dirWithBase = $this->uploadsDirectory . DIRECTORY_SEPARATOR . $dir;
        if (!is_dir($dirWithBase)) {
            return;
        }

        $files = array_diff(scandir($dirWithBase), [".", ".."]);
        foreach ($files as $file) {
            if (is_dir($dirWithBase . DIRECTORY_SEPARATOR . $file)) {
                $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $file);
            } else {
                unlink($dirWithBase . DIRECTORY_SEPARATOR . $file);
            }
        }

        rmdir($dirWithBase);
    }

    /**
     * Delete directories in uploads directory that are older than time difference.
     *
     * @param int $timediff The time difference in seconds.
     *
     * @return void
     */
    private function collectGarbage($timediff = 60)
    {
        $currentTimestamp = time();

        $directories = glob(
            $this->uploadsDirectory . DIRECTORY_SEPARATOR . "*",
            GLOB_ONLYDIR
        );

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            $timestamp = intval(explode("-", $dirName)[0] ?? $currentTimestamp);

            if ($currentTimestamp - $timestamp >= $timediff) {
                $this->deleteDirectory($dirName);
            }
        }
    }
}
