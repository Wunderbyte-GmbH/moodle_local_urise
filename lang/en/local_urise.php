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
$string['messageprovider:sendmessages'] = 'Send messages';
$string['urise:cansendmessages'] = 'Can send messages';
$string['urise:editavailability'] = 'Can change availability and reservations';
$string['urise:editsubstitutionspool'] = 'Can edit the substitutions pool of teachers for different courses';
$string['urise:viewsubstitutionspool'] = 'Can view the substitutions pool of teachers for different courses and send emails to substitution pools';

// Caches.
$string['cachedef_cachedpaymenttable'] = 'Cached payment table (transaction list).';

// Shortcodes.
$string['sciencecommunicationprogramme'] = "Science Communication Programme";
$string['shortcodelists'] = 'Shortcode lists';
$string['shortcodelists_desc'] = 'Configure lists generated by shortcodes (e.g. [allekurseliste]).';
$string['shortcodelists_showdescriptions'] = 'Show descriptions of booking options';
$string['shortcodeslistofbookingoptions'] = 'All courses as list';
$string['shortcodeslistofbookingoptionsascards'] = 'All courses as cards';
$string['shortcodeslistofmybookingoptionsascards'] = 'My courses as cards';
$string['shortcodeslistofmybookingoptionsaslist'] = 'My courses as list';
$string['shortcodeslistofteachersascards'] = 'List of teachers as cards';
$string['shortcodeslistofmytaughtbookingoptionsascards'] = 'Courses I teach as cards';
$string['shortcodesshowallsports'] = "List of all courses";
$string['uriseshortcodes:showstart'] = 'Show "Start time of the course"';
$string['uriseshortcodes:showend'] = 'Show "End time of the course"';
$string['uriseshortcodes:showbookablefrom'] = 'Show "Bookable from"';
$string['uriseshortcodes:showbookableuntil'] = 'Show "Bookable until"';
$string['uriseshortcodes:showfiltercoursetime'] = 'Show filter "Course time"';
$string['uriseshortcodes:showfilterbookingtime'] = 'Show filter "Booking time"';

$string['nobookinginstancesselected'] = "Currently, no booking instances are selected to provide booking options.";

// General strings.
$string['campaigns'] = 'Campaigns';
$string['collapsedescriptionoff'] = 'Do not collapse descriptions';
$string['collapsedescriptionmaxlength'] = 'Collapse descriptions (max. length)';
$string['collapsedescriptionmaxlength_desc'] = 'Enter the maximum length of characters of a description. Descriptions having more characters will be collapsed.';
$string['dayofweek'] = 'Weekday';
$string['reihenprogramm'] = "Series/Programs";
$string['editavailabilityanddescription'] = 'Edit availability & description';
$string['editavailability'] = 'Edit availability';
$string['editdescription'] = 'Edit description';
$string['editnews'] = 'Edit newselements';
$string['substitutionspool'] = 'Substitutions pool for {$a}';
$string['editsubstitutionspool'] = 'Edit substitutions pool';
$string['viewsapfiles'] = 'View SAP files';
$string['viewsubstitutionspool'] = 'View substitutions pool';
$string['mailtosubstitutionspool'] = 'Send email to substitutions pool';
$string['substitutionspool:infotext'] = 'Teachers allowed to substitute <b>{$a}</b>:';
$string['substitutionspool:mailproblems'] = 'Click here if you have problems with sending emails...';
$string['substitutionspool:copypastemails'] = 'You can copy the emails manually and paste them into the BCC of your mail client:';
$string['gateway'] = 'Gateway';
$string['invisibleoption'] = '<i class="fa fa-eye-slash" aria-hidden="true"></i>';
$string['showmore'] = 'Show more';
$string['sportsdivision'] = 'Type of courses';
$string['sportsdivisions'] = 'Types of courses';
$string['titleprefix'] = 'Course number';
$string['unknown'] = 'Unknown';
$string['merchantref'] = 'MerchantRef';
$string['customorderid'] = 'CustomOrderID';
$string['viewcards'] = 'Cards view';
$string['viewlist'] = 'List view with image';
$string['viewtextlist'] = 'List view (text only)';

