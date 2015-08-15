## Example seeding your laravel app

Seed your table by doing something like:

```php
foreach (MartinLindhe\DataCountries\Reader::asObjectList() as $o) {
    Country::create([
        'alpha3' => $o->alpha3,
        'name' => $o->name
    ]);
}
```
