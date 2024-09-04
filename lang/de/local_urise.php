<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_urise
 * @category    string
 * @copyright   2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'urise Plugin';

$string['dashboard'] = 'Dashboard';
$string['messageprovider:sendmessages'] = 'Verschicke Nachrichten';
$string['urise:cansendmessages'] = 'Kann Nachrichten schicken.';
$string['urise:editavailability'] = 'Kann die Verfügbarkeit von Buchungsoptionen ändern und Vorreservierungen anlegen';
$string['urise:editsubstitutionspool'] = 'Kann den Vertretungspool für einzelne Kursarten bearbeiten';
$string['urise:viewsubstitutionspool'] = 'Kann den Vertretungspool für einzelne Kursarten sehen und E-Mails an den Vertretungspool senden';

// Caches.
$string['cachedef_cachedpaymenttable'] = 'Zahlungstransaktionen (Cache)';

// Shortcodes.
$string['shortcodelists'] = 'Shortcode-Listen';
$string['shortcodelists_desc'] = 'Hier können Sie Listen konfigurieren, die durch Shortcodes (z.B. [allekurseliste]) generiert werden.';
$string['shortcodelists_showdescriptions'] = 'Beschreibungen von Buchungsoptionen anzeigen';
$string['shortcodeslistofbookingoptions'] = 'Alle Kurse als Liste';
$string['shortcodeslistofbookingoptionsascards'] = 'Alle Kurse als Karten';
$string['shortcodeslistofmybookingoptionsascards'] = 'Meine Kurse als Karten';
$string['shortcodeslistofmybookingoptionsaslist'] = 'Meine Kurse als Liste';
$string['shortcodeslistofteachersascards'] = 'Liste aller Trainer als Karten';
$string['shortcodeslistofmytaughtbookingoptionsascards'] = 'Kurse, die ich unterrichte, als Karten';
$string['shortcodesshowallsports'] = "Liste aller sportarten";
$string['uriseshortcodes:showstart'] = 'Kursbeginn anzeigen';
$string['uriseshortcodes:showend'] = 'Kursende anzeigen';
$string['uriseshortcodes:showbookablefrom'] = '"Buchbar ab" anzeigen';
$string['uriseshortcodes:showbookableuntil'] = '"Buchbar bis" anzeigen';
$string['uriseshortcodes:showfiltercoursetime'] = 'Filter "Kurszeiten" anzeigen';
$string['uriseshortcodes:showfilterbookingtime'] = 'Filter "Anmeldezeiten" anzeigen';

$string['nobookinginstancesselected'] = "Aktuell sind keine Buchungsinstanzen ausgewählt, um Buchungsoptionen anzuzeigen";

// General strings.
$string['campaigns'] = 'Kampagnen';
$string['collapsedescriptionoff'] = 'Beschreibungen nicht einklappen';
$string['collapsedescriptionmaxlength'] = 'Beschreibungen einklappen (Zeichenanzahl)';
$string['collapsedescriptionmaxlength_desc'] = 'Geben Sie die maximale Anzahl an Zeichen, die eine Beschreibung haben darf, ein.
Beschreibungen, die länger sind werden eingeklappt.';
$string['dayofweek'] = 'Wochentag';
$string['editavailabilityanddescription'] = 'Verfügbarkeit & Beschreibung bearbeiten';
$string['editavailability'] = 'Verfügbarkeit bearbeiten';
$string['editdescription'] = 'Beschreibung bearbeiten';
$string['substitutionspool'] = 'Vertretungspool für {$a}';
$string['editsubstitutionspool'] = 'Vertretungspool bearbeiten';
$string['viewsubstitutionspool'] = 'Vertretungspool ansehen';
$string['mailtosubstitutionspool'] = 'E-Mail an Vertretungspool senden';
$string['substitutionspool:infotext'] = 'Trainer:innen, die <b>{$a}</b> vertreten dürfen:';
$string['substitutionspool:mailproblems'] = 'Hier klicken, wenn Sie Probleme beim Versenden der E-Mails haben...';
$string['substitutionspool:copypastemails'] = 'Kopieren Sie die folgenden E-Mail-Adressen in das BCC-Feld Ihres E-Mail-Programms:';
$string['gateway'] = 'Gateway';
$string['invisibleoption'] = '<i class="fa fa-eye-slash" aria-hidden="true"></i>';
$string['showmore'] = 'Zeige mehr';
$string['sportsdivision'] = 'Sparte';
$string['sportsdivisions'] = 'Sparten';
$string['titleprefix'] = 'Kursnummer';
$string['unknown'] = 'Unbekannt';

