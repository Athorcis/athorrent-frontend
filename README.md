[![Code Climate](https://codeclimate.com/github/Athorcis/athorrent-frontend/badges/gpa.svg)](https://codeclimate.com/github/Athorcis/athorrent-frontend)

## Getting started

Install the following prerequesites
- Apache (or any webserver)
- PHP and extensions mysql, pdo, apcu (optional)
- Mysql Server
- NodeJS & NPM

Then run
``` sh
git clone https://github.com/Athorcis/athorrent-frontend athorrent
cd athorrent
./install.sh <db_username> <db_password> <seedbox_username> <seedbox_password>
```

If you use Apache, create a .htaccess file in the web directory
``` htaccess

Options +FollowSymLinks

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^ index.php [QSA,L]
</IfModule>
```

If you don't you might find an anwser [here] (http://silex.sensiolabs.org/doc/master/web_servers.html)

Create a bin directory and put the backend binary in it.
To build the backend see [athorrent-backend] (https://github.com/Athorcis/athorrent-backend)

And make sure the athorrent directory and all its subdirectories and files are owned by the user who run the webserver.
