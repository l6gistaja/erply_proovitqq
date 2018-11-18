# Purpose

1. Save products to the Erply database through [Erply's API](https://erply.com/api/). Check that product with the same name doesn't already exist.
1. Communicate with Erply API through some message broker.

# Setup

Project was developed in Debian 9.

1. Download project and copy it to your public web directory.
1. Install dependencies: ```apt-get install php7.0 php7.0-curl php7.0-bcmath php7.0-mbstring rabbitmq-server mysql-server mysql-client php-mysql```
1. Start servers: ```service rabbitmq-server start ; service mysql start```
1. Copy configuration file template from templates/ErplyConf.php to somewhere outside public web directory, change three dots (...) with your own specific values. Add path to this new file to the first require_once in dependencies.php.
1. Login to MySQL server and commit commands in file templates/database.sql
1. On console, go to project's directory and start RabbitMQ listener: ```php receive.php```
1. Open project's URL in your browser.

# Usage

1. To save product directly through Erply API, fill product name and press button "Add via API".
1. To save product through RabbitMQ, fill product name and press button "Add via Rabbit". Results can be viewed by pressing button "View Rabbit Log".


