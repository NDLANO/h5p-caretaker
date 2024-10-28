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
        if (preg_match("/^[a-zA-Z]{2}|fil|FIL$/", $language)) {
            $language = strtolower($language);

            if (isset($locales[$language])) {
                $completeLocale = $locales[$language];
            } else {
                $completeLocale = $language . "_" . strtoupper($language);
            }
        } elseif (preg_match("/^[a-zA-Z]{2}_[a-zA-Z]{2}$/", $language)) {
            $split = explode("_", $language);
            $completeLocale = strtolower($split[0]) . "_" . strtoupper($split[1]);
        } else {
            return null;
        }

        return $completeLocale . ".UTF-8";
    }

    /**
     * Get translations for keywords.
     *
     * @return array The translations.
     */
    public static function getKeywordTranslations($isEnglish)
    {
        $translations = [
            "category" => _("category"),
            "type" => _("type"),
            "summary" => _("summary"),
            "recommendation" => _("recommendation"),
            "details" => _("details"),
            "title" => _("title"),
            "semanticsPath" => _("semanticsPath"),
            "path" => _("path"),
            "subContentId" => _("subContentId"),
            "description" => _("description"),
            "status" => _("status"),
            "level" => _("level"),
            "info" => _("info"),
            "warning" => _("warning"),
            "error" => _("error"),
            "infos" => _("infos"),
            "warnings" => _("warnings"),
            "errors" => _("errors"),
            "reference" => _("reference"),
            "accessibility" => _("accessibility"),
            "missingAltText" => _("missingAltText"),
            "libreText" => "libreText",
            "features" => _("features"),
            "resume" => _("resume"),
            "xAPI" => _("xAPI"),
            "questionTypeContract" => _("questionTypeContract"),
            "license" => _("license"),
            "missingLicense" => _("missingLicense"),
            "missingLicenseExtras" => _("missingLicenseExtras"),
            "missingAuthor" => _("missingAuthor"),
            "missingTitle" => _("missingTitle"),
            "missingSource" => _("missingSource"),
            "missingChanges" => _("missingChanges"),
            "discouragedLicenseAdaptation" => _("discouragedLicenseAdaptation"),
            "invalidLicenseAdaptation" => _("invalidLicenseAdaptation"),
            "invalidLicenseRemix" => _("invalidLicenseRemix"),
            "efficiency" => _("efficiency"),
            "imageSize" => _("imageSize"),
            "imageResolution" => _("imageResolution"),
            "statistics" => _("statistics"),
            "contentTypeCount" => _("contentTypeCount"),
        ];

        if ($isEnglish) {
            $translations["missingLicense"] = "missing license";
            $translations["missingLicenseExtras"] = "missing license extras";
            $translations["missingAuthor"] = "missing author";
            $translations["missingTitle"] = "missing title";
            $translations["missingSource"] = "missing source";
            $translations["missingChanges"] = "missing changes";
            $translations["missingAltText"] = "missing alternative text";
            $translations["discouragedLicenseAdaptation"] = "discouraged license adaptation";
            $translations["invalidLicenseAdaptation"] = "invalid license adaptation";
            $translations["invalidLicenseRemix"] = "invalid license remix";
            $translations["questionTypeContract"] = "question type contract";
            $translations["contentTypeCount"] = "content type count";
            $translations["imageSize"] = "image size";
            $translations["imageResolution"] = "image resolution";
        }

        return $translations;
    }
}
