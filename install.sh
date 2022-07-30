#!/bin/bash

INSTALL_PATH=/var/www/html

sudo apt-get update
sudo apt-get install whiptail sudo -y

set -x

function apt_get_upgrade {
	sudo apt-get upgrade -y
}

function install_apache {
	sudo apt-get install curl unzip ca-certificates apt-transport-https lsb-release gnupg apache2 -y
}

function install_php {
	wget -q https://packages.sury.org/php/apt.gpg -O- | apt-key add - && \
	echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
	sudo apt-get update && \
	sudo apt-get install php8.1 php8.1-cli php8.1-common php8.1-curl php8.1-gd php8.1-intl php8.1-mbstring php8.1-mysql php8.1-opcache php8.1-readline php8.1-xml php8.1-xsl php8.1-zip php8.1-bz2 libapache2-mod-php8.1 -y
}

function install_mariadb {
	sudo apt install mariadb-server mariadb-client -y
}

function setup_mariadb {
	mysql_secure_installation
}

function custompath {
	INSTALL_PATH=$(whiptail --inputbox "What is the path where the VVZ should be installed to?" 8 39 "$INSTALL_PATH" --title "Custom install path" 3>&1 1>&2 2>&3)
}

function copy_to_path {
	sudo mv $INSTALL_PATH /var/www/old_html
	sudo mkdir -p $INSTALL_PATH
	sudo cp -r * $INSTALL_PATH
}

function new_setup {
	touch $INSTALL_PATH/new_setup
}

eval `resize`
WHAT_TO_DO=$(
	whiptail --title "What to do?" --checklist \
	"Chose what you want to do" $LINES $COLUMNS $(( $LINES - 8 )) \
	"apt_get_upgrade" "run apt-get upgrade" ON \
	"install_apache" "Install Apache2" ON \
	"install_php" "Install PHP" ON \
	"install_mariadb" "Install MariaDB" ON \
	"setup_mariadb" "Setup MariaDB" ON \
	"custompath" "Set custom install path?" OFF \
	"copy_to_path" "Copy files to the apache path" ON \
	"new_setup" "Create new_setup file" ON \
	3>&1 1>&2 2>&3
)


for task in $WHAT_TO_DO; do
	eval $task
done

exit


PASSWORD=$(whiptail --passwordbox "What is your DB password (specified earlier when you installed mysql)" 8 78 --title "DB-password" 3>&1 1>&2 2>&3)
echo "$PASSWORD" > /etc/vvzdbpw

set +x
mysql -uroot -p$PASSWORD -e "create database uni"
set -x
