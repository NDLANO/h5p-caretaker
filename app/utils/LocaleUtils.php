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
class LocaleUtils
{
    private static $strings = [];
    private static $currentLocale = 'en';
    private static $defaultLocale = 'en';

    public static function getCompleteLocale($language)
    {
      // Define the mapping of short language codes to full locales
        $locales = [
          "af" => "af_ZA",
          "ar" => "ar_SA",
          "az" => "az_AZ",
          "be" => "be_BY",
          "bg" => "bg_BG",
          "bn" => "bn_BD",
          "bs" => "bs_BA",
          "ca" => "ca_ES",
          "cs" => "cs_CZ",
          "cy" => "cy_GB",
          "da" => "da_DK",
          "de" => "de_DE",
          "el" => "el_GR",
          "en" => "en_US",
          "eo" => "eo",
          "es" => "es_ES",
          "et" => "et_EE",
          "eu" => "eu_ES",
          "fa" => "fa_IR",
          "fi" => "fi_FI",
          "fil" => "fil_PH",
          "fo" => "fo_FO",
          "fr" => "fr_FR",
          "ga" => "ga_IE",
          "gl" => "gl_ES",
          "gu" => "gu_IN",
          "he" => "he_IL",
          "hi" => "hi_IN",
          "hr" => "hr_HR",
          "hu" => "hu_HU",
          "hy" => "hy_AM",
          "id" => "id_ID",
          "is" => "is_IS",
          "it" => "it_IT",
          "ja" => "ja_JP",
          "ka" => "ka_GE",
          "kk" => "kk_KZ",
          "km" => "km_KH",
          "kn" => "kn_IN",
          "ko" => "ko_KR",
          "lt" => "lt_LT",
          "lv" => "lv_LV",
          "mk" => "mk_MK",
          "ml" => "ml_IN",
          "mn" => "mn_MN",
          "mr" => "mr_IN",
          "ms" => "ms_MY",
          "mt" => "mt_MT",
          "nb" => "nb_NO",
          "ne" => "ne_NP",
          "nl" => "nl_NL",
          "nn" => "nn_NO",
          "pa" => "pa_IN",
          "pl" => "pl_PL",
          "pt" => "pt_PT",
          "ro" => "ro_RO",
          "ru" => "ru_RU",
          "sk" => "sk_SK",
          "sl" => "sl_SI",
          "sq" => "sq_AL",
          "sr" => "sr_RS",
          "sv" => "sv_SE",
          "sw" => "sw_KE",
          "ta" => "ta_IN",
          "te" => "te_IN",
          "th" => "th_TH",
          "tr" => "tr_TR",
          "uk" => "uk_UA",
          "ur" => "ur_PK",
          "uz" => "uz_UZ",
          "vi" => "vi_VN",
          "zh" => "zh_CN",
          // Add more mappings if needed
        ];

        // Validate the input
        if (preg_match("/^[a-zA-Z]{2}_[a-zA-Z]{2}$/", $language)) {
            $split = explode("_", $language);
            $completeLocale = strtolower($split[0]) . "_" . strtoupper($split[1]);
        } elseif (preg_match("/^[a-zA-Z]{2}|fil|FIL$/", $language)) {
            $language = strtolower($language);

            if (isset($locales[$language])) {
                $completeLocale = $locales[$language];
            } else {
                $completeLocale = $language . "_" . strtoupper($language);
            }
        } else {
            return null;
        }

        return $completeLocale . ".UTF-8";
    }

    /**
     * Set the locale.
     *
     * @param string $locale The locale to set.
     *
     * @return string The current locale.
     */
    public static function setLocale($locale)
    {
        $langFile = join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'lang', $locale, 'strings.php']);

        if (!file_exists($langFile)) {
            $locale = explode("_", $locale)[0];
            $langFile = join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'lang', $locale, 'strings.php']);
        }

        if (file_exists($langFile)) {
            self::$currentLocale = $locale;
            include $langFile;
            self::$strings[self::$currentLocale] = $string;
        } else {
            self::$currentLocale = self::$defaultLocale;
        }

        return self::$currentLocale;
    }

    /**
     * Get a string by its identifier.
     *
     * @param string $identifier The identifier of the string.
     * @param string $specificLocale The locale to use. Optional.
     *
     * @return string The string.
     */
    public static function getString($identifier, $specificLocale = null)
    {
        if (!isset(self::$strings[self::$currentLocale])) {
            self::setLocale(self::$defaultLocale);
        }

        return self::$strings[$specificLocale ?? self::$currentLocale][$identifier] ??
            self::$strings[self::$defaultLocale][$identifier] ??
            $identifier;
    }

    /**
     * Get translations for keywords.
     *
     * @return array The translations.
     */
    public static function getKeywordTranslations()
    {
        $translations = [
            "category" => self::getString("category"),
            "type" => self::getString("type"),
            "summary" => self::getString("summary"),
            "recommendation" => self::getString("recommendation"),
            "details" => self::getString("details"),
            "title" => self::getString("title"),
            "semanticsPath" => self::getString("semanticsPath"),
            "path" => self::getString("path"),
            "subContentId" => self::getString("subContentId"),
            "description" => self::getString("description"),
            "status" => self::getString("status"),
            "licenseNote" => self::getString("licenseNote"),
            "level" => self::getString("level"),
            "info" => self::getString("info"),
            "warning" => self::getString("warning"),
            "error" => self::getString("error"),
            "infos" => self::getString("infos"),
            "warnings" => self::getString("warnings"),
            "errors" => self::getString("errors"),
            "caution" => self::getString("caution"),
            "cautions" => self::getString("cautions"),
            "reference" => self::getString("reference"),
            "accessibility" => self::getString("accessibility"),
            "missingAltText" => self::getString("missingAltText"),
            "missingA11yTitle" => self::getString("missingA11yTitle"),
            "libreText" => "libreText",
            "features" => self::getString("features"),
            "missingLibrary" => self::getString("missingLibrary"),
            "resume" => self::getString("resume"),
            "xAPI" => self::getString("xAPI"),
            "questionTypeContract" => self::getString("questionTypeContract"),
            "license" => self::getString("license"),
            "missingLicense" => self::getString("missingLicense"),
            "missingLicenseVersion" => self::getString("missingLicenseVersion"),
            "missingLicenseExtras" => self::getString("missingLicenseExtras"),
            "missingAuthor" => self::getString("missingAuthor"),
            "missingTitle" => self::getString("missingTitle"),
            "missingSource" => self::getString("missingSource"),
            "missingChanges" => self::getString("missingChanges"),
            "discouragedLicenseAdaptation" => self::getString("discouragedLicenseAdaptation"),
            "invalidLicenseAdaptation" => self::getString("invalidLicenseAdaptation"),
            "invalidLicenseRemix" => self::getString("invalidLicenseRemix"),
            "efficiency" => self::getString("efficiency"),
            "imageSize" => self::getString("imageSize"),
            "imageResolution" => self::getString("imageResolution"),
            "statistics" => self::getString("statistics"),
            "contentTypeCount" => self::getString("contentTypeCount"),
            "reuse" => self::getString("reuse"),
            "notCulturalWork" => self::getString("notCulturalWork"),
            "noAuthorComments" => self::getString("noAuthorComments"),
            "hasLicenseExtras" => self::getString("hasLicenseExtras"),
            "learnMore" => self::getString("Learn more about this topic."),
            "image" => self::getString("image"),
            "audio" => self::getString("audio"),
            "video" => self::getString("video"),
            "file" => self::getString("file"),
            "untitled" => self::getString("untitled"),
        ];

        return $translations;
    }
}
