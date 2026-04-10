<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SylvainCombes\Lcid\Finder;
use Symfony\Component\Intl\Locales;

#[Group('lcid')]
class LcidTest extends TestCase
{
    private Finder $finder;
    private array $locales;

    #[Group('symfony-locale')]
    public function testIsoCodes(): void
    {
        $libDatas = $this->finder->getDatasReversed();

        foreach ($libDatas as $libData) {
            $this->assertTrue(in_array($libData, $this->locales), 'Language '.$libData.' not found in icu list');
        }
    }

    #[Group('lcid')]
    public function testFindByLcid(): void
    {
        $locale = $this->finder->findByLcid(1036);
        $this->assertEquals('fr_FR', $locale);
    }

    #[Group('lcid')]
    public function testFindByLcidWithFallback(): void
    {
        $locale = $this->finder->findByLcid(434341234);
        $this->assertNull($locale);

        $locale = $this->finder->findByLcidWithFallback(9225);
        $this->assertEquals('en', $locale);

        $locale = $this->finder->findByLcidWithFallback(1078);
        $this->assertEquals('af_ZA', $locale);

        $locale = $this->finder->findByLcidWithFallback(3082);
        $this->assertEquals('es_ES', $locale);

        $locale = $this->finder->findByLcidWithFallback(1034);
        $this->assertEquals('es_ES', $locale);
    }

    #[Group('lcid')]
    public function testNotFoundByLcid(): void
    {
        $locale = $this->finder->findByLcid(456789);
        $this->assertNull($locale);
    }

    #[Group('locale')]
    public function testFindOneByLocale(): void
    {
        $fr = $this->finder->findOneByLocale('fr_FR');
        $this->assertEquals(1036, $fr);

        $locale = $this->finder->findOneByLocale('ro');
        $this->assertEquals(1048, $locale);
    }

    #[Group('locale')]
    public function testFindByLocale(): void
    {
        $locale = $this->finder->findByLocale('fr');
        $this->assertIsArray($locale);
        $this->assertCount(2, $locale);
        $this->assertEquals([0 => 1036, 1 => 7180], $locale, 'LCID for fr should be 1036 and 7180');

        $locale = $this->finder->findByLocale('ru_RU');
        $this->assertIsArray($locale);
        $this->assertCount(1, $locale);

        $locale = $this->finder->findByLocale('efoijzefiozejf');
        $this->assertNull($locale);
    }

    #[Group('locale')]
    public function testNotFoundByLocale(): void
    {
        $locale = $this->finder->findOneByLocale('xx');
        $this->assertNull($locale);
    }

    #[Group('custom')]
    public function testCustomWrongTypeThrows(): void
    {
        $this->expectException(\TypeError::class);
        new Finder(666);
    }

    #[Group('custom')]
    public function testCustomNotFound(): void
    {
        $this->expectException(Exception::class);
        new Finder('./void.json');
    }

    #[Group('custom')]
    #[DoesNotPerformAssertions]
    public function testCustomValid1(): void
    {
        new Finder(__DIR__.'/custom-valid-datas-1.json');
    }

    #[Group('custom')]
    #[DoesNotPerformAssertions]
    public function testCustomValid2(): void
    {
        new Finder(__DIR__.'/custom-valid-datas-2.json');
    }

    #[Group('custom')]
    public function testCustomInvalid1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Finder(__DIR__.'/custom-invalid-datas.json');
    }

    #[Group('custom')]
    public function testCustomInvalid2(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Finder('/dev/null');
    }

    protected function setUp(): void
    {
        $this->finder = new Finder();
        $this->locales = Locales::getLocales();
    }
}
