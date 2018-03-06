<?php

namespace SylvainCombes\Lcid;

use League\JsonGuard\Validator;

/**
 * Class Finder
 *
 * @package SylvainCombes\Lcid
 */
class Finder
{
    /**
     * @var string
     */
    const DATA_PATH = __DIR__.'/Resources/datas.json';

    /**
     * @var string
     */
    const SCHEMA_PATH = __DIR__.'/Resources/datas-schema.json';

    /**
     * @var array|null
     */
    private $datas = null;

    /**
     * @var mixed|null
     */
    private $fallbackDatas = null;

    /**
     * @var mixed|null
     */
    private $mappedDatas = null;

    /**
     * Finder constructor.
     *
     * @param null|string $customJsonDataFile
     *
     * @throws \Exception
     */
    public function __construct($customJsonDataFile = null)
    {
        if (!is_null($this->datas)) {
            return $this;
        }

        if (!is_null($customJsonDataFile) && is_string($customJsonDataFile)) {
            if (!file_exists($customJsonDataFile)) {
                throw new \Exception('File not found in '.$customJsonDataFile);
            }

            $customJson = json_decode(file_get_contents($customJsonDataFile));
            $jsonSchema = json_decode(file_get_contents(self::SCHEMA_PATH));
            $validator  = new Validator($customJson, $jsonSchema);
            if ($validator->fails()) {
                $errors = PHP_EOL;
                foreach ($validator->errors() as $error) {
                    $errors .= $error->getMessage().PHP_EOL;
                }
                throw new \InvalidArgumentException('Your custom data file is not valid : '.$errors);
            }

            $customJson = json_decode(file_get_contents($customJsonDataFile), true);
            $this->setDatas($customJson);

            return $this;
        }

        if (!file_exists(self::DATA_PATH)) {
            throw new \Exception('Datas file not found in '.self::DATA_PATH);
        }

        $datas = json_decode(file_get_contents(self::DATA_PATH), true);
        $this->setDatas($datas);

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getAllDatas()
    {
        return $this->datas;
    }

    /**
     * @return string
     */
    public function getAllDatasAsJson()
    {
        return json_encode($this->datas, JSON_PRETTY_PRINT);
    }

    /**
     * @return array|mixed
     */
    public function getAllDatasReversed()
    {
        $datas['mapped']    = $this->getDatasReversed();
        $datas['fallbacks'] = $this->getFallbackDatas();

        return $datas;
    }

    /**
     * @return string
     */
    public function getAllDatasReversedAsJson()
    {
        return json_encode($this->getAllDatasReversedAsJson(), JSON_PRETTY_PRINT);
    }

    /**
     * @return array|mixed
     */
    public function getFallbackDatas()
    {
        return $this->fallbackDatas;
    }

    /**
     * @return string
     */
    public function getFallbackDatasAsJson()
    {
        return json_encode($this->getFallbackDatas(), JSON_PRETTY_PRINT);
    }

    /**
     * @return array|mixed
     */
    public function getDatas()
    {
        return $this->mappedDatas;
    }

    /**
     * @return string
     */
    public function getDatasAsJson()
    {
        return json_encode($this->mappedDatas, JSON_PRETTY_PRINT);
    }

    /**
     * @return array|mixed
     */
    public function getDatasReversed()
    {
        return array_flip($this->mappedDatas);
    }

    /**
     * @return string
     */
    public function getDatasReversedAsJson()
    {
        return json_encode($this->getDatasReversed(), JSON_PRETTY_PRINT);
    }

    /**
     * Return only one lcid code, don't search in fallbacks
     *
     * @param string $locale
     *
     * @return int|null
     * @throws \TypeError
     */
    public function findOneByLocale($locale)
    {
        if (!is_string($locale)) {
            throw new \TypeError('Expected a locale string');
        }

        if (strlen($locale) == 2) {
            $locale = LanguageMatchDefaultCountry::match($locale);
        }

        if (!empty($this->mappedDatas[$locale])) {
            return $this->mappedDatas[$locale];
        }

        return null;
    }

    /**
     * Return one or more lcid matching codes, null on no match
     *
     * @param string $locale
     *
     * @return array|null
     * @throws \TypeError
     */
    public function findByLocale($locale)
    {
        // Best match first
        $lcid = $this->findOneByLocale($locale);

        // Add fallback results
        $lcids = $this->searchLocaleInFallback($locale);

        if (!is_null($lcids)) {
            if ($lcid) {
                array_unshift($lcids, $lcid);
                $lcids = array_unique($lcids);
            }

            return $lcids;
        }

        if ($lcid) {
            return [$lcid];
        }

        return null;
    }

    /**
     * Return one locale code by lcid code
     *
     * @param integer $lcid decimal integer code of the lcid
     *
     * @return null|string
     * @throws \TypeError
     */
    public function findByLcid($lcid)
    {
        if (!is_int($lcid)) {
            throw new \TypeError('Expected a number');
        }

        $datas = $this->getDatasReversed();

        if (!empty($datas[$lcid])) {
            return $datas[$lcid];
        }

        return null;
    }

    /**
     * Return one locale code by lcid code
     *
     * @param integer $lcid decimal integer code of the lcid
     *
     * @return null|string
     * @throws \TypeError
     */
    public function findByLcidWithFallback($lcid)
    {
        $result = $this->findByLcid($lcid);

        if (is_null($result)) {
            return $this->searchLcidInFallback($lcid);
        }

        return $result;
    }

    /**
     * Search lcid value in fallback datas
     *
     * @param int $lcid
     *
     * @return int|null|string
     */
    private function searchLcidInFallback($lcid)
    {
        if (is_int($lcid)) {
            foreach ($this->fallbackDatas as $key => $fallbackData) {
                if (in_array($lcid, $fallbackData)) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Search locale value in fallback datas
     *
     * @param string $locale
     *
     * @return null|array
     */
    private function searchLocaleInFallback($locale)
    {
        if (is_string($locale)) {
            if (!empty($this->fallbackDatas[$locale])) {
                return $this->fallbackDatas[$locale];
            }
        }

        return null;
    }

    /**
     * @param $datas mixed
     */
    private function setDatas($datas)
    {
        $this->datas = $datas;

        if (!empty($this->datas["mapped"])) {
            $this->mappedDatas = $this->datas["mapped"];
        }

        if (!empty($this->datas["fallbacks"])) {
            $this->fallbackDatas = $this->datas["fallbacks"];
        }
    }
}
