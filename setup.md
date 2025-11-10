## This is a project setup instructions

1. Install composer

```bash
composer install
```

2. generate application key

```bash
php artisan key:generate
```

3. config the env variables and

```bash
php artisan migrate
```

4. you can select application features inside `config/features.php`

5. create the roles and persmissions in the route and uncomment this lines once and refresh after that comment them back

```php
use Spatie\Permission\Models\Role;
$role = Role::create(['name' => 'admin']);
$role = Role::create(['name' => 'user']);
$role = Role::create(['name' => 'manager']);
$role = Role::create(['name' => 'staff']);
```

6. assign admin access to user id 1

```bash
php artisan tinker
```

then run

```php
$user = \App\Models\User::find(1);
$user->assignRole('admin');
```
