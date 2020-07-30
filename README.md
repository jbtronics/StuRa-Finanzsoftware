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
 * PHP-Version >= 7.2 mit Erweiterungen `php-ctype`, `php-iconv`, `php-intl`, `php-ctype`, `php-pdo`.
  Die Installation von ´php-opcache` ist empfohlen, da es die Performance enorm verbessert.
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