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

use Tree\Node\Node;

/**
 * Main class.
 *
 * @category Tool
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/H5PCaretaker
 */
class ContentTree
{
    private $contents;

    /**
     * Constructor.
     *
     * @param array $rawdata The raw data.
     */
    public function __construct($rawdata)
    {
        $h5pJson = $rawdata["h5pJson"];
        $this->contents = [];

        // Root node
        $this->contents[] = new Content([
            "attributes" => [
                "id" => "root",
                "versionedMachineName" => self::getversionedMachineName(
                    $h5pJson
                ),
                "metadata" => JSONUtils::h5pJsonToMetadata($h5pJson),
                "semanticsPath" => "",
                "params" => $h5pJson,
            ],
        ]);

        $libraryPaths = self::findLibraryPaths($rawdata["contentJson"]);

        foreach ($libraryPaths as $path) {
            $libraryParams = JSONUtils::getElementAtPath(
                $rawdata["contentJson"],
                $path
            );

            $this->contents[] = new Content([
                "attributes" => [
                    "id" => $libraryParams["subContentId"] ?? "",
                    "versionedMachineName" => $libraryParams["library"],
                    "metadata" => $libraryParams["metadata"],
                    "semanticsPath" => $path,
                    "params" => $libraryParams["params"],
                ],
            ]);
        }

        foreach ($this->contents as $child) {
            if ($child->getAttribute("id") === "root") {
                continue;
            }

            $childPath = $child->getAttribute("semanticsPath");
            $parentPath = self::getParentPath($childPath, $libraryPaths);

            if ($parentPath === "") {
                $parent = $this->contents[0];
            } else {
                $parent = $this->getByPath($parentPath);
            }

            $child->setParent($parent);
            $parent->addChild($child);
        }

        // Set base64 data for content files
        foreach ($this->contents as $content) {
            $contentFiles = $content->getAttribute("contentFiles") ?? [];
            foreach ($contentFiles as $contentFile) {
                $pathSegments = explode(
                    "/",
                    $contentFile->getAttribute("path")
                );

                $media = $rawdata["media"];

                // Access $media based on pathSegments
                foreach ($pathSegments as $segment) {
                    if (isset($media->$segment)) {
                        $media = $media->$segment;
                    } else {
                        $media = null;
                        break;
                    }
                }

                if ($media !== null) {
                    if (isset($media["base64"])) {
                        $contentFile->setAttribute("base64", $media["base64"]);
                    }
                }
            }
        }

        // Add libretext data
        foreach ($this->contents as $content) {
            $machineName = explode(
                " ",
                $content->getAttribute("versionedMachineName")
            )[0];
            $libraryInfo = $rawdata["libraries"][$machineName] ?? null;
            if (isset($libraryInfo) && isset($libraryInfo->libreTextA11y)) {
                $content->setAttribute(
                    "libreText",
                    $libraryInfo->libreTextA11y
                );
            }
        }
    }

    /**
     * Get the root node.
     *
     * @return Node|null The root node.
     */
    public function getRoot()
    {
        return $this->contents[0] ?? null;
    }

    /**
     * Get the contents.
     *
     * @return array The contents.
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Get all reports.
     *
     * @return array The reports.
     */
    public function getReports()
    {
        $reports = [];
        foreach ($this->contents as $content) {
            $reports = array_merge($reports, $content->getReports());
        }

        return $reports;
    }

    /**
     * Get node by semantics path.
     *
     * @param string $path The semantics path.
     *
     * @return Node|null The node.
     */
    private function getByPath($path)
    {
        foreach ($this->contents as $content) {
            $semanticsPath = $content->getAttribute("semanticsPath");
            if (isset($semanticsPath) && $semanticsPath === $path) {
                return $content;
            }
        }

        return null;
    }

    /**
     * Find all library paths in the content JSON.
     *
     * @param array $data The content JSON.
     * @param bool  $onlyChildren Whether to only return direct children.
     * @param string $currentPath The current path.
     *
     * @return array The library paths.
     */
    private static function findLibraryPaths(
        $data,
        $onlyChildren = false,
        $currentPath = ""
    ) {
        $paths = [];
        $libraryRegex = "/^H5P\..+ \d+\.\d+/";

        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $newPath =
                    $currentPath === "" ? $key : $currentPath . "." . $key;

                if ($key === "library" && preg_match($libraryRegex, $value)) {
                    // Path should be in JavaScript notation
                    $paths[] = preg_replace(
                        '/\.(\d+)(\.|$)/',
                        '[$1]$2',
                        $currentPath
                    );
                } else {
                    $paths = array_merge(
                        $paths,
                        self::findLibraryPaths($value, $onlyChildren, $newPath)
                    );
                }
            }
        }

        // Ensure depth-first search order
        usort($paths, function ($a, $b) {
            if (strpos($a, $b) === 0 && strlen($a) > strlen($b)) {
                return 1;
            }

            if (strpos($b, $a) === 0 && strlen($b) > strlen($a)) {
                return -1;
            }

            return strcmp($a, $b);
        });

        /*
         * If at some point performance issues arise, we could implement a
         * different approach for when only direct children are needed and
         * it is not required to traverse the whole tree.
         */
        if (!$onlyChildren) {
            return $paths;
        } else {
            $paths = array_filter($paths, function ($path) use ($paths) {
                foreach ($paths as $otherPath) {
                    if (
                        $path !== $otherPath &&
                        strpos($path, $otherPath) === 0
                    ) {
                        return false;
                    }
                }

                return true;
            });
        }

        return $paths;
    }

    /**
     * Find the parent path of a given path.
     *
     * @param string $childPath The child path.
     * @param array  $paths     The paths to look in.
     *
     * @return string The parent path.
     */
    private static function getParentPath($childPath, $paths)
    {
        $parentPath = "";

        foreach ($paths as $path) {
            if ($path === $childPath) {
                break; // $paths is sorted as depth-first search
            }

            if (
                strpos($childPath, $path) === 0 &&
                strlen($path) < strlen($childPath) &&
                strlen($path) > strlen($parentPath)
            ) {
                $parentPath = $path;
            }
        }

        return $parentPath;
    }

    /**
     * Find the version of the main library.
     *
     * @param array $h5pJson The H5P JSON object.
     *
     * @return string|null The versioned name of the main library.
     */
    private static function getversionedMachineName($h5pJson)
    {
        if (
            !isset($h5pJson["mainLibrary"]) ||
            !isset($h5pJson["preloadedDependencies"])
        ) {
            return null;
        }

        foreach ($h5pJson["preloadedDependencies"] as $dependency) {
            if ($dependency["machineName"] === $h5pJson["mainLibrary"]) {
                return $dependency["machineName"] .
                    " " .
                    $dependency["majorVersion"] .
                    "." .
                    $dependency["minorVersion"];
            }
        }

        return null;
    }
}