$string['format'] = "Format";
$string['german'] = 'German';
$string['germanenglish'] = 'German & English';
$string['english'] = 'English';
$string['onsite'] = 'On site';
$string['hybrid'] = 'Hybrid';
$string['blendedlearningonsite'] = 'Blended learning with on-site sessions';
$string['blendedlearningonline'] = 'Blended learning with online sessions';
$string['blendedlearninghybrid'] = 'Blended learning with hybrid sessions';
$string['online'] = 'Online session';
$string['selfpaced'] = 'Self-paced course';

// Errors.
$string['error:starttime'] = 'Start has to be before end.';
$string['error:endtime'] = 'End has to be after start.';

// List of all courses.
$string['allcourses'] = 'All courses';

// Cards.
$string['listofsports'] = 'Types of courses';
$string['listofsports_desc'] = 'View and edit the list of courses on this system';

$string['numberofcourses'] = 'Moodle Courses';
$string['numberofcourses_desc'] = 'Information about courses and bookings on this platform.';

$string['numberofentities'] = 'Number of entities';
$string['numberofentities_desc'] = 'Information about the entities on the platform.';

$string['coursesavailable'] = 'Courses available';
$string['coursesbooked'] = 'Courses booked';
$string['coursesincart'] = 'Courses in shopping cart';
$string['coursesdeleted'] = 'Deleted courses';
$string['coursesboughtcard'] = 'Courses bought online';
$string['coursespending'] = 'Courses pending';
$string['coursesboughtcashier'] = 'Courses bought at cashier';
$string['paymentsaborted'] = 'Aborted payments';
$string['bookinganswersdeleted'] = "Deleted booking answers";

$string['settingsandreports'] = 'Settings & Reports';
$string['settingsandreports_desc'] = 'Various settings and reports relevant for urise.';
$string['editentities'] = 'Edit entities';
$string['editentitiescategories'] = 'Edit categories of entities';
$string['importentities'] = 'Import entities';
$string['editbookinginstance'] = 'Edit semester instance';
$string['editbookings'] = 'Overview of courses';
$string['viewteachers'] = 'Teacher overview';
$string['teachersinstancereport'] = 'Teachers instance report (courses, missing hours, substitutions)';
$string['sapdailysums'] = 'SAP booking files';
$string['searchcourses'] = "Search course ...";

$string['addbookinginstance'] = '<span class="bg-danger font-weight-bold">No semester instance! Click here to choose one.</span>';
$string['editpricecategories'] = 'Edit price categories';
$string['editsemesters'] = 'Edit semesters';
$string['changebookinginstance'] = 'Set default semester instance';
$string['editbotags'] = 'Edit tags';
$string['createbotag'] = 'Create new tag...';
$string['createbotag:helptext'] = '<p>
<a data-toggle="collapse" href="#collapseTagsHelptext" role="button" aria-expanded="false" aria-controls="collapseTagsHelptext">
  <i class="fa fa-question-circle" aria-hidden="true"></i><span>&nbsp;Help: How to configure tags...</span>
</a>
</p>
<div class="collapse" id="collapseTagsHelptext">
<div class="card card-body">
  <p>In order to use tags, you have to create a Booking customfield for booking options of the type "Dynamic Dropdown menu" which has the following settings:</p>
  <ul>
  <li><strong>Category: </strong>Tags</li>
  <li><strong>Name: </strong>Tags</li>
  <li><strong>Short name: </strong>botags</li>
  <li><strong>SQL query: </strong><code>SELECT botag as id, botag as data FROM {local_urise_botags}</code></li>
  <li><strong>Auto-complete: </strong><span class="text-success">active</span></li>
  <li><strong>Multi select: </strong><span class="text-success">active</span></li>
  </ul>
  <p>Now you can apply the tags you have created here to your booking options.<br>You need to have created at least one tag, in order to be able to use tagging.</p>
</div>
</div>';

$string['youneedcustomfieldkompetenzen'] = 'The Booking option customfield "Kompetenzen (Shortname: kompetenzen)" is missing or no value for this field is set.';

