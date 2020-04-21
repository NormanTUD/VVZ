#!/bin/bash

echo "This script installs VVZ. IT HAS ONLY BEEN TESTED ON DEBIAN"

echo "THIS DOES NOT QUITE WORK YET. DO IT MANUALLY"
exit 1

read -p "Where to put VVZ: " maindir
read -p "Name of the database: " dbname
read -p "DB Pass (user: root): " dbpass

mkdir -p $maindir

function run_code_on_db {
	echo "Running $1 on DB"
	mysql -hlocalhost -uroot -p$dbpass -e "$1"
}

echo "$dbpass" > /etc/vvzdbpw

sudo apt-get install -y apache2 php7.3 git php7.3-mysql mariadb-client mariadb-server

sudo systemctl restart apache2

cd ${maindir}
git clone --depth 1 https://github.com/NormanTUD/VVZ.git .

sed --in-place "s/PLACEHOLDERDBNAME/$dbname/" mysql.php

sudo systemctl restart mysql
run_code_on_db "UPDATE user SET Password=PASSWORD('$dbpass') where USER='root';"
run_code_on_db "FLUSH PRIVILEGES;"
sudo systemctl restart mysql
run_code_on_db "create database ${dbname}"

echo "Open the URL of this host in your browser to finish the setup"
