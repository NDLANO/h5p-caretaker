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
 * @link     https://github.com/ndlano/H5PCaretaker
 */

namespace Ndlano\H5PCaretaker;

/**
 * Class for handling H5P specific stuff.
 *
 * @category File
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/H5PCaretaker
 */
class H5PUtils
{
    /**
     * Build the class name for the given H5P content type.
     *
     * @param string $machineName  The machine name of the content type.
     * @param int    $majorVersion The major version of the content type.
     * @param int    $minorVersion The minor version of the content type.
     * @param string $prefix       The optional prefix for the class name.
     *
     * @return string The class name for the given H5P content type.
     */
    public static function buildClassName(
        $machineName,
        $majorVersion,
        $minorVersion,
        $prefix = ""
    ) {
        return $prefix .
            explode(".", $machineName)[1] .
            "Major" .
            $majorVersion .
            "Minor" .
            $minorVersion;
    }

    /**
     * Get the library info from the given string.
     *
     * @param string $fullName  The full name of the library.
     * @param string $delimiter The delimiter between name and version.
     *
     * @return array|false The library info or false if invalid name.
     */
    public static function getLibraryFromString($fullName, $delimiter = " ")
    {
        $pattern = "/(H5P\..+)" . preg_quote($delimiter) . "(\d+)\.(\d+)/";

        if (!preg_match($pattern, $fullName, $matches)) {
            return false; // Invalid library name
        }

        return [
            "machineName" => $matches[1],
            "majorVersion" => $matches[2],
            "minorVersion" => $matches[3],
        ];
    }
}
