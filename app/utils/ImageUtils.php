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
class ImageUtils
{
    /** Supported file types by GD. */
    private static $SUPPORTED_FILE_TYPES = ["jpeg", "png", "gif", "bmp"];

    public static function scaleDown($arguments, $filePath)
    {
        $targetProperty = array_shift($arguments);
        if ($targetProperty !== "width" && $targetProperty !== "height") {
            return [-1, -1]; // Invalid target property
        }

        $targetValue = array_shift($arguments);
        if (!is_numeric($targetValue) || $targetValue <= 0) {
            return [-1, -1]; // Invalid target value
        }

        $mime = FileUtils::getMimeType($filePath);
        $fileType = explode("/", $mime)[1];
        if (!in_array($fileType, self::$SUPPORTED_FILE_TYPES)) {
            return [-1, -1];
        }

        list($width, $height) = getimagesize($filePath);

        if ($targetProperty === "width") {
            if ($width === 0 || $targetValue >= $width) {
                return [-1, -1];
            }
            $newWidth = (int)$targetValue;
            $newHeight = ($height / $width) * $newWidth;
        } elseif ($targetProperty === "height") {
            if ($height === 0 || $targetValue >= $height) {
                return [-1, -1];
            }
            $newHeight = (int)$targetValue;
            $newWidth = ($width / $height) * $newHeight;
        }

        $newImage_p = imagecreatetruecolor($newWidth, $newHeight);

        if ($fileType === "jpeg") {
            $newImage = imagecreatefromjpeg($filePath);
        } elseif ($fileType === "png") {
            $newImage = imagecreatefrompng($filePath);
        } elseif ($fileType === "gif") {
            $newImage = imagecreatefromgif($filePath);
        } elseif ($fileType === "bmp") {
            $newImage = imagecreatefrombmp($filePath);
        }

        imagecopyresampled($newImage_p, $newImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        if ($fileType === "jpeg") {
            imagejpeg($newImage_p, $filePath, 90);
        } elseif ($fileType === "png") {
            imagepng($newImage_p, $filePath, 9);
        } elseif ($fileType === "gif") {
            imagegif($newImage_p, $filePath);
        } elseif ($fileType === "bmp") {
            imagebmp($newImage_p, $filePath);
        }

        imagedestroy($newImage);
        imagedestroy($newImage_p);

        return [$newWidth, $newHeight];
    }

    /**
     * Convert an image to a different format. Does NOT change the file extension!
     *
     * @param array  $arguments The arguments for the conversion.
     * @param string $filePath  The path to the image file.
     *
     * @return bool True on success, false on failure.
     */
    public static function convert($arguments, $filePath)
    {
        $outputType = array_shift($arguments);
        if (!in_array($outputType, self::$SUPPORTED_FILE_TYPES)) {
            return false;
        }

        $mime = FileUtils::getMimeType($filePath);
        $inputType = explode("/", $mime)[1];
        if (!in_array($inputType, self::$SUPPORTED_FILE_TYPES)) {
            return false;
        }

        if ($inputType === "jpeg") {
            $image = imagecreatefromjpeg($filePath);
        } elseif ($inputType === "png") {
            $image = imagecreatefrompng($filePath);
        } elseif ($inputType === "gif") {
            $image = imagecreatefromgif($filePath);
        } elseif ($inputType === "bmp") {
            $image = imagecreatefrombmp($filePath);
        }

        if ($outputType === "jpeg") {
            imagejpeg($image, $filePath, 90);
        } elseif ($outputType === "png") {
            imagepng($image, $filePath, 9);
        } elseif ($outputType === "gif") {
            imagegif($image, $filePath);
        } elseif ($outputType === "bmp") {
            imagebmp($image, $filePath);
        }

        imagedestroy($image);

        return true;
    }
}
