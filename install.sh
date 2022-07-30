#!/bin/bash

set -x

sudo apt-get update
sudo apt-get upgrade
apt-get install nano curl unzip ca-certificates apt-transport-https lsb-release gnupg apache2 whiptail -y && \
	wget -q https://packages.sury.org/php/apt.gpg -O- | apt-key add - && \
	echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
apt-get update && \
	apt-get install php8.1 php8.1-cli php8.1-common php8.1-curl php8.1-gd php8.1-intl php8.1-mbstring php8.1-mysql php8.1-opcache php8.1-readline php8.1-xml php8.1-xsl php8.1-zip php8.1-bz2 libapache2-mod-php8.1 -y

apt install mariadb-server mariadb-client -y
mysql_secure_installation

sudo mv /var/www/html /var/www/old_html
sudo mkdir -p /var/www/html
sudo cp -r * /var/www/html/

PASSWORD=$(whiptail --passwordbox "What is your DB password (specified earlier)" 8 78 --title "DB-password" 3>&1 1>&2 2>&3)
echo $PASSWORD > /etc/vvzdbpw

touch /var/www/html/new_setup

set +x
mysql -uroot -p$PASSWORD -e "create database uni"
set -x
