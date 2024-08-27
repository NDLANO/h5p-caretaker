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
class Content
{
    private $parent;
    private $children;
    private $attributes;
    private $reports;

    /**
     * Constructor.
     *
     * @param array $data The content data.
     */
    public function __construct($data)
    {
        $this->parent = $data["parent"] ?? null;
        $this->children = $data["children"] ?? [];

        foreach ($data["attributes"] as $name => $value) {
            $this->setAttribute($name, $value);
        }

        $this->setAttribute("contentFiles", $this->assembleContentFiles());
        foreach ($this->attributes["contentFiles"] as $contentFile) {
            $contentFile->setParent($this);
        }

        $this->reports = [];
    }

    /**
     * Set an attribute.
     *
     * @param string $name  The name of the attribute.
     * @param mixed  $value The value of the attribute.
     */
    public function setAttribute($name, $value)
    {
        if (!isset($name) || getType($name) !== "string") {
            return;
        }

        if ($name === "id" && !isset($value)) {
            $value = "";
        } elseif ($name === "versionedMachineName" && !isset($value)) {
            $value = "";
        } elseif ($name === "metadata" && !isset($value)) {
            $value = [];
        } elseif ($name === "semanticsPath" && !isset($value)) {
            $value = "";
        } elseif ($name === "params" && !isset($value)) {
            $value = [];
        } elseif ($name === "contentFiles" && !isset($value)) {
            $value = [];
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
    public function getAttribute($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Get description.
     *
     * @param string $template The template for the description.
     *
     * @return string The description.
     */
    public function getDescription($template = "{title} ({machineName})")
    {
        $title = $this->attributes["metadata"]["title"] ?? "Untitled";
        $machineName = explode(
            " ",
            $this->attributes["versionedMachineName"]
        )[0];

        return str_replace(
            ["{title}", "{machineName}"],
            [$title, $machineName],
            $template
        );
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
     * Remove the parent content.
     */
    public function removeParent()
    {
        $this->parent = null;
    }

    /**
     * Add a child.
     *
     * @param Content $child The child to add.
     */
    public function addChild($child)
    {
        if (in_array($child, $this->children)) {
            return;
        }

        $this->children[] = $child;
    }

    /**
     * Remove a child.
     *
     * @param Content $child The child to remove.
     */
    public function removeChild($child)
    {
        $index = array_search($child, $this->children);
        if ($index !== false) {
            unset($this->children[$index]);
        }
    }

    /**
     * Remove all children.
     */
    public function removeAllChildren()
    {
        $this->children = [];
    }

    /**
     * Get all children.
     *
     * @return array The children.
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add message to report.
     * @param string $category
     * @param array $message
     */
    public function addReportMessage($message)
    {
        $category = $message["category"] ?? null;
        if (!isset($category)) {
            return;
        }

        if (!isset($this->reports[$category])) {
            $this->reports[$category] = [];
        }

        $this->reports[$category][] = $message;
    }

    /**
     * Get all reports.
     *
     * @return array The reports.
     */
    public function getReports()
    {
        return $this->reports;
    }

    /**
     * Get a specific report.
     *
     * @param string $name The name of the report.
     *
     * @return array The report.
     */
    public function getReport($name)
    {
        return $this->reports[$name] ?? [];
    }

    /**
     * Assemble content directly associated with this content but not with its children.
     *
     * @return array The content files.
     */
    private function assembleContentFiles()
    {
        $files = [];

        $machineName =
            $this->attributes["versionedMachineName"] !== ""
                ? explode(" ", $this->attributes["versionedMachineName"])[0]
                : "";

        // TODO: Currently, the medatada for Audio and Video will be the same for
        // all sources. This may not be correct, but currently the AV widget of
        // H5P Editor does not work as expected, and it is not clear if separate
        // copyright information objects are to be expected when fixed.

        if ($machineName === "H5P.Image") {
            if (!isset($this->attributes["params"]["file"])) {
                return $files;
            }

            $files[] = new ContentFile([
                "attributes" => [
                    "type" => "image",
                    "path" => $this->attributes["params"]["file"]["path"] ?? "",
                    "semanticsPath" => $this->attributes["semanticsPath"] . "." . "file",
                    "mime" => $this->attributes["params"]["file"]["mime"] ?? "",
                    "metadata" => $this->attributes["metadata"],
                    "width" => $this->attributes["params"]["file"]["width"] ?? 0,
                    "height" => $this->attributes["params"]["file"]["height"] ?? 0,
                ],
            ]);
        } elseif ($machineName === "H5P.Audio") {
            if (!isset($this->attributes["params"]["files"])) {
                return $files;
            }

            for (
                $i = 0; $i < count($this->attributes["params"]["files"]); $i++
            ) {
                $file = $this->attributes["params"]["files"][$i];
                $files[] = new ContentFile([
                    "attributes" => [
                        "type" => "audio",
                        "path" => $file["path"] ?? "",
                        "semanticsPath" => $this->attributes["semanticsPath"] . "." . "files[" . $i . "]",
                        "mime" => $file["mime"] ?? "",
                        "metadata" => $this->attributes["metadata"],
                    ],
                ]);
            }
        } elseif ($machineName === "H5P.Video") {
            if (!isset($this->attributes["params"]["sources"])) {
                return $files;
            }

            for (
                $i = 0; $i < count($this->attributes["params"]["sources"]); $i++
            ) {
                $file = $this->attributes["params"]["sources"][$i];
                $files[] = new ContentFile([
                    "attributes" => [
                        "type" => "video",
                        "path" => $file["path"] ?? "",
                        "semanticsPath" => $this->attributes["semanticsPath"] . "." . "sources[" . $i . "]",
                        "mime" => $file["mime"] ?? "",
                        "metadata" => $this->attributes["metadata"],
                    ],
                ]);
            }
        } else {
            $prunedParams = JSONUtils::pruneChildren(
                $this->attributes["params"]
            );

            // Find all files
            $fileParams = JSONUtils::findAttributeValuePairs($prunedParams, [
                ["mime", '/^\w+\.\w+$/'],
                ["path", "/.+/"],
            ]);

            foreach ($fileParams as $params) {
                $type = explode("/", $params["object"]["mime"])[0];
                if (
                    $type !== "image" &&
                    $type !== "audio" &&
                    $type !== "video"
                ) {
                    $type = "file";
                }

                $semanticsPath = $this->attributes["semanticsPath"];
                $semanticsPath .= $semanticsPath === "" ? "" : ".";
                $semanticsPath .= $params["path"];

                $files[] = new ContentFile([
                    "attributes" => [
                        "type" => $type,
                        "path" => $params["object"]["path"],
                        "semanticsPath" => $semanticsPath,
                        "mime" => $params["object"]["mime"],
                        "metadata" => JSONUtils::copyrightToMetadata(
                            $params["object"]["copyright"] ?? []
                        ),
                    ],
                ]);
            }
        }

        return $files;
    }
}
