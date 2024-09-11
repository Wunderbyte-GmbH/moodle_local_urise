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
 * Moodle hooks for local_urise
 * @package    local_urise
 * @copyright  2024 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\event\capability_assigned;
use local_urise\permissions;
use mod_booking\singleton_service;

// Define booking status parameters.
define('local_urise_STATUSPARAM_BOOKED', 0);
define('local_urise_STATUSPARAM_WAITINGLIST', 1);
define('local_urise_STATUSPARAM_RESERVED', 2);
define('local_urise_STATUSPARAM_NOTBOOKED', 4);
define('local_urise_STATUSPARAM_DELETED', 5);

/**
 * Adds module specific settings to the settings block
 *
 * @param navigation_node $modnode The node to add module settings to
 * @return void
 */
function local_urise_extend_navigation(navigation_node $navigation) {
    $context = context_system::instance();
    if (has_capability('local/urise:canedit', $context)) {
        $nodehome = $navigation->get('home');
        if (empty($nodehome)) {
            $nodehome = $navigation;
        }
        $pluginname = get_string('pluginname', 'local_urise');
        $link = new moodle_url('/local/urise/dashboard.php', array());
        $icon = new pix_icon('i/dashboard', $pluginname, 'local_urise');
        $nodecreatecourse = $nodehome->add($pluginname, $link, navigation_node::NODETYPE_LEAF, $pluginname, 'urise_editor', $icon);
        $nodecreatecourse->showinflatnavigation = true;
    }
}

/**
 * Get icon mapping for font-awesome.
 *
 * @return  array
 */
function local_urise_get_fontawesome_icon_map() {
    return [
        'local_urise:i/dashboard' => 'fa-tachometer'
    ];
}

/**
 * Renders the popup.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function local_urise_render_navbar_output(\renderer_base $renderer) {
    global $CFG, $DB;
    // Early bail out conditions.
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Here, we need to check the capability, but user will not have it on system, but only on coursecategory level.
    if (!permissions::has_capability_anywhere()) {
        return true;
    }
    $context = context_system::instance();
    if (has_capability('moodle/user:editprofile', $context)) {
        $editteacherlink = '<a class="dropdown-item" href="'
                . $CFG->wwwroot . '/mod/booking/teachers.php">'
                . get_string('editteachers', 'local_urise') . '</a>';
    } else {
        $editteacherlink = '';
    }


    $output = '<div class="popover-region nav-link icon-no-margin dropdown" data-id="urise-popover-region">
        <button class="btn btn-secondary dropdown-toggle" type="button"
        id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        '. get_string('urise', 'local_urise') .'
        </button>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <a class="dropdown-item" href="'
                . $CFG->wwwroot . '/local/urise/dashboard.php">'
                . get_string('dashboard', 'local_urise') . '</a>
            <a class="dropdown-item" href="'
                . $CFG->wwwroot . '/local/entities/entities.php">'
                . get_string('entities', 'local_urise') . '</a>
            <a class="dropdown-item" href="'
                . $CFG->wwwroot . '/local/urise/meinekurse.php">'
                . get_string('mycourses', 'local_urise') . '</a>'
            . $editteacherlink . '
        </div>
    </div>';
    return $output;
}

/**
 * Callback checking permissions and preparing the file for serving plugin files, see File API.
 *
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return mixed
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function local_urise_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Check the contextlevel is as expected - if your plugin is a block.
    // We need context course if we like to access template files.
    if (!in_array($context->contextlevel, [CONTEXT_SYSTEM])) {
        return false;
    }
    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.
    // Use the itemid to retrieve any relevant data records and
    // perform any security checks to see if the
    // user really does have access to the file in question.
    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        // Var $args is empty => the path is '/'.
        $filepath = '/';
    } else {
        // Var $args contains elements of the filepath.
        $filepath = '/' . implode('/', $args) . '/';
    }
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_urise', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
    // Send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 0, 0, true, $options);
}
