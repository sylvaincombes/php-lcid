<?php

namespace SylvainCombes\Lcid;

/**
 * Class LanguageMatchDefaultCountry
 *
 * @package SylvainCombes\Lcid
 */
class LanguageMatchDefaultCountry
{
    /**
     * Tries to return a [language + country code] with a language code in input
     * Example : fr => fr_FR
     * If no match, return the input language string
     *
     * @param string $language
     *
     * @see https://msdn.microsoft.com/en-us/library/ms912047(v=winembedded.10).aspx
     * @return string
     */
    public static function match($language)
    {
        $matches = [
            'af' => 'af_ZA',
            'am' => 'am_ET',
            'as' => 'as_IN',
            'az' => 'az_AZ',
            'be' => 'be_BY',
            'bg' => 'bg_BG',
            'bn' => 'bn_IN',
            'bo' => 'bo_CN',
            'br' => 'br_FR',
            'bs' => 'bs_BA',
            'ca' => 'ca_ES',
            'cs' => 'cs_CZ',
            'cy' => 'cy_GB',
            'da' => 'da_DK',
            'de' => 'de_DE',
            'el' => 'el_GR',
            'en' => 'en_US',
            'es' => 'es_ES',
            'et' => 'et_EE',
            'eu' => 'eu_ES',
            'fa' => 'fa_IR',
            'fi' => 'fi_FI',
            'fo' => 'fo_FO',
            'fr' => 'fr_FR',
            'fy' => 'fy_NL',
            'ga' => 'ga_IE',
            'gl' => 'gl_ES',
            'gu' => 'gu_IN',
            'ha' => 'ha_NG',
            'he' => 'he_IL',
            'hi' => 'hi_IN',
            'hr' => 'hr_HR',
            'hu' => 'hu_HU',
            'hy' => 'hy_AM',
            'id' => 'id_ID',
            'ii' => 'ii_CN',
            'is' => 'is_IS',
            'it' => 'it_IT',
            'ja' => 'ja_JP',
            'ka' => 'ka_GE',
            'kk' => 'kk_KZ',
            'kl' => 'kl_GL',
            'kn' => 'kn_IN',
            'ko' => 'ko_KR',
            'ky' => 'ky_KG',
            'lb' => 'lb_LU',
            'lo' => 'lo_LA',
            'lt' => 'lt_LT',
            'lv' => 'lv_LV',
            'mk' => 'mk_MK',
            'ml' => 'ml_IN',
            'mn' => 'mn_MN',
            'mr' => 'mr_IN',
            'ms' => 'ms_MY',
            'mt' => 'mt_MT',
            'my' => 'my_MM',
            'nb' => 'nb_NO',
            'ne' => 'ne_NP',
            'nl' => 'nl_NL',
            'nn' => 'nn_NO',
            'or' => 'or_IN',
            'pa' => 'pa_IN',
            'pl' => 'pl_PL',
            'ps' => 'ps_AF',
            'pt' => 'pt_PT',
            'rm' => 'rm_CH',
            'ro' => 'ro_RO',
            'ru' => 'ru_RU',
            'rw' => 'rw_RW',
            // don't know for se ...
            'si' => 'si_LK',
            'sk' => 'sk_SK',
            'sl' => 'sl_SI',
            'sq' => 'sq_AL',
            'sr' => 'sr_BA',
            'sv' => 'sv_SE',
            'sw' => 'sw_KE',
            'ta' => 'ta_IN',
            'te' => 'te_IN',
            'tg' => 'tg_TJ',
            'th' => 'th_TH',
            'tr' => 'tr_TR',
            'tt' => 'tt_RU',
            'ug' => 'ug_CN',
            'uk' => 'uk_UA',
            'ur' => 'ur_PK',
            'uz' => 'uz_UZ',
            'vi' => 'vi_VN',
            'wo' => 'wo_SN',
            'yo' => 'yo_NG',
            'zh' => 'zh_CN',
            'zu' => 'zu_ZA',
        ];

        if (!empty($matches[$language])) {
            return $matches[$language];
        }

        return $language;
    }
}