$string['format'] = "Kursformat";
$string['german'] = 'Deutsch';
$string['english'] = 'Englisch';
$string['onsite'] = 'Vor Ort';
$string['hybrid'] = 'Hybrid';
$string['blendedlearningonsite'] = 'Blended Learning mit Vor-Ort-Terminen';
$string['blendedlearningonline'] = 'Blended Learning mit Online-Terminen';
$string['blendedlearningonline'] = 'Blended Learning mit Hybrid-Terminen';
$string['onsite'] = 'Online-Termin';
$string['selfpaced'] = 'Selbstlernkurs';

// Errors.
$string['error:starttime'] = 'Start muss vor dem Ende sein.';
$string['error:endtime'] = 'Ende muss nach dem Start sein.';

// List of all courses.
$string['allcourses'] = 'Alle Kurse';

// Cards.
$string['listofsports'] = 'Kursarten';
$string['listofsports_desc'] = 'Zeige und editiere die Liste der Kursarten auf diesem System.';

$string['numberofcourses'] = 'Kurse';
$string['numberofcourses_desc'] = 'Informationen über die Kurse und Buchungen auf der Plattform.';

$string['numberofentities'] = 'Anzahl der Organisations-Einheiten';
$string['numberofentities_desc'] = 'Informationen über die Organisations-Einheiten auf der Plattform.';

$string['coursesavailable'] = "Buchbare Kurse";
$string['coursesbooked'] = 'Gebuchte Kurse';
$string['coursesincart'] = 'Im Warenkorb';
$string['coursesdeleted'] = 'Gelöschte Kurse';
$string['coursesboughtcard'] = 'Gekaufte Kurse (Online)';
$string['coursespending'] = 'Noch unbestätigte Kurse';
$string['coursesboughtcashier'] = 'Gekaufte Kurse (Kassa)';
$string['paymentsaborted'] = 'Abgebrochene Zahlungen';
$string['bookinganswersdeleted'] = "Gelöschte Buchungen";

$string['settingsandreports'] = 'Einstellungen & Berichte';
$string['settingsandreports_desc'] = 'Verschiedene Einstellungen und Berichte, die für urise relevant sind.';
$string['editentities'] = 'Organisations-Einheiten bearbeiten';
$string['editentitiescategories'] = 'Kategorien der Organisations-Einheiten bearbeiten';
$string['importentities'] = 'Organisations-Einheiten importieren';
$string['editbookinginstance'] = 'Buchungs-Instanz bearbeiten';
$string['editbookings'] = 'Kurs-Übersicht';
$string['viewteachers'] = 'Trainer:innen-Übersicht';
$string['teachersinstancereport'] = 'Trainer:innen-Gesamtbericht (Kurse, Fehlstunden, Vertretungen)';
$string['sapdailysums'] = 'SAP-Buchungsdateien';

$string['addbookinginstance'] = '<span class="bg-danger font-weight-bold">Keine Buchungs-Instanz! Hier klicken, um eine einzustellen.</span>';
$string['editpricecategories'] = 'Preiskategorien bearbeiten';
$string['editsemesters'] = 'Semester bearbeiten';
$string['changebookinginstance'] = 'Standard-Semester-Instanz setzen';
$string['editbotags'] = 'Tags verwalten';
$string['createbotag'] = 'Neuen Tag anlegen...';
$string['createbotag:helptext'] = '<p>
<a data-toggle="collapse" href="#collapseTagsHelptext" role="button" aria-expanded="false" aria-controls="collapseTagsHelptext">
  <i class="fa fa-question-circle" aria-hidden="true"></i><span>&nbsp;Hilfe: So können Sie Tags konfigurieren...</span>
</a>
</p>
<div class="collapse" id="collapseTagsHelptext">
<div class="card card-body">
  <p>Damit Sie Tags verwenden können, müssen Sie ein Benutzerdefiniertes Buchungsoptionsfeld vom Typ "Dynamic Dropdown menu" mit folgenden Einstellungen anlegen:</p>
  <ul>
  <li><strong>Kategorie: </strong>Tags</li>
  <li><strong>Name: </strong>Tags</li>
  <li><strong>Kurzname: </strong>botags</li>
  <li><strong>SQL query: </strong><code>SELECT botag as id, botag as data FROM {local_urise_botags}</code></li>
  <li><strong>Auto-complete: </strong><span class="text-success">aktiviert</span></li>
  <li><strong>Multi select: </strong><span class="text-success">aktiviert</span></li>
  </ul>
  <p>Nun können Sie die hier angelegten Tags den Buchungsoptionen zuordnen.<br>Sie müssen hier mindestens einen Tag angelegt haben, damit Sie Tagging verwenden können.</p>
</div>
</div>';

// Edit sports.
$string['youneedcustomfieldsport'] = 'Diese Veranstaltung ist keiner Organisation zugeordnet';

