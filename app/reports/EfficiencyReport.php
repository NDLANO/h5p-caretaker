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
class EfficiencyReport
{
    public static $categoryName = "efficiency";
    public static $typeNames = ["imageSize", "imageResolution"];

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

    private const WCAG_ZOOM_FACTOR = 4;

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
                $fileSize = $rawInfo["media"]->images->$fileName["size"] ?? null;

                $type = self::getImageType(
                    $contentFile->getAttribute("mime"),
                    explode(".", $fileName)[1] ?? "*"
                );

                // TODO: Retrieve from actual file instead?
                $width = $contentFile->getAttribute("width");
                $height = $contentFile->getAttribute("height");

                self::handleResolution($width, $height, $contentFile);
                self::handleFileSize($width, $height, $fileSize, $type, $contentFile);
            }
        }
    }

    /**
     * Handle resolution. There are some content types that will limit the rendering size of images.
     * @param integer $width Image width in pixels.
     * @param integer $height Image height in pixels.
     * @param ContentFile $contentFile Content file instance.
     */
    private static function handleResolution($width, $height, $contentFile)
    {
        $machineName = explode(" ", $contentFile->getParent()->getAttribute("versionedMachineName"))[0];

        if ($machineName === "H5P.BranchingScenario") {
            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => ".startScreenImage",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 240, // 15em is max height of start screen image
            ]);

            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => ".endScreenImage",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 240, // 15em is max height of end screen image
            ]);
        } elseif ($machineName === "H5P.Dialogcards") {
            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => ".image",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 240, // 15em is max height of end screen image
            ]);
        } elseif ($machineName === "H5P.Flashcards") {
            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => ".image",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 240, // 15em is max height of end screen image
            ]);
        } elseif ($machineName === "H5P.Image") {
            $parentMachineName = explode(
                " ",
                $contentFile->getParent()->getParent()->getAttribute("versionedMachineName")
            )[0];

            if ($parentMachineName === "H5P.ARScavenger") {
                self::handleResolutionContentType([
                    "contentFile" => $contentFile,
                    "targetSemanticsPath" => ".titleScreenImage.params.file",
                    "width" => $width,
                    "height" => $height,
                    "maxHeightPx" => 240, // 15em is max height of image
                ]);

                self::handleResolutionContentType([
                    "contentFile" => $contentFile,
                    "targetSemanticsPath" => ".endScreenImage.params.file",
                    "width" => $width,
                    "height" => $height,
                    "maxHeightPx" => 240, // 15em is max height of image
                ]);
            } elseif ($parentMachineName === "H5P.InteractiveBook") {
                self::handleResolutionContentType([
                    "contentFile" => $contentFile,
                    "targetSemanticsPath" => ".coverMedium.params.file",
                    "width" => $width,
                    "height" => $height,
                    "maxHeightPx" => 240, // 15em is max height of image
                ]);
            } elseif ($parentMachineName === "H5P.InfoWall") {
                $infoWallParams = $contentFile->getParent()->getParent()->getAttribute("params") ?? null;
                $behaviour = $infoWallParams["infoWall"]["behaviour"] ?? null;

                self::handleResolutionContentType([
                    "contentFile" => $contentFile,
                    "targetSemanticsPath" => ".image.params.file",
                    "width" => $width,
                    "height" => $height,
                    "maxHeightPx" => $behaviour["imageHeight"] ?? null,
                    "maxWidthPx" => $behaviour["imageWidth"] ?? null,
                ]);
            }
        } elseif ($machineName === "H5P.ImageHotspots") {
            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => "iconImage",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 75,
                "maxWidthPx" => 75,
            ]);
        } elseif ($machineName === "H5P.ImagePair") {
            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => ".image",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 106, // 6.625em is max height of image
                "maxWidthPx" => 106, // 6.625em is max width of image
            ]);

            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => ".match",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 106, // 6.625em is max height of image
                "maxWidthPx" => 106, // 6.625em is max width of image
            ]);
        } elseif ($machineName === "H5P.ImageSequencing") {
            self::handleResolutionContentType([
                "contentFile" => $contentFile,
                "targetSemanticsPath" => ".image",
                "width" => $width,
                "height" => $height,
                "maxHeightPx" => 165.6,
                "maxWidthPx" => 165.6,
            ]);
        }
    }

    /**
     * Handle the resolution for specific content types.
     * @param array $params Parameters.
     */
    private static function handleResolutionContentType($params = [])
    {
        if (!isset($params['contentFile']) || !isset($params['targetSemanticsPath'])) {
            return;
        }

        $width = $params['width'] ?? 0;
        $height = $params['height'] ?? 0;
        $maxDisplayHeightPx = $params['maxHeightPx'] ?? INF;
        $maxDisplayWidthPx = $params['maxWidthPx'] ?? INF;
        $contentFile = $params['contentFile'];
        $targetSemanticsPath = $params['targetSemanticsPath'];

        $semanticsPath = $contentFile->getAttribute("semanticsPath");

        if (!str_ends_with($semanticsPath, $targetSemanticsPath)) {
            return;
        }

        if (
            $height <= $maxDisplayHeightPx * self::WCAG_ZOOM_FACTOR &&
            $width <= $maxDisplayWidthPx * self::WCAG_ZOOM_FACTOR
        ) {
            return; // image is smaller than the maximum display size considering WCAG zoom factor
        }

        $description = [];
        if (getType($width) === "integer" && getType($height) === "integer") {
            $description[] = sprintf(
                LocaleUtils::getString("efficiency:imageResolution"),
                $width,
                $height
            );
        } else {
            $description[] = LocaleUtils::getString("efficiency:imageUnknownResolution");
        }

        if ($maxDisplayHeightPx < INF && $maxDisplayHeightPx > 0) {
            $description[] = sprintf(
                LocaleUtils::getString("efficiency:imageMaxHeight"),
                $maxDisplayHeightPx
            );
        }

        if ($maxDisplayWidthPx < INF && $maxDisplayWidthPx > 0) {
            $description[] = sprintf(
                LocaleUtils::getString("efficiency:imageMaxWidth"),
                $maxDisplayWidthPx
            );
        }

        $gdActions = [];
        $recommendation = [];
        if ($height > $maxDisplayHeightPx * self::WCAG_ZOOM_FACTOR) {
            $gdActions[] = "scale-down height " . $maxDisplayHeightPx * self::WCAG_ZOOM_FACTOR;
            $recommendation[] = sprintf(
                LocaleUtils::getString("efficiency:imageScaleDownHeight"),
                $maxDisplayHeightPx * self::WCAG_ZOOM_FACTOR
            );
        }

        if ($width > $maxDisplayWidthPx * self::WCAG_ZOOM_FACTOR) {
            $gdActions[] = "scale-down width " . $maxDisplayWidthPx * self::WCAG_ZOOM_FACTOR;
            $recommendation[] = sprintf(
                LocaleUtils::getString("efficiency:imageScaleDownWidth"),
                $maxDisplayWidthPx * self::WCAG_ZOOM_FACTOR
            );
        }

        $editDirectly = [
            "field" => [
                "uuid" => GeneralUtils::createUUID(),
                "type" => "boolean",
                "label" => LocaleUtils::getString("editDirectly"),
                "checkboxLabel" => LocaleUtils::getString("efficiency:applySuggestedChanges"),
                "valueTrue" => implode(";", $gdActions),
                "valueFalse" => "",
                "initialValue" => "",
                "semanticsPath" => ReportUtils::buildMetadataPath(
                    $contentFile->getParent()->getAttribute("semanticsPath"),
                    "file",
                    $contentFile->getAttribute("path")
                ),
                "filePath" => $contentFile->getAttribute("path")
            ]
        ];

        $message = ReportUtils::buildMessage([
            "category" => "efficiency",
            "type" => "imageResolution",
            "summary" => sprintf(
                LocaleUtils::getString("efficiency:imageCouldScaleDown"),
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
              "subContentId" => $contentFile->getParent()->getAttribute("id")
            ],
            "level" => "caution",
            "subContentId" => $contentFile->getParent()->getAttribute("id"),
            "editDirectly" => $editDirectly
        ]);
        $contentFile->getParent()->addReportMessage($message);
    }

    /**
     * Handle the default case.
     * @param integer $width Image width in pixels.
     * @param integer $height Image height in pixels.
     * @param integer $fileSize Image file size in bytes.
     * @param string $imageType Image file type.
     * @param ContentFile $contentFile Content file instance.
     */
    private static function handleFileSize($width, $height, $fileSize, $imageType, $contentFile)
    {
        $recommendedMaxSize = self::getMaxImageSize($imageType, $width, $height);

        if ($fileSize > $recommendedMaxSize) {
            $description = [];
            if (getType($width) === "integer" && getType($height) === "integer") {
                $description[] = sprintf(
                    LocaleUtils::getString("efficiency:imageResolution"),
                    $width,
                    $height
                );
            } else {
                $description[] = LocaleUtils::getString("efficiency:imageUnknownResolution");
            }

            $description[] = sprintf(
                LocaleUtils::getString("efficiency:imageFileSize"),
                number_format($fileSize)
            );

            $description[] = sprintf(
                LocaleUtils::getString("efficiency:imageType"),
                $imageType === '*' ? LocaleUtils::getString("efficiency:imageTypeUnknown") : strtoupper($imageType)
            );

            $gdActions = [];
            $recommendation = [];
            $recommendation[] = sprintf(
                LocaleUtils::getString("efficiency:imageRecommendedSize"),
                number_format($recommendedMaxSize),
                number_format($fileSize)
            );

            // TODO: Add option to iteratively reduce file size until it is below the recommended size?
            // Alternative: Require user to set a resolution/quality

            $recommendation[] = LocaleUtils::getString("efficiency:imageReduceResolution");

            if ($imageType !== "jpeg") {
                $recommendation[] = LocaleUtils::getString("efficiency:imageConvertJPEG");
                $gdActions[] = "convert jpeg";
            } else {
                $recommendation[] = LocaleUtils::getString("efficiency:imageReduceQuality");
            }

            if (count($gdActions) > 0) {
                $editDirectly = [
                    "field" => [
                        "uuid" => GeneralUtils::createUUID(),
                        "type" => "boolean",
                        "label" => LocaleUtils::getString("editDirectly"),
                        "checkboxLabel" => LocaleUtils::getString("efficiency:applySuggestedChanges"),
                        "valueTrue" => implode(";", $gdActions),
                        "valueFalse" => "",
                        "initialValue" => "",
                        "semanticsPath" => $contentFile->getAttribute("semanticsPath"),
                        "filePath" => $contentFile->getAttribute("path")
                    ]
                    ];
            } else {
                $editDirectly = [];
            }

            $message = ReportUtils::buildMessage([
                "category" => "efficiency",
                "type" => "imageSize",
                "summary" => sprintf(
                    LocaleUtils::getString("efficiency:imageTooLarge"),
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
                  "subContentId" => $contentFile->getParent()->getAttribute("id")
                ],
                "level" => "caution",
                "subContentid" => $contentFile->getParent()->getAttribute("id"),
                "editDirectly" => $editDirectly
            ]);
            $contentFile->getParent()->addReportMessage($message);
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
