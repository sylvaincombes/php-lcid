<?php

declare(strict_types=1);

namespace SylvainCombes\Lcid;

use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator as JsonSchemaValidator;

final class Finder
{
    public const DATA_PATH = __DIR__.'/Resources/datas.json';
    public const SCHEMA_PATH = __DIR__.'/Resources/datas-schema.json';

    /** @var array{mapped: array<string, int>, fallbacks?: array<string, list<int>>}|null */
    private ?array $datas = null;

    /** @var array<string, list<int>>|null */
    private ?array $fallbackDatas = null;

    /** @var array<string, int>|null */
    private ?array $mappedDatas = null;

    public function __construct(?string $customJsonDataFile = null)
    {
        if ($customJsonDataFile !== null) {
            if (!file_exists($customJsonDataFile)) {
                throw new \Exception('File not found in '.$customJsonDataFile);
            }

            $customJson = (string) file_get_contents($customJsonDataFile);
            $schema = (string) file_get_contents(self::SCHEMA_PATH);

            $validator = new JsonSchemaValidator();
            /** @var object $schemaObject */
            $schemaObject = json_decode($schema, false, 512, JSON_THROW_ON_ERROR);
            $result = $validator->validate(
                Helper::toJSON(json_decode($customJson)),
                $schemaObject
            );

            if (!$result->isValid()) {
                $errors = PHP_EOL;
                /** @psalm-suppress MixedAssignment */
                foreach ($result->error()?->subErrors() ?? [] as $error) {
                    if ($error instanceof \Opis\JsonSchema\Errors\ValidationError) {
                        $errors .= $error->message().PHP_EOL;
                    }
                }
                throw new \InvalidArgumentException('Your custom data file is not valid : '.$errors);
            }

            /** @var array{mapped: array<string, int>, fallbacks?: array<string, list<int>>} $decoded */
            $decoded = json_decode($customJson, true, 512, JSON_THROW_ON_ERROR);
            $this->setDatas($decoded);

            return;
        }

        if (!file_exists(self::DATA_PATH)) {
            throw new \Exception('Datas file not found in '.self::DATA_PATH);
        }

        /** @var array{mapped: array<string, int>, fallbacks?: array<string, list<int>>} $decoded */
        $decoded = json_decode((string) file_get_contents(self::DATA_PATH), true, 512, JSON_THROW_ON_ERROR);
        $this->setDatas($decoded);
    }

    /** @return array{mapped: array<string, int>, fallbacks?: array<string, list<int>>} */
    public function getAllDatas(): array
    {
        return $this->datas ?? ['mapped' => [], 'fallbacks' => []];
    }

    public function getAllDatasAsJson(): string
    {
        return (string) json_encode($this->datas, JSON_PRETTY_PRINT);
    }

    /** @return array<string, mixed> */
    public function getAllDatasReversed(): array
    {
        return [
            'mapped' => $this->getDatasReversed(),
            'fallbacks' => $this->getFallbackDatas(),
        ];
    }

    public function getAllDatasReversedAsJson(): string
    {
        return (string) json_encode($this->getAllDatasReversed(), JSON_PRETTY_PRINT);
    }

    /** @return array<string, list<int>>|null */
    public function getFallbackDatas(): ?array
    {
        return $this->fallbackDatas;
    }

    public function getFallbackDatasAsJson(): string
    {
        return (string) json_encode($this->fallbackDatas, JSON_PRETTY_PRINT);
    }

    /** @return array<string, int>|null */
    public function getDatas(): ?array
    {
        return $this->mappedDatas;
    }

    public function getDatasAsJson(): string
    {
        return (string) json_encode($this->mappedDatas, JSON_PRETTY_PRINT);
    }

    /** @return array<int, string> */
    public function getDatasReversed(): array
    {
        return array_flip($this->mappedDatas ?? []);
    }

    public function getDatasReversedAsJson(): string
    {
        return (string) json_encode($this->getDatasReversed(), JSON_PRETTY_PRINT);
    }

    public function findOneByLocale(string $locale): ?int
    {
        if (strlen($locale) === 2) {
            $locale = LanguageMatchDefaultCountry::match($locale);
        }

        return $this->mappedDatas[$locale] ?? null;
    }

    /** @return list<int>|null */
    public function findByLocale(string $locale): ?array
    {
        $lcid = $this->findOneByLocale($locale);
        $lcids = $this->searchLocaleInFallback($locale);

        if ($lcids !== null) {
            if ($lcid !== null) {
                array_unshift($lcids, $lcid);
                $lcids = array_unique($lcids);
            }

            return array_values($lcids);
        }

        if ($lcid !== null) {
            return [$lcid];
        }

        return null;
    }

    public function findByLcid(int $lcid): ?string
    {
        return $this->getDatasReversed()[$lcid] ?? null;
    }

    public function findByLcidWithFallback(int $lcid): ?string
    {
        return $this->findByLcid($lcid) ?? $this->searchLcidInFallback($lcid);
    }

    private function searchLcidInFallback(int $lcid): ?string
    {
        foreach ($this->fallbackDatas ?? [] as $key => $fallbackData) {
            if (in_array($lcid, $fallbackData)) {
                return $key;
            }
        }

        return null;
    }

    /** @return list<int>|null */
    private function searchLocaleInFallback(string $locale): ?array
    {
        return $this->fallbackDatas[$locale] ?? null;
    }

    /** @param array{mapped: array<string, int>, fallbacks?: array<string, list<int>>} $datas */
    private function setDatas(array $datas): void
    {
        $this->datas = $datas;
        $this->mappedDatas = $datas['mapped'];
        $this->fallbackDatas = $datas['fallbacks'] ?? null;
    }
}
