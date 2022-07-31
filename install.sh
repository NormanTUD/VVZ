#!/bin/bash

if [ "$EUID" -ne 0 ]; then
	echo "Please run as root"
	exit
fi

INSTALL_PATH=/var/www/html
INSTITUT_NAME="Institut für Philosophie"

sudo apt-get update
sudo apt-get install xterm whiptail sudo curl git -y

eval `resize`

set -x

INSTALL_PATH=$(whiptail --inputbox "What is the path where the VVZ should be installed to?" $LINES $COLUMNS $(( $LINES - 8 )) "$INSTALL_PATH" --title "Custom install path" 3>&1 1>&2 2>&3)

if [ -d "$INSTALL_PATH" ]; then
	MOVE_TO=$INSTALL_PATH
	i=0
	while [ -d "$MOVE_TO" ]; do
		i=$((i+1))
		MOVE_TO=${MOVE_TO}_${i}
	done

	mv "$INSTALL_PATH" "$MOVE_TO"
fi

cd $INSTALL_PATH

git clone --depth 1 https://github.com/NormanTUD/VVZ.git .

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
	#mysql_secure_installation

	mysql -u root <<-EOF
UPDATE mysql.user SET Password=PASSWORD('$PASSWORD') WHERE User='root';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.db WHERE Db='test' OR Db='test_%';
FLUSH PRIVILEGES;
EOF
}

function copy_to_path {
	sudo mv $INSTALL_PATH /var/www/old_html
	sudo mkdir -p $INSTALL_PATH
	sudo cp -r * $INSTALL_PATH
}

function new_setup {
	touch $INSTALL_PATH/new_setup
}

function create_institut {
	INSTITUT_NAME=$(whiptail --inputbox "Initial Institut?" $LINES $COLUMNS $(( $LINES - 8 )) "$INSTITUT_NAME" --title "Name of the Default Institut" 3>&1 1>&2 2>&3)
	echo "$INSTITUT_NAME" > /etc/default_institut_name
}

PASSWORD=$(whiptail --passwordbox "What is your DB password" $LINES $COLUMNS $(( $LINES - 8 )) --title "DB-password" 3>&1 1>&2 2>&3)
echo "$PASSWORD" > /etc/vvzdbpw

WHAT_TO_DO=$(
	whiptail --title "What to do?" --checklist \
	"Chose what you want to do" $LINES $COLUMNS $(( $LINES - 8 )) \
	"apt_get_upgrade" "run apt-get upgrade" ON \
	"install_apache" "Install Apache2" ON \
	"install_php" "Install PHP" ON \
	"install_mariadb" "Install MariaDB" ON \
	"setup_mariadb" "Setup MariaDB" ON \
	"copy_to_path" "Copy files to the apache path" ON \
	"new_setup" "Create new_setup file" ON \
	"create_institut" "Create a default Institut" ON \
	3>&1 1>&2 2>&3
)


for task in $WHAT_TO_DO; do
	eval $task
done

LOCAL_IP=$(ip -o route get to 8.8.8.8 | sed -n 's/.*src \([0-9.]\+\).*/\1/p')

ADMIN_USERNAME=$(whiptail --inputbox "Admin-Username" $LINES $COLUMNS $(( $LINES - 8 )) "Admin" --title "Admin-Username" 3>&1 1>&2 2>&3)
ADMIN_PASSWORD=$(whiptail --passwordbox "What should be the admin password=" $LINES $COLUMNS $(( $LINES - 8 )) --title "Admin-password" 3>&1 1>&2 2>&3)

curl "http://$LOCAL_IP/" --data-raw "username=$ADMIN_USERNAME&password=$ADMIN_PASSWORD"

rm $INSTALL_PATH/new_setup

whiptail --title "Installer" --msgbox "Installation done!" $LINES $COLUMNS $(( $LINES - 8 ))
