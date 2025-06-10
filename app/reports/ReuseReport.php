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
        $licenseExtras = $content->getAttribute("metadata")["licenseExtras"] ?? "";
        $licenseVersion = $content->getAttribute("metadata")["licenseVersion"] ?? "";

        if (!empty($licenseExtras) && $license !== "GNU GPL" && $license !== "U" && $license !== "C") {
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
                "editDirectly" => [
                    "field" => [
                        "uuid" => GeneralUtils::createUUID(),
                        "type" => "textarea",
                        "label" => LocaleUtils::getString("editDirectly"),
                        "initialValue" => $licenseExtras,
                        "semanticsPath" => ReportUtils::buildMetadataPath(
                            $content->getAttribute("semanticsPath"),
                            "licenseExtras",
                            $content->getAttribute("path")
                        )
                    ]
                ]
            ];

            $message = ReportUtils::buildMessage($arguments);
            $content->addReportMessage($message);
        }

        $culturalWorksLicenses = [
            "PD",
            "ODC PDDL",
            "CC0 1.0",
            "CC PDM",
            "CC BY",
            "CC BY-SA",
            "GNU GPL",
        ];

        if (in_array($license, $culturalWorksLicenses)) {
            return; // Is a cultural work
        }

        // TOOD: Introduce a pool of variables to reuse across reports, e.g. this list
        $licenseOptions = [
            [
                "value" => "U",
                "label" => LocaleUtils::getString("license:undisclosed")
            ],
            [
                "value" => "CC BY",
                "label" => LocaleUtils::getString("license:ccBy")
            ],
            [
                "value" => "CC BY-SA",
                "label" => LocaleUtils::getString("license:ccBySa")
            ],
            [
                "value" => "CC BY-ND",
                "label" => LocaleUtils::getString("license:ccByNd")
            ],
            [
                "value" => "CC BY-NC",
                "label" => LocaleUtils::getString("license:ccByNc")
            ],
            [
                "value" => "CC BY-NC-SA",
                "label" => LocaleUtils::getString("license:ccByNcSa")
            ],
            [
                "value" => "CC BY-NC-ND",
                "label" => LocaleUtils::getString("license:ccByNcNd")
            ],
            [
                "value" => "CC0 1.0",
                "label" => LocaleUtils::getString("license:CC0")
            ],
            [
                "value" => "CC PDM",
                "label" => LocaleUtils::getString("license:ccPdm")
            ],
            [
                "value" => "GNU GPL",
                "label" => LocaleUtils::getString("license:gnuGpl")
            ],
            [
                "value" => "PD",
                "label" => LocaleUtils::getString("license:pd")
            ],
            [
                "value" => "ODC PDDL",
                "label" => LocaleUtils::getString("license:odcPddl")
            ],
            [
                "value" => "C",
                "label" => LocaleUtils::getString("license:copyright")
            ]
        ];

        $licenseOptions = array_values(array_filter(
            $licenseOptions,
            function ($option) use ($license, $culturalWorksLicenses) {
                return $option['value'] === $license || in_array($option['value'], $culturalWorksLicenses);
            }
        ));

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
                "editDirectly" => [
                    "field" => [
                        "uuid" => GeneralUtils::createUUID(),
                        "type" => "group",
                        "label" => LocaleUtils::getString("editDirectly"),
                        "fields" => [
                            [
                                "uuid" => GeneralUtils::createUUID(),
                                "type" => "select",
                                "label" => LocaleUtils::getString("license:license"),
                                "initialValue" => $license,
                                "semanticsPath" => ReportUtils::buildMetadataPath(
                                    $content->getAttribute("semanticsPath"),
                                    "license",
                                    $content->getAttribute("path")
                                ),
                                "options" => $licenseOptions,
                            ],
                            [
                                "uuid" => GeneralUtils::createUUID(),
                                "type" => "select",
                                "label" => LocaleUtils::getString("license:licenseVersion"),
                                "initialValue" => $licenseVersion,
                                "semanticsPath" => ReportUtils::buildMetadataPath(
                                    $content->getAttribute("semanticsPath"),
                                    "licenseVersion",
                                    $content->getAttribute("path")
                                ),
                                "options" => [
                                    [
                                        "value" => "",
                                        "label" => "---"
                                    ],
                                    [
                                        "value" => "4.0",
                                        "label" => LocaleUtils::getString("license:version4")
                                    ],
                                    [
                                        "value" => "3.0",
                                        "label" => LocaleUtils::getString("license:version3")
                                    ],
                                    [
                                        "value" => "2.5",
                                        "label" => LocaleUtils::getString("license:version2_5")
                                    ],
                                    [
                                        "value" => "2.0",
                                        "label" => LocaleUtils::getString("license:version2")
                                    ],
                                    [
                                        "value" => "1.0",
                                        "label" => LocaleUtils::getString("license:version1")
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
        ];

        $path = $content->getContentFilePath();
        if (isset($path)) {
            $arguments["details"]["path"] = $path;
        }

        $message = ReportUtils::buildMessage($arguments);
        $content->addReportMessage($message);
    }

  /**
   * Check if the content has author comments.
   *
   * @param Content $content The content to check.
   */
    private static function checkAuthorComments($content)
    {
        $authorComments = $content->getAttribute("metadata")["authorComments"] ?? "";

        if (
            !empty($authorComments) ||
            ($content->getAttribute("path") ?? "" !== "") // Old media widget without author comments
        ) {
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
            "editDirectly" => [
                "field" => [
                    "uuid" => GeneralUtils::createUUID(),
                    "type" => "textarea",
                    "label" => LocaleUtils::getString("editDirectly"),
                    "initialValue" => "",
                    "pattern" => ValidationUtils::getPattern('authorComments') ?? "",
                    "placeholder" => LocaleUtils::getString("sampleAuthorComments"),
                    "semanticsPath" => ReportUtils::buildMetadataPath(
                        $content->getAttribute("semanticsPath"),
                        "authorComments",
                        $content->getAttribute("path")
                    )
                ]
            ]
        ];

        $path = $content->getContentFilePath();
        if (isset($path)) {
            $arguments["details"]["path"] = $path;
        }

        $message = ReportUtils::buildMessage($arguments);
        $content->addReportMessage($message);
    }
}