// Shortcodes.
$string['shortcodeslistofbookingoptions'] = 'Liste der buchbaren Kurse';
$string['shortcodeslistofbookingoptionsascards'] = 'Liste der buchbaren Kurse als Karten';
$string['shortcodeslistofmybookingoptionsascards'] = 'Liste meiner gebuchte Kurse als Karten';
$string['shortcodessetdefaultinstance'] = 'Setze eine Standard-Instanz für Shortcodes';
$string['shortcodessetdefaultinstancedesc'] = 'Damit kann eine Standard-Buchungsinstanz definiert werden, die dann verwendet wird,
wenn keine ID definiert wurde. Dies erlaubt den schnellen Wechsel (zum Beispiel von einem Semster zum nächsten), wenn es Überblicks-
Seiten für unterschiedliche Kategorien gibt.';
$string['shortcodessetinstance'] = 'Definiere die Buchungsinstanz, die standardmäßig verwendet werden soll.';
$string['shortcodessetinstancedesc'] = 'Wenn Du hier einen Wert setzt, kann der Shortcode so verwendet werden: [allekurseliste category="philosophy"]
Es ist also nicht mehr nötig, eine ID zu übergeben.';
$string['shortcodesnobookinginstance'] = '<div class="text-danger font-weight-bold">Noch keine Buchungsinstanz erstellt!</div>';
$string['shortcodesnobookinginstancedesc'] = 'Sie müssen mindestens eine Buchungsinstanz in einem Moodle-Kurs erstellen, bevor Sie hier eine auswählen können.';
$string['shortcodesuserinformation'] = 'Zeige Informationen von NutzerInnen';
$string['shortcodesarchivecmids'] = 'Liste von IDs für das "Meine Kurse"-Archiv';
$string['shortcodesarchivecmids_desc'] = 'Geben Sie eine Komma-getrennte Liste von Kursmodul-IDs (cmids) der Semester-Instanzen (Buchungsinstanzen) an,
die im "Meine Kurse"-Archiv aufscheinen sollen.';

$string['archive'] = '<i class="fa fa-archive" aria-hidden="true"></i> Archiv';
$string['mycourses'] = 'Meine Buchungen';
$string['coursesibooked'] = '<i class="fa fa-ticket" aria-hidden="true"></i> Kurse, die ich im aktuellen Semester gebucht habe:';
$string['coursesibookedarchive'] = 'Kurse, die ich in vergangenen Semestern gebucht habe:';
$string['coursesiteach'] = '<i class="fa fa-graduation-cap" aria-hidden="true"></i> Kurse, die ich im aktuellen Semester unterrichte:';
$string['coursesiteacharchive'] = 'Kurse, die ich in vergangenen Semestern unterrichtet habe:';

// Access.php.
$string['urise:canedit'] = 'Nutzer:in darf verwalten';
$string['urise:viewdashboard'] = "User kann Dashboard sehen";


// Dashboard Vue.
$string['dashboardnewbookings'] = 'Buchungen';
$string['dashboardpplwl'] = 'Personen in Wartelisten';
$string['dashboardneuestornos'] = 'Neue Stornos';
$string['dashboardnoshows'] = 'Nicht erschienen';
$string['dashboardmanagelocation'] = 'Standorte verwalten';

$string['dashboardoverview'] = 'Übersicht';
$string['dashboardbookingfields'] = 'Buchungsoptionsfelder';
$string['dashboardstats'] = 'Auswertung';
$string['dashboardmydashboard'] = 'Mein Dashboard';

$string['dashboard_zeitraum'] = 'Zeitraum';
$string['dashboard_organisation'] = 'Organisation';
$string['dashboard_auswertung'] = 'Auswertung';


