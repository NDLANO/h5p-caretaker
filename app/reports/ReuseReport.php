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
class ReuseReport
{
    public static $categoryName = "reuse";
    public static $typeNames = ["notCulturalWork", "noAuthorComments", "hasLicenseExtras"];

  /**
   * Get the reuse report.
   *
   * @param ContentTree $contentTree The content tree.
   * @param array       $rawInfo     The raw info.
   */
    public static function generateReport($contentTree, $rawInfo)
    {
        $contents = $contentTree->getContents();

        foreach ($contents as $content) {
            $machineName = explode(" ", $content->getAttribute("versionedMachineName"))[0];
            $libraryJson = $content->getAttribute("libraryJson");

            $contentTypeHasMetadata =
                !isset($libraryJson['metadataSettings']) ||
                ($libraryJson['metadataSettings']['disable'] ?? 0) !== 1;

            if (!$contentTypeHasMetadata) {
                continue; // No metadata for content
            }

            self::checkReuse($content);

            $contentFiles = $content->getAttribute("contentFiles");

            foreach ($contentFiles as $contentFile) {
                $parentMachineName = $contentFile
                ->getParent()
                ->getDescription("{machineName}");
                if (
                    in_array($parentMachineName, [
                    "H5P.Image",
                    "H5P.Audio",
                    "H5P.Video",
                    ])
                ) {
                    continue; // Already handled by content type specific reports
                }

                self::checkReuse($contentFile);
            }
        }
    }

  /**
   * Check reuse.
   *
   * @param Content $content The content to check.
   */
    private static function checkReuse($content)
    {
        self::checkCulturalWorks($content);
        self::checkAuthorComments($content);
    }

  /**
   * Check if the content is a cultural work.
   *
   * @param Content $content The content to check.
   */
    private static function checkCulturalWorks($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";

        if (
            $license === "PD" || $license === "ODC PDDL" ||
            $license === "CC0 1.0" || $license === "CC PDM" ||
            $license === "CC BY" || $license === "CC BY-SA" ||
            $license === "GNU GPL"
        ) {
            return; // Is a cultural work
        }

        $arguments = [
        "category" => "reuse",
        "type" => "notCulturalWork",
        "summary" => sprintf(
            LocaleUtils::getString("reuse:licenseNotApproved"),
            $content->getDescription()
        ),
        "details" => [
          "semanticsPath" => $content->getAttribute("semanticsPath"),
          "title" => $content->getDescription("{title}"),
          "subContentId" => $content->getAttribute("id"),
          "reference" => "https://creativecommons.org/public-domain/freeworks/",
        ],
        "recommendation" => LocaleUtils::getString("reuse:licenseNotApprovedRecommendation"),
        "level" => "info",
        "subContentId" => $content->getAttribute("id"),
        ];

        $path = $content->getAttribute("path");
        if (isset($path)) {
            $arguments["details"]["path"] = $path;
        }

        $message = ReportUtils::buildMessage($arguments);
        $content->addReportMessage($message);

        $licenseExtras = $content->getAttribute("metadata")["licenseExtras"] ?? "";
        if (!empty($licenseExtras)) {
            $arguments = [
            "category" => "reuse",
            "type" => "hasLicenseExtras",
            "summary" => sprintf(
                LocaleUtils::getString("reuse:licenseHasAdditionalInfo"),
                $content->getDescription()
            ),
            "details" => [
              "semanticsPath" => $content->getAttribute("semanticsPath"),
              "title" => $content->getDescription("{title}"),
              "subContentId" => $content->getAttribute("id"),
              "licenseExtras" => $licenseExtras,
            ],
            "recommendation" => LocaleUtils::getString("reuse:licenseHasAdditionalInfoRecommendation"),
            "level" => "info",
            "subContentId" => $content->getAttribute("id"),
            ];

            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
        }
    }

  /**
   * Check if the content has author comments.
   *
   * @param Content $content The content to check.
   */
    private static function checkAuthorComments($content)
    {
        $authorComments = $content->getAttribute("metadata")["authorComments"] ?? "";

        if (!empty($authorComments)) {
            return;
        }

        $arguments = [
        "category" => "reuse",
        "type" => "noAuthorComments",
        "summary" => sprintf(
            LocaleUtils::getString("reuse:noAuthorComments"),
            $content->getDescription()
        ),
        "details" => [
          "semanticsPath" => $content->getAttribute("semanticsPath"),
          "title" => $content->getDescription("{title}"),
          "subContentId" => $content->getAttribute("id"),
        ],
        "recommendation" => LocaleUtils::getString("reuse:noAuthorCommentsRecommendation"),
        "level" => "info",
        "subContentId" => $content->getAttribute("id"),
        ];

        $path = $content->getAttribute("path");
        if (isset($path)) {
            $arguments["details"]["path"] = $path;
        }

        $message = ReportUtils::buildMessage($arguments);
        $content->addReportMessage($message);
    }
}
