<?php

/**
 * Proof of concept code for extracting and displaying H5P content server-side.
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
 * Class for handling H5P specific stuff.
 *
 * @category File
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */
class JSONUtils
{
    /**
     * Find an element in a JSON object by its attribute value pairs.
     *
     * @param array $json The JSON object to search in.
     * @param array $pairs The attribute value pairs to search for.
     *
     * @return array The found elements.
     */
    public static function findAttributeValuePairs($json, $pairs)
    {
        $results = [];

        self::traverseJson($json, $pairs, "", $results);
        return $results;
    }

    /**
     * Traverse a JSON object and search for attribute value pairs.
     *
     * @param array $json The JSON object to traverse.
     * @param array $pairs The attribute value pairs to search for.
     * @param string $currentPath The current path in the JSON object.
     * @param array $results The results array to store the results in.
     */
    private static function traverseJson($json, $pairs, $currentPath, &$results)
    {
        if (is_array($json)) {
            foreach ($json as $key => $value) {
                $newPath = $currentPath === "" ? $key : "$currentPath.$key";
                if (is_array($value)) {
                    foreach ($pairs as $pair) {
                        $attribute = $pair[0];
                        $valuePattern = $pair[1];
                        if (
                            array_key_exists($attribute, $value) &&
                            preg_match($valuePattern, $value[$attribute])
                        ) {
                            $results[] = [
                                "path" => preg_replace(
                                    "/\.(\d+)(\.|$)/",
                                    '[$1]$2',
                                    $newPath
                                ),
                                "object" => $value,
                            ];
                        }
                    }

                    self::traverseJson($value, $pairs, $newPath, $results);
                }
            }
        }
    }

    /**
     * Convert H5P JSON to metadata.
     *
     * @param array $h5pJson The H5P JSON.
     *
     * @return array The metadata.
     */
    public static function h5pJsonToMetadata($h5pJson)
    {
        $metadata = [];

        $metadata["title"] = $h5pJson["title"] ?? "";

        if (isset($h5pJson["a11yTitle"])) {
            $metadata["a11yTitle"] = $h5pJson["a11yTitle"];
        }

        $metadata["license"] = $h5pJson["license"] ?? "U";
        $metadata["authors"] = $h5pJson["authors"] ?? [];

        if (isset($h5pJson["source"])) {
            $metadata["source"] = $h5pJson["source"];
        }

        if (isset($h5pJson["licenseVersion"])) {
            $metadata["licenseVersion"] = $h5pJson["licenseVersion"];
        }

        if (isset($h5pJson["yearFrom"])) {
            $metadata["yearFrom"] = $h5pJson["yearFrom"];
        }

        if (isset($h5pJson["yearTo"])) {
            $metadata["yearTo"] = $h5pJson["yearTo"];
        }

        if (isset($h5pJson["changes"])) {
            $metadata["changes"] = $h5pJson["changes"];
        }

        if (isset($h5pJson["licenseExtras"])) {
            $metadata["licenseExtras"] = $h5pJson["licenseExtras"];
        }

        if (isset($h5pJson["authorComments"])) {
            $metadata["authorComments"] = $h5pJson["authorComments"];
        }

        return $metadata;
    }

    /**
     * Convert a copyright object to metadata.
     *
     * @param array $copyright The copyright object.
     *
     * @return array The metadata.
     */
    public static function copyrightToMetadata($copyright)
    {
        $metadata = [];

        $metadata["license"] = $copyright["license"] ?? "U";

        if (isset($copyright["title"])) {
            $metadata["title"] = $copyright["title"];
        }

        if (isset($copyright["author"])) {
            $metadata["authors"] = [
                "author" => $copyright["author"],
                "role" => "Author",
            ];
        } else {
            $metadata["authors"] = [];
        }

        $yearInput = trim($copyright["year"] ?? "");
        if ($yearInput !== "") {
            $patternSingleYear = '/^-?\d+$/';
            $patternYearRange = '/^(-?\d+)\s*-\s*(-?\d+)$/';

            if (preg_match($patternSingleYear, $yearInput)) {
                $metadata["yearFrom"] = $yearInput;
            } elseif (preg_match($patternYearRange, $yearInput, $matches)) {
                $metadata["yearFrom"] = $matches[1];
                $metadata["yearTo"] = $matches[2];
            }
        }

        if (isset($copyright["source"])) {
            $metadata["source"] = $copyright["source"];
        }

        if (isset($copyright["version"])) {
            $metadata["licenseVersion"] = $copyright["version"];
        }

        return $metadata;
    }