// Vue strings.
$string['vuedashboardchecked'] = 'Default Ausgewählt';
$string['vuedashboardname'] = 'Name';
$string['vuedashboardcoursecount'] = 'Anzahl der Kurse';
$string['vuedashboardpath'] = 'Pfad';
$string['vuedashboardcreateoe'] = 'Neue OE erstellen';
$string['vuedashboardassignrole'] = 'Rollen zuweisen';
$string['vuedashboardnewcourse'] = 'Neuen Kurs erstellen';
$string['vuenotfoundroutenotfound'] = 'Route nicht gefunden';
$string['vuenotfoundtryagain'] = 'Bitte versuchen Sie es später erneut';
$string['vuebookingstatscapability'] = 'Berechtigung';
$string['vuebookingstatsback'] = 'Zurück';
$string['vuebookingstatssave'] = 'Speichern';
$string['vuebookingstatsrestore'] = 'Zurücksetzten';
$string['vuebookingstatsselectall'] = 'Alle auswählen';
$string['vuebookingstatsbookingoptions'] = 'Buchungsoptionen';
$string['vuebookingstatsbooked'] = 'Gebucht';
$string['vuebookingstatswaiting'] = 'Warteliste';
$string['vuebookingstatsreserved'] = 'Reserviert';
$string['vuebookingstatsrealparticipants'] = 'Tatsächliche TN';
$string['vuebookingstatsrealcosts'] = 'Tatsächliche Kosten';
$string['vuecapabilityoptionscapconfig'] = 'Berechtigungskonfiguration';
$string['vuecapabilityoptionsnecessary'] = 'notwendig';
$string['vuecapabilityunsavedchanges'] = 'Es gibt ungespeicherte Änderungen';
$string['vuecapabilityunsavedcontinue'] = 'Möchten Sie diese Konfiguration wirklich zurücksetzen?';
$string['vuebookingstatsrestoreconfirmation'] = 'Möchten Sie diese Konfiguration wirklich zurücksetzen?';
$string['vuebookingstatsyes'] = 'Ja';
$string['vuebookingstatsno'] = 'Nein';
$string['vueconfirmmodal'] = 'Sind Sie sicher, dass Sie zurückgehen möchten?';
$string['vueheadingmodal'] = 'Bestätigung';
$string['vuenotificationtitleunsave'] = 'Keine ungespeicherten Änderungen erkannt';
$string['vuenotificationtextunsave'] = 'Es wurden keine ungespeicherten Änderungen erkannt.';
$string['vuenotificationtitleactionsuccess'] = 'Die Konfiguration wurde erfolgreich {$a}';
$string['vuenotificationtextactionsuccess'] = 'Die Konfiguration wurde erfolgreich {$a}.';
$string['vuenotificationtitleactionfail'] = 'Die Konfiguration wurde nicht erfolgreich {$a}';
$string['vuenotificationtextactionfail'] = 'Beim Speichern ist ein Fehler aufgetreten. Die Änderungen wurden nicht vorgenommen.';
$string['vuedashboardgotocategory'] = 'Zur Kategorie';
$string['vuedashboardbookinginstances'] = 'Booking instances';
$string['vuenotabsfounds'] = 'Keine Tabs gefunden';

$string['booking:expertoptionform'] = "Expert option form";
$string['booking:reducedoptionform1'] = "1. Reduced option form for course category";
$string['booking:reducedoptionform2'] = "2. Reduced option form for course category";
$string['booking:reducedoptionform3'] = "3. Reduced option form for course category";
$string['booking:reducedoptionform4'] = "4. Reduced option form for course category";
$string['booking:reducedoptionform5'] = "5. Reduced option form for course category";

// Access.
$string['mod/booking:expertoptionform'] = 'Buchungsoption für ExpertInnen';
$string['mod/booking:reducedoptionform1'] = 'Buchungsoption reduziert 1';
$string['mod/booking:reducedoptionform2'] = 'Buchungsoption reduziert 2';
$string['mod/booking:reducedoptionform3'] = 'Buchungsoption reduziert 3';
$string['mod/booking:reducedoptionform4'] = 'Buchungsoption reduziert 4';
$string['mod/booking:reducedoptionform5'] = 'Buchungsoption reduziert 5';
$string['booking:editoptionformconfig'] = 'Buchungsoptionsfelder bearbeiten';
$string['booking:bookanyone'] = 'Darf alle Nutzer:innen buchen';
$string['mod/booking:bookanyone'] = 'JedeN buchen';
$string['mod/booking:seepersonalteacherinformation'] = 'Detailinfos über Lehrende anzeigen';

// Optionformconfig.php / optionformconfig_form.php.
$string['optionformconfig'] = 'Formulare für Buchungsoptionen anpassen (PRO)';
$string['optionformconfig_infotext'] = 'Mit diesem PRO-Feature können Sie sich mit Drag & Drop und den Checkboxen beliebige Buchungsoptionsformulare zusammenstellen.
Die einzelnen Formulare werden auf bestimmten Kontext-Ebenen (z.B. pro Buchungsinstanz, Systemweit...) definiert. Den jeweiligen Nutzer:innen sind die Formulare nur zugänglich,
wenn Sie die jeweils entsprechende Berechtigung haben.';
$string['optionformconfig_getpro'] = 'Mit Booking <span class="badge bg-success text-light"><i class="fa fa-cogs" aria-hidden="true"></i> PRO</span> haben Sie die Möglichkeit, mit Drag & Drop individuelle Formulare für bestimmte Nutzer:innen-Gruppen und Kontexte
(z.B. nur für eine bestimmte Buchungsinstanz) anzulegen.';
$string['optionformconfigsaved'] = 'Konfiguration für das Buchungsoptionsformular gespeichert.';
$string['optionformconfigsubtitle'] = '<p>Hier können Sie nicht benötigte Funktionalitäten entfernen, um das Formular für die Erstellung von Buchungsoptionen übersichtlicher zu gestalten.</p>
<p><strong>ACHTUNG:</strong> Deaktivieren Sie nur Felder, von denen Sie sicher sind, dass Sie sie nicht benötigen!</p>';
$string['optionformconfig:nobooking'] = 'Sie müssen zumindest eine Buchungsinstanz anlegen, bevor Sie dieses Formular nutzen können!';

