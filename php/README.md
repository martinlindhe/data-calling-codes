## Example seeding your laravel app

Seed your table by doing something like:

```php
foreach (MartinLindhe\Data\CallingCodes::all() as $cc) {
    Country::create([
        'alpha3' => $cc->alpha3,
        'name' => $cc->name
    ]);
}
```
