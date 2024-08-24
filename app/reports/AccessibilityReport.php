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
        // TOOD: The semantics path should report the full path to the field, not just the content/file

        $contents = $contentTree->getContents();

        // Get all unique libreText evaluations
        $contentsLibreOffice = array_filter($contents, function ($content) {
            $libreText = $content->getAttribute("libreText");
            return $libreText !== null && $libreText !== "";
        });

        $contentsLibreOffice = array_values(array_reduce($contentsLibreOffice, function ($carry, $content) {
            $type = $content->getAttribute("libreText")["type"];
            if (!isset($carry[$type])) {
                $carry[$type] = $content;
            }
            return $carry;
        }, []));

        foreach ($contentsLibreOffice as $content) {
            $libreText = $content->getAttribute("libreText") ?? "";
            $summary = sprintf(
                _("LibreText evaluation for %s"),
                explode(
                    " ",
                    $content->getAttribute("versionedMachineName")
                )[0]
            );

            $message = ReportUtils::buildMessage([
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

            $content->addReportMessage($message);
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
                            $message = ReportUtils::buildMessage([
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

                            $content->addReportMessage($message);
                        }
                    } else {
                        $message = ReportUtils::buildMessage([
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

                        $content->addReportMessage($message);
                    }
                }
            }
        }
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
        if ($parentMachineName === "H5P.AdventCalendar") {
            if (
                str_ends_with(
                    $semanticsPath,
                    ".previewImage"
                ) ||
                str_ends_with(
                    $semanticsPath,
                    ".doorCover"
                ) ||
                str_ends_with(
                    $semanticsPath,
                    "visuals.backgroundImage"
                )
            ) {
                $decorative = true;
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.ARScavenger") {
            if (str_ends_with($semanticsPath, "markerImage")) {
                $decorative = true;
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.BranchingQuestion") {
            if (str_ends_with($semanticsPath, ".feedback.image")) {
                $decorative = true; // Has no alt text option
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.BranchingScenario") {
            if (
                str_ends_with($semanticsPath, ".endScreenImage") ||
                str_ends_with($semanticsPath, ".feedback.image")
            ) {
                $decorative = true; // Has no alt text option
                $hasCustomHandling = true;
            } elseif (str_ends_with($semanticsPath, ".startScreenImage")) {
                $semanticsPath = preg_replace('/\.startScreenImage$/', "", $semanticsPath);
                $imageParams = JSONUtils::getElementAtPath(
                    $contentTree->getRoot()->getAttribute("params"),
                    $semanticsPath
                );

                $alt = $imageParams["startScreenAltText"] ?? "";

                $title = $contentFile->getDescription("{title}");
                $recommendation =
                    _("Set an alternative text for the start screen image.");

                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.Collage") {
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
                _("Set an alternative text for the image of the card.");

            $hasCustomHandling = true;
        } elseif ($parentMachineName === "H5P.DragQuestion") {
            if (str_ends_with($semanticsPath, "question.settings.background")) {
                $decorative = true; // Is background image
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.Flashcards") {
            $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
            $imageParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $alt = $imageParams["imageAltText"] ?? "";

            $title = $contentFile->getDescription("{title}");
            $recommendation =
                _("Set an alternative text for the image of the card.");

            $hasCustomHandling = true;
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
        } elseif ($parentMachineName === "H5P.ImageHotspotQuestion") {
            if (str_ends_with($semanticsPath, "backgroundImageSettings")) {
                $decorative = true; // Does not allow entering alt text
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.ImageMultipleHotspotQuestion") {
            if (str_ends_with($semanticsPath, "backgroundImage")) {
                $decorative = true; // Does not allow entering alt text
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.ImagePair") {
            if (str_ends_with($semanticsPath, "image")) {
                $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
                $cardParams = JSONUtils::getElementAtPath(
                    $contentTree->getRoot()->getAttribute("params"),
                    $semanticsPath
                );

                $alt = $cardParams["imageAlt"] ?? "";

                $title = $contentFile->getDescription("{title}");
                $recommendation =
                    _("Set an alternative text for the original image.");

                $hasCustomHandling = true;
            } elseif (str_ends_with($semanticsPath, "match")) {
                $semanticsPath = preg_replace('/\.match$/', "", $semanticsPath);
                $cardParams = JSONUtils::getElementAtPath(
                    $contentTree->getRoot()->getAttribute("params"),
                    $semanticsPath
                );

                $alt = $cardParams["matchAlt"] ?? "";

                $title = $contentFile->getDescription("{title}");
                $recommendation =
                    _("Set an alternative text for the matching image.");

                $hasCustomHandling = true;
            } elseif (str_ends_with($semanticsPath, "originalImage")) {
                $decorative = true; // Old parameter that should have been removed by an upgrades.js script
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.ImageSequencing") {
            $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
            $cardParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $alt = $cardParams["imageDescription"] ?? "";

            $title = $contentFile->getDescription("{title}");
            $recommendation =
                _("Set a description text for the image.");

            $hasCustomHandling = true;
        } elseif ($parentMachineName === "H5P.ImpressivePresentation") {
            $decorative = true; // Does not allow to enter anything
            $hasCustomHandling = true;
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
        } elseif ($parentMachineName === "H5P.QuestionSet") {
            $decorative = true; // Does not allow to enter anything
            $hasCustomHandling = true;
        } elseif ($parentMachineName === "H5P.SpeakTheWordsSet") {
            $semanticsPath = preg_replace('/\.introductionImage$/', "", $semanticsPath);
            $introParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $alt = $introParams["introductionImageAltText"] ?? "";

            $title = $contentFile->getDescription("{title}");
            $recommendation =
                _("Set an alternative text for the introduction image.");

            $hasCustomHandling = true;
        } elseif ($parentMachineName === "H5P.ThreeImage") {
            if (str_ends_with($semanticsPath, ".scenesrc")) {
                $decorative = true; // Does not allow entering alt text
                $hasCustomHandling = true;
            }
        } elseif ($parentMachineName === "H5P.Timeline") {
            if (str_ends_with($semanticsPath, "backgroundImage")) {
                $decorative = true; // Does not allow entering alt text
                $hasCustomHandling = true;
            } elseif (str_ends_with($semanticsPath, "thumbnail")) {
                $semanticsPath = preg_replace('/\.thumbnail$/', "", $semanticsPath);
                $assetParams = JSONUtils::getElementAtPath(
                    $contentTree->getRoot()->getAttribute("params"),
                    $semanticsPath
                );

                $alt = $assetParams["caption"] ?? "";

                $title = $contentFile->getDescription("{title}");
                $recommendation =
                    _("Set a caption text for the asset thumbnail image.");

                $hasCustomHandling = true;
            }

            $semanticsPath = preg_replace('/\.introductionImage$/', "", $semanticsPath);
            $introParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $alt = $introParams["introductionImageAltText"] ?? "";

            $title = $contentFile->getDescription("{title}");
            $recommendation =
                _("Set an alternative text for the introduction image.");

            $hasCustomHandling = true;
        }

        return [$alt, $decorative, $title, $recommendation, $hasCustomHandling];
    }
}
