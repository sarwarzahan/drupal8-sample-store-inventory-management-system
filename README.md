## System Requirements
Store Management
Hackdonalds has chain of stores (approx 800 in total) distributed across the
country. The store carries just one product - Bighack. Managing inventory levels has
been challenging as there is no central system to track inventory. Hackdonalds
would like to build a system that allows for the following:
1. Head office users can add, remove stores.
2. Head office users can create users (store owners, head office users).
3. Individual store owners can login and increase/reduce inventory levels as
stock levels change.
4. Head office users can view inventory levels of any store.
5. Individual store users can only see inventory levels of their own store
6. Individual store users can create users, but these users can only view
inventory of their local store.
7. Head office users are not allowed to change inventory levels.

## Usage

First you need to [install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The instructions below refer to the [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).
You might need to replace `composer` with `php composer.phar` (or similar) 
for your setup.

## Clone the repository in your local machine or server's web root

* git clone https://github.com/sarwarzahan/drupal8-sample-store-inventory-management-system.git
* go inside drupal8-sample-store-inventory-management-system folder

After that you can create the project using:

```
composer install
```

## Install drupal
* Now access project url in your browser, drupal new installation page will show.
* Select hackdonalds install profile and follow the steps on the screen.
