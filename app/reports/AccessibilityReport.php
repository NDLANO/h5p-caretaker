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
class AccessibilityReport
{
    public static $categoryName = "accessibility";
    public static $typeNames = ["libreText", "missingAltText"];

    /**
     * Get the license report.
     *
     * @param ContentTree $contentTree The content tree.
     */
    public static function generateReport($contentTree)
    {
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
                LocaleUtils::getString("accessibility:libreTextEvaluation"),
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
                    "licenseNote" => $libreText["licenseNote"]
                ],
                "level" => "info",
                "subContentId" => $content->getAttribute("id") ?? 'fake-' . GeneralUtils::createUUID(),
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
                        $altTextPath,
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
                                    LocaleUtils::getString("accessibility:missingAltText"),
                                    $content->getDescription()
                                ),
                                "details" => [
                                    "path" => $contentFile->getAttribute("path"),
                                    "semanticsPath" => $altTextPath,
                                    "title" => $title,
                                    "subContentId" => $content->getAttribute("id"),
                                    "reference" => "https://www.w3.org/WAI/alt/"
                                ],
                                "recommendation" => $recommendation,
                                "subContentId" => $content->getAttribute("id") ?? 'fake-' . GeneralUtils::createUUID(),
                            ]);

                            $content->addReportMessage($message);
                        }
                    } else {
                        $message = ReportUtils::buildMessage([
                            "category" => "accessibility",
                            "type" => "missingAltText",
                            "summary" => sprintf(
                                LocaleUtils::getString("accessibility:missingAltText"),
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
                            "recommendation" => LocaleUtils::getString("accessibility:setAltTextImage"),
                            "subContentId" => $content->getAttribute("id"),
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
    private static function handleAltForImage($parentMachineName, $content, $contentFile, $contentTree)
    {
        $data = [
            'alt' => "",
            'altTextPath' => "",
            'decorative' => false,
            'title' => "",
            'recommendation' => "",
            'hasCustomHandling' => false
        ];

        $semanticsPath = $contentFile->getAttribute("semanticsPath");

        switch ($parentMachineName) {
            case "H5P.AdventCalendar":
                self::handleAdventCalendar($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.Animator":
                self::handleAnimator($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ARScavenger":
                self::handleARScavenger($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.BranchingQuestion":
                self::handleBranchingQuestion($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.BranchingScenario":
                self::handleBranchingScenario($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.Collage":
                self::handleCollage($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.CoursePresentation":
                self::handleCoursePresentation($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.Dialogcards":
                self::handleDialogCards($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.DragQuestion":
                self::handleDragQuestion($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.Flashcards":
                self::handleFlashcards($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.GameMap":
                self::handleGameMap($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.Image":
                self::handleImage($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ImageHotspots":
                self::handleImageHotspots($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ImageHotspotQuestion":
                self::handleImageHotspotQuestion($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ImageMultipleHotspotQuestion":
                self::handleImageMultipleHotspotQuestion($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ImagePair":
                self::handleImagePair($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ImageSequencing":
                self::handleImageSequencing($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ImpressivePresentation":
                self::handleImpressivePresentation($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.MemoryGame":
                self::handleMemoryGame($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.QuestionSet":
                self::handleQuestionSet($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.SpeakTheWordsSet":
                self::handleSpeakTheWordsSet($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.ThreeImage":
                self::handleThreeImage($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            case "H5P.Timeline":
                self::handleTimeline($contentTree, $content, $contentFile, $semanticsPath, $data);
                break;
            default:
                // Handle default case if needed
                break;
        }

        return array_values($data);
    }

    /**
     * Handle AdventCalendar content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleAdventCalendar($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (
            str_ends_with($semanticsPath, ".previewImage") ||
            str_ends_with($semanticsPath, ".doorCover") ||
            str_ends_with($semanticsPath, "visuals.backgroundImage")
        ) {
            $data["decorative"] = true;
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle Animator content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleAnimator($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, "backgroundImage")) {
            $data["decorative"] = true; // Is background image
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle ARScavenger content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleARScavenger($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, "markerImage")) {
            $data["decorative"] = true;
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle branching question content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleBranchingQuestion($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, ".feedback.image")) {
            $data["decorative"] = true; // Has no alt text option
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle branching scenario content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleBranchingScenario($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (
            str_ends_with($semanticsPath, ".endScreenImage") ||
            str_ends_with($semanticsPath, ".feedback.image")
        ) {
            $data["decorative"] = true; // Has no alt text option
            $data["hasCustomHandling"] = true;
        } elseif (str_ends_with($semanticsPath, ".startScreenImage")) {
            $semanticsPath = preg_replace('/\.startScreenImage$/', "", $semanticsPath);
            $imageParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $data["alt"] = $imageParams["startScreenAltText"] ?? "";
            $data["altTextPath"] = $semanticsPath . "." . "" . "startScreenAltText";

            $data["title"] = $contentFile->getDescription("{title}");
            $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextStartScreen");

            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle Collage content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleCollage($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
        $imageParams = JSONUtils::getElementAtPath(
            $contentTree->getRoot()->getAttribute("params"),
            $semanticsPath
        );

        $data["alt"] = $imageParams["alt"] ?? "";
        $data["altTextPath"] = $semanticsPath . "." . "" . "alt";

        $data["title"] = $contentFile->getDescription("{title}");
        $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextImage");

        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle CoursePresentation content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleCoursePresentation($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
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
            $data["decorative"] = true; // Is background image
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle DialogCards content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleDialogCards($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
        $imageParams = JSONUtils::getElementAtPath(
            $contentTree->getRoot()->getAttribute("params"),
            $semanticsPath
        );

        $data["alt"] = $imageParams["imageAltText"] ?? "";
        $data["altTextPath"] = $semanticsPath . "." . "" . "imageAltText";

        $data["title"] = $contentFile->getDescription("{title}");
        $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextCard");

        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle DragQuestion content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleDragQuestion($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, "question.settings.background")) {
            $data["decorative"] = true; // Is background image
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle Flashcards content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleFlashcards($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
        $imageParams = JSONUtils::getElementAtPath(
            $contentTree->getRoot()->getAttribute("params"),
            $semanticsPath
        );

        $data["alt"] = $imageParams["imageAltText"] ?? "";
        $data["altTextPath"] = $semanticsPath . "." . "" . "imageAltText";

        $data["title"] = $contentFile->getDescription("{title}");
        $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextCard");

        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle GameMap content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleGameMap($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (
            $semanticsPath ===
            "gamemapSteps.backgroundImageSettings.backgroundImage"
        ) {
            $data["decorative"] = true; // Is background image
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle Image content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleImage($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $data["alt"] = $content->getAttribute("params")["alt"] ?? "";
        $data["altTextPath"] = preg_replace('/\.file$/', "", $semanticsPath) . ".alt";
        $data["decorative"] =
            $content->getAttribute("params")["decorative"] ?? false;

        $data["title"] = $content->getDescription("{title}");
        $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextImage");

        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle ImageHotspots content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleImageHotspots($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, "image")) {
            $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
            $imageParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $data["alt"] = $imageParams["backgroundImageAltText"] ?? "";
            $data["altTextPath"] = $semanticsPath . "." . "" . "backgroundImageAltText";

            $data["title"] = $contentFile->getDescription("{title}");
            $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextBackground");

            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle ImageHotspotQuestion content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleImageHotspotQuestion($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, "backgroundImage")) {
            $data["decorative"] = true; // Does not allow entering alt text
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle ImageMultipleHotspotQuestion content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleImageMultipleHotspotQuestion(
        $contentTree,
        $content,
        $contentFile,
        $semanticsPath,
        &$data
    ) {
        if (str_ends_with($semanticsPath, "backgroundImage")) {
            $data["decorative"] = true; // Does not allow entering alt text
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle ImagePair content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleImagePair($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, "image")) {
            $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
            $cardParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $data["alt"] = $cardParams["imageAlt"] ?? "";
            $data["altTextPath"] = $semanticsPath . "." . "imageAlt";

            $data["title"] = $contentFile->getDescription("{title}");
            $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextOriginal");

            $data["hasCustomHandling"] = true;
        } elseif (str_ends_with($semanticsPath, "match")) {
            $semanticsPath = preg_replace('/\.match$/', "", $semanticsPath);
            $cardParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $data["alt"] = $cardParams["matchAlt"] ?? "";
            $data["altTextPath"] = $semanticsPath . "." . "matchAlt";

            $data["title"] = $contentFile->getDescription("{title}");
            $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextMatching");

            $data["hasCustomHandling"] = true;
        } elseif (str_ends_with($semanticsPath, "originalImage")) {
            $data["decorative"] = true; // Old parameter that should have been removed by an upgrades.js script
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle ImageSequencing content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleImageSequencing($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
        $cardParams = JSONUtils::getElementAtPath(
            $contentTree->getRoot()->getAttribute("params"),
            $semanticsPath
        );

        $data["alt"] = $cardParams["imageDescription"] ?? "";
        $data["altTextPath"] = $semanticsPath . "." . "imageDescription";

        $data["title"] = $contentFile->getDescription("{title}");
        $data["recommendation"] = LocaleUtils::getString("accessibility:setDescriptionText");

        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle ImpressivePresentation content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleImpressivePresentation($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $data["decorative"] = true; // Does not allow to enter anything
        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle MemoryGame content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleMemoryGame($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $semanticsPath = preg_replace('/\.image$/', "", $semanticsPath);
        $cardParams = JSONUtils::getElementAtPath(
            $contentTree->getRoot()->getAttribute("params"),
            $semanticsPath
        );

        $data["alt"] = $cardParams["imageAlt"] ?? "";
        $data["altTextPath"] = $semanticsPath . "." . "imageAlt";

        $data["title"] = $contentFile->getDescription("{title}");
        $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextCard");

        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle QuestionSet content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleQuestionSet($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $data["decorative"] = true; // Does not allow to enter anything
        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle SpeakTheWordsSet content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleSpeakTheWordsSet($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        $semanticsPath = preg_replace('/\.introductionImage$/', "", $semanticsPath);
        $introParams = JSONUtils::getElementAtPath(
            $contentTree->getRoot()->getAttribute("params"),
            $semanticsPath
        );

        $data["alt"] = $introParams["introductionImageAltText"] ?? "";
        $data["altTextPath"] = $semanticsPath . "." . "" . "introductionImageAltText";

        $data["title"] = $contentFile->getDescription("{title}");
        $data["recommendation"] = LocaleUtils::getString("accessibility:setAltTextIntro");

        $data["hasCustomHandling"] = true;
    }

    /**
     * Handle ThreeImage content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleThreeImage($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, ".scenesrc")) {
            $data["decorative"] = true; // Does not allow entering alt text
            $data["hasCustomHandling"] = true;
        }
    }

    /**
     * Handle Timeline content type.
     * @param ContentTree $contentTree The content tree.
     * @param Content $content The content.
     * @param ContentFile $contentFile The content file.
     * @param string $semanticsPath The semantics path.
     * @param array $data The data array.
     */
    private static function handleTimeline($contentTree, $content, $contentFile, $semanticsPath, &$data)
    {
        if (str_ends_with($semanticsPath, "backgroundImage")) {
            $data["decorative"] = true; // Does not allow entering alt text
            $data["hasCustomHandling"] = true;
        } elseif (str_ends_with($semanticsPath, "thumbnail")) {
            $semanticsPath = preg_replace('/\.thumbnail$/', "", $semanticsPath);
            $assetParams = JSONUtils::getElementAtPath(
                $contentTree->getRoot()->getAttribute("params"),
                $semanticsPath
            );

            $data["alt"] = $assetParams["caption"] ?? "";
            $data["altTextPath"] = $semanticsPath . "." . "" . "caption";

            $data["title"] = $contentFile->getDescription("{title}");
            $data["recommendation"] = LocaleUtils::getString("accessibility:setCaptionText");

            $data["hasCustomHandling"] = true;
        }
    }
}
