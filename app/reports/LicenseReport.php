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
class LicenseReport
{
    public static $categoryName = "license";
    public static $typeNames = [
        "missingLicense",
        "missingLicenseVersion",
        "missingAuthor",
        "missingTitle",
        "missingLink",
        "missingChanges",
        "missingLicenseExtras",
        "invalidLicenseRemix",
        "invalidLicenseAdaptation",
        "discouragedLicenseAdaptation"
    ];

    /**
     * Get the license report.
     *
     * @param ContentTree $contentTree The content tree.
     * @param array $rawInfo The raw info.
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

            self::checkLicense($content);
            self::checkLicenseAdaptation($content);
            self::checkLicenseRemixing($content);

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

                self::checkLicense($contentFile);
            }
        }
    }

    /**
     * Check if the licenses of a content's subcontents are compatible.
     *
     * @param Content $content The content to check.
     */
    private static function checkLicenseRemixing($content)
    {
        // Combine subcontents and content files
        $children = $content->getChildren();
        $machineName = $content->getDescription("{machineName}");
        if (
            !in_array($machineName, [
                "H5P.Image",
                "H5P.Audio",
                "H5P.Video",
            ])
        ) {
            $children = array_merge(
                $children,
                $content->getAttribute("contentFiles")
            );
        }

        self::checkLicenseRemixND($children);
        self::checkLicenseRemixNCSA($children);
    }

    /**
     * Check if the CC BY-SA license of a subcontent is compatible with sibling contents.
     *
     * @param array $contents The contents to check.
     */
    private static function checkLicenseRemixNCSA($contents)
    {
        if (count($contents) < 2) {
            return [];
        }

        $contentLicenses = array_map(
            function ($content) {
                return [
                    "content" => $content,
                    "license" => $content->getAttribute("metadata")["license"] ?? ""
                ];
            },
            $contents
        );

        $contentLicensedNCorNCSA = array_filter(
            $contentLicenses,
            function ($content) {
                return (
                    $content["license"] === "CC BY-NC" ||
                    $content["license"] === "CC BY-NC-SA"
                );
            }
        );

        $contentLicensedSA = array_filter(
            $contentLicenses,
            function ($content) {
                return $content["license"] === "CC BY-SA";
            }
        );

        foreach ($contentLicensedSA as $sa) {
            foreach ($contentLicensedNCorNCSA as $nc) {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseRemix",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidRemix"),
                        $sa["content"]->getDescription(),
                        $sa["content"]->getParent()->getDescription()
                    ),
                    "description" => sprintf(
                        LocaleUtils::getString("license:remixCollectionOnly"),
                        $sa["content"]->getDescription(),
                        $nc["content"]->getDescription(),
                        $nc["license"]
                    ),
                    "details" => [
                        "semanticsPath" => $sa["content"]->getAttribute("semanticsPath"),
                        "title" => $sa["content"]->getDescription("{title}"),
                        "subContentId" => $sa["content"]->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $sa["content"]->getParent()->getAttribute("id"),
                ]);

