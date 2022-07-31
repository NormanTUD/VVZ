# VVZ
Eine freie Software zur Verwaltung von Vorlesungen (*V*orlesungs*v*er*z*eichnis). 

Damit lassen sich

- Dozenten
- Institute
- Veranstaltungen
- Prüfungen
- uvm.

verwalten. Darüber lässt sich eine ganze Universität verwalten!

Aktuell läuft die Software unter vvz.phil.tu-dresden.de.

# Requirements

- Linux (tested on Debian and Suse)
- PHP7+
- MySQL/MariaDB
- MySQL/Maria-DB Plugin for PHP7
- Email server on localhost if you want to send Emails

# Installation

```console
export PRIVATE_ACCESS_TOKEN=gpb_... # Ask me to get private access!
curl -H "Authorization: token $PRIVATE_ACCESS_TOKEN" -H 'Accept: application/vnd.github.v3.raw' -L https://raw.githubusercontent.com/NormanTUD/VVZ/master/install.sh | bash
```

Open the URL in the browser after doing this and follow the instructions.

After deleting `new_setup`, log in and follow the further instructions. Then, edit the `data.php` appropriately.

Then edit the `config.php` according to your institution.

Once done, the software is ready.

# Debug

Wenn du 

```console
touch /etc/vvz_debug_query_all
```

ausführst, solltest du am Ende der Seite eine Liste aller Queries, die ausgeführt worden sind, sehen.
