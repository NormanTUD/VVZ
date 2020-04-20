# VVZ
Eine freie Software zur Verwaltung von Vorlesungen (*V*orlesungs*v*er*z*eichnis)

# Requirements

- Linux (tested on Debian and Suse)
- PHP7+
- MySQL/MariaDB
- MySQL/Maria-DB Plugin for PHP7

# Installation

> cd $VVZDIR

> echo "mysqldbpw" > /etc/vvzdbpw

> touch new_setup

Öffne die URL vom Server im Browser.

# Debug

Wenn du 

> touch /etc/vvz_debug_query_all

ausführst, solltest du am Ende der Seite eine Liste aller Queries, die ausgeführt worden sind, sehen.
