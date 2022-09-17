#!/bin/bash

if [ "$EUID" -ne 0 ]; then
	echo "Please run as root"
	exit
fi

if ! command -v apt 2>&1 > /dev/null; then
	echo "Installer can only be run on Debian based system"
	exit
fi

INSTALL_PATH=/var/www/html

apt-get update
apt-get install xterm whiptail curl git etckeeper ntpdate -y

git config --global credential.helper store

eval `resize`

INSTALL_PATH=$(whiptail --inputbox "What is the path where the VVZ should be installed to?" $LINES $COLUMNS "$INSTALL_PATH" --title "Custom install path" 3>&1 1>&2 2>&3)
if [ $? == 1 ]; then
    echo "User selected Cancel."
    exit
fi

while [[ -z "$INSTALL_PATH" ]]; do
	INSTALL_PATH=$(whiptail --inputbox "What is the path where the VVZ should be installed to?" $LINES $COLUMNS "$INSTALL_PATH" --title "Custom install path" 3>&1 1>&2 2>&3)
	if [ $? == 1 ]; then
	    echo "User selected Cancel."
	    exit
	fi
done

mkdir -p $INSTALL_PATH

PASSWORD=""
while [[ -z "$PASSWORD" ]]; do
	PASSWORD=$(whiptail --passwordbox "What is your DB password" $LINES $COLUMNS --title "DB-password" 3>&1 1>&2 2>&3)
	if [ $? == 1 ]; then
	    echo "User selected Cancel."
	    exit
	fi
done

cd $INSTALL_PATH
git clone --depth 1 https://github.com/NormanTUD/VVZ.git .
git config --global user.name "$(hostname)"
git config --global user.email "kochnorman@rocketmail.com"
git config pull.rebase false
cd -


function apt_get_upgrade {
	apt-get upgrade -y
}

function install_apache {
	apt-get install curl unzip ca-certificates apt-transport-https lsb-release gnupg apache2 -y
}

function install_php {
	wget -q https://packages.sury.org/php/apt.gpg -O- | apt-key add - && \
	echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list
	apt-get update && \
	apt-get install php8.1 php8.1-cli php8.1-common php8.1-curl php8.1-gd php8.1-intl php8.1-mbstring php8.1-mysql php8.1-opcache php8.1-readline php8.1-xml php8.1-xsl php8.1-zip php8.1-bz2 libapache2-mod-php8.1 php-bcmath -y
}

function install_mariadb {
	apt install mariadb-server mariadb-client -y
}

function write_config_file {
	PORT=$(whiptail --inputbox "Port to run on (if 80, which is the default, no new file will be written and the default file will be used.)" $LINES $COLUMNS "80" --title "Custom port" 3>&1 1>&2 2>&3)

	if [[ "$PORT" -eq "80" ]]; then
		echo "No new file will be written";
	else
		cd /etc
		git add .
		git commit -am "Before modifying apache config"
		cd -

		i="001"
		config_file="/etc/apache2/sites-enabled/$i-default.conf"
		while [ -f $config_file ]; do
			config_file="/etc/apache2/sites-enabled/$i-default.conf"
			i=$(printf "%03d" $((i+1)))
		done

		echo "Listen $PORT
<VirtualHost *:$PORT>
	ServerAdmin webmaster@localhost
	DocumentRoot /var/www/html
	ErrorLog \${APACHE_LOG_DIR}/error.log
	CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
" > $config_file

		service apache2 restart
	fi
}

function setup_mariadb {
	mysql -u root <<-EOF
SET PASSWORD FOR 'root'@'localhost' = PASSWORD('$PASSWORD');
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.db WHERE Db='test' OR Db='test_%';
FLUSH PRIVILEGES;
EOF
}

echo "$PASSWORD" > /etc/vvzdbpw

WHAT_TO_DO=$(
	whiptail --title "What to do?" --checklist \
	"Chose what you want to do" $LINES $COLUMNS $(( $LINES - 8 )) \
	"apt_get_upgrade" "run apt-get upgrade" ON \
	"install_apache" "Install Apache2" ON \
	"install_php" "Install PHP" ON \
	"install_mariadb" "Install MariaDB" ON \
	"write_config_file" "Port settings and apache config" ON \
	"setup_mariadb" "Setup MariaDB" ON \
	3>&1 1>&2 2>&3
)
if [ $? == 1 ]; then
    echo "User selected Cancel."
    exit
fi


for task in $WHAT_TO_DO; do
	eval $task
done

LOCAL_IP=$(ip -o route get to 8.8.8.8 | sed -n 's/.*src \([0-9.]\+\).*/\1/p')

sed -i "s:AllowOverride None:AllowOverride All:g" /etc/apache2/apache2.conf

a2enmod rewrite
a2enmod env

service apache2 restart

#curl "http://$LOCAL_IP/" --data-raw "username=$ADMIN_USERNAME&password=$ADMIN_PASSWORD" 2>&1 > /dev/null
curl "http://$LOCAL_IP/"

apt-get install latexmk texlive imagemagick texlive-lang-german -y

touch /etc/hardcore_debugging

# Für Rechnungserstellungs-LaTeX Apache erlauben auf /tmp zuzugreifen
set +e
sed -i 's/PrivateTmp/#PrivateTmp/' /etc/systemd/system/multi-user.target.wants/apache2.service

sed 's/\(\(post_max_size\|upload_max_filesize\) = \).M/\16M/g' /etc/php/*/apache2/php.ini
set -e

if [[ $(grep -L "curl -s localhost" /etc/crontab ) ]]; then
	echo "*/30 * * * * curl -s localhost 2>&1 >/dev/null >/dev/null 2>&1" >> /etc/crontab
	echo "*/30 * * * * curl -s localhost/delete_demo.php 2>&1 >/dev/null >/dev/null 2>&1" >> /etc/crontab
fi


if [[ $(grep -L "ntpdate" /etc/crontab ) ]]; then
	echo "*/30 * * * * ntpdate -s time.nist.gov 2>&1 >/dev/null >/dev/null 2>&1" >> /etc/crontab
fi

systemctl daemon-reload
systemctl restart apache2

if [[ -f /var/www/html/index.html ]]; then
	rm /var/www/html/index.html
fi

whiptail --title "Installer" --msgbox "Installation done!" $LINES $COLUMNS
