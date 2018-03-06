<?php

use PHPUnit\Framework\TestCase;
use SylvainCombes\Lcid\Finder;

/**
 * Class LcidTest
 *
 * @group lcid
 */
class LcidTest extends TestCase
{
    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var
     */
    private $locales;

    /**
     * Test that all iso locale in datas matches symfony icu locale
     *
     * @group symfony-locale
     */
    public function testIsoCodes()
    {
        $libDatas = $this->finder->getDatasReversed();

        foreach ($libDatas as $libData) {
            $this->assertTrue(in_array($libData, $this->locales), 'Language '.$libData.' not found in icu list');
        }

    }

    /**
     * @group lcid
     */
    public function testFindByLcid()
    {
        $locale = $this->finder->findByLcid(1036);
        $this->assertEquals('fr_FR', $locale);
    }

    /**
     * @group lcid
     */
    public function testFindByLcidWithFallback()
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

    /**
     * @group lcid
     */
    public function testNotFoundByLcid()
    {
        $locale = $this->finder->findByLcid(456789);
        $this->assertNull($locale);
    }

    /**
     * @group locale
     */
    public function testFindOneByLocale()
    {
        $fr = $this->finder->findOneByLocale('fr_FR');
        $this->assertEquals(1036, $fr);

        $locale = $this->finder->findOneByLocale('ro');
        $this->assertEquals(1048, $locale);
    }

    /**
     * @group locale
     */
    public function testFindByLocale()
    {
        $locale = $this->finder->findByLocale('fr');
        $this->assertTrue(is_array($locale));
        $this->assertCount(8, $locale);

        $locale = $this->finder->findByLocale('ru_RU');
        $this->assertTrue(is_array($locale));
        $this->assertCount(1, $locale);

        $locale = $this->finder->findByLocale('efoijzefiozejf');
        $this->assertNull($locale);
    }


    /**
     * @group locale
     */
    public function testNotFoundByLocale()
    {
        $locale = $this->finder->findOneByLocale('xx');
        $this->assertNull($locale);
    }

    /**
     * @group custom
     * @doesNotPerformAssertions
     */
    public function testCustomNotStringIgnored()
    {
        $f = new Finder(666);
    }

    /**
     * @group custom
     * @expectedException Exception
     */
    public function testCustomNotFound()
    {
        $f = new Finder('./void.json');
    }

    /**
     * @group custom
     * @doesNotPerformAssertions
     */
    public function testCustomValid1()
    {
        $f = new Finder(__DIR__.'/custom-valid-datas-1.json');
    }

    /**
     * @group custom
     * @doesNotPerformAssertions
     */
    public function testCustomValid2()
    {
        $f = new Finder(__DIR__.'/custom-valid-datas-2.json');
    }

    /**
     * @group custom
     * @expectedException \InvalidArgumentException
     */
    public function testCustomInvalid1()
    {
        $f = new Finder(__DIR__.'/custom-invalid-datas.json');
    }

    /**
     * @group custom
     * @expectedException \InvalidArgumentException
     */
    public function testCustomInvalid2()
    {
        $f = new Finder('/dev/null');
    }


    /**
     * @throws Exception
     */
    protected function setUp()
    {
        $this->finder  = new Finder();
        $this->locales = \Symfony\Component\Intl\Intl::getLanguageBundle()->getLocales();
    }
}