$string['optionformconfigsavedsystem'] = 'Ihre Formular-Definition wurde auf dem Kontextlevel System gespeichert';
$string['optionformconfigsavedcoursecat'] = 'Ihre Formular-Definition wurde auf dem Kontextlevel Kurskategorie gespeichert';
$string['optionformconfigsavedmodule'] = 'Ihre Formular-Definition wurde auf dem Kontextlevel Modul gespeichert';
$string['optionformconfigsavedcourse'] = 'Ihre Formular-Definition wurde auf dem Kontextlevel Kurs gespeichert';
$string['optionformconfigsavedother'] = 'Ihre Formular-Definition wurde auf Kontextlevel {$a} gespeichert';

$string['optionformconfignotsaved'] = 'Es wurde keine besondere Formular-Definition gespeichert';

$string['prepareimport'] = "Bereite den Import vor";
$string['id'] = "Id";
$string['json'] = "Sammelfeld für zum Speichern von Informationen";
$string['returnurl'] = "Adresse für Rückkehr";
$string['youareusingconfig'] = 'Sie verwenden folgende Formular-Konfiguration: {$a}';
$string['formconfig'] = 'Anzeige, welches Formular verwendet wird';
$string['template'] = 'Vorlagen';
$string['moveoption'] = 'Option verschieben';
$string['dontmove'] = 'Don\' move';
$string['moveoption_help'] = 'Option in eine andere Buchungsaktivität verschieben';
$string['text'] = 'Titel';
$string['maxanswers'] = 'Limit für Antworten';
$string['identifier'] = 'Identifikator';
$string['easytext'] = 'Einfacher, nicht veränderbarer Text';
$string['easybookingopeningtime'] = 'Einfache Buchungsstartzeit';
$string['easybookingclosingtime'] = 'Einfache Buchungsendzeit';
$string['easyavailabilityselectusers'] = 'Einfache NutzerInnen Voraussetzung';
$string['easyavailabilitypreviouslybooked'] = 'Einfache bereits gebuchte Voraussetzung';
$string['invisible'] = 'Unsichtbar';
$string['annotation'] = 'Interne Anmerkung';
$string['courseid'] = 'Kurs, in den eingeschrieben wird';
$string['entitiesfieldname'] = 'Ort(e)';
$string['entities'] = 'Orte mit Entities Plugin auswählen';
$string['shoppingcart'] = 'Zahlungsoptionen mit Shopping Cart Plugin definieren';
$string['optiondates'] = 'Termine';
$string['actions'] = 'Buchungsaktionen';
$string['attachment'] = 'Angehängte Dateien';
$string['howmanyusers'] = 'Beschränkungen';
$string['recurringoptions'] = 'Wiederkehrende Optionen';
$string['bookusers'] = 'Feld für den Import, um NutzerInnen zu buchen';
$string['timemodified'] = 'Bearbeitungszeit';
$string['waitforconfirmation'] = 'Buchen nur nach Bestätigung';

// Filter.
$string['organisation'] = 'Organisationseinheit';
$string['location'] = 'Ort';
$string['competency'] = 'Kompetenzen';

// Nav.
$string['urise'] = 'u:rise';
$string['entities'] = 'Raum-Management';
$string['coursename'] = 'Kursname';

// Contract management.
$string['contractmanagementsettings'] = 'Vertragsmanagement-Einstellungen';
$string['contractmanagementsettings_desc'] = 'Konfigurieren Sie hier, wie sich Verträge auf Abrechnungen
 auswirken und welche Sonderfälle es gibt.';
$string['contractformula'] = 'Vertragsformel';
$string['contractformula_desc'] = 'Hier können Sie eine JSON-Formel angeben, die festlegt, wie sich Verträge auf Abrechnungen
 auswirken und welche Sonderfälle es gibt.';
$string['contractformulatest'] = 'Vertragsformel testen';
$string['editcontractformula'] = 'Vertragsformel bearbeiten';

// Userinformation.mustache.
$string['userinformation'] = 'Benutzer-Information';

// My Courses List.
$string['tocoursecontent'] = 'Zu den Kursmaterialien';

// Shortlist section information.
$string['dayofweekalt'] = 'Wochentag und Termin, an dem eine Kurseinheit stattfindet';
$string['locationalt'] = 'Abhaltungsort des Kurses';
$string['bookingsalt'] = 'Anzahl der freien und maximal verfügbaren Kursplätze';
$string['teacheralt'] = 'Leiter des Kurses';
$string['imagealt'] = 'Titelbild des Kurses';


// Transactions List.
$string['status'] = 'Status';
$string['openorder'] = 'Offen';
$string['bookedorder'] = 'Bezahlt';
$string['transactionslist'] = 'Zahlungstransaktionen';
$string['checkstatus'] = 'Überprüfe Status';
$string['statuschanged'] = 'Status geändert';
$string['statusnotchanged'] = 'Status nicht geändert';

