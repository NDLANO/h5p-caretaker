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
class H5PCaretaker
{
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config The configuration.
     */
    public function __construct($config = [])
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoloader.php';

        if (!isset($config['uploadsPath'])) {
            $config['uploadsPath'] = __DIR__ . DIRECTORY_SEPARATOR . '..' .
                DIRECTORY_SEPARATOR  . 'uploads';
        }

        if (!isset($config['cachePath'])) {
            $config['cachePath'] = __DIR__ . DIRECTORY_SEPARATOR . '..' .
                DIRECTORY_SEPARATOR  . 'cache';
        }

        $this->config = $config;
    }

    /**
     * Done.
     *
     * @param string|null $result The result. Should be null if there is an error.
     * @param string|null $error  The error. Should be null if there is no error.
     *
     * @return array The result or error.
     */
    private function done($result, $error = null)
    {
        if (isset($error)) {
            $result = null;
        } elseif (!isset($result)) {
            $error = 'Something went wrong, but I dunno what, sorry!';
        }

        return [
            'result' => $result,
            'error' => $error
        ];
    }

    /**
     * Analyze the H5P content from the given file.
     *
     * @param array $params The parameters. file (tmp file), format (html, text).
     *
     * @return array The result or error.
     */
    public function analyze($params)
    {
        if (!isset($params['file'])) {
            $this->done(
                null,
                'It seems that no file was provided.'
            );
        }

        $file = $params['file'];

        $fileSize = filesize($file);
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($fileInfo, $file);

        if ($fileSize === 0) {
            return $this->done(null, 'The file is empty.');
        }

        $fileSizeLimit = 1024 * 1024 * 20; // 20 MB
        if ($fileSize > $fileSizeLimit) {
            return $this->done(
                null,
                'The file is larger than the limit of '. $fileSizeLimit .' bytes.'
            );
        }

        if ($fileType !== 'application/zip') {
            return $this->done(
                null,
                'The file is not a valid H5P file / ZIP archive.'
            );
        }

        try {
            $h5pFileHandler = new H5PFileHandler(
                $file,
                $this->config['uploadsPath'],
                $this->config['cachePath']
            );
        } catch (\Exception $error) {
            return $this->done(null, $error->getMessage());
        }

        if (!$h5pFileHandler->isFileOkay()) {
            return $this->done(
                null,
                'The file does not seem to follow the H5P specification.'
            );
        }

        /*
         * TODO: Ultimately, $report will not contain the raw data, but only messages
         */
        $reportRaw = array();
        $reportRaw['h5pJson'] = $h5pFileHandler->getH5PInformation();
        $reportRaw['contentJson'] = $h5pFileHandler->getH5PContentParams();
        $reportRaw['libraries'] = $h5pFileHandler->getLibrariesInformation();
        $reportRaw['media'] = $h5pFileHandler->getMediaInformation();

        $report = [
            'messages' => []
        ];
        $report['messages'] = array_merge(
            $report['messages'],
            AccessibilityReport::getReport($reportRaw)
        );
        $report['messages'] = array_merge(
            $report['messages'],
            LicenseReport::getReport($reportRaw)
        );
        $report['raw'] = $reportRaw;

        $h5pFileHandler = null;

        return $this->done(json_encode($report, JSON_UNESCAPED_SLASHES));
    }
}
