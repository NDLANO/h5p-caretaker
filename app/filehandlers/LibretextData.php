<?php

/**
 * Proof of concept code for extracting and displaying H5P content server-side.
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
 * Class for handling H5P specific stuff.
 *
 * @category File
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/h5p-caretaker
 */
class LibretextData
{
    /**
     * The endpoint template.
     *
     * @var string
     */
    private const ENDPOINT_TEMPLATE = "https://studio.libretexts.org/api/h5p/accessibility?type=%s";

    /**
     * The content URL.
     *
     * @var string
     */
    private const CONTENT_URL = "https://studio.libretexts.org/help/h5p-accessibility-guide";

    /**
     * The license URL.
     *
     * @var string
     */
    private const LICENSE_URL = "https://creativecommons.org/licenses/by/4.0/";

    /**
     * The author URL.
     *
     * @var string
     */
    private const AUTHOR_URL = "https://libretexts.org";

    /**
     * The endpoint ID.
     *
     * @var string
     */
    private const ENDPOINT_ID = "libretext";

    /**
     * The maximum age of cached file in seconds.
     *
     * @var int
     */
    private const MAX_CACHE_AGE_S = 60 * 60 * 24; // 1 day

    /**
     * Fetch the data for a given library title. Will try to fetch from/store to
     * cache if a cache path is provided.
     *
     * @param string $libraryTitle The title of the library to fetch.
     * @param string $cachePath The path to the cache directory.
     *
     * @return array|boolean The fetched data or false if the fetch failed.
     */
    public static function fetch($libraryTitle, $cachePath = false)
    {
        if (empty($libraryTitle)) {
            return false;
        }

        $url = sprintf(
            LibretextData::ENDPOINT_TEMPLATE,
            urlencode($libraryTitle)
        );
        if ($cachePath !== false) {
            if (!is_dir($cachePath)) {
                mkdir($cachePath);
            }

            $cacheFile =
                $cachePath .
                DIRECTORY_SEPARATOR .
                LibretextData::ENDPOINT_ID .
                "-" .
                hash("sha256", $url);

            if (file_exists($cacheFile)) {
                $fileAge = time() - filemtime($cacheFile);
                if ($fileAge > LibretextData::MAX_CACHE_AGE_S) {
                    unlink($cacheFile);
                }
            }
        }

        if (isset($cacheFile) && file_exists($cacheFile)) {
            $response = file_get_contents($cacheFile);
        } else {
            $response = file_get_contents($url);

            if ($response !== false) {
                file_put_contents($cacheFile, $response);
            }
        }

        $result = LibretextData::parseEndpointResult($response);

        if ($result === false) {
            return false;
        }

        // Add license note that LibreText does not share in the API
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $licenseNoteTemplate = _("The H5P Accessibility Guide (%s) is shared under a CC BY 4.0 license (%s) and was authored, remixed, and/or curated by LibreTexts (%s)");
        $licenseNote = sprintf(
            $licenseNoteTemplate,
            self::CONTENT_URL,
            self::LICENSE_URL,
            self::AUTHOR_URL
        );

        $result[0]["licenseNote"] = $licenseNote;

        return $result;
    }

    /**
     * Clear the cache.
     *
     * @param string $cachePath The path to the cache directory.
     */
    public static function clearCache($cachePath)
    {
        if (empty($cachePath) || !is_dir($cachePath)) {
            return false;
        }

        $files = glob(
            $cachePath . DIRECTORY_SEPARATOR . LibretextData::ENDPOINT_ID . "-*"
        );

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Parse the result from the Libretext endpoint.
     *
     * @param string|boolean $endpointResult The result to parse.
     *
     * @return array|boolean The parsed result or false if the result is empty.
     */
    private static function parseEndpointResult($endpointResult)
    {
        if (empty($endpointResult) || $endpointResult === false) {
            return false;
        }

        $result = json_decode($endpointResult, true);
        if ($result === null || count($result) === 0) {
            return false; // No valid JSON or empty result
        }

        return $result;
    }
}
