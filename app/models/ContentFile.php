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
    private $attributes = [];
    private $parent;

    /**
     * Constructor.
     *
     * @param array $data The parameters.
     */
    public function __construct($data)
    {
        foreach($data['attributes'] as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Set an attribute.
     *
     * @param string $name  The name of the attribute.
     * @param mixed  $value The value of the attribute.
     */
    public function setAttribute($name, $value) {
        if (!isset($name) || getType($name) !== 'string') {
            return;
        }

        if ($name === 'versionedMachineName' && !isset($value)) {
            $value = '';
        } elseif ($name === 'type' && !isset($value)) {
            $value = '';
        } elseif ($name === 'path' && !isset($value)) {
            $value = '';
        } elseif ($name === 'semanticsPath' && !isset($value)) {
            $value = '';
        } elseif ($name === 'base64' && !isset($value)) {
            $value = null;
        } elseif ($name === 'mime' && !isset($value)) {
            $value = '';
        } elseif ($name === 'metadata' && !isset($value)) {
            $value = [];
        } elseif ($name === 'alt' && !isset($value)) {
            $value = '';
        } elseif ($name === 'decorative' && !isset($value)) {
            $value = false;
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Get an attribute.
     *
     * @param string $name The name of the attribute.
     *
     * @return mixed The value of the attribute.
     */
    public function getAttribute($name) {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Set the parent content.
     *
     * @param Content $parent The parent content.
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get the parent content.
     *
     * @return Content The parent content.
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get description.
     *
     * @param string $template The template for the description.
     *
     * @return string The description.
     */
    public function getDescription($template = '{title} ({type}) inside {parentTitle} ({parentMachineName})') {
        $title = $this->attributes['metadata']['title'] ?? 'Untitled';
        $type = $this->attributes['type'] ?? 'file';

        return str_replace(
            ['{title}', '{type}', '{parentTitle}', '{parentMachineName}'],
            [$title, $type, $this->parent->getDescription('{title}'), $this->parent->getDescription('{machineName}')],
            $template
        );
    }
}
