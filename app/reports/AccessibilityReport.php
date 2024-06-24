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
  static function getReport($raw)
  {
    $report = [];
    $report = array_merge(
      $report,
      self::getMissingAltText($raw['contentJson'], $raw['media'])
    );
    $report = array_merge($report, self::getLibreText($raw['libraries']));

    return $report;
  }

  static function getLibreText($libraries) {
    $libraries = array_filter(
      $libraries,
      function ($library) {
        return isset($library->libreTextA11y);
      }
    );

    $messages = [];
    foreach ($libraries as $library) {
      $messages[] = [
        'category' => 'accessibility',
        'type' => 'libreText',
        'details' => [
          'type' => $library->libreTextA11y['type'],
          // Should be added in the libretext API response, "type" is "title" and not unique
          //'machineName' => $library->libreTextA11y->machineName,
          'description' => $library->libreTextA11y['description'],
          'status' => $library->libreTextA11y['status'],
          'url' => $library->libreTextA11y['url'],
        ]
      ];
    }

    return $messages;
  }

  static function getMissingAltText($contentJson, $media)
  {
    $h5pImageContents = JSONUtils::findAttributeValuePair(
      $contentJson,
      'library',
      '/^H5P.Image/'
    );

    $missingAltText = array_filter(
      $h5pImageContents,
      function ($item) {
        $params = $item['object']['params'];
        return
          (!isset($params['alt']) || $params['alt'] === '') &&
          $params['decorative'] !== true;
      }
    );

    $messages = [];

    // TODO: i10n
    foreach ($missingAltText as $key) {
      $message = [
        'category' => 'accessibility',
        'type' => 'missingAltText',
        'summary' => 'Missing alt text for image ' .
          $key['object']['metadata']['title'] . ' at ' . $key['path'],
        'recommendation' => 'Check whether there is a reason for the image to not have an alternative text. If not, it is recommended to add one or to declare the image as decorative.',
        'details' => [
          'path' => $key['path'],
          'title' => $key['object']['metadata']['title'],
          'subContentId' => $key['object']['subContentId']
        ]
      ];

      $base64 = null;
      if (isset($key['object']['params']['file']['path'])) {
        $imageFileName = explode(
          DIRECTORY_SEPARATOR,
          $key['object']['params']['file']['path']
        )[1];

        foreach($media->images as $fileName => $value) {
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
