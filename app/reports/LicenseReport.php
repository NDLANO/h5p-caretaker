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
class LicenseReport
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

        // TODO: Simplify this code

        foreach($contents as $content) {
            $semanticsPath = $content->getAttribute('semanticsPath');
            $metadata = $content->getAttribute('metadata') ?? [];

            if (($metadata['license'] ?? '') === 'U') {
                $title = $metadata['title'] ?? '';
                $machineName = explode(' ', $content->getAttribute('versionedMachineName'))[0];
                $pathText = $semanticsPath === '' ?
                    ' as H5P main content' : ' at ' .
                    $semanticsPath;

                $message = [
                    'category' => 'license',
                    'type' => 'missingLicense',
                    'summary' => 'Missing license information for content ' .
                        $title . ' (' . $machineName . ')' . $pathText,
                    'recommendation' =>
                        'Check the license information of the content and add it to the metadata.',
                    'details' => [
                        'path' => $semanticsPath,
                        'title' => $title,
                        'subContentId' => $content->getAttribute('id')
                    ]
                ];

                $report['messages'][] = $message;
            }

            $contentFiles = $content->getAttribute('contentFiles');

            foreach($contentFiles as $contentFile) {
                $data = $contentFile->getData();
                $metadata = $data['metadata'];

                if ($metadata['license'] === 'U') {
                    $title = $metadata['title'] ?? '';
                    $path = $data['semanticsPath'];
                    $pathText = $path === '' ?
                        ' as H5P main content' : ' at ' .
                        $path;

                    $message = [
                        'category' => 'license',
                        'type' => 'missingLicense',
                        'summary' => 'Missing license information for file ' .
                            ($metadata['title'] ?? '') . ' at ' .
                            $data['semanticsPath'],
                        'recommendation' =>
                            'Check the license information of the content and add it to the metadata.',
                        'details' => [
                            'path' => $path,
                            'title' => $title
                        ]
                    ];

                    $report['messages'][] = $message;
                }
            }

            $content->setReport('license', $report);
        }
    }
}