$string['id'] = 'Eintrag';
$string['transactionid'] = 'Interne ID';
$string['itemid'] = 'ItemID';
$string['username'] = 'Nutzer';
$string['price'] = 'Betrag';
$string['names'] = 'Buchungen';
$string['action'] = 'Aktion';

// Easy availability feature.
$string['easyavailability:overbook'] = 'Sogar dann, wenn der Kurs <b>ausgebucht</b> ist';
$string['easyavailability:previouslybooked'] = 'Nutzer:innen, die bereits einen bestimmten USI-Kurs gebucht haben, dürfen immer buchen';
$string['easyavailability:selectusers'] = 'Ausgewählte Nutzer:innen dürfen außerhalb der Buchungszeiten buchen';
$string['easyavailability:formincompatible'] = '<div class="alert alert-warning">Diese Buchungsoption verwendet Einschränkungen,
 die mit diesem Formular nicht kompatibel sind. Bitte wenden Sie sich an einen urise-Admin.</div>';
$string['easyavailability:openingtime'] = 'Kann gebucht werden ab';
$string['easyavailability:closingtime'] = 'Kann gebucht werden bis';
$string['easyavailability:heading'] = '<div class="alert alert-info">Sie bearbeiten die Verfügbarkeit von "<b>{$a}</b>"</div>';

// Task.
$string['create_sap_files'] = 'Die täglichen SAP Dateien erstellen.';
$string['add_sports_division'] = 'Die Sparten zu den Sportarten automatisch hinzufügen';

// Sports division.
$string['nosportsdivision'] = 'Keine Sparten auf dieser Website verfügbar';

$string['shortcodes::unifiedlist'] = "Shortcode unified list";
$string['shortcodes::calendarblock'] = "Shortcode calendarblock";


$string['summary'] = 'Allgemein';
$string['summary_desc'] = 'Enthält die Einstellungsmöglichkeiten und Statistiken der gesamten Plattform';

// Rolls.
$string['urise:create'] = "Erstelle";
$string['urise:view'] = "Ansehen";

$string['searchheadertext'] = "Was möchten Sie lernen?";
$string['myspace'] = "Mein Bereich";

// Descriptionview.
$string['requirements'] = "Voraussetzungen";
$string['goals'] = "Ziele";
$string['coursecontent'] = "Inhalte";
$string['coursemethods'] = "Methoden";
$string['additionalinfo'] = "Sonstige Informationen";
$string['targetaud'] = "Zielgruppe";
$string['optiondates'] = "Termin(e)";
$string['nolocation'] = "Kein Ort angegeben";
$string['showdates'] = "Termine anzeigen";
$string['teachers'] = "Trainer*in";
$string['gotoprofile'] = "Zum Profil";
$string['buchungsbedingungen'] = "Buchungsbedingungen";
$string['orgacontact'] = "Kontakt der verantwortlichen Organisationseinheit";
$string['aboutoffer'] = "Über das Angebot";

$string['zgcommunities'] = "Interessant für";
$string['organisationfilter'] = "Angeboten von";
$string['fromdate'] = "Ab ";

$string['kurssprache'] = 'Kurssprache';

