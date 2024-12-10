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
  public static $typeNames = [
    "notCulturalWork", "noAuthorComments", "hasLicenseExtras"
  ];

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
      // TODO: This information should be fetched from library.json of the content type!
      $contentTypesWithoutMetadata = [
          "H5P.GoToQuestion",
          "H5P.IVHotspot",
          "H5P.Link",
          "H5P.Shape",
          "H5P.Text",
          "H5P.TextInputField"
      ];
      $machineName = explode(" ", $content->getAttribute("versionedMachineName"))[0];
      if (in_array($machineName, $contentTypesWithoutMetadata)) {
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

          self::checkReuse($content);
      }
    }
  }

  /**
   * Check reuse.
   *
   * @param Content $content The content to check.
   */
  private static function checkReuse ($content)
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
          _("License of %s is not approved for free cultural works."),
          $content->getDescription()
      ),
      "details" => [
          "semanticsPath" => $content->getAttribute("semanticsPath"),
          "title" => $content->getDescription("{title}"),
          "subContentId" => $content->getAttribute("id"),
          "reference" => "https://creativecommons.org/public-domain/freeworks/",
      ],
      "recommendation" => _("Think about using a license that is approved for free cultural works if this is your work, or think about reaching out to the original author and ask whether this work could be released under a license that is approved for free cultural works."),
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
            _("License of %s contains additional information."),
            $content->getDescription()
        ),
        "details" => [
            "semanticsPath" => $content->getAttribute("semanticsPath"),
            "title" => $content->getDescription("{title}"),
            "subContentId" => $content->getAttribute("id"),
            "licenseExtras" => $licenseExtras,
        ],
        "recommendation" => _("The license of this content contains additional information, potentially amending the reuse terms. Check if it makes it more suitable for reuse than what the original license states."),
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
          _("Content %s does not provide author comments."),
          $content->getDescription()
      ),
      "details" => [
          "semanticsPath" => $content->getAttribute("semanticsPath"),
          "title" => $content->getDescription("{title}"),
          "subContentId" => $content->getAttribute("id"),
      ],
      "recommendation" => _("Think about adding author comments to the metadata in order to describe the context and use case of your resource to give others a better understanding of how you use it."),
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
