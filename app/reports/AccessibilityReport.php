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
                $report['messages'][] = ReportUtils::buildMessage(
                    'accessibility',
                    'libreText',
                    [
                        'LibreText evaluation for',
                        explode(' ', $content->getAttribute('versionedMachineName'))[0]
                    ],
                    [
                        'type' => $libreText['type'],
                        // Should be added in the libretext API response, "type" is "title" and not unique
                        //'machineName' => $library->libreTextA11y->machineName,
                        'description' => $libreText['description'],
                        'status' => $libreText['status'],
                        'url' => $libreText['url'],
                    ]
                );
            }
        }

        foreach($contents as $content) {
            $contentFiles = $content->getAttribute('contentFiles') ?? [];

            foreach($contentFiles as $contentFile) {
                if ($contentFile->getParent()->getDescription('{machineName}') === 'H5P.Image') {
                    $alt = $contentFile->getAttribute('alt') ?? '';
                    $decorative = $contentFile->getAttribute('decorative') ?? false;

                    if ($alt === '' && $decorative === false) {
                        $report['messages'][] = ReportUtils::buildMessage(
                            'accessibility',
                            'missingAltText',
                            [
                                'Missing alt text for image inside',
                                $content->getDescription('{title}'),
                                'at',
                                $contentFile->getAttribute('path')
                            ],
                            [
                                'path' => $contentFile->getAttribute('path'),
                                'title' => $content->getDescription('{title}'),
                                'subContentId' => $content->getAttribute('id')
                            ],
                            'Check whether there is a reason for the image to not have an alternative text. If so, explicitly set it as decorative in the editor.'
                        );
                    }
                } elseif ($contentFile->getAttribute('type') === 'image') {
                    // TODO: Add exception check for content types with known decorative images, etc.

                    $report['messages'][] = ReportUtils::buildMessage(
                        'accessibility',
                        'missingAltText',
                        [
                            'Potentially missing alt text for image inside',
                            $content->getDescription('{title}')
                        ],
                        [
                            'path' => $contentFile->getAttribute('path'),
                            'title' => $content->getDescription('{title}')
                        ],
                        'Check whether the content type that uses the image offers a custom alternative text field or whether it is not required to have one here.'
                    );
                }
            }
        }

        $content->setReport('accessibility', $report);
    }
}
