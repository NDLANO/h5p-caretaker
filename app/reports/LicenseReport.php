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

                $license =
                    $contentFile->getAttribute("metadata")["license"] ?? "";

                $licenseVersion =
                    $contentFile->getAttribute("metadata")["licenseVersion"] ??
                    "";

                if ($license === "U") {
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
                $authors =
                    $contentFile->getAttribute("metadata")["authors"] ?? [];
                if (
                    (count($authors) === 0 ||
                        ($authors["name"] ?? "") === "") &&
                    $license !== "CC PDM" &&
                    $license !== "PD"
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingAuthor",
                        [
                            "Missing author information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "Add the author nameor creator name in the metadata."
                    );
                }

                $title = $contentFile->getAttribute("metadata")["title"] ?? "";
                if (
                    $title === "" &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion !== "4.0"
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingTitle",
                        [
                            "Missing title information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "Add the title of the content (if supplied) in the metadata."
                    );
                }

                $link = $contentFile->getAttribute("metadata")["source"] ?? "";
                if (
                    $link === "" &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion === "4.0"
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingSource",
                        [
                            "Missing source information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "Add the link to the content in the metadata."
                    );
                }
                if (
                    $link === "" &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion !== "4.0" &&
                    $licenseVersion !== "1.0"
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingSource",
                        [
                            "Potentially missing source information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "Add the link to the content in the metadata if the link target " .
                            "contains a copyright notice or licensing information."
                    );
                }

                $changes =
                    $contentFile->getAttribute("metadata")["changes"] ?? [];
                if (
                    count($changes) === 0 &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion === "4.0"
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingChanges",
                        [
                            "Potentially missing changes information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "If this is not your work and you made changes, " .
                        "you must indicate your changes and all previous modifications in the metadata."
                    );
                }

                if (
                    count($changes) === 0 &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion !== "4.0"
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingChanges",
                        [
                            "Potentially missing changes information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "If this is not your work and you made changes to a degree that you created a derivative" .
                            "you must indicate your changes and all previous modifications in the metadata."
                    );
                }

                if (count($changes) === 0 && $license === "GNU GPL") {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingChanges",
                        [
                            "Potentially missing changes information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "List any changes you made in the metadata."
                    );
                }

                if (
                    $license === "GNU GPL" &&
                    ($contentFile->getAttribute("metadata")["licenseExtras"] ??
                        "") ===
                        ""
                ) {
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingLicenseExtras",
                        [
                            "Missing license extras information for",
                            $contentFile->getDescription(),
                        ],
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        "Add the original GPL license text in the \"license extras\" field."
                    );
                }
            }

            $content->setReport("license", $report);
        }
    }
}
