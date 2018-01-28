
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://travis-ci.org/czim/laravel-cms-auth.svg?branch=master)](https://travis-ci.org/czim/laravel-cms-auth)
[![Coverage Status](https://coveralls.io/repos/github/czim/laravel-cms-auth/badge.svg?branch=master)](https://coveralls.io/github/czim/laravel-cms-auth?branch=master)

# CMS for Laravel - Auth Component

Authentication component for the CMS.

This excludes (optional) API/OAuth authentication support.
If you intend to access the CMS through its API, check out the [czim/laravel-cms-auth-api component](https://github.com/czim/laravel-cms-auth-api) as well.

## Version Compatibility

 Laravel             | Package 
:--------------------|:--------
 5.3.x               | 1.3.x
 5.4.x               | 1.4.x
 5.5.x               | 1.4.3+

## Commands

Users may be created on the fly by using the `cms:user:create` command.

Use `php artisan help cms:user:create` for more information.

Deleting users may be done through the command line as well, by using `cms:user:delete`.


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/laravel-cms-auth.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/laravel-cms-auth.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/laravel-cms-auth
[link-downloads]: https://packagist.org/packages/czim/laravel-cms-auth
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
