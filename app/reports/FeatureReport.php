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
                    _("Content %s seems to support resuming.") :
                    _("Content %s does not seem to support resuming."),
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
                    _("Content %s seems to support xAPI.") :
                    _("Content %s does not seem to support xAPI."),
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
                $summary = _("Content %s seems to support the full H5P question type contract.");
                $description = [
                sprintf(
                    _("Supported functions/variables: %s."),
                    implode(", ", $supported)
                )
                ];
            } elseif (count($supported) !== 0) {
                /// phpcs:ignore Generic.Files.LineLength.TooLong
                $summary = _("Content %s seems to partially support the full H5P question type contract.");
                $description = [
                sprintf(
                    _("Supported functions/variables: %s."),
                    implode(", ", $supported)
                ),
                sprintf(
                    _("Unsupported functions/variables: %s."),
                    implode(", ", $notSupported)
                )
                ];
            } else {
                // phpcs:ignore Generic.Files.LineLength.TooLong
                $summary = _("Content %s does not seem to support the H5P question type contract.");
                $description = [
                sprintf(
                    _("Unsupported functions/variables: %s."),
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
