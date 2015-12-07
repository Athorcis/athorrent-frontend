[![Code Climate](https://codeclimate.com/github/Athorcis/athorrent-frontend/badges/gpa.svg)](https://codeclimate.com/github/Athorcis/athorrent-frontend)

## Getting started

``` sh
composer update -o
npm install
node_modules/.bin/bower install
node utils/build.js
```

Create a .htaccess file in the web directory
``` htaccess
Options +FollowSymLinks

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^ index.php [QSA,L]
</IfModule>
```

Create a config.php file in the app directory
``` php
<?php

// whether debug is enable or not
define('DEBUG', true);

// user credentials usable when debug is enable
// convenient when there is no user in the database yet
define('DEBUG_USERNAME', 'root');
define('DEBUG_PASSWORD', 'password');

define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'athorrent');

// salt used to generate remember me cookies
define('REMEMBER_ME_KEY', 'h1!6Kb1d6;c9RU2k4,K]5lf9w40');

// salt used to generate CSRF tokens
define('CSRF_SALT','jfgjgkofdgçà');

// hostname for static resources
define('STATIC_HOST', $_SERVER['HTTP_HOST']);

// Google Analytics
define('GA_ENABLED', true);
define('GA_ID', 'UA-67608080-1');
define('GA_DOMAIN', 'seedbox.athorcis.ovh');

?>
```

Import utils/athorrent.sql in your database

Build the [backend](https://github.com/Athorcis/athorrent-backend)

Create a bin directory and put the backend in it.
