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
 * @link     https://github.com/ndlano/H5PCaretaker
 */

namespace H5PCaretaker;

/**
 * Class for handling H5P specific stuff.
 *
 * @category File
 * @package  H5PCare
 * @author   Oliver Tacke <oliver@snordian.de>
 * @license  MIT License
 * @link     https://github.com/ndlano/H5PCaretaker
 */
class JSONUtils
{
    public static function findAttributeValuePair($json, $attribute, $valuePattern) {
      $results = [];

      self::traverseJson($json, $attribute, $valuePattern, '', $results);
      return $results;
    }

    private static function traverseJson($json, $attribute, $valuePattern, $currentPath, &$results) {
      if (is_array($json)) {
          foreach ($json as $key => $value) {
              $newPath = $currentPath === '' ? $key : "$currentPath.$key";
              if (is_array($value)) {
                  if (array_key_exists($attribute, $value) && preg_match($valuePattern, $value[$attribute])) {
                      $results[] = [
                        'path' => preg_replace('/\.(\d+)\./', '[$1].', $newPath),
                        'object' => $value
                    ];
                  }
                  self::traverseJson($value, $attribute, $valuePattern, $newPath, $results);
              }
          }
      }
  }
}
