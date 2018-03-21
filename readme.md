# SmartEmailing API pro Nette Framework

Nastavení v **config.neon**
```neon
extensions:
    smartEmailing: NAttreid\SmartEmailing\DI\SmartEmailingExtension

smartEmailing:
    username: 'username@mail.com'
    apiKey: 'apiKey'
    listId: 3 # vychozi seznam pro ukladani kontaktu
    debug: true # default false
```

Použití

```php
/** @var NAttreid\SmartEmailing\SmartEmailingClient @inject */
public $smartEmailing;

```
