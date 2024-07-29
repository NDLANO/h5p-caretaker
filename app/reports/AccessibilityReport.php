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
                $report["messages"][] = ReportUtils::buildMessage(
                    "accessibility",
                    "libreText",
                    $summary,
                    [
                        "type" => $libreText["type"],
                        // Should be added in the libretext API response, "type" is "title" and not unique
                        //'machineName' => $library->libreTextA11y->machineName,
                        "description" => $libreText["description"],
                        "status" => $libreText["status"],
                        "url" => $libreText["url"],
                    ]
                );
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
                            $summary = sprintf(
                                _("Missing alt text for image inside %s"),
                                $content->getDescription()
                            );
                            $report["messages"][] = ReportUtils::buildMessage(
                                "accessibility",
                                "missingAltText",
                                $summary,
                                [
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
                                ],
                                $recommendation
                            );
                        }
                    } else {
                        $summary = sprintf(
                            _("Missing alt text for image inside %s"),
                            $content->getDescription()
                        );
                        $recommendation =
                            _("Check whether the content type that uses the image offers a custom alternative text field or whether it is not required to have one here.");
                        $report["messages"][] = ReportUtils::buildMessage(
                            "accessibility",
                            "missingAltText",
                            $summary,
                            [
                                "path" => $contentFile->getAttribute("path"),
                                "semanticsPath" => $contentFile->getAttribute(
                                    "semanticsPath"
                                ),
                                "title" => $contentFile->getDescription(
                                    "{title}"
                                ),
                                "subContentId" => $content->getAttribute("id"),
                            ],
                            $recommendation
                        );
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

        if ($parentMachineName === "H5P.Image") {
            $alt = $content->getAttribute("params")["alt"] ?? "";
            $decorative =
                $content->getAttribute("params")["decorative"] ?? false;

            $title = $content->getDescription("{title}");
            $recommendation = _("Check whether there is a reason for the image to not have an alternative text. If so, explicitly set the image as decorative in the editor.");

            $hasCustomHandling = true;
        } elseif ($parentMachineName === "H5P.MemoryGame") {
            $semanticsPath = $contentFile->getAttribute("semanticsPath");
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
        } elseif ($parentMachineName === "H5P.ImageHotspots") {
            if ($contentFile->getAttribute("semanticsPath") === "image") {
                $decorative = true; // Is background image
                $hasCustomHandling = true;
            }
        }

        return [$alt, $decorative, $title, $recommendation, $hasCustomHandling];
    }
}
