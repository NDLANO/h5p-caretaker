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
class EfficiencyReport
{
    public static $categoryName = "efficiency";
    public static $typeNames = ["imageSize"];

    // Maximum image sizes in bytes for different image types and resolutions
    private const MAX_IMAGE_SIZES = [
        "jpeg" => [
            0 => 51200,
            10000 => 102400,
            307200 => 204800,
            2073600 => 512000
        ],
        "png" => [
            0 => 51200,
            10000 => 153600,
            307200 => 307200,
            2073600 => 512000,
        ],
        "gif" => [
            0 => 51200,
            10000 => 204800,
            307200 => 512000,
            2073600 => 1048576
        ],
        "*" => [
            0 => 51200,
            10000 => 204800,
            960000 => 512000
        ]
    ];

  /**
     * Get the license report.
     *
     * @param ContentTree $contentTree The content tree.
     * @param array       $rawInfo     The raw info.
     */
    public static function generateReport($contentTree, $rawInfo)
    {
        $contents = $contentTree->getContents();

        foreach ($contents as $content) {
            $contentFiles = $content->getAttribute("contentFiles");
            foreach ($contentFiles as $contentFile) {
                $path = $contentFile->getAttribute("path");
                if (!str_starts_with($path, "images/")) {
                    continue; // Only reporting for images
                }

                $fileName = str_replace("images/", "", $path);
                $size = $rawInfo["media"]->images->$fileName["size"];

                $type = self::getImageType(
                    $contentFile->getAttribute("mime"),
                    explode(".", $fileName)[1] ?? "*"
                );

                $width = $contentFile->getAttribute("width");
                $height = $contentFile->getAttribute("height");

                $recommendedMaxSize = self::getMaxImageSize($type, $width, $height);

                if ($size > $recommendedMaxSize) {
                    $description = [];
                    if (getType($width) === "integer" && getType($height) === "integer") {
                        $description[] = sprintf(
                            _("The image has a resolution of %dx%d pixels."),
                            $width,
                            $height
                        );
                    } else {
                        $description[] = _("The image has an unknown resolution.");
                    }

                    $description[] = sprintf(
                        _("The image file size is %s bytes."),
                        number_format($size)
                    );

                    $description[] = sprintf(
                        _("The image type is %s."),
                        $type === '*' ? _("unknown") : strtoupper($type)
                    );

                    $recommendation = [];
                    $recommendation[] = sprintf(
                        _("For this image type, we recommend a maximum file size of %s bytes in a web based context."),
                        number_format($recommendedMaxSize)
                    );

                    $recommendation[] =
                      _("You might consider reducing the image's resolution if it does not need to be this high.");

                    if ($type !== "jpeg") {
                        $recommendation[] =
                          _("You might consider converting the image to a JPEG file which often take less space.");
                    } else {
                        $recommendation[] =
                          _("You might consider reducing the quality level of the JPEG image.");
                    }

                    $message = ReportUtils::buildMessage(
                        [
                        "category" => "efficiency",
                        "type" => "imageSize",
                        "summary" => sprintf(
                            _("Image file inside %s feels quite large."),
                            $contentFile->getParent()->getDescription()
                        ),
                        "recommendation" => $recommendation,
                        "description" => $description,
                        "details" => [
                          "path" => $contentFile->getAttribute("path"),
                          "semanticsPath" => $contentFile->getAttribute(
                              "semanticsPath"
                          ),
                          "title" => $contentFile->getDescription(
                              "{title}"
                          ),
                          "subContentId" => $content->getAttribute("id")
                        ],
                        "level" => "warning"
                        ]
                    );
                    $content->addReportMessage($message);
                }
            }
        }
    }

    /**
     * Get the maximum recommended image size.
     * @param string $type Type of image or *.
     * @param integer|null $width Width of image.
     * @param integer|null $height Height of image.
     * @return integer Maximum recommended image size in bytes
     */
    private static function getMaxImageSize($type = "*", $width = null, $height = null)
    {
        if (
            !isset($width) || getType($width) !== "integer" ||
            !isset($height) || getType($height) !== "integer"
        ) {
            $type = "*";
            $resolution = INF;
        }
        if (!isset($resolution)) {
            $resolution = $width * $height;
        }

        $maxSizes = self::MAX_IMAGE_SIZES[$type];
        $maxSizesCount = count($maxSizes);

        for ($i = $maxSizesCount - 1; $i >= 0; $i--) {
            if ($resolution > array_keys($maxSizes)[$i]) {
                return $maxSizes[array_keys($maxSizes)[$i]];
            }
        }

        return $maxSizes[0];
    }

    /**
     * Get the image type.
     * @param string $mime MIME type.
     * @param string $suffix Suffix.
     * @return string Image type, could be *.
     */
    private static function getImageType($mime = "*", $suffix = "*")
    {
        if (!str_starts_with($mime, "image/")) {
            $mime = "*";
        }

        if ($mime === "*" && $suffix === "*") {
            return "*";
        }

        if ($mime === "*") {
            if ($suffix === "jpg") {
                $suffix = "jpeg";
            }
            $mime = "images/" . $suffix;
        }

        $mime = explode("/", $mime)[1];

        if (array_key_exists($mime, self::MAX_IMAGE_SIZES)) {
            return $mime;
        }

        return "*";
    }
}
