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
class AccessibilityReport
{
    /**
     * Get the license report.
     *
     * @param ContentTree $contentTree The content tree.
     */
    public static function generateReport($contentTree)
    {
        $contents = $contentTree->getContents();

        $report = [];
        $report["messages"] = [];

        foreach ($contents as $content) {
            $libreText = $content->getAttribute("libreText") ?? "";
            if ($libreText !== "") {
                $summary = sprintf(
                    _("LibreText evaluation for %s"),
                    explode(
                        " ",
                        $content->getAttribute("versionedMachineName")
                    )[0]
                );
                $report["messages"][] = ReportUtils::buildMessage([
                    "category" => "accessibility",
                    "type" => "libreText",
                    "summary" => $summary,
                    "details" => [
                        "type" => $libreText["type"],
                        // Should be added in the libretext API response, "type" is "title" and not unique
                        //'machineName' => $library->libreTextA11y->machineName,
                        "description" => $libreText["description"],
                        "status" => $libreText["status"],
                        "reference" => $libreText["url"],
                    ],
                    "level" => "info"
                ]);
            }
        }

        foreach ($contents as $content) {
            $contentFiles = $content->getAttribute("contentFiles") ?? [];

            foreach ($contentFiles as $contentFile) {
                if ($contentFile->getAttribute("type") === "image") {
                    $parentMachineName = $contentFile
                        ->getParent()
                        ->getDescription("{machineName}");

                    list(
                        $alt,
                        $decorative,
                        $title,
                        $recommendation,
                        $hasCustomHandling,
                    ) = self::handleAltForImage(
                        $parentMachineName,
                        $content,
                        $contentFile,
                        $contentTree
                    );

                    if ($hasCustomHandling) {
                        if ($alt === "" && $decorative === false) {
                            $report["messages"][] = ReportUtils::buildMessage([
                                "category" => "accessibility",
                                "type" => "missingAltText",
                                "summary" => sprintf(
                                    _("Missing alt text for image inside %s"),
                                    $content->getDescription()
                                ),
                                "details" => [
                                    "path" => $contentFile->getAttribute(
                                        "path"
                                    ),
                                    "semanticsPath" => $contentFile->getAttribute(
                                        "semanticsPath"
                                    ),
                                    "title" => $title,
                                    "subContentId" => $content->getAttribute(
                                        "id"
                                    ),
                                    "reference" => "https://www.w3.org/WAI/alt/"
                                ],
                                "recommendation" => $recommendation
                            ]);
                        }
                    } else {
                        $report["messages"][] = ReportUtils::buildMessage([
                            "category" => "accessibility",
                            "type" => "missingAltText",
                            "summary" => sprintf(
                                _("Missing alt text for image inside %s"),
                                $content->getDescription()
                            ),
                            "details" => [
                                "path" => $contentFile->getAttribute("path"),
                                "semanticsPath" => $contentFile->getAttribute(
                                    "semanticsPath"
                                ),
                                "title" => $contentFile->getDescription(
                                    "{title}"
                                ),
                                "subContentId" => $content->getAttribute("id"),
                                "reference" => "https://www.w3.org/WAI/alt/"
                            ],
                            "recommendation" => _(
                                "Check whether the content type that uses the image " .
                                "offers a custom alternative text field or " .
                                "whether it is not required to have one here."
                            )
                        ]);
                    }
                }
            }
        }

        $content->setReport("accessibility", $report);
    }

    /**
     * Handle alternative text for images.
     *
     * @param string $parentMachineName The machine name of the parent content.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param ContentTree $contentTree The content tree.
     *
     * @return array List of variables to work with.
     */
    private static function handleAltForImage(
        $parentMachineName,
        $content,
        $contentFile,
        $contentTree
    ) {
        $alt = "";
        $decorative = false;
        $title = "";
        $recommendation = "";
        $hasCustomHandling = false;

        $semanticsPath = $contentFile->getAttribute("semanticsPath");
        if ($parentMachineName === "H5P.ARScavenger") {
            if (str_ends_with($semanticsPath, "markerImage")) {
                $decorative = true;
                $hasCustomHandling = true;
            }
        }
        else if ($parentMachineName === "H5P.Collage") {
            $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
            $imageParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $alt = $imageParams["alt"] ?? "";

            $title = $contentFile->getDescription("{title}");
            $recommendation =
                _("Set an alternative text for the image.");

            $hasCustomHandling = true;
        } elseif ($parentMachineName === "H5P.CoursePresentation") {
            if (
                str_ends_with(
                    $semanticsPath,
                    "slideBackgroundSelector.imageSlideBackground"
                ) ||
                str_ends_with(
                    $semanticsPath,
                    "globalBackgroundSelector.imageGlobalBackground"
                )
            ) {
                $decorative = true; // Is background image
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.Dialogcards") {
            $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
            $imageParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $alt = $imageParams["imageAltText"] ?? "";

            $title = $contentFile->getDescription("{title}");
            $recommendation =
                _("Set an alternative text for the image.");

            $hasCustomHandling = true;
        }
        elseif ($parentMachineName === "H5P.DragQuestion") {
            if (str_ends_with($semanticsPath, "question.settings.background")) {
                $decorative = true; // Is background image
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.GameMap") {
            if (
                $semanticsPath ===
                    "gamemapSteps.backgroundImageSettings.backgroundImage"
            ) {
                $decorative = true; // Is background image
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.Image") {
            $alt = $content->getAttribute("params")["alt"] ?? "";
            $decorative =
                $content->getAttribute("params")["decorative"] ?? false;

            $title = $content->getDescription("{title}");
            $recommendation = _(
                "Check whether there is a reason for the image to not have an alternative text. " .
                "If so, explicitly set the image as decorative in the editor."
            );

            $hasCustomHandling = true;
        } elseif ($parentMachineName === "H5P.ImageHotspots") {
            if ($semanticsPath === "image") {
                $decorative = true; // Is background image
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.MemoryGame") {
            $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
            $cardParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $alt = $cardParams["imageAlt"] ?? "";

            $title = $contentFile->getDescription("{title}");
            $recommendation =
                _("Set an alternative text for the image of the card.");

            $hasCustomHandling = true;
        }

        return [$alt, $decorative, $title, $recommendation, $hasCustomHandling];
    }
}
