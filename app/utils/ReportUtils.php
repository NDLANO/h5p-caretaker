<?php

/**
 * Proof of concept code for extracting and displaying H5P content server-side.
 *
 * PHP version 8
 *
 * @category Tool
 * @package  H5PCaretaker
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */

namespace Ndlano\H5PCaretaker;

/**
 * Class for handling CSS.
 *
 * @category File
 * @package  H5PCaretaker
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */
class ReportUtils
{
    /**
     * Build a message for a report.
     *
     * @param array $params The parameters for the message.
     *
     * @return array The message.
     */
    public static function buildMessage($params)
    {
        $category = $params["category"];
        $type = $params["type"];
        $summary = $params["summary"];
        $description = $params["description"] ?? null;
        $details = $params["details"] ?? null;
        $recommendation = $params["recommendation"] ?? null;
        $level = $params["level"] ?? "error";
        $subContentId = $params["subContentId"] ?? null;
        $editDirectly = $params["editDirectly"] ?? null;

        if (is_array($summary)) {
            $summary = implode(" ", $summary);
        }

        if (is_array($description)) {
            $description = implode(" ", $description);
        }

        if (is_array($recommendation)) {
            $recommendation = implode(" ", $recommendation);
        }

        $message = [
            "category" => $category,
            "type" => $type,
            "summary" => $summary,
        ];

        if ($description !== null) {
            $message["description"] = $description;
        }

        if ($recommendation !== null) {
            $message["recommendation"] = $recommendation;
        }

        if ($level !== null) {
            $message["level"] = $level;
        }
        if ($subContentId !== null) {
            $message["subContentId"] = $subContentId;
        }

        if ($details !== null && is_array($details)) {
            $details = array_filter(
                $details,
                function ($value) {
                    return ($value !== null && $value !== "");
                }
            );

            $message["details"] = $details;
        }

        if ($editDirectly !== null) {
            $message["editDirectly"] = $editDirectly;
        }

        return $message;
    }

    /**
     * Build a path to the metadata object.
     *
     * @param string $semanticsPath The path to the semantics file.
     * @param string $fieldName The name of the field.
     *
     * @return string The path to the metadata file or empty string if invalid.
     */
    public static function buildMetadataPath($semanticsPath, $fieldName, $path = "")
    {
        if (
            !isset($semanticsPath) || !isset($fieldName)
        ) {
            return "";
        }

        $isMediaFile = ($path !== "" && $path !== null);

        // TODO: This might be a little risky
        // .params alone is not enough to ensure that we're dealing with library content
        if (str_ends_with($semanticsPath, ".params")) {
            if ($isMediaFile) {
                return $semanticsPath . "." . $fieldName;
            }

            // Content library
            return preg_replace("/\.params$/", ".metadata." . $fieldName, $semanticsPath);
        } elseif ($semanticsPath === "") {
            // Main content
            return "root." . $fieldName;
        } elseif ($isMediaFile) {
            // Media file
            if ($fieldName === "licenseVersion") {
                $fieldName = "version";
            }

            return $semanticsPath . ".copyright." . $fieldName;
        } else {
            return "";
        }
    }
}