    /**
     * Get an element at a specific path in a JSON object.
     *
     * @param array $contentJson The JSON object to search in.
     * @param string $path The path to the element.
     *
     * @return array|null The element at the path or null if not found.
     */
    public static function getElementAtPath($contentJson, $path)
    {
        $pathSegments = explode(".", $path);

        $current = $contentJson;
        foreach ($pathSegments as $segment) {
            // Split segment /(\w)[(\d+)]/ into attribute as (\w) and index as (\d+)
            $matches = [];
            preg_match("/(\w+)(?:\[(\d+)\])?/", $segment, $matches);
            $part = $matches[1];
            $index = $matches[2] ?? null;

            if (!isset($index) && isset($current[$part])) {
                $current = $current[$part];
            } elseif (
                isset($index) &&
                isset($current[$part]) &&
                isset($current[$part][$index])
            ) {
                $current = $current[$part][$index];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Get the parent path of a path.
     *
     * @param string $path The path.
     *
     * @return string The parent path.
     */
    public static function getParentPath($path)
    {
        $lastDotPosition = strrpos($path, ".");
        return $lastDotPosition === false
            ? $path
            : substr($path, 0, $lastDotPosition);
    }

    /**
     * Get the last segment of a path.
     *
     * @param string $path The path.
     *
     * @return string The last segment of the path.
     */
    public static function getPathProperty($path)
    {
        $lastDotPosition = strrpos($path, ".");
        return $lastDotPosition === false
            ? $path
            : substr($path, $lastDotPosition + 1);
    }

    /**
     * Set a property at a specific path in a JSON object. Array items will be created.
     *
     * @param array &$contentJson The JSON object to modify (by reference).
     * @param string $path The path to the property.
     * @param mixed $value The value to set.
     *
     */
    public static function setPropertyAtPath(&$contentJson, $path, $value)
    {
        $parentPath = self::getParentPath($path);
        if ($parentPath === $path) {
            return;
        }

        $property = self::getPathProperty($path);
        $segments = explode('.', $parentPath);

        $reference = &$contentJson;
        foreach ($segments as $segment) {
            $matches = [];
            preg_match("/(\w+)(?:\[(\d+)\])?/", $segment, $matches);

            $part = $matches[1];
            $index = $matches[2] ?? null;

            if (!isset($reference[$part])) {
                $reference[$part] = [];
            }

            if ($index !== null) {
                if (!isset($reference[$part][$index])) {
                    $index = count($reference[$part]);
                    $reference[$part][$index] = [];
                }
                $reference = &$reference[$part][$index];
            } else {
                $reference = &$reference[$part];
            }
        }

        $reference[$property] = $value;
    }

    /**
     * Get the closest library to a path in a JSON object.
     *
     * @param array $json The JSON object to search in.
     * @param string $path The path to the element.
     *
     * @return array|null The closest library or null if not found.
     */
    public static function getClosestLibrary($json, $path)
    {
        $testElement = self::getElementAtPath($json, $path);
        if ($testElement === null) {
            return null;
        } elseif (isset($testElement["library"])) {
            return [
                "params" => $testElement,
                "jsonPath" => $path,
            ];
        } elseif (strrpos($path, ".") === false) {
            return null;
        } else {
            $parentPath = self::getParentPath($path);
            return self::getClosestLibrary($json, $parentPath);
        }
    }

    /**
     * Prune parameters of children.
     *
     * @param array $params The parameters.
     *
     * @return array The pruned parameters.
     */
    public static function pruneChildren($params = [])
    {
        $prunedParams = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                if (array_key_exists("library", $value)) {
                    continue;
                }

                $prunedValue = self::pruneChildren($value);
                if (!empty($prunedValue)) {
                    $prunedParams[$key] = $prunedValue;
                }
            } elseif ($key !== "library") {
                $prunedParams[$key] = $value;
            }
        }
        return $prunedParams;
    }
}
