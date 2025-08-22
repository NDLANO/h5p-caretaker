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
class StatisticsReport
{
    public static $categoryName = "statistics";
    public static $typeNames = ["contentTypeCount"];

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

            $version = implode(".", $content->getVersion());

            if ($machineName === "") {
                continue;
            }

            $contentTypeIndex = $machineName . " (" . $version . ")";

            if (!isset($counts[$contentTypeIndex])) {
                $counts[$contentTypeIndex] = 1;
            } else {
                $counts[$contentTypeIndex]++;
            }
        }

        $messages[] = ReportUtils::buildMessage([
            "category" => "statistics",
            "type" => "contentTypeCount",
            "summary" => LocaleUtils::getString("statistics:contentTypeCount"),
            "details" => $counts,
            "level" => "info",
            "subContentId" => $content->getAttribute("id"),
        ]);

        return $messages;
    }
}