// Shortcodes.
$string['shortcodeslistofbookingoptions'] = 'List of booking options';
$string['shortcodeslistofbookingoptionsascards'] = 'List of booking options as cards';
$string['shortcodeslistofmybookingoptionsascards'] = 'List of my booked booking options as cards';
$string['shortcodessetdefaultinstance'] = 'Set default instance for shortcodes implementation';
$string['shortcodessetdefaultinstancedesc'] = 'This allows you to change instances quickly when you want to change
a lot of them at once. One example would be that you have a lot of teaching categories and they are listed on different
pages, but you need to change the booking options form one semester to the next.';
$string['shortcodessetinstance'] = 'Set the booking instance which should be used by default';
$string['shortcodessetinstancedesc'] = 'If you use this setting, you can use the shortcode like this: [allekurseliste category="philosophy"]
So no need to specify the ID';
$string['shortcodesnobookinginstance'] = '<div class="text-danger font-weight-bold">No booking instance created yet!</div>';
$string['shortcodesnobookinginstancedesc'] = 'You need to create at least one booking instance within a moodle course before you can choose one.';
$string['shortcodesarchivecmids'] = 'List of IDs for "My courses"-archive';
$string['shortcodesarchivecmids_desc'] = 'Enter a comma-separated list of course module ids (cmids) of booking instances you want to show in the "My courses"-archive.';

$string['archive'] = '<i class="fa fa-archive" aria-hidden="true"></i> Archive';
$string['mycourses'] = 'My courses';
$string['coursesibooked'] = '<i class="fa fa-ticket" aria-hidden="true"></i> Courses I booked in the current semester:';
$string['coursesibookedarchive'] = 'Courses I booked in previous semesters:';
$string['coursesiteach'] = '<i class="fa fa-graduation-cap" aria-hidden="true"></i> Courses I teach in the current semester:';
$string['coursesiteacharchive'] = 'Courses I taught in previous semesters:';

// Access.php.
$string['urise:canedit'] = 'User can edit';
$string['urise:viewdashboard'] = "User can see Dashboard";

// Dashboard Vue.
$string['dashboardnewbookings'] = 'Bookings';
$string['dashboardpplwl'] = 'People in queue';
$string['dashboardneuestornos'] = 'Cancellations';
$string['dashboardnoshows'] = 'No shows';
$string['dashboardmanagelocation'] = 'Manage locations';

$string['dashboardoverview'] = 'Overview';
$string['dashboardbookingfields'] = 'Bookingoptionfields';
$string['dashboardstats'] = 'Stats';
$string['dashboardmydashboard'] = 'My Dashboard';

$string['dashboard_zeitraum'] = 'Timespan';
$string['dashboard_organisation'] = 'Organisation';
$string['dashboard_auswertung'] = 'Reporting';

// Vue strings.
$string['vuedashboardchecked'] = 'Visible in "Our Offer"';
$string['vuedashboardname'] = 'Name';
$string['vuedashboardcoursecount'] = 'Moodle Course Count';
$string['vuedashboardpath'] = 'Path';
$string['vuedashboardcreateoe'] = 'Create new OE';
$string['vuedashboardassignrole'] = 'Assign Roles';
$string['vuedashboardnewcourse'] = 'Create new course';
$string['vuedashboardbookinginstances'] = 'Booking instances';
$string['vuenotfoundroutenotfound'] = 'Route not found';
$string['vuenotfoundtryagain'] = 'Please try later again';
$string['vuebookingstatscapability'] = 'Capability';
$string['vuebookingstatsback'] = 'Back';
$string['vuebookingstatssave'] = 'Save';
$string['vuebookingstatsrestore'] = 'Restore';
$string['vuebookingstatsselectall'] = 'Select all';
$string['vuebookingstatsbookingoptions'] = 'Booking Options';
$string['vuebookingstatsbooked'] = 'Booked';
$string['vuebookingstatswaiting'] = 'Waiting List';
$string['vuebookingstatsreserved'] = 'Reserved';
$string['vuebookingstatsrealparticipants'] = 'Actual participants';
$string['vuebookingstatsrealcosts'] = 'Costs for org. unit';
$string['vuebookingstatsparticipated'] = 'Participated';
$string['vuebookingstatsexcused'] = 'Excused';
$string['vuebookingstatsnoshow'] = 'No Show';
$string['vuecapabilityoptionscapconfig'] = 'Capability Configuration';
$string['vuecapabilityoptionsnecessary'] = 'necessary';
$string['vuecapabilityunsavedchanges'] = 'There are unsaved changes';
$string['vuecapabilityunsavedcontinue'] = 'You really want to reset this configuration?';
$string['vuebookingstatsrestoreconfirmation'] = 'You really want to reset this configuration?';
$string['vuebookingstatsyes'] = 'Yes';
$string['vuebookingstatsno'] = 'No';
$string['vueconfirmmodal'] = 'Are you sure you want to go back?';
$string['vuefiltertabs'] = 'Filter tabs...';
$string['vueheadingmodal'] = 'Confirmation';
$string['vuenotificationtitleunsave'] = 'No unsaved changes detected';
$string['vuenotificationtextunsave'] = 'There were no unsaved changes detected.';
$string['vuenotificationtitleactionsuccess'] = 'Configuration was {$a}';
$string['vuenotificationtextactionsuccess'] = 'Configuration was {$a} successfully.';
$string['vuenotificationtitleactionfail'] = 'Configuration was not  {$a}';
$string['vuenotificationtextactionfail'] = 'Something went wrong while saving. The changes have not been made.';
$string['vuedashboardgotocategory'] = 'Go to landing page';
$string['vuenotabsfounds'] = 'No tabs were found';

