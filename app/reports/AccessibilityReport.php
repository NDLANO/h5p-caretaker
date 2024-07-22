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
     * Get the license report.
     *
     * @param ContentTree $contentTree The content tree.
     */
    public static function generateReport($contentTree)
    {
        $contents = $contentTree->getContents();

        $report = [];
        $report['messages'] = [];

        foreach($contents as $content) {
            $libreText = $content->getAttribute('libreText') ?? '';
            if ($libreText !== '') {
                $message = [
                    'category' => 'accessibility',
                    'type' => 'libreText',
                    'details' => [
                        'type' => $libreText['type'],
                        // Should be added in the libretext API response, "type" is "title" and not unique
                        //'machineName' => $library->libreTextA11y->machineName,
                        'description' => $libreText['description'],
                        'status' => $libreText['status'],
                        'url' => $libreText['url'],
                    ]
                ];

                $report['messages'][] = $message;
            }
        }

        foreach($contents as $content) {
            $contentFiles = $content->getAttribute('contentFiles') ?? [];

            foreach($contentFiles as $contentFile) {
                // TODO: Use 'versionedMachineName' if set to distingiush between different contents

                if ($contentFile->getData()['type'] === 'image') {
                    $alt = $contentFile->getData()['alt'] ?? '';
                    $decorative = $contentFile->getData()['decorative'] ?? false;

                    if ($alt === '' && $decorative === false) {
                        $message = [
                            'category' => 'accessibility',
                            'type' => 'missingAltText',
                            'summary' => 'Missing alt text for image inside' .
                                $content->getAttribute('title') . ' at ' .
                                $contentFile->getData()['path'],
                            'recommendation' =>
                                'Check whether there is a reason for the image to not have an alternative text.' .
                                ' ' .
                                'If not, it is recommended to add one or to declare the image as decorative.',
                            'details' => [
                            'path' => $contentFile->getData()['path'],
                            'title' => $content->getAttribute('title'),
                            'subContentId' => $content->getAttribute('id')
                            ]
                        ];

                        $report['messages'][] = $message;
                    }
                }
            }
        }

        $content->setReport('accessibility', $report);
    }
}
