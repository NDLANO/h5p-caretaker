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
 * Class for handling Validation specific stuff.
 *
 * @category File
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */
class ValidationUtils
{
    // Taken from H5P core (https://github.com/h5p/h5p-php-library/blob/master/h5p.classes.php)
    private static $validationRegexes = [
      "title " => "/^.{1,255}$/",
      "a11yTitle" => "/^.{1,255}$/",
      "author" => "/^.{1,255}$/", // internal copyright widget
      "authors" => [
        "name" => "/^.{1,255}$/",
        "role" => "/^\w+$/",
      ],
      "source" => "/^(http[s]?:\/\/.+)$/",
      "license" =>
        "/^(CC BY|CC BY-SA|CC BY-ND|CC BY-NC|CC BY-NC-SA|CC BY-NC-ND|CC0 1\.0|GNU GPL|PD|ODC PDDL|CC PDM|U|C)$/",
      "licenseVersion" => "/^(1\.0|2\.0|2\.5|3\.0|4\.0)$/",
      'licenseExtras' => '/^.{1,5000}$/s',
      "version" => "/^(1\.0|2\.0|2\.5|3\.0|4\.0)$/",
      "changes" => [
        "date" => "/^[0-9]{2}-[0-9]{2}-[0-9]{2} [0-9]{1,2}:[0-9]{2}:[0-9]{2}$/",
        "author" => "/^.{1,255}$/",
        "log" => "/^.{1,5000}$/s"
      ],
      'authorComments' => '/^.{1,5000}$/s'
    ];

    /**
     * Check whether the given value is valid for the given key.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value to check.
     *
     * @return bool True if the value is valid for the given key, false otherwise.
     */
    public static function isValidH5PJsonValue($key, $value)
    {
        if (!is_string($key) || !is_string($value)) {
            return false;
        }

        $regex = self::getRegex($key);
        if (!isset($regex) || $regex === false) {
            return false;
        }

        return preg_match($regex, $value);
    }

    /**
     * Get the regex for the given key.
     *
     * @param string $key The key to get the regex for.
     *
     * @return string|false The regex for the given key or false if invalid key.
     */
    public static function getRegex($key)
    {
        if (!is_string($key)) {
            return false;
        }

        if (str_contains($key, ".")) {
            $keyParts = explode(".", $key);
            $key = array_shift($keyParts);
            foreach ($keyParts as $part) {
                if (!array_key_exists($part, self::$validationRegexes[$key])) {
                    return false;
                }
                $key = self::$validationRegexes[$key][$part];
            }
            return $key;
        }

        if (!array_key_exists($key, self::$validationRegexes)) {
            return false;
        }

        return self::$validationRegexes[$key];
    }

    /**
     * Get the pattern for the given key.
     *
     * @param string $key The key to get the pattern for.
     *
     * @return string|false The pattern for the given key or false if invalid key.
     */
    public static function getPattern($key)
    {
        $pattern = self::getRegex($key);
        if ($pattern === false) {
            return false;
        }

        // Strip outer / and modifier flags
        if (preg_match("/^\/(.*?)(\/[a-zA-Z]*)?$/", $pattern, $matches)) {
            $pattern = $matches[1];
        }

        return $pattern;
    }
}
