# ZestMoney
ZestMoney Magento 2 Payment extension

Create a folder structure like :- 

app
  - code
       - Zest
       		- ZestMoney
       				  - Block
       				  - Controller
       				  - etc
       				  - Helper
       				  - Model
       				  - Plugin
       				  - Setup
       				  - view
       				  - composer.json
       				  - registration.php
----------------------------------------

Run Commands one by one :- 

rm -rf var/cache/* var/page_cache/* var/generation/* var/view_preprocessed/* pub/static/frontend/* pub/static/adminhtml/* pub/static/_requirejs/* var/di/*

php -f bin/magento cache:flush

php -f bin/magento setup:upgrade

php -f bin/magento setup:static-content:deploy