// Access.
$string['mod/booking:expertoptionform'] = 'Bookingoption for experts';
$string['mod/booking:reducedoptionform1'] = 'Reduced booking option 1';
$string['mod/booking:reducedoptionform2'] = 'Reduced booking option 2';
$string['mod/booking:reducedoptionform3'] = 'Reduced booking option 3';
$string['mod/booking:reducedoptionform4'] = 'Reduced booking option 4';
$string['mod/booking:reducedoptionform5'] = 'Reduced booking option 5';
$string['mod/booking:bookanyone'] = 'Book anyone';
$string['mod/booking:seepersonalteacherinformation'] = 'See personal teacher information';

$string['more'] = 'more';

// Optionformconfig.php / optionformconfig_form.php.
$string['optionformconfig'] = 'Configure booking option forms (PRO)';
$string['optionformconfig_infotext'] = 'With this PRO feature, you can create your individual booking option forms by using drag & drop
and the checkboxes. The forms are stored on a specific context level (e.g. booking instance, system-wide...). Users can only access the forms
if they have the appropriate capabilities.';
$string['optionformconfig_getpro'] = ' With Booking <span class="badge bg-success text-light"><i class="fa fa-cogs" aria-hidden="true"></i> PRO</span> you have the possibility to create individual forms with drag and drop
for specific user roles and contexts (e.g. for a specific booking instance or system wide).';
$string['optionformconfigsaved'] = 'Configuration for the booking option form saved.';
$string['optionformconfigsubtitle'] = '<p>Turn off features you do not need, in order to make the booking option form more compact for your administrators.</p>
<p><strong>BE CAREFUL:</strong> Only deactivate fields if you are completely sure that you won\'t need them!</p>';
$string['optionformconfig:nobooking'] = 'You need to create at least one booking instance before you can use this form!';

$string['optionformconfigsavedsystem'] = 'Your form definition was saved on context level system';
$string['optionformconfigsavedcoursecat'] = 'Your form definition was saved on context level course category';
$string['optionformconfigsavedmodule'] = 'Your form definition was saved on context level module';
$string['optionformconfigsavedcourse'] = 'Your form definition was saved on context level course';
$string['optionformconfigsavedother'] = 'Your form definition was saved on context level {$a}';

$string['optionformconfignotsaved'] = 'No special configuration was saved for your form';

$string['prepareimport'] = "Prepare Import";
$string['id'] = "Id";
$string['json'] = "Stores supplementary information";
$string['returnurl'] = "Url to return to";
$string['youareusingconfig'] = 'Your are using the following form configuration: {$a}';
$string['formconfig'] = 'Show which form is used.';
$string['template'] = 'Templates';
$string['moveoption'] = 'Move booking option';
$string['dontmove'] = 'Nicht verschieben';
$string['moveoption_help'] = 'Move booking option to different booking instance';
$string['text'] = 'Titel';
$string['maxanswers'] = 'Limit for answers';
$string['identifier'] = 'Identification';
$string['easytext'] = 'Easy, not changeable text';
$string['easybookingopeningtime'] = 'Easy booking opening time';
$string['easybookingclosingtime'] = 'Easy booking closing time';
$string['easyavailabilityselectusers'] = 'Easy selected users condition';
$string['easyavailabilitypreviouslybooked'] = 'Easy already booked condition';
$string['invisible'] = 'Invisible';
$string['annotation'] = 'Internal annotation';
$string['courseid'] = 'Course to subscribe to';
$string['entities'] = 'Choose places with entities plugin';
$string['entitiesfieldname'] = 'Place(s)';
$string['shoppingcart'] = 'Set payment options with shopping cart plugin';
$string['optiondates'] = 'Dates';
$string['actions'] = 'Booking actions';
$string['attachment'] = 'Attachments';
$string['howmanyusers'] = 'Book other users limit';
$string['recurringoptions'] = 'Recurring booking options';
$string['bookusers'] = 'For Import, to book users directly';
$string['timemodified'] = 'Time modified';
$string['waitforconfirmation'] = 'Book only after confirmation';

