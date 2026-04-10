<?php

declare(strict_types=1);

namespace SylvainCombes\Lcid\Command;

use SylvainCombes\Lcid\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Locales;

final class GenerateDatasCommand extends Command
{
    public const DISTANT_JSON_URL = 'https://raw.githubusercontent.com/sindresorhus/lcid/main/lcid.json';
    public const LOCAL_JSON_PATH = __DIR__.'/../Resources/datas.json';
    public const LOCAL_MANUAL_DATAS_PATH = __DIR__.'/../Resources/datas-manual.php';

    private bool $getDistantDatas = true;
    private ?string $json = null;
    private bool $useFallbacks = true;
    private bool $useManualDatas = true;

    /** @var array<string> */
    private array $locales = [];

    #[\Override]
    protected function configure(): void
    {
        $this->setName('lcid:generate-datas')
            ->setDescription('Generate lcid to iso locale datas building.')
            ->setHelp(
                'This command allows you to refresh datas using a distant json and local php datas to re-generate lcid datas'
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->locales = Locales::getLocales();

        $io = new SymfonyStyle($input, $output);
        $io->title('LCID data builder');

        if ($this->getDistantDatas) {
            $download = $this->downloadDistantJson($io);
            if ($download > 0) {
                return $download;
            }

            if (file_exists(self::LOCAL_JSON_PATH)) {
                if ($io->isVeryVerbose()) {
                    $io->text('Found local data file, start loading');
                }
                $existingJson = file_get_contents(self::LOCAL_JSON_PATH);

                if ($this->json !== $existingJson) {
                    $io->comment('Local and distant data are different, update local datas');
                    $write = $this->writeDistantDatas((string) $this->json, $io);
                    if ($write > 0) {
                        return $write;
                    }
                } else {
                    $io->comment('Datas are identical, no write');
                }
            } else {
                $io->warning('No local data file found.');
                $write = $this->writeDistantDatas((string) $this->json, $io);
                if ($write > 0) {
                    return $write;
                }
            }
        }

        if ($this->useManualDatas) {
            /** @var list<array{name: string, language: string, locale?: string, lcid: int}> $old */
            $old = include self::LOCAL_MANUAL_DATAS_PATH;

            $finder = new Finder();
            $datas = $finder->getAllDatas();
            $datas['fallbacks'] ??= [];
            /** @var array<string, int> $mapped */
            $mapped = &$datas['mapped'];
            /** @var array<string, list<int>> $fallbacks */
            $fallbacks = &$datas['fallbacks'];

            foreach ($old as $item) {
                $language = $item['language'];
                $locale = $item['locale'] ?? null;
                $lcid = $item['lcid'];

                $mapped = array_flip($mapped);
                $found = isset($mapped[$lcid]);
                $mapped = array_flip($mapped);

                if (!$found) {
                    echo 'Lcid '.$lcid.' in old datas not existing in datas'.PHP_EOL;

                    if ($locale !== null && $locale !== '' && in_array($locale, $this->locales)) {
                        echo 'Valid locale '.$locale.' found !'.PHP_EOL;

                        if (!isset($mapped[$locale]) && (strlen($locale) === 5 || strlen($locale) === 6)) {
                            $mapped[$locale] = $lcid;

                            if (isset($fallbacks[$locale]) && in_array($lcid, $fallbacks[$locale])) {
                                $key = (int) array_search($lcid, $fallbacks[$locale]);
                                array_splice($fallbacks[$locale], $key, 1);
                                /** @psalm-suppress DocblockTypeContradiction */
                                if ($fallbacks[$locale] === []) {
                                    unset($fallbacks[$locale]);
                                }
                            }

                            if (isset($fallbacks[$language]) && in_array($lcid, $fallbacks[$language])) {
                                $key = (int) array_search($lcid, $fallbacks[$language]);
                                array_splice($fallbacks[$language], $key, 1);
                                /** @psalm-suppress DocblockTypeContradiction */
                                if ($fallbacks[$language] === []) {
                                    unset($fallbacks[$language]);
                                }
                            }
                        } elseif (isset($mapped[$locale]) || strlen($locale) === 2 || strlen($locale) === 3) {
                            if (!isset($fallbacks[$locale]) || !in_array($lcid, $fallbacks[$locale])) {
                                $fallbacks[$locale][] = $lcid;
                            }
                        }
                    } elseif ($language !== '' && in_array($language, $this->locales)) {
                        echo 'Valid language '.$language.' found !'.PHP_EOL;
                        if (!isset($fallbacks[$language]) || !in_array($lcid, $fallbacks[$language])) {
                            $fallbacks[$language][] = $lcid;
                        }
                    }
                }
            }

            ksort($mapped);
            ksort($fallbacks);

            $encoded = json_encode($datas, JSON_PRETTY_PRINT);
            if ($encoded === false) {
                $io->error('Failed to encode data as JSON');

                return Command::FAILURE;
            }

            $write = $this->writeDistantDatas($encoded, $io);
            if ($write > 0) {
                return $write;
            }
        }

        return Command::SUCCESS;
    }

    private function writeDistantDatas(string $json, SymfonyStyle $io): int
    {
        $io->text('Writing distant datas to local');
        $writeSuccess = file_put_contents(self::LOCAL_JSON_PATH, $json);
        if ($writeSuccess !== false) {
            $io->success('Done');

            return Command::SUCCESS;
        }

        $io->error('Error while trying to write local file to '.self::LOCAL_JSON_PATH);

        return Command::FAILURE;
    }

    private function downloadDistantJson(SymfonyStyle $io): int
    {
        $io->comment('Fetching json datas from '.self::DISTANT_JSON_URL.' ...');

        $contents = file_get_contents(self::DISTANT_JSON_URL);

        if ($contents === false || $contents === '') {
            $io->error('Error fetching distant datas');

            return Command::FAILURE;
        }

        if ($io->isVerbose()) {
            $io->comment('Distant datas fetched');
            $io->comment('Starting cleanup for locale not matching icu');
        }

        $this->json = $this->cleanupIcuNotFoundLocales($contents, $io);

        return Command::SUCCESS;
    }

    private function cleanupIcuNotFoundLocales(string $jsonDatas, SymfonyStyle $io): string
    {
        /** @var array<int, string> $datas */
        $datas = json_decode($jsonDatas, true);

        $formattedDatas = ['mapped' => [], 'fallbacks' => []];

        foreach ($datas as $key => $data) {
            if (!in_array($data, $this->locales) && $this->useFallbacks) {
                if ($io->isVeryVerbose()) {
                    echo 'Locale '.$data.' with lcid '.$key.' not found in icu list'.PHP_EOL;
                }

                $locale = trim(\Locale::lookup($this->locales, $data, true, 'NOT_FOUND') ?? '');

                if ($io->isVeryVerbose()) {
                    echo 'Testing if a fallback on locale '.$locale.' with lcid '.$key.' will be found in icu list'.PHP_EOL;
                }

                if (!in_array($locale, $this->locales)) {
                    $lang = explode('_', $data)[0];

                    if ($io->isVeryVerbose()) {
                        echo 'Testing if a fallback on language '.$lang.' with lcid '.$key.' will be found in icu list'.PHP_EOL;
                    }

                    if (!in_array($lang, $this->locales)) {
                        if ($io->isVerbose()) {
                            echo 'Ignoring Locale '.$data.' with lcid '.$key.', no fallback worked'.PHP_EOL;
                        }
                    } else {
                        if ($io->isVerbose()) {
                            echo 'Adding lcid '.$key.', to fallback by language '.$lang.PHP_EOL;
                        }
                        $formattedDatas['fallbacks'][$lang][] = $key;
                    }
                } else {
                    if ($io->isVerbose()) {
                        echo 'Adding lcid '.$key.', to fallback by language '.$locale.PHP_EOL;
                    }
                    $formattedDatas['fallbacks'][$locale][] = $key;
                }
            } else {
                $formattedDatas['mapped'][$key] = $data;
            }
        }

        $formattedDatas['mapped'] = array_flip($formattedDatas['mapped']);

        return (string) json_encode($formattedDatas, JSON_PRETTY_PRINT);
    }
}
