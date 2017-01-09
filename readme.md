# SmartEmailing pro Nette Framework

Nastavení v **config.neon**
```neon
extensions:
    smartEmailing: NAtrreid\SmartEmailing\DI\SmartEmailingExtension

smartEmailing:
    username: 'username@mail.com'
    key: 'apiKey'
```

Použití

```php
/** @var NAtrreid\SmartEmailing\Client @inject */
public $smartEmailing;

```