// Filter.
$string['organisation'] = 'Organisation';
$string['location'] = 'Location';
$string['competency'] = 'Competencies';

// Nav.
$string['urise'] = 'u:rise';
$string['entities'] = 'Manage locations';
$string['coursename'] = "Coursename";

// Contract management.
$string['contractmanagementsettings'] = 'Contract management settings';
$string['contractmanagementsettings_desc'] = 'Configure how contracts affect staff invoices and define special cases.';
$string['contractformula'] = 'Contract formula';
$string['contractformula_desc'] = 'Configure how contracts affect staff invoices and define special cases using a JSON formula.';
$string['contractformulatest'] = 'Test the contract formula';
$string['editcontractformula'] = 'Edit contract formula';

// My Courses List.
$string['tocoursecontent'] = 'To the Moodle course';

// Shortlist section information.
$string['dayofweekalt'] = 'Day of week and the time slot, when a course will take place';
$string['locationalt'] = 'Location of the course';
$string['bookingsalt'] = 'Available course slots and maximum capacity';
$string['teacheralt'] = 'Teacher';
$string['imagealt'] = 'Course cover image';

// Transactions List.
$string['status'] = 'Status';
$string['openorder'] = 'Open';
$string['bookedorder'] = 'Complete';
$string['transactionslist'] = 'Payment transactions';
$string['checkstatus'] = 'Check status';
$string['statuschanged'] = 'Status changed';
$string['statusnotchanged'] = 'Status not changed';

$string['id'] = 'Entry';
$string['transactionid'] = 'Internal ID';
$string['itemid'] = 'ItemID';
$string['username'] = 'User';
$string['price'] = 'Amount';
$string['names'] = 'Purchases';
$string['action'] = 'Action';

// Easy availability feature.
$string['easyavailability:overbook'] = 'Even if the course is fully booked';
$string['easyavailability:previouslybooked'] = 'Users who already booked a specific course are always allowed to book';
$string['easyavailability:selectusers'] = 'Selected users are allowed to book outside this timespan';
$string['easyavailability:formincompatible'] = '<div class="alert alert-warning">This form uses availability conditions
 that are incompatible with this form. Please contact a urise admin.</div>';
 $string['easyavailability:openingtime'] = 'Can be booked from';
$string['easyavailability:closingtime'] = 'Can be booked until';
$string['easyavailability:heading'] = '<div class="alert alert-info">You are editing the availability of "<b>{$a}</b>"</div>';

// Task.
$string['create_sap_files'] = 'Create the daily SAP files';
$string['add_sports_division'] = 'Add course type';

// Sports division.
$string['nosportsdivision'] = 'No course types set on this site';

$string['shortcodes::unifiedcards'] = "Shortcode unified cards";
$string['shortcodes::unifiedlist'] = "Shortcode unified list";
$string['shortcodes::unifiedtextlist'] = "Shortcode unified list (text only)";
$string['shortcodes::unifiedmybookingslist'] = "Shortcode unified my bookings list";
$string['shortcodes::calendarblock'] = "Shortcode calendarblock";
$string['shortcodes::navbarhtml'] = "Shortcode that returns html defined in the settings of this plugin";
$string['shortcodes::filterview'] = "Shortcode for a special filter element";

$string['summary'] = 'General';
$string['summary_desc'] = 'Contains the settings and stats for the whole Moodle site';

// Rolls.
$string['urise:create'] = "Create";
$string['urise:view'] = "View";
$string['urise:viewcourselistindashboard'] = 'Can see courselist in dashobard';

// Settings.
$string['multibookinginstances'] = "List of default activeted booking instances";
$string['multibookinginstances_desc'] = "Choose which booking instances are activated by default.";

$string['searchheadertext'] = "What are you interested in?";
$string['myspace'] = "My bookings";

