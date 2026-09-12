# MetaGer

[MetaGer](https://metager.de) has been Free Software under the GNU AGPL v3 since 16.08.2016, so that our strict protection of your data and your privacy
can be publicly verified, and so that you as a programmer can help make everything even better. Further background information can be found in the
<a href="http://heise.de/-3295586" target="_blank">Heise news ticker</a>.

## Dependencies
* composer (https://getcomposer.org/)
* php7.0
  * php7.0-mbstring
  * php7.0-dom
  * php7.0-xml
* sqlite3
* redis-server
* The Perl package: Lingua::Identify (http://search.cpan.org/~ambs/Lingua-Identify-0.56/lib/Lingua/Identify.pm)

## MetaGer too slow?
For MetaGer to become as fast as on our live server, a little configuration work is required. The reason the version after checkout is slower than normal is that the configured search engines are queried synchronously by default.
This means that when searching with 20 search engines, one search engine is queried after the other.
Parallel processing can be achieved with the help of Laravel's queue system ( https://laravel.com/docs/5.2/queues ).
By default, QUEUE_DRIVER=sync is set in the ".env" file.
On our servers we use QUEUE_DRIVER=redis and, with the help of Supervisor ( https://laravel.com/docs/5.2/queues#supervisor-configuration ), we have many queue:worker processes running that ensure parallel processing.

## Official Documentation

The documentation can be found in the wiki of the Gitlab project.

## Contributing

Thank you for considering contributing to MetaGer!
Unfortunately, we are not yet ready to accept changes from outside.
However, you are free to open a ticket.

## Security Vulnerabilities

If you find a security vulnerability or something seems insecure to you,
please do not hesitate to write a ticket or send an email to [office@suma-ev.de](mailto:office@suma-ev.de).

## Licenses

The MetaGer-specific code, unless otherwise noted, is licensed under the [AGPL License Version 3](https://www.gnu.org/licenses/agpl-3.0).

A list of the projects MetaGer is based on, and their licenses, can be found in the LICENSE file.
