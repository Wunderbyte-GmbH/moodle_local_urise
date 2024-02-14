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
 * Add dates to option.
 *
 * @package local_berta
 * @copyright 2024 Georg Maißer <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_berta\output\dashboard;

require_once(__DIR__ . '/../../config.php');

// No guest autologin.
require_login(0, false);

global $DB, $PAGE, $OUTPUT, $USER;

if (!$context = context_system::instance()) {
    throw new moodle_exception('badcontext');
}

// Check if optionid is valid.
$PAGE->set_context($context);
$PAGE->set_url('/local/berta/dashboard.php');

if ((has_capability('mod/booking:updatebooking', $context) || has_capability('mod/booking:addeditownoption', $context)) == false) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('accessdenied', 'mod_booking'), 4);
    echo get_string('nopermissiontoaccesspage', 'mod_booking');
    echo $OUTPUT->footer();
    die();
}

$title = get_string('pluginname', 'local_berta');
$PAGE->navbar->add($title);
$PAGE->set_title(format_string($title));
$PAGE->set_heading($title);

$PAGE->set_pagelayout('mydashboard');
$PAGE->add_body_class('local_berta-dashboard');

echo $OUTPUT->header();

$PAGE->requires->js_call_amd(
    'local_berta/botagsmodal',
    'init',
    [
        '[data-action=openbotagsmodal]',
        get_string('editbotags', 'local_berta')
    ]
);
$PAGE->requires->js_call_amd('local_berta/app-lazy', 'init');
echo <<<EOT
    <div id="local-berta-app" name="local-berta-app">
    <router-view></router-view>
    </div>
  EOT;

// Render the page content via mustache templates.
$output = $PAGE->get_renderer('local_berta');
$data = new dashboard();
echo $output->render_dashboard($data);

echo $OUTPUT->footer();
