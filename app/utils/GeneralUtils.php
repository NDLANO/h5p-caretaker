<?php

/**
 * Proof of concept code for extracting and displaying H5P content server-side.
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
 * Class for general utility functions.
 *
 * @category File
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */
class GeneralUtils
{
    /**
     * Create a UUID.
     *
     * @return string The UUID.
     */
    public static function createUUID()
    {
        return preg_replace_callback(
            "/[xy]/",
            function ($match) {
                $random = random_int(0, 15);
                $newChar = $match[0] === "x" ? $random : ($random & 0x3) | 0x8;
                return dechex($newChar);
            },
            "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
        );
    }

    /**
     * Convert a human-readable size to bytes.
     *
     * @param string $size The human-readable size (as in php.ini).
     *
     * @return int The size in bytes.
     */
    public static function convertToBytes($size)
    {
        $unit = substr($size, -1);
        $value = (int)$size;

        switch (strtoupper($unit)) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return $value;
        }
    }
}