// Descriptionview.
$string['requirements'] = "Requirements";
$string['goals'] = "Goals";
$string['coursecontent'] = "Content";
$string['coursemethods'] = "Methods";
$string['additionalinfo'] = "Additional information";
$string['targetaud'] = "Target audience";
$string['optiondates'] = "Date(s)";
$string['nolocation'] = "No location provided";
$string['showdates'] = "Show dates";
$string['teachers'] = "Teacher";
$string['gotoprofile'] = "Profile";
$string['buchungsbedingungen'] = "Booking requirements";
$string['orgacontact'] = "Contact for content-related inquiries";
$string['aboutoffer'] = "About the offer";

$string['zgcommunities'] = "Interesting for";
$string['organisationfilter'] = "Offered by";
$string['filter'] = "Filter";
$string['fromdate'] = "From ";

$string['kurssprache'] = 'Language';

$string['fbafrikawissenschaftenundorientalistik'] = "African and Middle Eastern Studies Library";
$string['fbaltegeschichte'] = "Ancient History Library";
$string['fbanglistikundamerikanistik'] = "English and American Studies Library";
$string['fbarchaelogieundnumismatik'] = "Archaeology and Numismatics Library";
$string['fbastronomie'] = "Astronomy Library";
$string['fbbildungswissenschaftsprachwissenschaftundvergleichendeliteraturwissenschaft'] = "Education, Linguistics, European and Comparative Language and Literature Studies Library";
$string['fbbiologieundbotanikstandortbiologie'] = "Biology Library";
$string['fbbiologieundbotanikstandortbotanik'] = "Botany Library";
$string['fbbyzantistikundneograezistik'] = "Byzantine and Modern Greek Studies Library";
$string['fbgeographieundregionalforschung'] = "Geography and Regional Research Library";
$string['fberdwissenschaftenundmeteorologie'] = "Earth Sciences and Meteorology Library";
$string['fbeuropaeischeethnologie'] = "European Ethnology Library";
$string['fbfinnougristik'] = "Finno-Ugrian Studies Library";
$string['fbgermanistiknederlandistikundskandinavistik'] = "German, Dutch and Scandinavian Studies Library";
$string['fbgeschichtswissenschaften'] = "Historical Studies Library";
$string['fbjudaistik'] = "Jewish Studies Library";
$string['fbklassischephilologiemittelundneulatein'] = "Classical Philology, Medieval and Neo-Latin Studies Library";
$string['fbkulturundsozialanthropologie'] = "Social and Cultural Anthropology Library";
$string['fbkunstgeschichte'] = "Art History Library";
$string['fbmusikwissenschaft'] = "Musicology Library";
$string['fbostasienwissenschaften'] = "East Asian Studies Library";
$string['fbosteuropaeischegeschichteundslawistik'] = "East European History and Slavonic Studies Library";
$string['fbpharmazieundernaehrungswissenschaften'] = "Pharmacy and Nutritional Sciences Library";
$string['fbphilosophieundpsychologie'] = "Philosophy and Psychology Library";
$string['zbphysikundchemie'] = "Physics and Chemistry Library";
$string['fbpublizistikundkommunikationswissenschaftundinformatik'] = "Journalism, Communications and Informatics Library";
$string['fbrechtswissenschaften'] = "Law Library";
$string['fbromanistik'] = "Romance Studies Library";
$string['fbsoziologieundpolitikwissenschaft'] = "Sociology and Political Science Library";
$string['fbsportwissenschaft'] = "Sports Science Library";
$string['fbsuedasientibetundbuddhismuskunde'] = "South Asian, Tibetan and Buddhist Studies Library";
$string['fbtheaterfilmundmedienwissenschaft'] = "Theatre, Film and Media Studies Library";
$string['fbtheologie'] = "Theology Library";
$string['fbtranslationswissenschaft'] = "Translation Studies Library";
$string['fbwirtschaftswissenschaftenundmathematik'] = "Business, Economics and Mathematics Library";
$string['fbzeitgeschichte'] = "Contemporary History Library";
$string['forschungsserviceundnachwuchsfoerderung'] = "Research Service and Career Development";
$string['forschungsundpublikationsservices'] = "Research and Publication Services";
$string['hauptbibliothek'] = "Main Library";
$string['universitaetsarchiv'] = "University Archive";
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
$string['viennaschoolofmathematicsjointdoctoralschoolwithtuwien'] = "Vienna School of Mathematics, Joint Doctoral School with TU Wien";
$string['viennadoctoralschoolinphysics'] = "Vienna Doctoral School in Physics";
$string['viennadoctoralschoolofpharmaceuticalnutritionalandsportsciences'] = "Vienna Doctoral School of Pharmaceutical, Nutritional and Sport Sciences";
$string['viennabiocenterphdprogramjointdoctoralschooloftheuniversityofviennaandthemedicaluniversityofvienna'] = "Vienna BioCenter PhD Program, joint doctoral school of the University of Vienna and the Medical University of Vienna";
$string['viennadoctoralschoolincognitionbehaviorandneurosciencefrombiologytopsychologyandthehumanities'] = "Vienna Doctoral School in Cognition, Behavior, and Neuroscience - from Biology to Psychology and the Humanities";
$string['viennadoctoralschooloftheologyandresearchonreligion'] = "Vienna Doctoral School of Theology and Research on Religion";
$string['viennadoctoralschoolineducation'] = "Vienna Doctoral School in Education";
$string['organisationskulturundgleichstellung'] = "Culture and Equality";
$string['personalentwicklungundrecruiting'] = "Personnel Development";
$string['centerforteachingandlearning'] = "Center for Teaching and Learning";
$string['koordinationstudienservices'] = "Coordination of Student Services";
$string['zentralerinformatikdienst'] = "Vienna University Computer Center";
$string['lppostdoc'] = "LP PostDocs";
$string['lpfuehrungskraefte'] = "LP Executive staff";
$string['lpallgemeinesuniversitaetspersonal'] = "LP General university staff";
$string['lpstudierende'] = "LP Students";
$string['lpexterne'] = "LP Externe";
$string['lpbibliothek'] = "LP Bibliothek";
$string['teachinglibrary'] = "Teaching Library";

