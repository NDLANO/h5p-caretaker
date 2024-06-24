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
     * @param array $raw The raw data.
     *
     * @return array The license report.
     */
    public static function getReport($raw)
    {
        $report = [];
        $report = array_merge(
            $report,
            self::getMissingLicencesMedia($raw['contentJson'], $raw['media'])
        );

        return $report;
    }

    /**
     * Check if the file type matches the library type.
     *
     * @param string $library The library.
     * @param string $mime    The mime type.
     *
     * @return boolean True if the file type matches the library type.
     */
    private static function isFileTypeMatchLibrary($library, $mime)
    {
        $libraryType = substr($library, 4, 5);
        $fileType = substr($mime, 0, 5);
        return strpos($library, 'H5P.') === 0 &&
            strtolower($libraryType) === strtolower($fileType);
    }

    /**
     * Get a list of all media that have missing license information.
     *
     * @param array $contentJson The content JSON.
     * @param array $media       The media.
     *
     * @return array A list of all media that have missing license information.
     */
    private static function getMissingLicencesMedia($contentJson, $media)
    {
        $messages = [];

        // Find all files
        $fileParams = JSONUtils::findAttributeValuePairs(
            $contentJson,
            [
                ['mime', '/^\w+\.\w+$/'],
                ['path', '/.+/']
            ]
        );

        // Retrieve all media metadata
        $metadata = [];
        foreach ($fileParams as $fileParam) {
            $jsonPath = $fileParam['path'];
            $closestLibraryAndPath = JSONUtils::getClosestLibrary($contentJson, $jsonPath);

            if (isset($closestLibraryAndPath) &&
                    self::isFileTypeMatchLibrary(
                        $closestLibraryAndPath['params']['library'],
                        $fileParam['object']['mime']
                    )
            ) {
                $metadata[] = [
                    'jsonPath' => $jsonPath,
                    'filePath' => $fileParam['object']['path'],
                    'metadata' => $closestLibraryAndPath['params']['metadata']
                ];
            } else {
                $metadata[] = [
                    'jsonPath' => $jsonPath,
                    'filePath' => $fileParam['object']['path'],
                    'metadata' => JSONUtils::copyrightToMetadata($fileParam['object']['copyright'])
                ];
            }
        }

        $missingLicenseInfo = array_filter(
            $metadata,
            function ($item) {
                return $item['metadata']['license'] === 'U';
            }
        );

        foreach ($missingLicenseInfo as $metadata) {
            $message = [
                'category' => 'license',
                'type' => 'missingLicense',
                'summary' => 'Missing license information for file ' .
                    $metadata['metadata']['title'] . ' at ' .
                    $metadata['jsonPath'],
                'recommendation' =>
                    'Check the license information of the file and add it to the metadata of the medium.',
                'details' => [
                    'path' => $metadata['jsonPath'],
                    'title' => $metadata['metadata']['title'] ?? '',
                    'subContentId' => $metadata['metadata']['subContentId'] ?? ''
                ]
            ];

            $base64 = null;
            if (isset($metadata['filePath'])) {
                $imageFileName = explode(
                    DIRECTORY_SEPARATOR,
                    $metadata['filePath']
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

        return $messages;
    }
}
