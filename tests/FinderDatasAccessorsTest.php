<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SylvainCombes\Lcid\Finder;
use SylvainCombes\Lcid\LanguageMatchDefaultCountry;

#[Group('datas')]
class FinderDatasAccessorsTest extends TestCase
{
    private Finder $finderWithFallbacks;
    private Finder $finderWithoutFallbacks;

    #[Group('datas')]
    public function testGetDatasMapsLocaleToLcid(): void
    {
        $datas = $this->finderWithFallbacks->getDatas();

        $this->assertIsArray($datas);
        $this->assertSame(1036, $datas['fr_FR']);
        $this->assertSame(1033, $datas['en_US']);
    }

    #[Group('datas')]
    public function testGetDatasReversedMapsLcidToLocale(): void
    {
        $reversed = $this->finderWithFallbacks->getDatasReversed();

        $this->assertSame('fr_FR', $reversed[1036]);
        $this->assertSame('en_US', $reversed[1033]);
    }

    #[Group('datas')]
    public function testGetFallbackDatas(): void
    {
        $fallbacks = $this->finderWithFallbacks->getFallbackDatas();

        $this->assertIsArray($fallbacks);
        $this->assertSame([9225], $fallbacks['en']);
        $this->assertSame([4, 31748], $fallbacks['zh']);
    }

    #[Group('datas')]
    public function testGetFallbackDatasIsNullWhenDataFileHasNone(): void
    {
        $this->assertNull($this->finderWithoutFallbacks->getFallbackDatas());
        $this->assertSame('null', $this->finderWithoutFallbacks->getFallbackDatasAsJson());
    }

    #[Group('datas')]
    public function testGetAllDatasExposesMappedAndFallbacks(): void
    {
        $datas = $this->finderWithFallbacks->getAllDatas();

        $this->assertSame(1036, $datas['mapped']['fr_FR']);
        $this->assertSame([9225], $datas['fallbacks']['en']);
    }

    #[Group('datas')]
    public function testGetAllDatasReversed(): void
    {
        $reversed = $this->finderWithFallbacks->getAllDatasReversed();

        $this->assertSame('fr_FR', $reversed['mapped'][1036]);
        $this->assertSame([9225], $reversed['fallbacks']['en']);
    }

    #[Group('datas')]
    public function testJsonAccessorsMatchArrayAccessors(): void
    {
        $finder = $this->finderWithFallbacks;

        $this->assertSame($finder->getDatas(), json_decode($finder->getDatasAsJson(), true));
        $this->assertSame($finder->getFallbackDatas(), json_decode($finder->getFallbackDatasAsJson(), true));
        $this->assertSame($finder->getAllDatas(), json_decode($finder->getAllDatasAsJson(), true));
        $this->assertSame($finder->getAllDatasReversed(), json_decode($finder->getAllDatasReversedAsJson(), true));
        $this->assertEquals($finder->getDatasReversed(), json_decode($finder->getDatasReversedAsJson(), true));
    }

    #[Group('locale')]
    public function testFindByLocaleReturnsMappedLcidFirstThenFallbacks(): void
    {
        $this->assertSame([1033, 9225], $this->finderWithFallbacks->findByLocale('en'));
    }

    #[Group('locale')]
    public function testFindByLocaleWithoutFallbacksReturnsOnlyMappedLcid(): void
    {
        $this->assertSame([1036], $this->finderWithoutFallbacks->findByLocale('fr_FR'));
        $this->assertSame([1033], $this->finderWithoutFallbacks->findByLocale('en'));
    }

    #[Group('locale')]
    public function testFindByLocaleFindsFallbackOnlyLocale(): void
    {
        $this->assertSame([1107], $this->finderWithFallbacks->findByLocale('km'));
        $this->assertSame([2052, 4, 31748], $this->finderWithFallbacks->findByLocale('zh'));
    }

    #[Group('lcid')]
    public function testFindByLcidWithFallbackWithoutFallbacksReturnsNullForUnknownLcid(): void
    {
        $this->assertNull($this->finderWithoutFallbacks->findByLcidWithFallback(9225));
    }

    #[Group('lcid')]
    public function testFindByLcidDoesNotUseFallbacks(): void
    {
        $this->assertNull($this->finderWithFallbacks->findByLcid(9225));
        $this->assertSame('en', $this->finderWithFallbacks->findByLcidWithFallback(9225));
    }

    #[Group('default-country')]
    public function testLanguageMatchDefaultCountry(): void
    {
        $this->assertSame('fr_FR', LanguageMatchDefaultCountry::match('fr'));
        $this->assertSame('en_US', LanguageMatchDefaultCountry::match('en'));
        $this->assertSame('de_DE', LanguageMatchDefaultCountry::match('de'));
    }

    #[Group('default-country')]
    public function testLanguageMatchDefaultCountryReturnsInputWhenUnknown(): void
    {
        $this->assertSame('xx', LanguageMatchDefaultCountry::match('xx'));
    }

    protected function setUp(): void
    {
        $this->finderWithFallbacks = new Finder(__DIR__.'/custom-valid-datas-1.json');
        $this->finderWithoutFallbacks = new Finder(__DIR__.'/custom-valid-datas-2.json');
    }
}
