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
class AccessibilityReport
{
    /**
     * Get the accessibility report.
     *
     * @param array $raw The raw data.
     *
     * @return array The accessibility report.
     */
    public static function getReport($raw)
    {
        $report = [];
        $report = array_merge(
            $report,
            self::getMissingAltText($raw['contentJson'], $raw['media'])
        );
        $report = array_merge($report, self::getLibreText($raw['libraries']));

        return $report;
    }

    /**
     * Get a list of all libraries that have a LibreText accessibility report.
     *
     * @param array $libraries The libraries of the H5P content.
     *
     * @return array A list of all libraries that have a LibreText accessibility report.
     */
    private static function getLibreText($libraries)
    {
        $libraries = array_filter(
            $libraries,
            function ($library) {
                return isset($library->libreTextA11y);
            }
        );

        $messages = [];
        foreach ($libraries as $library) {
            $messages[] = [
            'category' => 'accessibility',
            'type' => 'libreText',
            'details' => [
            'type' => $library->libreTextA11y['type'],
            // Should be added in the libretext API response, "type" is "title" and not unique
            //'machineName' => $library->libreTextA11y->machineName,
            'description' => $library->libreTextA11y['description'],
            'status' => $library->libreTextA11y['status'],
            'url' => $library->libreTextA11y['url'],
            ]
            ];
        }

        return $messages;
    }

    /**
     * Get a list of all images that are missing an alternative text.
     * TODO: Will need to add use of H5P core image widget anc content type with custom alt text field.
     *
     * @param array $contentJson The content.json file of the H5P content.
     * @param array $media The media files of the H5P content.
     *
     * @return array A list of all images that are missing an alternative text.
     */
    private static function getMissingAltText($contentJson, $media)
    {
        // H5P.Image
        $h5pImageContents = JSONUtils::findAttributeValuePairs(
            $contentJson,
            [['library', '/^H5P\.Image/']]
        );

        $missingAltText = array_filter(
            $h5pImageContents,
            function ($item) {
                $params = $item['object']['params'];
                return
                (!isset($params['alt']) || $params['alt'] === '') &&
                $params['decorative'] !== true;
            }
        );

        $messages = [];

        // TODO: i10n
        foreach ($missingAltText as $key) {
            $message = [
                'category' => 'accessibility',
                'type' => 'missingAltText',
                'summary' => 'Missing alt text for image ' .
                $key['object']['metadata']['title'] . ' at ' . $key['path'],
                'recommendation' =>
                    'Check whether there is a reason for the image to not have an alternative text.' .
                    'If not, it is recommended to add one or to declare the image as decorative.',
                'details' => [
                'path' => $key['path'],
                'title' => $key['object']['metadata']['title'],
                'subContentId' => $key['object']['subContentId']
                ]
            ];

            $base64 = null;
            if (isset($key['object']['params']['file']['path'])) {
                $imageFileName = explode(
                    DIRECTORY_SEPARATOR,
                    $key['object']['params']['file']['path']
                )[1];

                $images = is_object($media) ? $media->images : $media['images'];
                foreach ($images as $fileName => $value) {
                    if ($fileName === $imageFileName) {
                        $base64 = $value['base64'];
                        break;
                    }
                }

                if ($base64 !== null) {
                    $message['details']['base64'] = $base64;
                }
            }

            $messages[] = $message;
        }

      // TODO: Would now also need to check internal image widgets and content types with custom alt text field.

        return $messages;
    }
}
