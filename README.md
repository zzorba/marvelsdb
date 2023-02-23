MarvelsDB
=======

# Very quick guide on how to install a local copy

This guide assumes you know how to use the command-line and that your machine has php and mysql installed.

- install composer: https://getcomposer.org/download/
- clone the repo somewhere
- cd to it
- run `composer install` (at the end it will ask for the database configuration parameters)
- if `composer install` fails with version issues, you may need to run `composer self-update --1` to downgrade to composer version 1
- run `php bin/console doctrine:database:create`
- run `php bin/console doctrine:schema:create`
- checkout the card data from https://github.com/zzorba/marvelsdb-json-data
- run `php bin/console app:import:std path-to-marvelsdb-json-data/` pointing to where you checked out the json data
- run `php bin/console server:run`
