# php-lcid

Php library trying to get a valid Unicode Locale for a given Microsoft LCID.

Requires PHP 8.4+.

## Installation

```
composer require sylvaincombes/php-lcid
```

## Usage

### Basic usage

```php
<?php

require '../vendor/autoload.php';

use SylvainCombes\Lcid\Finder;

$finder = new Finder();

// NB : find methods returns null if not found


// 1. Finding a locale by lcid

// You should always use this method (use fallbacks if no 'perfect match' to try to answer something to your query instead of failing if no 'perfect match')
$locale = $finder->findByLcidWithFallback(8201);
// Should give you $locale == 'en'

// You can avoid searching in fallbacks if you really want to
$locale = $finder->findByLcid(1036);
// Should give you $locale == 'fr_FR'


// 2. Finding lcid(s) by locale
$localeLcid = $finder->findOneByLocale('fr');
// Should give you $localeLcid == 1036
// NB : this method is limited as it will return only 'perfect match'

$localeLcid = $finder->findByLocale('fr');
/**
 * Get an array or null in response (search everywhere)
 * First result in array should be the 'best' match
 *
 * Return example :
 * array(2) {
 *     [0] => int(1036)
 *     [1] => int(7180)
 *   }
 *
 */
```

### "Advanced" usage

#### Using custom datas

```php
<?php

require '../vendor/autoload.php';

use SylvainCombes\Lcid\Finder;

// Pass your json file in constructor
// Your json must validate against the json schema, see src/SylvainCombes/Lcid/Resources/datas-schema.json
$finder = new Finder('src/Me/Resources/my-lcids.json');

// ...


```

## Why this library

One day at work I needed to consume some api service who exposed some microsoft sharepoint datas. Language information were only given by microsoft LCID, and I needed to have them in an exploitable icu standard locale. 

After searching a long time I discovered that this "simple" task was not really easy as I found no exhaustive complete list or existing php project answering my need. Plus I learned that depending on the php-intl extension some locale doesn't match in the end.

So I hacked with some datas sources founded on the web and some other libraries and tools, hoping to never do this very boring task again and maybe helping another dev :)

**Fun fact**

> LCIDs were deprecated with Windows Vista and Microsoft recommends that developers use [BCP47](https://tools.ietf.org/html/bcp47) style tags instead (uloc_toLanguageTag).

## Contributing

All contributions are very welcome.

### Updating datas list

There is a command who build the datas, what it does is :

1. Fetching a json from [sindresorhus/lcid](https://raw.githubusercontent.com/sindresorhus/lcid/main/lcid.json) - **this is our starting non editable datas point**
1. Check if locales are found in symfony/intl locales list
    - If yes keep them
    - If no trying to find if a locale or language is matching
        - If yes adding to the fallback list
        - If no remove this entry
1. Load the php array in file [src/SylvainCombes/Lcid/Resources/datas-manual.php](src/SylvainCombes/Lcid/Resources/datas-manual.php) if some lcid codes are not in the datas do it's best with lcid, language, locale fields to add them in the list or in the fallback entries.

So to edit / delete / add some datas not present in the base json, you should edit [src/SylvainCombes/Lcid/Resources/datas-manual.php](src/SylvainCombes/Lcid/Resources/datas-manual.php) and re-launch the command :

```
php bin/console lcid:generate-datas -v
```

And the [datas.json](src/SylvainCombes/Lcid/Resources/datas.json) will be updated.

> NB : It also mean you can't override datas provided by the base json grabbed from [sindresorhus/lcid](https://raw.githubusercontent.com/sindresorhus/lcid/main/lcid.json)

### Testing

Launching the project tests :

```
composer test
```

### Static analysis

```
composer phpstan
composer psalm
```

### Lint php

```
composer lint
```

You can also "autofix" :

```
composer fix
```

### Run all checks

```
composer check
```

## References

### ICU Locale

- [ICU Locale](http://userguide.icu-project.org/locale#TOC-Language-code)

### LCID

- [sindresorhus/lcid](https://github.com/sindresorhus/lcid) - Based on the [mapping](https://github.com/python/cpython/blob/be2a1a76fa43bb1ea1b3577bb5bdd506a2e90e37/Lib/locale.py#L1395-L1604) used in the Python standard library.
- [Locale codes](https://www.science.co.il/language/Locale-codes.php)
- [Microsoft Locale ID Values](https://msdn.microsoft.com/en-us/library/ms912047(WinEmbedded.10).aspx)

### BCP 47

- [IANA Registry](https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry)
- [IANA RFC](http://www.rfc-editor.org/rfc/bcp/bcp47.txt)

### Formats

- [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
- [ISO15924](http://unicode.org/iso15924/iso15924-codes.html)
- [ISO_3166-1](https://en.wikipedia.org/wiki/ISO_3166-1#Current_codes)


### Libraries used

#### Required

- [opis/json-schema](https://opis.io/json-schema) - *used to validate custom user json datas against a json schema*

#### Used in dev

- [phpunit](https://phpunit.de/) - *used for testing*
- [symfony/intl](https://symfony.com/doc/current/components/intl.html) - *used to check if locales in datas sources match the ICU locales list*
- [symfony/console](https://symfony.com/doc/current/components/console.html) - *used for the console command building datas*
- [php-cs-fixer](https://cs.symfony.com/) - *code style enforcement*
- [phpstan](https://phpstan.org/) - *static analysis at level 10*
- [psalm](https://psalm.dev/) - *static analysis at error level 1*


