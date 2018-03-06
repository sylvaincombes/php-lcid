<?php

namespace SylvainCombes\Lcid\Command;

use SylvainCombes\Lcid\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Intl;

/**
 * Class GenerateDatasCommand
 *
 * @package SylvainCombes\Lcid\Command
 */
class GenerateDatasCommand extends Command
{
    /**
     * @var string
     */
    const DISTANT_JSON_URL = 'https://raw.githubusercontent.com/sindresorhus/lcid/master/lcid.json';

    /**
     * @var string
     */
    const LOCAL_JSON_PATH = __DIR__.'/../Resources/datas.json';

    /**
     * @var string
     */
    const LOCAL_MANUAL_DATAS_PATH = __DIR__.'/../Resources/datas-manual.php';

    /**
     * @var bool
     */
    private $getDistantDatas = true;

    /**
     * @var
     */
    private $json;

    /**
     * @var
     */
    private $locales;

    /**
     * @var
     */
    private $originalDistantJson;

    /**
     * @var bool
     */
    private $useFallbacks = true;

    /**
     * @var bool
     */
    private $useManualDatas = true;

    /**
     *
     */
    protected function configure()
    {
        $this->setName('lcid:generate-datas')
            ->setDescription('Generate lcid to iso locale datas building.')
            ->setHelp(
                'This command allows you to refresh datas using a distant json and local php datas to re-generate lcid datas'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Exception
     * @throws \TypeError
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->locales = Intl::getLanguageBundle()->getLocales();

        $io = new SymfonyStyle($input, $output);
        $io->title('LCID data builder');

        if ($this->getDistantDatas) {
            $download = $this->downloadDistantJson($io);
            if (is_int($download) && $download > 0) {
                return $download;
            }

            if (file_exists(self::LOCAL_JSON_PATH)) {
                if ($io->isVeryVerbose()) {
                    $io->text('Found local data file, start loading');
                }
                $existingJson = file_get_contents(self::LOCAL_JSON_PATH);

                if ($this->json !== $existingJson) {
                    $io->comment('Local and distant data are different, update local datas');
                    $write = $this->writeDistantDatas($this->json, $io);
                    if (is_int($write) && $write > 0) {
                        return $write;
                    }
                } else {
                    $io->comment('Datas are identical, no write');
                }
            } else {
                $io->warning('No local data file found.');
                $write = $this->writeDistantDatas($this->json, $io);
                if (is_int($write) && $write > 0) {
                    return $write;
                }
            }
        }

        if ($this->useManualDatas) {
            // PHASE 2 :: use complementary datas
            $old = include(self::LOCAL_MANUAL_DATAS_PATH);

            $finder = new Finder();
            $datas  = $finder->getAllDatas();

            foreach ($old as $item) {
                $mapped    = &$datas['mapped'];
                $fallbacks = &$datas['fallbacks'];

                $language = $item['language'];
                $locale   = isset($item['locale']) ? $item['locale'] : null;
                $lcid     = $item['lcid'];

                $mapped = array_flip($mapped);
                $found  = !empty($mapped[$lcid]);
                $mapped = array_flip($mapped);

                if (!$found) {
                    echo 'Lcid '.$lcid.' in old datas not existing in datas'.PHP_EOL;

                    if (!empty($locale) && in_array($locale, $this->locales)) {
                        echo 'Valid locale '.$locale.' found !'.PHP_EOL;

                        if (empty($mapped[$locale]) && (strlen($locale) == 5 || strlen($locale) == 6)) {
                            $mapped[$locale] = $lcid;

                            if (isset($fallbacks[$locale]) && in_array($lcid, $fallbacks[$locale])) {
                                $key = array_search($lcid, $fallbacks[$locale]);
                                array_splice($fallbacks[$locale], $key, 1);
                                if (count($fallbacks[$locale]) === 0) {
                                    unset($fallbacks[$locale]);
                                }
                            }

                            if (isset($fallbacks[$language]) && in_array($lcid, $fallbacks[$language])) {
                                $key = array_search($lcid, $fallbacks[$language]);
                                array_splice($fallbacks[$language], $key, 1);
                                if (count($fallbacks[$language]) === 0) {
                                    unset($fallbacks[$language]);
                                }
                            }
                        } elseif (!empty($mapped[$locale]) || (strlen($locale) == 2 || strlen($locale) == 3)) {
                            if (!isset($fallbacks[$locale]) || !in_array($lcid, $fallbacks[$locale])) {
                                $fallbacks[$locale][] = $lcid;
                            }
                        }
                    } elseif (!empty(($language)) && in_array($language, $this->locales)) {
                        echo 'Valid language '.$language.' found !'.PHP_EOL;
                        if (!isset($fallbacks[$language]) || !in_array(
                            $lcid,
                            $fallbacks[$language]
                        )
                        ) {
                            $fallbacks[$language][] = $lcid;
                        }
                    }
                }
            }

            ksort($mapped);
            ksort($fallbacks);

            $write = $this->writeDistantDatas(json_encode($datas, JSON_PRETTY_PRINT), $io);
            if (is_int($write) && $write > 0) {
                return $write;
            }
        }

        return 0;
    }

    /**
     * @param string       $json
     * @param SymfonyStyle $io
     *
     * @return int
     */
    private function writeDistantDatas($json, SymfonyStyle $io)
    {
        $io->text('Writing distant datas to local');
        $writeSuccess = file_put_contents(self::LOCAL_JSON_PATH, $json);
        if ($writeSuccess !== false) {
            $io->success('Done');

            return 0;
        } else {
            $io->error('Error while trying to write local file to '.self::LOCAL_JSON_PATH);

            return 1;
        }
    }

    /**
     * @param SymfonyStyle $io
     *
     * @return int
     */
    private function downloadDistantJson(SymfonyStyle $io)
    {
        $io->comment('Fetching json datas from '.self::DISTANT_JSON_URL.' ...');

        $this->originalDistantJson = file_get_contents(self::DISTANT_JSON_URL);

        if ($this->originalDistantJson) {
            if ($io->isVerbose()) {
                $io->comment('Distant datas fetched');
                $io->comment('Starting cleanup for locale not matching icu');
            }
            $this->json = $this->cleanupIcuNotFoundLocales($this->originalDistantJson, $io);
        } else {
            $io->error('Error fetching distant datas');

            return 1;
        }

        return 0;
    }

    /**
     * @param string       $jsonDatas
     * @param SymfonyStyle $io
     *
     * @return string
     */
    private function cleanupIcuNotFoundLocales($jsonDatas, SymfonyStyle $io)
    {
        $datas    = json_decode($jsonDatas, true);
        $libDatas = array_flip($datas);

        $formattedDatas = [];

        foreach ($libDatas as $key => $data) {
            if (!in_array($data, $this->locales) && $this->useFallbacks) {
                if ($io->isVeryVerbose()) {
                    echo 'Locale '.$data.' with lcid '.$key.' not found in icu list'.PHP_EOL;
                }

                // First fallback with intl
                $locale = \Locale::lookup($this->locales, $data, true, 'NOT_FOUND').PHP_EOL;

                if ($io->isVeryVerbose()) {
                    echo 'Testing if a fallback on locale '.$locale.' with lcid '.$key.' will be found in icu list'.PHP_EOL;
                }

                if (!in_array($locale, $this->locales)) {
                    // Second fallback by language
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

        return json_encode($formattedDatas, JSON_PRETTY_PRINT);
    }
}
