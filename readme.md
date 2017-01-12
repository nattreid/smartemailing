# SmartEmailing pro Nette Framework

Nastavení v **config.neon**
```neon
extensions:
    smartEmailing: NAtrreid\SmartEmailing\DI\SmartEmailingExtension

smartEmailing:
    username: 'username@mail.com'
    key: 'apiKey'
    debug: true # default false
```

Použití

```php
/** @var NAttreid\SmartEmailing\Client @inject */
public $smartEmailing;

```
