# VVZ
Eine freie Software zur Verwaltung von Vorlesungen (*V*orlesungs*v*er*z*eichnis)

# Requirements

- Linux (tested on Debian and Suse)
- PHP7+
- MySQL/MariaDB
- MySQL/Maria-DB Plugin for PHP7

# Installation

After installing MariaDB, create a database (and change to `$GLOBALS['dbname']` to this in mysql.php). Default is `uni`.

> cd $VVZDIR

> echo "mysqldbpw" > /etc/vvzdbpw

> touch new_setup

Open the URL in the browser after doing this and follow the instructions.

After deleting `new_setup`, log in and follow the further instructions. Once done, the software is ready.

# Debug

Wenn du 

> touch /etc/vvz_debug_query_all

ausführst, solltest du am Ende der Seite eine Liste aller Queries, die ausgeführt worden sind, sehen.