$string['fbafrikawissenschaftenundorientalistik'] = "FB Afrikawissenschaften und Orientalistik";
$string['fbaltegeschichte'] = "FB Alte Geschichte";
$string['fbanglistikundamerikanistik'] = "FB Anglistik und Amerikanistik";
$string['fbarchälogieundnumismatik'] = "FB Archäologie und Numismatik";
$string['fbastronomie'] = "FB Astronomie";
$string['fbbildungswissenschaftsprachwissenschaftundvergleichendeliteraturwissenschaft'] = "FB Bildungswissenschaft, Sprachwissenschaft und vergleichende Literaturwissenschaft";
$string['fbbiologieundbotanikstandortbiologie'] = "FB Biologie und Botanik, Standort Biologie";
$string['fbbiologieundbotanikstandortbotanik'] = "FB Biologie und Botanik, Standort Botanik";
$string['fbbyzantistikundneogräzistik'] = "FB Byzantistik und Neogräzistik";
$string['fbgeographieundregionalforschung'] = "FB Geographie und Regionalforschung";
$string['fberdwissenschaftenundmeteorologie'] = "FB Erdwissenschaften und Meteorologie";
$string['fbeuropaeischeethnologie'] = "FB Europäische Ethnologie";
$string['fbfinnougristik'] = "FB Finno-Ugristik";
$string['fbgermanistiknederlandistikundskandinavistik'] = "FB Germanistik, Nederlandistik und Skandinavistik";
$string['fbgeschichtswissenschaften'] = "FB Geschichtswissenschaften";
$string['fbjudaistik'] = "FB Judaistik";
$string['fbklassischephilologiemittelundneulatein'] = "FB Klassische Philologie, Mittel- und Neulatein";
$string['fbkulturundsozialanthropologie'] = "FB Kultur- und Sozialanthropologie";
$string['fbkunstgeschichte'] = "FB Kunstgeschichte";
$string['fbmusikwissenschaft'] = "FB Musikwissenschaft";
$string['fbostasienwissenschaften'] = "FB Ostasienwissenschaften";
$string['fbosteuropäischegeschichteundslawistik'] = "FB Osteuropäische Geschichte und Slawistik";
$string['fbpharmazieundernaehrungswissenschaften'] = "FB Pharmazie und Ernährungswissenschaften";
$string['fbphilosophieundpsychologie'] = "FB Philosophie und Psychologie";
$string['zbphysikundchemie'] = "ZB Physik und Chemie";
$string['fbpublizistik-undkommunikationswissenschaftundinformatik'] = "FB Publizistik- und Kommunikationswissenschaft und Informatik";
$string['fbrechtswissenschaften'] = "FB Rechtswissenschaften";
$string['fbromanistik'] = "FB Romanistik";
$string['fbsoziologieundpolitikwissenschaft'] = "FB Soziologie und Politikwissenschaft";
$string['fbsportwissenschaft'] = "FB Sportwissenschaft";
$string['fbsuedasientibetundbuddhismuskunde'] = "FB Südasien-, Tibet- und Buddhismuskunde";
$string['fbtheaterfilmundmedienwissenschaft'] = "FB Theater-, Film- und Medienwissenschaft";
$string['fbtheologie'] = "FB Theologie";
$string['fbtranslationswissenschaft'] = "FB Translationswissenschaft";
$string['fbwirtschaftswissenschaftenundmathematik'] = "FB Wirtschaftswissenschaften und Mathematik";
$string['fbzeitgeschichte'] = "FB Zeitgeschichte";
$string['forschungsundpublikationsservices'] = "Forschungs- und Publikationsservices";
$string['hauptbibliothek'] = "Hauptbibliothek";
$string['universitaetsarchiv'] = "Universitätsarchiv";
$string['advancedresearchschoolinlawandjurisprudence'] = "Advanced Research School in Law and Jurisprudence";
$string['doctoralschoolmicrobiologyandenvironmentalscience'] = "Doctoral School Microbiology and Environmental Science";
$string['doctoralschoolofphilologicalandculturalstudies'] = "Doctoral School of Philological and Cultural Studies";
$string['oskarmorgensterndoctoralschool'] = "Oskar Morgenstern Doctoral School";
$string['univiedoctoralschoolcomputerscience'] = "UniVie Doctoral School Computer Science";
$string['viennadoctoralschoolofhistoricalandculturalstudies'] = "Vienna Doctoral School of Historical and Cultural Studies";
$string['viennadoctoralschoolofphilosophy'] = "Vienna Doctoral School of Philosophy";
$string['viennadoctoralschoolofsocialsciences'] = "Vienna Doctoral School of Social Sciences";
$string['viennadoctoralschoolinchemistry'] = "Vienna Doctoral School in Chemistry";
$string['viennadoctoralschoolofecologyandevolution'] = "Vienna Doctoral School of Ecology and Evolution";
$string['viennainternationalschoolinearthandspacesciences'] = "Vienna International School in Earth and Space Sciences";
$string['viennaschoolofmathematicsjointdoctoralschoolwithtuwien'] = "Vienna School of Mathematics (joint Doctoral School with TU Wien)";
$string['viennadoctoralschoolinphysics'] = "Vienna Doctoral School in Physics";
$string['viennadoctoralschoolofpharmaceuticalnutritionalandsportsciences'] = "Vienna Doctoral School of Pharmaceutical, Nutritional and Sports Sciences";
$string['viennabiocenterphdprogramjointdoctoralschooloftheuniversityofviennaandthemedicaluniversityofvienna'] = "Vienna Biocenter PhD Program (joint Doctoral School of the University of Vienna and the Medical University of Vienna)";
$string['viennadoctoralschoolincognitionbehaviorandneurosciencefrombiologytopsychologyandthehumanities'] = "Vienna Doctoral School in Cognition, Behavior and Neuroscience (from Biology to Psychology and the Humanities)";
$string['viennadoctoralschooloftheologyandresearchonreligion'] = "Vienna Doctoral School of Theology and Research on Religion";
$string['viennadoctoralschoolineducation'] = "Vienna Doctoral School in Education";
$string['organisationskulturundgleichstellung'] = "Organisationskultur und Gleichstellung";
$string['personalentwicklungundrecruiting'] = "Personalentwicklung und Recruiting";
$string['centerforteachingandlearning'] = "Center for Teaching and Learning";
$string['koordinationstudienservices'] = "Koordination Studienservices";
$string['zentralerinformatikdienst'] = "Zentraler Informatikdienst";
$string['lppostdoc'] = "LP Postdoc";
$string['lpfuehrungskräfte'] = "LP Führungskräfte";
$string['lpallgemeinesuniversitätspersonal'] = "LP Allgemeines Universitätspersonal";
$string['lpstudierende'] = "LP Studierende";
$string['lpexterne'] = "LP Externe";
$string['lpbibliothek'] = "LP Bibliothek";