$string['phdstudents'] = "PhD Students";
$string['postdoc'] = "PostDocs";
$string['fuehrungskraefte'] = "Executive staff";
$string['allgemeinespersonal'] = "General staff";
$string['wissenschaftlichespersonal'] = "Academic staff";
$string['studierende'] = "Students";
$string['interessierteoeffentlichkeit'] = "General public";

$string['doctoralschools'] = "Doctoral Schools";
$string['personalwesenundfrauenfoerderung'] = "Human Resources and Gender Equality";
$string['bibliotheksundarchivwesen'] = "Library and Archive Services";
$string['studienserviceundlehrwesen'] = "Teaching Affairs and Student Services";

$string['organisationfilterdefinition'] = "Organisation filter";
$string['organisationfilterdefinition_desc'] = "You need to add the filter of hierarchical organisation structure here.";

$string['bibliothekszielgruppe'] = 'Library';
$string['studentmultipliers'] = 'Student multipliers';
$string['students'] = 'Students';
$string['doctoralcandidates'] = 'Doctoral candidates';
$string['lecturers'] = 'University lecturers';
$string['researchers'] = 'Researchers';
$string['pupilsandteachers'] = 'High school students and teachers';
$string['generalpublic'] = 'General public';

// Kompetenzen.
$string['lehrkompetenzen'] = "Teaching competencies";
$string['lehrkonzeptionplanung'] = 'Designing & planning teaching';
$string['lehrundlernmethoden'] = 'Teaching & learning methods';
$string['erstellunglehrlernmaterialien'] = 'Creation of teaching/learning materials';
$string['lehrenmitdigitalentechnologien'] = 'Teaching with digital technology';
$string['pruefenbeurteilen'] = 'Examining & assessment';
$string['betreuungschriftlicherarbeiten'] = 'Supervision of written work';
$string['weiterentwicklungderlehre'] = 'Continuous development of teaching skills';
$string['forschungskompetenzen'] = "Research competencies";
$string['wissenschaftlichesarbeiten'] = 'Academic research';
$string['wissenschaftlichespublizieren'] = 'Academic writing & publishing';
$string['openscience'] = 'Open Science';
$string['wissensaustauschinnovation'] = 'Knowledge exchange & innovation';
$string['wissenschaftlicheintegritaet'] = 'Academic integrity';
$string['networkinginderwissenschaft'] = 'Academic Networking';
$string['interdisziplinaereforschung'] = 'Interdisciplinary research';
$string['forschungsfoerderung'] = 'Research funding';
$string['karriereentwicklungplanung'] = 'Career development & planning';
$string['kommunikation'] = "Corporate Communications";
$string['kommunikationkooperation'] = "Communication & Collaboration";
$string['praesentation'] = 'Presentation skills';
$string['gespraechsverhandlungsfuehrung'] = 'Conversation & negotiation skills';
$string['feedback'] = 'Feedback';
$string['moderation'] = 'Moderation';
$string['sprachkenntnisse'] = 'Language skills';
$string['konfliktmanagement'] = 'Conflict management';
$string['informationskommunikation'] = 'Information & communication management';
$string['genderdiversitaetskompetenz'] = 'Equality, diversity & inclusion skills	';
$string['kooperationskompetenz'] = 'Collaborative skills';
$string['selbstundarbeitsorganisation'] = "Personal and professional organisation";
$string['veranstaltungsorganisation'] = 'Event organisation';
$string['arbeitsorganisation'] = 'Professional organisation';
$string['selbstorganisation'] = 'Personal organisation';
$string['servicedesk'] = 'Servicedesk';
$string['servicekundinnenorientierung'] = 'Service & customer-facing skills ';
$string['loesungszukunftsorientierung'] = 'Solutions & future focus';
$string['ressourceneffizienz'] = 'Resource efficiency';
$string['changekompetenz'] = 'Flexibility';
$string['changeuser'] = 'Change user';
$string['gesundheitsorientierung'] = 'Health awareness';
$string['lernkompetenz'] = 'Aptitude for learning';
$string['digitalkompetenzen'] = "Digital competencies";
$string['itsecurity'] = 'IT Security';
$string['digitaleinteraktion'] = 'Digital interaction';
$string['umgangmitinformationenunddaten'] = 'Handling information & data	';
$string['technologienutzung'] = 'IT skills';
$string['termsandconditions'] = 'With booking I accept the&nbsp;<a href="{$a}" target="_blank">terms and conditions</a>.';
$string['fuehrungskompetenzen'] = "Leadership competencies";
$string['educationalleadershipandmanagement'] = 'Educational Leadership & Management';
$string['teamfuehrungentwicklung'] = 'Team leadership & development';
$string['selbstfuehrung'] = 'Self-leadership';
$string['mitarbeitendefoerdern'] = 'Staff development';
$string['entscheidungskompetenzen'] = 'Decision-making competence';
$string['strategischeplanungentwicklung'] = 'Strategic planning & implementation';
$string['sonstige'] = "Others";
$string['sonstigekompetenzen'] = 'other competencies';

