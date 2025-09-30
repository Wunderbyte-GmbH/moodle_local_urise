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
 * Test the contract formula.
 *
 * @package local_urise
 * @copyright 2024 Georg Maißer <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_urise\contractmanager;

require_once(__DIR__ . '/../../../config.php');

// No guest autologin.
require_login(0, false);

global $DB, $PAGE, $OUTPUT, $USER;

// NOTE: THIS IS CURRENTLY NEVER USED!

$userid = required_param('userid', PARAM_INT);

if (!$context = context_system::instance()) {
    throw new moodle_exception('badcontext');
}
$PAGE->set_context($context);

$title = get_string('contractformulatest', 'local_urise');

$PAGE->set_url('/local/urise/tests/contractformula_test.php');
$PAGE->navbar->add($title);
$PAGE->set_title(format_string($title));
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('local_urise-contractformula-test');

echo $OUTPUT->header();

echo "<p>Gesetzte userid: $userid</p>";
echo "Stundensatz: " . contractmanager::get_hourrate($userid);

echo $OUTPUT->footer();
