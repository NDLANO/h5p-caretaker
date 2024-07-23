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
 * @link     https://github.com/ndlano/H5PCaretaker
 */

namespace H5PCaretaker;

/**
 * Class for handling CSS.
 *
 * @category File
 * @package  H5PCaretaker
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/H5PCaretaker
 */
class ReportUtils
{
    /**
     * Build a message for a report.
     *
     * @param string $category The category of the message.
     * @param string $type The type of the message.
     * @param string|array $summary The summary of the message.
     * @param array|null $details The details of the message for further processing.
     * @param string|null $recommendation The optional recommendation for the user.
     *
     * @return array The message.
     */
    public static function buildMessage($category, $type, $summary, $details = null, $recommendation = null)
    {
        if (is_array($summary)) {
            $summary = implode(' ', $summary);
        }

        $message = [
            'category' => $category,
            'type' => $type,
            'summary' => $summary
        ];

        if ($recommendation !== null) {
          $message['recommendation'] = $recommendation;
        }

        if ($details !== null && is_array($details)) {
            $message['details'] = $details;
        }

        return $message;
    }
}
