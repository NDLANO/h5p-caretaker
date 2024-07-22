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
class ContentFile
{
    private $versionedMachineName;
    private $type;
    private $path;
    private $base64;
    private $semanticsPath;
    private $mime;
    private $metadata;
    private $alt;
    private $decorative;

    // TODO: Use attributes as in Content

    /**
     * Constructor.
     *
     * @param array $params The parameters.
     */
    public function __construct($params)
    {
        $this->versionedMachineName = $params['versionedMachineName'] ?? '';
        $this->type = $params['type'] ?? '';
        $this->path = $params['path'] ?? '';
        $this->base64 = $params['base64'] ?? null;
        $this->semanticsPath = $params['semanticsPath'] ?? '';
        $this->mime = $params['mime'] ?? '';
        $this->metadata = $params['metadata'] ?? [];

        if ($this->type === 'image') {
            $this->alt = $params['alt'] ?? '';
            $this->decorative = $params['decorative'] ?? false;
        }
    }

    /**
     * Get the data.
     *
     * @return array The data.
     */
    public function getData()
    {
        return [
            'versionedMachineName' => $this->versionedMachineName,
            'type' => $this->type,
            'path' => $this->path,
            'semanticsPath' => $this->semanticsPath,
            'base64' => $this->base64,
            'mime' => $this->mime,
            'metadata' => $this->metadata,
            'alt' => $this->alt,
            'decorative' => $this->decorative,
        ];
    }

    public function setBase64($base64)
    {
        $this->base64 = $base64;
    }
}