$string['excludecourselistindashboard'] = "Exclude course list in urise dashboard";
$string['excludecourselistindashboard_desc'] = "Here, you can enter the names of course areas (comma-separated, without spaces in between). No course lists will be displayed in the areas entered here.";

$string['extrashortcodeone'] = "HTML for extra shortcode 1";
$string['extrashortcodeone_desc'] = "Can be used to output html code via a shortcode";
$string['extrashortcodetwo'] = "HTML for extra shortcode 2";
$string['extrashortcodetwo_desc'] = "Can be used to output html code via a shortcode";

$string['jointevent'] = "Joint event";
$string['editteachers'] = "Edit teachers";

$string['search'] = "Enter search term";

$string['basicqualification'] = "Basic Qualification for Staff New to Teaching";
$string['teachingcompetence'] = "Teaching Competence";
$string['teachingconversations'] = "Teaching Conversations";
$string['tailoredsupport'] = "Tailored Support for Teachers";
$string['qualificationforstudent'] = "Qualification for Student Multipliers";
$string['coachingforstaff'] = "Coaching for Staff New to Teaching";
$string['studyworkshops'] = "Study Workshops";
$string['mentoring'] = "Mentoring";
$string['imoox'] = "iMooX";
$string['basiswissenbiblio'] = "Basiswissen Bibliothek";
$string['literatursuche'] = "Literatursuche";
$string['orgauethikwissenschaft'] = "Organisation und Ethik des wissenschaftlichen Arbeitens";
$string['spezialwissenbiblio'] = "Spezialwissen Bibliothek";
$string['sciencecommunicationprogramme'] = "Science Communication Programme";
$string['kompakttrainingfuehrungs'] = "Condensed Leadership Training";

$string['roleforselfregisteredusers'] = "Role for self-registered users";
