[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jbtronics/StuRa-Finanzsoftware/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jbtronics/StuRa-Finanzsoftware/?branch=master)
![PHPUnit Tests](https://github.com/jbtronics/StuRa-Finanzsoftware/workflows/PHPUnit%20Tests/badge.svg)
![Static analysis](https://github.com/jbtronics/StuRa-Finanzsoftware/workflows/Static%20analysis/badge.svg)
[![codecov](https://codecov.io/gh/jbtronics/StuRa-Finanzsoftware/branch/master/graph/badge.svg)](https://codecov.io/gh/jbtronics/StuRa-Finanzsoftware)
![GitHub License](https://img.shields.io/github/license/Part-DB/Part-DB-symfony)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%207.2-green)

# StuRa-Finanzsoftware

## Einführung
Die Software in diesem Repository wurde für den Studierendenrat (StuRa) der Friedrich-Schiller-Universität Jena
entwickelt, um das Handling von Zahlungsaufträgen der FSR an die zentrale Finanzverwaltung des StuRas zu
vereinfachen (siehe TOP 9 der StuRa-Sitzung vom [14.07.20](https://www.stura.uni-jena.de/downloads/sitzungsmaterial/19-20/2020-07-14_Sitzungsmaterial.pdf)).

Wenn ein Fachschaftsrat (FSR) eine Zahlung aus ihrem Haushaltstopf tätigen, so musste bisher ein Zahlungsauftrag als Formular
an den Fachschaftsbeauftragten (FSB) geschickt werden. Die Idee dieser Software ist es das Handling dieser Zahlungsaufträge zu vereinfachen,
indem sie bereits in digitaler Form eingereicht werden.

Die Finanzbeauftragten der FSRe können die Zahlungsaufträge durch ein webbasiertes Formular abschicken, die Finanzer
des StuRas können in einem geschütztem Bereich die eingereichten Zahlungsaufträge einsehen, bearbeiten und bestätigen 
(der Kassenverantwortliche prüft die rechnerische Korrektheit, ein HHV die sachliche Korrektheit). Zusätzlich können dort neue Benutzer angelegt werden
und die Organsiationseinheiten der Studierendenschaft (also welche FSRe und Referate) gibt es.

Die Software erlaubt die Verwendung von Zwei-Faktor-Authentifizierung zur Absicherung der Logins, um eine hohe Sicherheit
der vertraulichen Daten zu gewährleisten.
Nutzern lassen sich verschiedene Berechtigungen zuweisen, sodass ein Benutzer nur Zugriff auf die Daten hat, die für seine Rolle notwendig sind.
Zusätzlich werden vielfach geprüfte Standardbibliotheken und verschiedene Techniken (z.B. CSFR-Tokens)
verwendet, um typische Angriffsvektoren zu minimieren.

Wichtige Features der Software sind:
 * Einfache Einreichung von Zahlungsaufträge über eine Weboberfläche, grundlegende Validierung der Daten (Prüfung ob Email, IBAN, etc. gültig sind)
 * Administrations-Backend zur Verwaltung von Benutzern, Strukturen und Zahlungsaufträgen. Sortierung, Suche und Filterung der Zahlungsaufträge möglich
 * Zwei-Faktor-Authentifizierung von Benutzern und Berechtigungssystem
 * Mehrsprachigkeit (Deutsch und Englisch unterstützt); weitere Sprachen einfach hinzufügbar
 * Verwendung von erprobten Standardbibliotheken (Symfony 5, Doctrine, etc.), damit einfache Erweiterbarkeit
 
## Installation

### Systemanforderungen
Zum Betrieb der Software werden folgende Anforderungen an den Server gestellt:
 * Webserver (am einfachsten einzurichten ist Apache2) mit eingerichtetem PHP
 * MySQL 5.6+ oder MariaDB 10.1+ als Datenbank-Server mit einer Datenbank die von der Software benutzt werden kann.
 * PHP-Version >= 7.2 mit Erweiterungen `php-ctype`, `php-iconv`, `php-intl`, `php-ctype`, `php-pdo`, `php-xsl` (die Existenz dieser Erweiterungen wird aber später auch noch überprüft)
  Die Installation von `php-opcache` ist empfohlen, da es die Performance enorm verbessert.
 * Eine eigene Subdomain / VHost, wo die Software eingerichtet werden kann
 * [Composer](https://getcomposer.org/): Entwender installiert aus Paketquellen (z.B. `apt install composer`) oder lokal via `wget https://getcomposer.org/composer-stable.phar`
 
Nachfolgend wird angenommen, dass SSH bzw. Konsolen Zugriff auf den Server besteht. Eine Installation ohne Konsolenzugriff ist zwar
prinzipiell möglich, wird aber nicht empfohlen. Wenn dies unbedingt notwendig ist, sollte Rücksprache mit dem Autor gehalten werden.

### Installationsschritte
1. `git clone [REPO]`: Die Installation via git ist empfohlen, da es später eine einfache Aktualisierung mittels `git pull` erlaubt. 
Das Repo kann aber auch normal heruntergeladen werden und auf den Server kopiert werden.
2. `cp .env .env.local`: Kopiere die Einstellungsvorlage in die bearbeitbare Einstellungen
3. `.env.local` in einem Editor öffnen und die Einstellungen anpassen (siehe Kommentare): Wichtig ist, dass `APP_ENV` auf `prod` steht 
und in `DATABASE_URL` die korrekte Datenbank und die Datenbankzugangsdaten eingetragen sind. 
4. `composer install --no-dev -o` (oder `php composer.phar install --no-dev -o` bei lokaler Installation): Installiert alle benötigten Bibliotheken und kopiert die benötigten Assets.
Dieser Schritt sollte als Benutzer des Webservers (meist `www-data`) ausgeführt werden, damit die Berechtigungen alle stimmen.
5. Anpassen des `DocumentRoot` in Webserver-Configuration: Die öffentlich erreichbaren Dateien dieser Software liegen im Ordner `public/`, daher muss die Webserverkonfiguration 
entsprechend angepasst werden. Bei Apache2 findet sich in der Konfigurationsdatei des VHosts die Einstellung `DocumentRoot`, diese muss auf den `public/`-Ordner
verweisen (d.h. z.B. `DocumentRoot /pfad/zur/software/public`)
6. `php bin/console doctrine:migrations:migrate` und bestätigen: Hierbei wird das Datenbankschema erstellt und wenn nötig aktualisiert.
7. Erstellen eines initialen Benutzers mit `php bin/console app:user-new [username]`. Es kann ein beliebiger Nutzername angegeben werden. 
 Mit dem eingegeben Passwort kann man sich nun in die Administrationsoberfläche einloggen und z.B. neue Benutzer und Strukturen anlegen.
 
### Updateanweisungen
Wenn ein Update verfügbar ist, kann die Software wie folgt aktualisiert werden:
1. `git pull`
2. `composer install --no-dev -o`
3. `php bin/console doctrine:migrations:migrate` (**vorher sollte unbedingt ein Backup der Datenbank geschehen!**)

## Administration

### Command Line Interface

Über die Konsole können Benutzer angelegt werden, Passwörter geändert, und Berechtigungen an Nutzer vergeben werden.
Dies ist nützlich, wenn das Passwort für den Administrationsbenutzer vergessen wurde, und diese Dinge daher nicht auf 
der Weboberfläche geändert werden können. Weiterhin lässt sich nur so die Zwei-Faktor-Authentifizierung zurücksetzen, wenn
ein Benutzer sein Gerät und die Backup-Codes vergessen hat.

Nützliche Befehle sind (ausgeführt aus dem Hauptverzeichnis):
* `php bin/console app:user-new [USERNAME]`: legt einen neuen Benutzer an
* `php bin/console app:user-change-password [USERNAME]`: ändert das Passwort eines Benutzers
* `php bin/console app:user-disable-2fa [USERNAME]`: Deaktiviert alle Zwei-Faktor-Authentifizierungsmaßnahmen für den Benutzer.
Der Nutzer kann sich danach alleine mit seinem Passwort anmelden.
* `php bin/console app:user-promote [USERNAME]`: Gibt dem Benutzer eine zusätzliche Rolle. Siehe eingebaute Hilfe für mehr Informationen.

Mit der Option `--help` kann eine Hilfe zur Verwendung und Funktionsweise dieser Befehle aufgerufen werden.

Weitere nützliche Befehle könnten nützlich sein:
* `php bin/console cache:clear`: Löscht den Programmcache. Notwendig wenn etwas an Dateien verändert wurde
* `php bin/console doctrine:migrations:migrate`: Aktualisiert die Datenbank auf den aktuellen Programmstand

### Fehlerbehebung

Log-Dateien finden sich in `var/log/` (vom Programmverzeichnis aus), insbesondere `var/log/prod.log` dürfte interessant sein.

Wenn Fehler auftreten, ist es hilfreich erstmal den Browsercache und Servercache (mit `php bin/console cache:clear`) zu löschen.
Weiterhin ist wichtig, dass der Webserver vollen Zugriff auf `var/` und alle darinliegenden Dateien hat (Lesen + Schreiben).
Wenn z.B. composer als root-User ausgeführt wurde, müssen die Berechtigungen angepasst werden. Eine andere Möglichkeit wäre,
den Ordner `var/` zu löschen und die Website aufzurufen. Die Software legt dann die benötigten Dateien und Ordnerstrukturen wieder an.

Wenn diese Tipps nicht helfen, ist es auch möglich in `.env.log` die `APP_ENV` zu `APP_ENV=dev` zu ändern 
(dann muss aber Dev-Abhängigkeiten mit `composer install -o` installiert worden sein). Mit dieser Einstellungen werden Fehlermeldungen
im Browser angezeigt, inklusiver Hilfreicher Debug-Tools. Da dies potentiell unsicher ist, sollte die Einstellung schnellstmöglich auf
`APP_ENV=prod` zurückgeändert werden.

## Lizenz
Diese Software ist unter der GNU Affero General Public License v3.0 (AGPL) lizensiert. 
Dies bedeutet diese Software kann für alle Zwecke kostenlos verwendet und modifiziert werden, solange alle Änderungen
ebenfalls unter AGPL bereitgestellt. Für weiter Infos siehe [LICENSE](https://github.com/jbtronics/StuRa-Finanzsoftware/blob/master/LICENSE).

Manche Bestandteile (externe Bibliotheken) stehen unter anderen Lizenzen, diese steht dann in der entsprechenden Datei.

## Entwicklerinformationen
Diese Anwendung ist mit [Symfony 5](https://symfony.com/) und [EasyAdmin](https://github.com/EasyCorp/EasyAdminBundle) entwickelt worden.
Für Weiterentwicklung dieser Software sollte dort die entsprechende Dokumentation gelesen werden.

Für eine Entwicklungsumgebung sollten die Dev-Abhängigkeiten ohne Optimierungen mit `composer install` installiert werden.
Dann kann der Entwicklermodus mit `APP_ENV=dev` in `.env.local` aktiviert werden, Symfony zeigt dann eine Entwicklertoolbar an und
Optimierungen werden deaktiviert.

Übersetzungsdateien befinden sich in `translations/` und können mit einem Editor wie [POEdit](https://poedit.net/) bearbeitet werden.
Nach Änderungen muss der Cache gelöscht werden: `php bin/console cache:clear`.

Der HTML-Code für die Frontpage findet sich in `templates/`. Für Templates wird [Twig](https://twig.symfony.com/) verwendet.

Alles was von einem Browser abgerufen werden können soll (z.B. Styles und Javascript), muss im Ordner `public/` liegen,
da dies der DocumentRoot ist. Die benötigten Frontendabhängigkeiten werden von Composer heruntergeladen und in den `public/assets/`
Ordner kopiert (siehe `extra.copyFile` Eintrag in composer.json). Wenn noch deutlich komplexe Frontend-Skripte benötigt werden sollten,
macht es eventuell Sinn auf Webpack bzw. [Webpack-Encore](https://github.com/symfony/webpack-encore) zu migrieren.

Die Abhängigkeiten können mit `composer update` (bzw. `composer update -o`, wenn es im Produktivbetrieb benutzt werden soll) aktualisiert werden.
Die Constraints sollten so gesetzt sein, dass alles weiterhin funktioniert nach einem Update, trotzdem sollte die Anwendung nach einem Update
der Abhängigkeiten getestet werden. Für ein Upgrade von Symfony folge [dieser](https://symfony.com/doc/current/setup/upgrade_minor.html) Anleitung.
Solange die Major-Version gleich bleibt, sollte ein Upgrade gefahrlos möglich sein (d.h. 5.1 -> 5.2 funktioniert, 5.2 -> 6.0 vermutlich nicht).
