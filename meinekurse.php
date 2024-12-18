<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * An overview of all courses the currently logged in user
 * either teacher or has booked.
 *
 * @package local_urise
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_shopping_cart\form\dynamic_select_users;
use local_shopping_cart\shopping_cart;
use mod_booking\singleton_service;

require_once(__DIR__ . '/../../config.php');
// No guest autologin.
require_login(0, false);

global $DB, $PAGE, $OUTPUT, $USER;

require_once($CFG->dirroot . '/mod/booking/lib.php');

if (!$context = context_system::instance()) {
    throw new moodle_exception('badcontext');
}

$isteacher = false;
$userid = optional_param('userid', $USER->id, PARAM_INT);

// Check if optionid is valid.
$PAGE->set_context($context);

$title = get_string('mycourses', 'local_urise');
$archive = get_string('archive', 'local_urise');

$PAGE->set_url('/local/urise/meinekurse.php');
// $PAGE->navbar->add($title);
$PAGE->set_title(format_string($title));
$PAGE->set_pagelayout('base');
$PAGE->add_body_class('local_urise-meinekurse');

// Get archive cmids.
$archivecmids = [];
$archivecmidsstring = get_config('local_urise', 'shortcodesarchivecmids');
if (!empty($archivecmidsstring)) {
    $archivecmidsstring = str_replace(';', ',', $archivecmidsstring);
    $archivecmidsstring = str_replace(' ', '', $archivecmidsstring);
    $archivecmids = explode(',', $archivecmidsstring);
}

if (has_capability('mod/shopping_cart:cansearchusers', context_system::instance())) {
     $changeuserbutton = html_writer::tag('a', get_string('changeuser', 'local_urise'), ['class' => 'btn btn-primary', 'data-toggle' => 'collapse', 'href' => '#changeUser', 'role' => 'button', 'aria-expanded' => 'false', 'aria-controls' => 'changeUser']);
     $changeuserelement = html_writer::start_tag('div', ['class' => 'collapse', 'id' => 'changeUser']);
     $changeuserelement .= html_writer::start_tag('div', ['class' => 'card card-body']);
     $changeuserelement .= html_writer::tag('div', '', ['data-id' => 'urise-selectuserformcontainer']);
     $changeuserelement .= html_writer::end_div();
     $changeuserelement .= html_writer::end_div();
} else {
     $changeuserbutton = '';
     $changeuserelement = '';
}
if (booking_check_if_teacher()) {
    $teacherelement = html_writer::tag(
        'a',
        get_string('teacher', 'mod_booking'),
        ['class' => 'btn btn-primary', 'href' => '/mod/booking/teacher.php?teacherid=' . $USER->id]
    );
} else {
    $teacherelement = '';
}

$adminelement = html_writer::start_div('col-12 text-right');
$adminelement .= html_writer::tag('div', $teacherelement);
$adminelement .= html_writer::tag('div', $changeuserbutton);
$adminelement .= html_writer::end_div();

echo $OUTPUT->header();

$selectuserform = new dynamic_select_users();
// echo html_writer::div($selectuserform->render(), '', ['data-id' => 'urise-selectuserformcontainer']);
$PAGE->requires->js_call_amd('local_urise/userselectorform', 'init');

if ($userid != $USER->id) {
    $user = $DB->get_record('user', ['id' => $userid]);
    $whosspace = "$user->firstname $user->lastname (id: $user->id) <br> $user->email";
} else {
    $whosspace = get_string('myspace', 'local_urise');
}

echo '<div class="background d-flex justify-content-center align-items-center">
               <div class="container mw-90">
               ' . $changeuserelement . '
                    <div class="row w-100">
                         ' . $adminelement . '
                    </div>
                    <div class="row mb-2 w-100 d-flex justify-content-center flex-column">
                         <h1 class="font-weight-light text-center mb-4 text-light">
                         ' . $whosspace . '
                         </h1>
                    </div>
               </div>
               </div>';

echo format_text("[unifiedmybookingslist cards=1 sort=1 filter=1 filterontop=1 all=true]", FORMAT_HTML);

$sql = "SELECT id
        FROM {local_shopping_cart_history}
        WHERE userid=:userid AND paymentstatus > 0";
if ($DB->record_exists_sql($sql, ['userid' => $userid])) {
     echo format_text("[shoppingcarthistory userid=$userid]", FORMAT_HTML);
}

echo $OUTPUT->footer();
