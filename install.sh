#!/bin/bash

echo "This script installs VVZ. IT HAS ONLY BEEN TESTED ON DEBIAN"

read -p "Where to put VVZ: " maindir
read -p "Name of the database: " dbname
read -p "DB Pass (user: root): " dbpass

mkdir -p $maindir

echo "$dbpass" > /etc/vvzdbpw

sudo apt-get install -y apache2 php7.3 git php7.3-mysql mariadb-client mariadb-server

sudo systemctl restart apache2

cd ${maindir}
git clone --depth 1 https://github.com/NormanTUD/VVZ.git .

sed --in-place "s/PLACEHOLDERDBNAME/$dbname/" mysql.php

mysql -hlocalhost -uroot -p$dbpass -e "create database ${dbname}"

echo "Open the URL of this host in your browser to finish the setup"
