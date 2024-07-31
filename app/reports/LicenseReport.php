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
            $report["messages"] = array_merge(
                $report["messages"],
                self::checkLicense($content),
                self::checkLicenseAdaptation($content)
            );

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

                $report["messages"] = array_merge(
                    $report["messages"],
                    self::checkLicense($content)
                );
            }

            $content->setReport("license", $report);
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent.
     *
     * @param Content $content The content to check.
     *
     * @return array The messages.
     */
    private static function checkLicenseAdaptation($content)
    {
        $messages = [];

        $license = $content->getAttribute("metadata")["license"] ?? "";
        if ($license === "U") {
            return []; // License should be set first anyway.
        }

        $children = $content->getChildren();
        foreach ($children as $child) {
            foreach (
                [
                    self::checkLicenseAdaptationNC($content, $child),
                    self::checkLicenseAdaptationND($content, $child),
                    self::checkLicenseAdaptationBY($content, $child),
                    self::checkLicenseAdaptationVersion($content, $child)
                ] as $result
            ) {
                if (isset($result)) {
                    $messages[] = $result;
                }
            }
        }

        $machineName = $content->getDescription("{machineName}");
        if (
            !in_array($machineName, [
                "H5P.Image",
                "H5P.Audio",
                "H5P.Video",
            ])
        ) {
            $contentFiles = $content->getAttribute("contentFiles");
            foreach ($contentFiles as $contentFile) {
                foreach (
                    [
                        self::checkLicenseAdaptationNC($content, $contentFile),
                        self::checkLicenseAdaptationND($content, $child),
                        self::checkLicenseAdaptationBY($content, $contentFile),
                        self::checkLicenseAdaptationVersion($content, $child)
                    ] as $result
                ) {
                    if (isset($result)) {
                        $messages[] = $result;
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Check if the license of a content is adapted to its subcontent for commercial use.
     *
     * @param Content $content The content to check.
     * @param Content|ContentFile $subcontent The subcontent or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkLicenseAdaptationNC($content, $subcontent)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $subcontentLicense = $subcontent->getAttribute("metadata")["license"] ?? "";
        if ($subcontentLicense === "U") {
            return; // License should be set first anyway.
        }

        if (
            strpos($license, "CC BY") !== 0 ||
            strpos($subcontentLicense, "CC BY") !== 0
        ) {
            return; // Only relevant for CC BY licenses
        }

        if (
            strpos($subcontentLicense, "NC") !== false &&
            strpos($license, "NC") === false
        ) {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "invalidLicenseAdaptation",
                "summary" => sprintf(
                    _("Invalid license adaptation for %s"),
                    $content->getDescription()
                ),
                "description" => sprintf(
                    _("Subcontent %s does not allow commercial use, but parent content %s does."),
                    $subcontent->getDescription(),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                    "title" => $subcontent->getDescription("{title}"),
                    "subContentId" => $subcontent->getAttribute("id"),
                    // phpcs:ignore
                    "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                ],
                // phpcs:ignore
                "recommendation" => _("Ensure that the license of the subcontent is compatible with the license of the parent content.")
            ]);
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent for ND.
     *
     * @param Content $content The content to check.
     * @param Content|ContentFile $subcontent The subcontent or content file to check.
     *
     * @return array The messages.
     */
    private static function checkLicenseAdaptationND($content, $subcontent)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $subcontentLicense = $subcontent->getAttribute("metadata")["license"] ?? "";
        if ($subcontentLicense === "U") {
            return; // License should be set first anyway.
        }

        if (
            strpos($license, "CC BY") !== 0 ||
            strpos($subcontentLicense, "CC BY") !== 0
        ) {
            return; // Only relevant for CC BY licenses
        }

        if (
            strpos($subcontentLicense, "ND") !== false &&
            strpos($license, "ND") === false
        ) {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "invalidLicenseAdaptation",
                "summary" => sprintf(
                    _("Probably invalid license adaptation for %s"),
                    $content->getDescription()
                ),
                "description" => [
                    sprintf(
                        _("Subcontent %s does not allow derivates, but parent %s uses it."),
                        $subcontent->getDescription(),
                        $content->getDescription()
                    ),
                    _("This is not allowed for remixes, but only for collections.")
                ],
                "details" => [
                    "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                    "title" => $subcontent->getDescription("{title}"),
                    "subContentId" => $subcontent->getAttribute("id"),
                    // phpcs:ignore
                    "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                ],
                // phpcs:ignore
                "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
            ]);
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent for BY.
     *
     * @param Content $content The content to check.
     * @param Content|ContentFile $subcontent The subcontent or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkLicenseAdaptationBY($content, $subcontent)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $subcontentLicense = $subcontent->getAttribute("metadata")["license"] ?? "";
        if ($subcontentLicense === "U") {
            return; // License should be set first anyway.
        }

        if (
            $subcontentLicense === "CC BY" &&
            in_array($license, ["CC0 1.0", "PD", "CC PDM", "ODC PDDL"])
        ) {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "discouragedLicenseAdaptation",
                "summary" => sprintf(
                    _("Discouraged license adaptation for %s"),
                    $content->getDescription()
                ),
                "description" => sprintf(
                    _("Subcontent %s is licensed under a CC BY license, but content is more openly licensed."),
                    $subcontent->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                    "title" => $subcontent->getDescription("{title}"),
                    "subContentId" => $subcontent->getAttribute("id"),
                    // phpcs:ignore
                    "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                ],
                // phpcs:ignore
                "recommendation" => _("Ensure that the license of the subcontent is compatible with the license of the parent content.")
            ]);
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent for version.
     *
     * @param Content $content The content to check.
     * @param Content|ContentFile $subcontent The subcontent or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkLicenseAdaptationVersion($content, $subcontent)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $licenseVersion =
            $content->getAttribute("metadata")["licenseVersion"] ?? "";
        $subcontentLicense = $subcontent->getAttribute("metadata")["license"] ?? "";
        $subcontentLicenseVersion =
            $subcontent->getAttribute("metadata")["licenseVersion"] ?? "";

        if ($subcontentLicense === "U") {
            return; // License should be set first anyway.
        }

        if (
            strpos($subcontentLicense, "CC BY") !== 0 ||
            $subcontentLicenseVersion === "" ||
            $licenseVersion === ""
        ) {
            return; // Version should be set anyway
        }

        $validVersions = [
            "2.0" => ["2.0", "2.5", "3.0", "4.0"],
            "2.5" => ["2.5", "3.0", "4.0"],
            "3.0" => ["3.0", "4.0"],
            "4.0" => ["4.0"]
        ];

        if ($subcontentLicense === "CC BY-SA") {
            if ($subcontentLicenseVersion !== "4.0" && $license === "GNU GPL") {
                return ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        _("Probably invalid license adaptation for %s"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            // phpcs:ignore
                            _("Subcontent %s is licensed under a CC BY-SA %s license, but content is licensed under a GNU GPL license."),
                            $subcontent->getDescription(),
                            $subcontentLicenseVersion
                        ),
                        _("This is not allowed for remixes, but only for collections.")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    // phpcs:ignore
                    "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
                ]);
            } elseif ($license !== "CC BY-SA") {
                return ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        _("Probably invalid license adaptation for %s"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            // phpcs:ignore
                            _("Subcontent %s is licensed under a CC BY-SA license, but content %s is not licensed under a CC BY-SA license."),
                            $subcontent->getDescription(),
                            $content->getDescription()
                        ),
                        _("This is not allowed for remixes, but only for collections.")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    // phpcs:ignore
                    "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
                ]);
            } elseif (
                $subcontentLicenseVersion === "1.0" &&
                $licenseVersion !== "1.0"
            ) {
                return ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        _("Probably invalid license adaptation for %s"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            _("Subcontent %s is licensed under a CC BY-SA 1.0 license, but parent content is not."),
                            $subcontent->getDescription()
                        ),
                        _("This is not allowed for remixes, but only for collections.")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    // phpcs:ignore
                    "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
                ]);
            } elseif (
                isset($validVersions[$subcontentLicenseVersion]) &&
                !in_array($licenseVersion, $validVersions[$subcontentLicenseVersion])
            ) {
                return ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        _("Probably invalid license adaptation for %s"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            // phpcs:ignore
                            _("Subcontent %s is licensed under a CC BY-SA %s license, but content %s uses version %s instead of the same license version or later version."),
                            $subcontent->getDescription(),
                            $subcontentLicenseVersion,
                            $content->getDescription(),
                            $licenseVersion
                        ),
                        _("This is not allowed for remixes, but only for collections.")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    // phpcs:ignore
                    "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
                ]);
            }
        } elseif ($subcontentLicense === "CC BY-NC-SA") {
            if ($license !== "CC BY-NC-SA") {
                return ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        _("Probably invalid license adaptation for %s"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            // phpcs:ignore
                            _("Subcontent %s is licensed under a CC BY-NC-SA license, but content %s is not licensed under a CC BY-NC-SA license."),
                            $subcontent->getDescription(),
                            $content->getDescription()
                        ),
                        _("This is not allowed for remixes, but only for collections.")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    // phpcs:ignore
                    "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
                ]);
            } elseif (
                $subcontentLicenseVersion === "1.0" &&
                $licenseVersion !== "1.0"
            ) {
                return ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        _("Probably invalid license adaptation for %s"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            // phpcs:ignore
                            _("Subcontent %s is licensed under a CC BY-NC-SA 1.0 license, but parent content is not."),
                            $subcontent->getDescription()
                        ),
                        _("This is not allowed for remixes, but only for collections.")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    // phpcs:ignore
                    "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
                ]);
            } elseif (
                isset($validVersions[$subcontentLicenseVersion]) &&
                !in_array($licenseVersion, $validVersions[$subcontentLicenseVersion])
            ) {
                return ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        _("Probably invalid license adaptation for %s"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            // phpcs:ignore
                            _("Subcontent %s is licensed under a CC BY-NC-SA %s license, but content %s uses version %s instead of the same license version or later version."),
                            $subcontent->getDescription(),
                            $subcontentLicenseVersion,
                            $content->getDescription(),
                            $licenseVersion
                        ),
                        _("This is not allowed for remixes, but only for collections.")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    // phpcs:ignore
                    "recommendation" => _("Ensure that your work is legally a collection or ensure that the license of the subcontent is compatible with the license of the parent content.")
                ]);
            }
        }
    }

    /**
     * Check the license of a content or content file.
     *
     * @param Content|Contentfile $content The content or contentFile to check.
     *
     * @return array The messages.
     */
    private static function checkLicense($content)
    {
        $messages = [];

        foreach (
            [
            self::checkMissingLicense($content),
            self::checkMissingLicenseVersion($content),
            self::checkMissingAuthor($content),
            self::checkMissingTitle($content),
            self::checkMissingLink($content),
            self::checkMissingChanges($content),
            self::checkMissingLicenseExtras($content)
            ] as $result
        ) {
            if (isset($result)) {
                $messages[] = $result;
            }
        }

        return $messages;
    }

    /**
     * Check if the license is missing.
     *
     * @param Content|ContentFile $content The content or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkMissingLicense($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";

        if ($license !== "U" && $license !== "") {
            return;
        }

        $summary = ($content->getAttribute("semanticsPath") === "") ?
            sprintf(
                _("Missing license information for %s as H5P main content"),
                $content->getDescription()
            ) :
            sprintf(
                _("Missing license information for %s inside %s"),
                $content->getDescription(),
                $content->getParent()->getDescription()
            );

            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "missingLicense",
                "summary" => $summary,
                "details" => [
                    "semanticsPath" => $content->getAttribute("semanticsPath"),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                ],
                "recommendation" => _("Check the license information of the content and add it to the metadata.")
            ]);
    }

    /**
     * Check if the license version is missing.
     * This is only relevant for Creative Commons licenses, but they need a version.
     *
     * @param Content|ContentFile $content The content or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkMissingLicenseVersion($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $licenseVersion =
            $content->getAttribute("metadata")["licenseVersion"] ?? "";

        if (
            strpos($license, "CC BY") !== 0 ||
            $licenseVersion !== ""
        ) {
            return;
        }

        return ReportUtils::buildMessage([
            "category" => "license",
            "type" => "missingLicenseVersion",
            "summary" => sprintf(
                _("Missing license version information for %s"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute("semanticsPath"),
                "title" => $content->getDescription("{title}"),
            ],
            "recommendation" => _("Set the license version in the metadata.")
        ]);
    }

    /**
     * Check if the author is missing.
     * Public Domain licenses do not need an author, but others must or should have at least.
     *
     * @param Content|ContentFile $content The content or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkMissingAuthor($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $authors =
            $content->getAttribute("metadata")["authors"] ?? [];
        if (
            (count($authors) !== 0 && ($authors[0]["name"] ?? "") !== "") ||
            $license === "CC PDM" ||
            $license === "PD"
        ) {
            return;
        }

        $reference = null;
        if (strpos($license, "CC BY") === 0) {
            $shortcode = strtolower(explode(" ", $license)[1]);
            $reference = sprintf(
                "https://creativecommons.org/licenses/%s/%s/#ref-appropriate-credit",
                $shortcode,
                $content->getAttribute("metadata")["licenseVersion"] ?? "4.0"
            );
        } elseif ($license === "GNU GPL") {
            $reference = "https://www.gnu.org/licenses/gpl-3.0.txt";
        }

        return ReportUtils::buildMessage([
            "category" => "license",
            "type" => "missingAuthor",
            "summary" => sprintf(
                _("Missing author information for %s"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute("semanticsPath"),
                "title" => $content->getDescription("{title}"),
                "reference" => $reference
            ],
            "recommendation" => _("Add the author name or creator name in the metadata.")
        ]);
    }

    /**
     * Check if the title is missing.
     * Creative Commons licenses prior to 4.0 do need a title if it's supplied.
     * @see https://creativecommons.org/licenses/by/4.0/#ref-appropriate-credit
     *
     * @param Content|ContentFile $content The content or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkMissingTitle($content)
    {
        $title = $content->getAttribute("metadata")["title"] ?? "";
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $licenseVersion =
            $content->getAttribute("metadata")["licenseVersion"] ?? "";

        if (
            $title !== "" ||
            strpos($license, "CC BY") !== 0 ||
            $licenseVersion === "4.0"
        ) {
            return;
        }

        $reference = null;
        if (strpos($license, "CC BY") === 0) {
            $shortcode = strtolower(explode(" ", $license)[1]);
            $reference = sprintf(
                "https://creativecommons.org/licenses/%s/%s/#ref-appropriate-credit",
                $shortcode,
                $content->getAttribute("metadata")["licenseVersion"] ?? "4.0"
            );
        }

        return  ReportUtils::buildMessage([
            "category" => "license",
            "type" => "missingTitle",
            "summary" => sprintf(
                _("Missing title information for %s"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute(
                    "semanticsPath"
                ),
                "title" => $content->getDescription("{title}"),
                "reference" => $reference
            ],
            "recommendation" => _("Add the title of the content (if supplied) in the metadata.")
        ]);
    }

    /**
     * Check if the source link is missing.
     * Creative Commons licenses in version 4.0 need a source link.
     * Creative Commons licenses prior to 4.0 and above 1.0 need a source link
     * if it contains a copyright notice or licensing information.
     * @see https://wiki.creativecommons.org/wiki/License_Versions#Attribution-specific_elements
     *
     * @param Content|ContentFile $content The content or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkMissingLink($content)
    {
        $link = $content->getAttribute("metadata")["source"] ?? "";
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $licenseVersion =
            $content->getAttribute("metadata")["licenseVersion"] ?? "";

        if (
            $link !== "" ||
            strpos($license, "CC BY") !== 0 ||
            $licenseVersion === "1.0"
        ) {
            return;
        }

        $reference = null;
        if (strpos($license, "CC BY") === 0) {
            $shortcode = strtolower(explode(" ", $license)[1]);
            $reference = sprintf(
                "https://creativecommons.org/licenses/%s/%s/#ref-appropriate-credit",
                $shortcode,
                $content->getAttribute("metadata")["licenseVersion"] ?? "4.0"
            );
        }

        if ($licenseVersion === "4.0") {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "missingSource",
                "summary" => sprintf(
                    _("Missing source information for %s"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "reference" => $reference
                ],
                "recommendation" => _("Add the link to the content in the metadata.")
            ]);
        } else {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "missingSource",
                "summary" => sprintf(
                    _("Potentially missing source information for %s"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "reference" => $reference
                ],
                // phpcs:ignore
                "recommendation" => _("Add the link to the content in the metadata if the link target contains a copyright notice or licensing information."),
                "level" => "warning"
            ]);
        }
    }

    /**
     * Check if the changes are missing.
     * Creative Commons licenses in version 4.0 need to have changes listed.
     * Creative Commons licenses prior to 4.0 need to have changes listed if
     * the content is a derivative.
     * GNU GPL licenses need to have changes listed.
     * @see https://creativecommons.org/licenses/by/4.0/#ref-indicate-changes
     * @see https://www.gnu.org/licenses/gpl-3.0.txt
     *
     * @param Content|ContentFile $content The content or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkMissingChanges($content)
    {
        $changes =
            $content->getAttribute("metadata")["changes"] ?? [];
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $licenseVersion =
            $content->getAttribute("metadata")["licenseVersion"] ?? "";

        if (count($changes) !== 0) {
            return;
        }

        $reference = null;
        if (strpos($license, "CC BY") === 0) {
            $shortcode = strtolower(explode(" ", $license)[1]);
            $reference = sprintf(
                "https://creativecommons.org/licenses/%s/%s/#ref-indicate-changes",
                $shortcode,
                $content->getAttribute("metadata")["licenseVersion"] ?? "4.0"
            );
        } elseif ($license === "GNU GPL") {
            $reference = "https://www.gnu.org/licenses/gpl-3.0.txt";
        }

        if (strpos($license, "CC BY") === 0 && $licenseVersion === "4.0") {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "missingChanges",
                "summary" => sprintf(
                    _("Potentially missing changes information for %s"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "reference" => $reference
                ],
                // phpcs:ignore
                "recommendation" => _("If this is not your work and you made changes to a degree that you created a derivative, you must indicate your changes and all previous modifications in the metadata."),
                "level" => "warning"
            ]);
        }

        if (strpos($license, "CC BY") === 0 && $licenseVersion !== "4.0") {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "missingChanges",
                "summary" => sprintf(
                    _("Potentially missing changes information for %s"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "reference" => $reference
                ],
                // phpcs:ignore
                "recommendation" => _("If this is not your work and you made changes to a degree that you created a derivative, you must indicate your changes and all previous modifications in the metadata."),
                "level" => "warning"
            ]);
        }

        if ($license === "GNU GPL") {
            return ReportUtils::buildMessage([
                "category" => "license",
                "type" => "missingChanges",
                "summary" => sprintf(
                    _("Potentially missing changes information for %s"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "reference" => $reference
                ],
                "recommendation" => _("List any changes you made in the metadata."),
                "level" => "warning"
            ]);
        }
    }

    /**
     * Check if the license extras are missing.
     * GNU GPL licenses need to contain the original GPL license text.
     * @see https://www.gnu.org/licenses/gpl-3.0.txt
     *
     * @param Content|ContentFile $content The content or content file to check.
     *
     * @return array|undefined The message or undefined if OK.
     */
    private static function checkMissingLicenseExtras($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        $licenseExtras =
            $content->getAttribute("metadata")["licenseExtras"] ?? "";

        if ($license !== "GNU GPL" || $licenseExtras !== "") {
            return;
        }

        return ReportUtils::buildMessage([
            "category" => "license",
            "type" => "missingLicenseExtras",
            "summary" => sprintf(
                _("Missing license extras information for %s"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute(
                    "semanticsPath"
                ),
                "title" => $content->getDescription("{title}"),
                "reference" => "https://www.gnu.org/licenses/gpl-3.0.txt"
            ],
            "recommendation" => _("Add the original GPL license text in the \"license extras\" field.")
        ]);
    }
}
