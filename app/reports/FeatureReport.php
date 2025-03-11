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
class FeatureReport
{
    public static $categoryName = "features";
    public static $typeNames = ["resume", "xAPI", "questionTypeContract"];

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
            $machineName = explode(" ", $content->getAttribute("versionedMachineName") ?? "")[0];

            if ($machineName === "") {
                continue;
            }

            $libraryJson = $content->getAttribute("libraryJson");
            if (empty($libraryJson)) {
                // No libraryJson, so we can't check for features
                $message = ReportUtils::buildMessage([
                    "category" => "features",
                    "type" => "missingLibrary",
                    "summary" => sprintf(
                        LocaleUtils::getString("features:missingLibrary"),
                        $content->getDescription()
                    ),
                    "details" => [
                        "semanticsPath" => $content->getAttribute("semanticsPath"),
                        "title" => $content->getDescription("{title}"),
                        "subContentId" => $content->getAttribute("id")
                    ],
                    "level" => "caution",
                    "subContentId" => $content->getAttribute("id") ?? 'fake-' . GeneralUtils::createUUID(),
                ]);
                $content->addReportMessage($message);
            }


            $features = ($rawInfo["libraries"][$machineName] ?? [])->questionTypeFeatures ?? null;
            if (empty($features)) {
                continue;
            }

            // Resume
            $message = ReportUtils::buildMessage([
                "category" => "features",
                "type" => "resume",
                "summary" => sprintf(
                    ($features["getCurrentState"] ?? false) ?
                    LocaleUtils::getString("features:supportsResume") :
                    LocaleUtils::getString("features:noResume"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute("semanticsPath"),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => "https://h5p.org/documentation/developers/contracts#guides-header-7"
                ],
                "level" => "info",
                "subContentId" => $content->getAttribute("id") ?? 'fake-' . GeneralUtils::createUUID(),
            ]);
            $content->addReportMessage($message);

            // xAPI
            $message = ReportUtils::buildMessage([
                "category" => "features",
                "type" => "xAPI",
                "summary" => sprintf(
                    ($features["getXAPIData"] ?? false) ?
                    LocaleUtils::getString("features:supportsXAPI") :
                    LocaleUtils::getString("features:noXAPI"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute("semanticsPath"),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => "https://h5p.org/documentation/for-authors/analyzing-results-and-answers"
                ],
                "level" => "info",
                "subContentId" => $content->getAttribute("id") ?? 'fake-' . GeneralUtils::createUUID(),
            ]);
            $content->addReportMessage($message);

            // Question type contract
            $supported = [];
            $notSupported = [];

            $questionTypeContractNames = [
                "enableRetry", "enableSolutionsButton", "getAnswerGiven", "getCurrentState",
                "getMaxScore", "getScore", "getXAPIData", "resetTask", "showSolutions"
            ];

            foreach ($features as $key => $value) {
                if (in_array($key, $questionTypeContractNames) && $value === true) {
                    $supported[] = $key;
                } else {
                    $notSupported[] = $key;
                }
            }

            if (count($supported) === count($questionTypeContractNames)) {
                $summary = LocaleUtils::getString("features:supportsQuestionType");
                $description = [
                    sprintf(
                        LocaleUtils::getString("features:supportedFunctions"),
                        implode(", ", $supported)
                    )
                ];
            } elseif (count($supported) !== 0) {
                $summary = LocaleUtils::getString("features:partialQuestionType");
                $description = [
                    sprintf(
                        LocaleUtils::getString("features:supportedFunctions"),
                        implode(", ", $supported)
                    ),
                    sprintf(
                        LocaleUtils::getString("features:unsupportedFunctions"),
                        implode(", ", $notSupported)
                    )
                ];
            } else {
                $summary = LocaleUtils::getString("features:noQuestionType");
                $description = [
                    sprintf(
                        LocaleUtils::getString("features:unsupportedFunctions"),
                        implode(", ", $notSupported)
                    )
                ];
            }

            $message = ReportUtils::buildMessage([
                "category" => "features",
                "type" => "questionTypeContract",
                "summary" => sprintf(
                    $summary,
                    $content->getDescription()
                ),
                "description" => $description,
                "details" => [
                    "semanticsPath" => $content->getAttribute("semanticsPath"),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => "https://h5p.org/documentation/developers/contracts"
                ],
                "level" => "info",
                "subContentId" => $content->getAttribute("id"),
            ]);
            $content->addReportMessage($message);
        }
    }
}