                $sa["content"]->getParent()->addReportMessage($message);
            }
        }
    }

    /**
     * Check if the non derivative-license of a subcontent is compatible with a sibling content.
     *
     * @param array $contents The contents to check.
     */
    private static function checkLicenseRemixND($contents)
    {
        if (count($contents) < 2) {
            return [];
        }

        foreach ($contents as $content) {
            $license = $content->getAttribute("metadata")["license"] ?? "";
            if ($license === "U") {
                continue; // License should be set first anyway.
            } elseif (
                strpos($license, "CC BY") === 0 &&
                strpos($license, "ND") !== false
            ) {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseRemix",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidRemix"),
                        $content->getDescription(),
                        $content->getParent()->getDescription()
                    ),
                    "description" => sprintf(
                        LocaleUtils::getString("license:noDerivates"),
                        $content->getDescription(),
                        $content->getParent()->getDescription()
                    ),
                    "details" => [
                        "semanticsPath" => $content->getAttribute("semanticsPath"),
                        "title" => $content->getDescription("{title}"),
                        "subContentId" => $content->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            }
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent.
     *
     * @param Content $content The content to check.
     */
    private static function checkLicenseAdaptation($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        if ($license === "U") {
            return []; // License should be set first anyway.
        }

        // Combine subcontents and content files
        $children = $content->getChildren();
        $machineName = $content->getDescription("{machineName}");
        if (
            !in_array($machineName, [
                "H5P.Image",
                "H5P.Audio",
                "H5P.Video",
            ])
        ) {
            $children = array_merge(
                $children,
                $content->getAttribute("contentFiles")
            );
        }

        foreach ($children as $child) {
            self::checkLicenseAdaptationNC($content, $child);
            self::checkLicenseAdaptationND($content, $child);
            self::checkLicenseAdaptationBY($content, $child);
            self::checkLicenseAdaptationVersion($content, $child);
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent for commercial use.
     *
     * @param Content $content The content to check.
     * @param Content|ContentFile $subcontent The subcontent or content file to check.
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
            $message = ReportUtils::buildMessage([
                "category" => "license",
                "type" => "invalidLicenseAdaptation",
                "summary" => sprintf(
                    LocaleUtils::getString("license:invalidAdaptation"),
                    $content->getDescription()
                ),
                "description" => sprintf(
                    LocaleUtils::getString("license:noCommercialUse"),
                    $subcontent->getDescription(),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                    "title" => $subcontent->getDescription("{title}"),
                    "subContentId" => $subcontent->getAttribute("id"),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                ],
                "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                "subContentId" => $content->getAttribute("id"),
            ]);
            $content->addReportMessage($message);
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent for ND.
     *
     * @param Content $content The content to check.
     * @param Content|ContentFile $subcontent The subcontent or content file to check.
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
            $message = ReportUtils::buildMessage([
                "category" => "license",
                "type" => "invalidLicenseAdaptation",
                "summary" => sprintf(
                    LocaleUtils::getString("license:invalidAdaptation"),
                    $content->getDescription()
                ),
                "description" => [
                    sprintf(
                        LocaleUtils::getString("license:noDerivates"),
                        $subcontent->getDescription()
                    ),
                    LocaleUtils::getString("license:remixCollectionOnly")
                ],
                "details" => [
                    "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                    "title" => $subcontent->getDescription("{title}"),
                    "subContentId" => $subcontent->getAttribute("id"),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                ],
                "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                "subContentId" => $content->getAttribute("id"),
            ]);
            $content->addReportMessage($message);
        }
    }

    /**
     * Check if the license of a content is adapted to its subcontent for BY.
     *
     * @param Content $content The content to check.
     * @param Content|ContentFile $subcontent The subcontent or content file to check.
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
            $message = ReportUtils::buildMessage([
                "category" => "license",
                "type" => "discouragedLicenseAdaptation",
                "summary" => sprintf(
                    LocaleUtils::getString("license:discouragedAdaptation"),
                    $content->getDescription()
                ),
                "description" => sprintf(
                    LocaleUtils::getString("license:moreLicensed"),
                    $subcontent->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                    "title" => $subcontent->getDescription("{title}"),
                    "subContentId" => $subcontent->getAttribute("id"),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    "reference" => "https://creativecommons.org/faq/#can-i-combine-material-under-different-creative-commons-licenses-in-my-work"
                ],
                "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                "subContentId" => $content->getAttribute("id"),
            ]);
            $content->addReportMessage($message);
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
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidAdaptation"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            LocaleUtils::getString("license:invalidVersion"),
                            $subcontent->getDescription()
                        ),
                        LocaleUtils::getString("license:remixCollectionOnly")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            } elseif ($license !== "CC BY-SA") {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidAdaptation"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            LocaleUtils::getString("license:invalidVersion"),
                            $subcontent->getDescription()
                        ),
                        LocaleUtils::getString("license:remixCollectionOnly")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            } elseif (
                $subcontentLicenseVersion === "1.0" &&
                $licenseVersion !== "1.0"
            ) {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidAdaptation"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            LocaleUtils::getString("license:invalidVersion"),
                            $subcontent->getDescription()
                        ),
                        LocaleUtils::getString("license:remixCollectionOnly")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            } elseif (
                isset($validVersions[$subcontentLicenseVersion]) &&
                !in_array($licenseVersion, $validVersions[$subcontentLicenseVersion])
            ) {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidAdaptation"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            LocaleUtils::getString("license:invalidVersion"),
                            $subcontent->getDescription()
                        ),
                        LocaleUtils::getString("license:remixCollectionOnly")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            }
        } elseif ($subcontentLicense === "CC BY-NC-SA") {
            if ($license !== "CC BY-NC-SA") {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidAdaptation"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            LocaleUtils::getString("license:invalidVersion"),
                            $subcontent->getDescription()
                        ),
                        LocaleUtils::getString("license:remixCollectionOnly")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            } elseif (
                $subcontentLicenseVersion === "1.0" &&
                $licenseVersion !== "1.0"
            ) {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidAdaptation"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            LocaleUtils::getString("license:invalidVersion"),
                            $subcontent->getDescription()
                        ),
                        LocaleUtils::getString("license:remixCollectionOnly")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            } elseif (
                isset($validVersions[$subcontentLicenseVersion]) &&
                !in_array($licenseVersion, $validVersions[$subcontentLicenseVersion])
            ) {
                $message = ReportUtils::buildMessage([
                    "category" => "license",
                    "type" => "invalidLicenseAdaptation",
                    "summary" => sprintf(
                        LocaleUtils::getString("license:invalidAdaptation"),
                        $content->getDescription()
                    ),
                    "description" => [
                        sprintf(
                            LocaleUtils::getString("license:invalidVersion"),
                            $subcontent->getDescription()
                        ),
                        LocaleUtils::getString("license:remixCollectionOnly")
                    ],
                    "details" => [
                        "semanticsPath" => $subcontent->getAttribute("semanticsPath"),
                        "title" => $subcontent->getDescription("{title}"),
                        "subContentId" => $subcontent->getAttribute("id"),
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "reference" => "https://creativecommons.org/share-your-work/licensing-considerations/compatible-licenses/"
                    ],
                    "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                    "subContentId" => $content->getAttribute("id"),
                ]);
                $content->addReportMessage($message);
            }
        }
    }

    /**
     * Check the license of a content or content file.
     *
     * @param Content|Contentfile $content The content or contentFile to check.
     */
    private static function checkLicense($content)
    {
        self::checkMissingLicense($content);
        self::checkMissingLicenseVersion($content);
        self::checkMissingAuthor($content);
        self::checkMissingTitle($content);
        self::checkMissingLink($content);
        self::checkMissingChanges($content);
        self::checkMissingLicenseExtras($content);
    }

    /**
     * Determine license message level based on content type.
     * Text content types are technically required to have a license, but it is often impractical.
     *
     * @param Content|ContentFile $content The content or content file to check.
     */
    private static function getLicenseMessageLevelForContent($content)
    {
        $versionedMachineName = $content->getAttribute("versionedMachineName");
        $machineName = explode(" ", $content->getAttribute("versionedMachineName"))[0];
        return ($machineName === "H5P.AdvancedText" || $machineName === "H5P.Text") ? "warning" : "error";
    }

    /**
     * Check if the license is missing.
     *
     * @param Content|ContentFile $content The content or content file to check.
     */
    private static function checkMissingLicense($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";

        if ($license !== "U" && $license !== "") {
            return;
        }

        $summary = ($content->getAttribute("semanticsPath") === "") ?
            sprintf(
                LocaleUtils::getString("license:missingMain"),
                $content->getDescription()
            ) :
            sprintf(
                LocaleUtils::getString("license:missingInside"),
                $content->getDescription(),
                $content->getParent()->getDescription()
            );

            $arguments = [
                "category" => "license",
                "type" => "missingLicense",
                "summary" => $summary,
                "details" => [
                    "semanticsPath" => $content->getAttribute("semanticsPath"),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                ],
                "recommendation" => LocaleUtils::getString("license:checkMaterial"),
                "level" => self::getLicenseMessageLevelForContent($content),
                "subContentId" => $content->getAttribute("id"),
            ];

            $path = $content->getAttribute("path");
            if (isset($path)) {
                $arguments["details"]["path"] = $path;
            }

            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
    }

    /**
     * Check if the license version is missing.
     * This is only relevant for Creative Commons licenses, but they need a version.
     *
     * @param Content|ContentFile $content The content or content file to check.
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

        $arguments = [
            "category" => "license",
            "type" => "missingLicenseVersion",
            "summary" => sprintf(
                LocaleUtils::getString("license:missingVersion"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute("semanticsPath"),
                "title" => $content->getDescription("{title}"),
                "subContentId" => $content->getAttribute("id"),
            ],
            "recommendation" => LocaleUtils::getString("license:setVersion"),
            "subContentId" => $content->getAttribute("id"),
        ];

        $path = $content->getAttribute("path");
        if (isset($path)) {
            $arguments["details"]["path"] = $path;
        }

        $message = ReportUtils::buildMessage($arguments);
        $content->addReportMessage($message);
    }

    /**
     * Check if the author is missing.
     * Public Domain licenses do not need an author, but others must or should have at least.
     *
     * @param Content|ContentFile $content The content or content file to check.
     */
    private static function checkMissingAuthor($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        if ($license === "U") {
            return; // License should be set first anyway.
        }

        $authors = $content->getAttribute("metadata")["authors"] ?? [];
        $hasValidAuthor = !empty($authors) && (!empty($authors[0]["name"]) || !empty($authors["author"]));

        if ($hasValidAuthor || $license === "CC PDM" || $license === "PD") {
            return; // No author needed for public domain licenses
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

        $arguments = [
            "category" => "license",
            "type" => "missingAuthor",
            "summary" => sprintf(
                LocaleUtils::getString("license:missingAuthor"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute("semanticsPath"),
                "title" => $content->getDescription("{title}"),
                "subContentId" => $content->getAttribute("id"),
                "reference" => $reference
            ],
            "recommendation" => LocaleUtils::getString("license:addAuthor"),
            "subContentId" => $content->getAttribute("id"),
        ];

        $path = $content->getAttribute("path");
        if (isset($path)) {
            $arguments["details"]["path"] = $path;
        }

        $message = ReportUtils::buildMessage($arguments);
        $content->addReportMessage($message);
    }

    /**
     * Check if the title is missing.
     * Creative Commons licenses prior to 4.0 do need a title if it's supplied.
     * @see https://creativecommons.org/licenses/by/4.0/#ref-appropriate-credit
     *
     * @param Content|ContentFile $content The content or content file to check.
     */
    private static function checkMissingTitle($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        if ($license === "U") {
            return; // License should be set first anyway.
        }

        $title = $content->getAttribute("metadata")["title"] ?? "";
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

        $arguments = [
            "category" => "license",
            "type" => "missingTitle",
            "summary" => sprintf(
                LocaleUtils::getString("license:missingTitle"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute(
                    "semanticsPath"
                ),
                "title" => $content->getDescription("{title}"),
                "subContentId" => $content->getAttribute("id"),
                "reference" => $reference
            ],
            "recommendation" => LocaleUtils::getString("license:addTitle"),
            "subContentId" => $content->getAttribute("id"),
        ];

        $path = $content->getAttribute("path");
        if (isset($path)) {
            $arguments["details"]["path"] = $path;
        }

        $message = ReportUtils::buildMessage($arguments);
        $content->addReportMessage($message);
    }

    /**
     * Check if the source link is missing.
     * Creative Commons licenses in version 4.0 need a source link.
     * Creative Commons licenses prior to 4.0 and above 1.0 need a source link
     * if it contains a copyright notice or licensing information.
     * @see https://wiki.creativecommons.org/wiki/License_Versions#Attribution-specific_elements
     *
     * @param Content|ContentFile $content The content or content file to check.
     */
    private static function checkMissingLink($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        if ($license === "U") {
            return; // License should be set first anyway.
        }

        $link = $content->getAttribute("metadata")["source"] ?? "";
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
            $arguments = [
                "category" => "license",
                "type" => "missingSource",
                "summary" => sprintf(
                    LocaleUtils::getString("license:missingSource"),
                    $content->getDescription()
                ),
                "description" => LocaleUtils::getString("license:linkIn40"),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => $reference
                ],
                "recommendation" => LocaleUtils::getString("license:addSource"),
                "level" => "warning",
                "subContentId" => $content->getAttribute("id"),
            ];

            $path = $content->getAttribute("path");
            if (isset($path)) {
                $arguments["details"]["path"] = $path;
            }

            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
        } elseif ($licenseVersion !== "1.0") {
            $arguments = [
                "category" => "license",
                "type" => "missingSource",
                "summary" => sprintf(
                    LocaleUtils::getString("license:potentiallyMissingSource"),
                    $content->getDescription()
                ),
                "description" => LocaleUtils::getString("license:linkIn2030"),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => $reference
                ],
                "recommendation" => LocaleUtils::getString("license:addSource"),
                "level" => "warning",
                "subContentId" => $content->getAttribute("id"),
            ];

            $path = $content->getAttribute('path');
            if (isset($path)) {
                $arguments['details']['path'] = $path;
            }

            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
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
     */
    private static function checkMissingChanges($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        if ($license === "U") {
            return; // License should be set first anyway.
        }

        $changes =
            $content->getAttribute("metadata")["changes"] ?? [];
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
            $arguments = [
                "category" => "license",
                "type" => "missingChanges",
                "summary" => sprintf(
                    LocaleUtils::getString("license:missingChanges"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => $reference
                ],
                "recommendation" => LocaleUtils::getString("license:addChanges"),
                "level" => "warning",
                "subContentId" => $content->getAttribute("id"),
            ];

            $path = $content->getAttribute('path');
            if (isset($path)) {
                $arguments['details']['path'] = $path;
            }

            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
        }

        if (strpos($license, "CC BY") === 0 && $licenseVersion !== "4.0") {
            $arguments = [
                "category" => "license",
                "type" => "missingChanges",
                "summary" => sprintf(
                    LocaleUtils::getString("license:missingChanges"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => $reference
                ],
                "recommendation" => LocaleUtils::getString("license:addChanges"),
                "level" => "warning",
                "subContentId" => $content->getAttribute("id"),

            ];

            $path = $content->getAttribute('path');
            if (isset($path)) {
                $arguments['details']['path'] = $path;
            }
            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
        }

        if ($license === "GNU GPL") {
            $arguments = [
                "category" => "license",
                "type" => "missingChanges",
                "summary" => sprintf(
                    LocaleUtils::getString("license:missingChanges"),
                    $content->getDescription()
                ),
                "details" => [
                    "semanticsPath" => $content->getAttribute(
                        "semanticsPath"
                    ),
                    "title" => $content->getDescription("{title}"),
                    "subContentId" => $content->getAttribute("id"),
                    "reference" => $reference
                ],
                "recommendation" => LocaleUtils::getString("license:addChanges"),
                "level" => "warning",
                "subContentId" => $content->getAttribute("id"),
            ];

            $path = $content->getAttribute('path');
            if (isset($path)) {
                $arguments['details']['path'] = $path;
            }

            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
        }
    }

    /**
     * Check if the license extras are missing.
     * GNU GPL licenses need to contain the original GPL license text.
     * @see https://www.gnu.org/licenses/gpl-3.0.txt
     *
     * @param Content|ContentFile $content The content or content file to check.
     */
    private static function checkMissingLicenseExtras($content)
    {
        $license = $content->getAttribute("metadata")["license"] ?? "";
        if ($license === "U") {
            return; // License should be set first anyway.
        }

        $licenseExtras =
            $content->getAttribute("metadata")["licenseExtras"] ?? "";

        if ($license !== "GNU GPL" || $licenseExtras !== "") {
            return;
        }

        $message = ReportUtils::buildMessage([
            "category" => "license",
            "type" => "missingLicenseExtras",
            "summary" => sprintf(
                LocaleUtils::getString("license:missingExtras"),
                $content->getDescription()
            ),
            "details" => [
                "semanticsPath" => $content->getAttribute(
                    "semanticsPath"
                ),
                "title" => $content->getDescription("{title}"),
                "subContentId" => $content->getAttribute("id"),
                "reference" => "https://www.gnu.org/licenses/gpl-3.0.txt"
            ],
            "recommendation" => LocaleUtils::getString("license:addGPLText"),
            "subContentId" => $content->getAttribute("id"),
        ]);
        $content->addReportMessage($message);
    }
}
