## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Instalaion
## Requriement ##

# PHP 7.4.^ https://www.php.net/

# Composer https://getcomposer.org/

# Xamp for DB https://www.apachefriends.org/index.html
---------------------------

Affter you clone the project
```sh
cd sso-gateway-sit-ldap
```

--For first time--
### `composer install`
### `composer dump-autoload`

--Run Project--
### `php artisan serve -p 8000`

--For migrate--
### `php artisan migrate:fresh --seed`

Setup DB
- You need to run xamp or Database manager
- Go to Create your DB in sugest should naming `sso`
- And then run Migrate

Setup .env
- Copy .env.example to .env
- Config your ENVIROMENT
