# Google Drive API Automaton

## Installation

PHP dependencies are managed via Composer and are committed into this
repository because they're deployed to the server via the repository.

This is only runnable on terminal.

#### Composer Installation
~~~
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
~~~

You can then install the project dependencies using the following command: 
~~~
composer install
~~~

#### Env File
Note: ID's can be seen at the URL.

~~~
MAIN_FOLDER_ID = ""
TEMPLATE_FOLDER_ID = ""
ARCHIVED_FOLDER_ID = ""
DRIVE_FOLDER_URL = "https://drive.google.com/drive/folders/"
~~~

#### Run script
If you still don't have a token, just run any of the two php script and it will return a link to add permission scope for the application. Copy and paste the verification code in the terminal.

To create project in google drive:
~~~
php create.php
~~~

To archive project in google drive:
~~~
php archive.php
~~~

**Enjoy !**