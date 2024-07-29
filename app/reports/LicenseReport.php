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
                $summary = ($semanticsPath === "") ?
                    sprintf(
                        _("Missing license information for %s as H5P main content"),
                        $content->getDescription()
                    ) :
                    sprintf(
                        _("Missing license information for %s inside %s"),
                        $content->getDescription(),
                        $content->getParent()->getDescription()
                    );
                $report["messages"][] = ReportUtils::buildMessage(
                    "license",
                    "missingLicense",
                    $summary,
                    [
                        "semanticsPath" => $semanticsPath,
                        "title" => $content->getDescription("{title}"),
                        "subContentId" => $content->getAttribute("id"),
                    ],
                    _(
                        "Check the license information of the content and add it to the metadata."
                    )
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
                    $summary = sprintf(
                        _("Missing license information for %s"),
                        $contentFile->getDescription()
                    );
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingLicense",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        _(
                            "Check the license information of the content and add it to the metadata."
                        )
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
                    $summary = sprintf(
                        _("Missing author information for %s"),
                        $contentFile->getDescription()
                    );
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingAuthor",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        _("Add the author name or creator name in the metadata.")
                    );
                }

                $title = $contentFile->getAttribute("metadata")["title"] ?? "";
                if (
                    $title === "" &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion !== "4.0"
                ) {
                    $summary = sprintf(
                        _("Missing title information for %s"),
                        $contentFile->getDescription()
                    );
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingTitle",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        _("Add the title of the content (if supplied) in the metadata.")
                    );
                }

                $link = $contentFile->getAttribute("metadata")["source"] ?? "";
                if (
                    $link === "" &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion === "4.0"
                ) {
                    $summary = sprintf(
                        _("Missing source information for %s"),
                        $contentFile->getDescription()
                    );
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingSource",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        _("Add the link to the content in the metadata.")
                    );
                }
                if (
                    $link === "" &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion !== "4.0" &&
                    $licenseVersion !== "1.0"
                ) {
                    $summary = sprintf(
                        _("Potentially missing source information for %s"),
                        $contentFile->getDescription()
                    );
                    $recommendation =
                        _("Add the link to the content in the metadata if the link target contains a copyright notice.");
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingSource",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        $recommendation
                    );
                }

                $changes =
                    $contentFile->getAttribute("metadata")["changes"] ?? [];
                if (
                    count($changes) === 0 &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion === "4.0"
                ) {
                    $summary = sprintf(
                        _("Potentially missing changes information for %s"),
                        $contentFile->getDescription()
                    );
                    $recommendation =
                        _("If this is not your work and you made changes, you must indicate your changes and all previous modifications in the metadata.");
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingChanges",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        $recommendation
                    );
                }

                if (
                    count($changes) === 0 &&
                    strpos($license, "CC BY") === 0 &&
                    $licenseVersion !== "4.0"
                ) {
                    $summary = sprintf(
                        _("Potentially missing changes information for %s"),
                        $contentFile->getDescription()
                    );
                    $recommendation =
                        _("If this is not your work and you made changes to a degree that you created a derivative, you must indicate your changes and all previous modifications in the metadata.");
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingChanges",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        $recommendation
                    );
                }

                if (count($changes) === 0 && $license === "GNU GPL") {
                    $summary = sprintf(
                        _("Potentially missing changes information for %s"),
                        $contentFile->getDescription()
                    );
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingChanges",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        _("List any changes you made in the metadata.")
                    );
                }

                if (
                    $license === "GNU GPL" &&
                    ($contentFile->getAttribute("metadata")["licenseExtras"] ??
                        "") ===
                        ""
                ) {
                    $summary = sprintf(
                        _("Missing license extras information for %s"),
                        $contentFile->getDescription()
                    );
                    $report["messages"][] = ReportUtils::buildMessage(
                        "license",
                        "missingLicenseExtras",
                        $summary,
                        [
                            "semanticsPath" => $contentFile->getAttribute(
                                "semanticsPath"
                            ),
                            "title" => $contentFile->getDescription("{title}"),
                        ],
                        _("Add the original GPL license text in the \"license extras\" field.")
                    );
                }
            }

            $content->setReport("license", $report);
        }
    }
}
