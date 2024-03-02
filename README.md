MarvelsDB
=======

# Very quick guide on how to install a local copy

This guide assumes you know how to use the command-line and that your machine has php 7.4 and mysql 8 installed.

- install composer: https://getcomposer.org/download/
- git clone the repo and `cd` to it
- run `composer install` (at the end it will ask for the database configuration parameters)
- if `composer install` fails with version issues, you may need to run `composer self-update --1` to downgrade to composer version 1
- run `php bin/console doctrine:database:create` to create the database.
- run `php bin/console doctrine:schema:create` to create the database schema.
- git clone the card data from https://github.com/zzorba/marvelsdb-json-data
- run `php bin/console app:import:std path-to-marvelsdb-json-data/` pointing to where you cloned the json data (can be a relative path)
- run `php bin/console server:run`

Additional useful commands.
- run `composer install` to rebuild minified JS files after making changes to the raw files.
- run `php bin/console doctrine:schema:update --dump-sql` to view database schema changes.
- run `php bin/console doctrine:schema:update --force` to execute database schema changes.
