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
class LicenseReport
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
            $semanticsPath = $content->getAttribute("semanticsPath");

            if (($content->getAttribute("metadata")["license"] ?? "") === "U") {
                $report["messages"][] = ReportUtils::buildMessage(
                    "license",
                    "missingLicense",
                    [
                        "Missing license information for content",
                        $content->getDescription(),
                        $semanticsPath === ""
                            ? "as H5P main content"
                            : "inside " .
                                $content->getParent()->getDescription(),
                    ],
                    [
                        "semanticsPath" => $semanticsPath,
                        "title" => $content->getDescription("{title}"),
                        "subContentId" => $content->getAttribute("id"),
                    ],
                    "Check the license information of the content and add it to the metadata."
                );
            }

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

                if (
                    ($contentFile->getAttribute("metadata")["license"] ??
                        "") ===
                    "U"
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingLicense",
                        [
                            "Missing license information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "Check the license information of the content and add it to the metadata."
                    );
                }
            }

            $content->setReport("license", $report);
        }
    }
}
