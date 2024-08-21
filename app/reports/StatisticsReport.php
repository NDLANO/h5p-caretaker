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
class StatisticsReport
{
      /**
     * Get the license report.
     *
     * @param ContentTree $contentTree The content tree.
     * @param array       $rawInfo     The raw info.
     */
    public static function generateReport($contentTree, $rawInfo)
    {
        $contents = $contentTree->getContents();

        $messages = [];

        $counts = [];
        foreach ($contents as $content) {
            $machineName = explode(" ", $content->getAttribute("versionedMachineName") ?? "")[0];

            if ($machineName === "") {
                continue;
            }

            if (!isset($counts[$machineName])) {
                $counts[$machineName] = 1;
            } else {
                $counts[$machineName]++;
            }
        }

        $messages[] = ReportUtils::buildMessage([
            "category" => "statistics",
            "type" => "contentTypeCount",
            "summary" => _("Numbers of content type uses"),
            "details" => $counts,
            "level" => "info"
        ]);

        return $messages;
    }
}
