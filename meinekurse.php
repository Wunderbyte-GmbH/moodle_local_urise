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

use mod_booking\singleton_service;

require_once(__DIR__ . '/../../config.php');

// No guest autologin.
require_login(0, false);

global $DB, $PAGE, $OUTPUT, $USER;

if (!$context = context_system::instance()) {
    throw new moodle_exception('badcontext');
}

$isteacher = false;

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

echo $OUTPUT->header();

echo '<div class="background d-flex justify-content-center align-items-center">
               <div class="container mw-90 d-flex justify-content-center">
                    <div class="row mb-2 w-100 d-flex justify-content-center flex-column">
                    <h1 class="font-weight-light text-center mb-4 text-light">
                    ' . get_string('myspace', 'local_urise') . '
                    </h1>
               </div>
          </div>
     </div>
';

// echo html_writer::div(get_string('coursesibooked', 'local_urise'), 'h2 mt-3 mb-2 text-center');
echo format_text("[unifiedmybookingslist cards=1 sort=1 filter=1 filterontop=1]", FORMAT_HTML);

echo $OUTPUT->footer();