$string['doctoralschools'] = "Doctoral Schools";
$string['personalwesenundfrauenfoerderung'] = "Personalwesen und Frauenförderung";
$string['bibliotheksundarchivwesen'] = "Bibliotheks - und Archivwesen";
$string['studienserviceundlehrwesen'] = "Studienservice und Lehrwesen";

$string['organisationfilterdefinition'] = "Organisationsfilter";
$string['organisationfilterdefinition_desc'] = "Der hierarchische Filter für die Organisationsstruktur muss hier eingefügt werden.";

$string['bibliothekszielgruppe'] = 'Bibliothekszielgruppe';
$string['students'] = 'Studierende';
$string['doctoralcandidates'] = 'Doktorand*innen';
$string['lecturers'] = 'Uni-Lehrende';
$string['researchers'] = 'Forschende';
$string['pupilsandteachers'] = 'Schüler*innen und Lehrer*innen';
$string['generalpublic'] = 'Interessierte Öffentlichkeit';

// Kompetenzen.
$string['lehrkompetenzen'] = "Lehrkompetenzen";
$string['lehrkonzeptionplanung'] = 'Lehrkonzeption & -planung';
$string['lehrundlernmethoden'] = 'Lehr- & Lernmethoden';
$string['erstellunglehrlernmaterialien'] = 'Erstellung Lehr-/Lernmaterialien';
$string['lehrenmitdigitalentechnologien'] = 'Lehren mit digitalen Technologien';
$string['pruefenbeurteilen'] = 'Prüfen & Beurteilen';
$string['betreuungschriftlicherarbeiten'] = 'Betreuung schriftlicher Arbeiten';
$string['weiterentwicklungderlehre'] = 'Weiterentwicklung der Lehre';
$string['forschungskompetenzen'] = "Forschungskompetenzen";
$string['wissenschaftlichesarbeiten'] = 'Wissenschaftliches Arbeiten';
$string['wissenschaftlichespublizieren'] = 'Wissenschaftliches Publizieren';
$string['openscience'] = 'Open Science';
$string['wissensaustauschinnovation'] = 'Wissensaustausch & Innovation';
$string['wissenschaftlicheintegritaet'] = 'Wissenschaftliche Integrität';
$string['networkinginderwissenschaft'] = 'Networking in der Wissenschaft';
$string['interdisziplinaereforschung'] = 'Interdisziplinäre Forschung';
$string['forschungsfoerderung'] = 'Forschungsförderung';
$string['karriereentwicklungplanung'] = 'Karriereentwicklung & -planung';
$string['kommunikationkooperation'] = "Kommunikation & Kooperation";
$string['praesentation'] = 'Präsentation';
$string['gespraechsverhandlungsfuehrung'] = 'Gesprächs- & Verhandlungsführung';
$string['feedback'] = 'Feedback';
$string['moderation'] = 'Moderation';
$string['sprachkenntnisse'] = 'Sprachkenntnisse';
$string['konfliktmanagement'] = 'Konfliktmanagement';
$string['informationskommunikation'] = 'Informations- & Kommunikation';
$string['genderdiversitaetskompetenz'] = 'Gender- & Diversitätskompetenz';
$string['kooperationskompetenz'] = 'Kooperationskompetenz';
$string['selbstundarbeitsorganisation'] = "Selbst- & Arbeitsorganisation";
$string['veranstaltungsorganisation'] = 'Veranstaltungsorganisation';
$string['arbeitsorganisation'] = 'Arbeitsorganisation';
$string['selbstorganisation'] = 'Selbstorganisation';
$string['servicekundinnenorientierung'] = 'Service- & Kund*innenorientierung';
$string['loesungszukunftsorientierung'] = 'Lösungs- & Zukunftsorientierung';
$string['ressourceneffizienz'] = 'Ressourceneffizienz';
$string['changekompetenz'] = 'Change-Kompetenz';
$string['gesundheitsorientierung'] = 'Gesundheitsorientierung';
$string['lernkompetenz'] = 'Lernkompetenz';
$string['digitalkompetenzen'] = "Digitalkompetenzen";
$string['itsecurity'] = 'IT Security';
$string['digitaleinteraktion'] = 'Digitale Interaktion';
$string['umgangmitinformationenunddaten'] = 'Umgang mit Informationen & Daten';
$string['technologienutzung'] = 'Technologienutzung';
$string['fuehrungskompetenzen'] = "Führungskompetenzen";
$string['educationalleadershipandmanagement'] = 'Educational Leadership & Management';
$string['sonstige'] = "Sonstige";
$string['sonstigekompetenzen'] = 'Sonstige Kompetenzen';
