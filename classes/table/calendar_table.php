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

namespace local_urise\table;
use local_urise\shortcodes;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../../lib.php');
require_once($CFG->libdir.'/tablelib.php');

use cache;
use coding_exception;
use context_module;
use context_system;
use dml_exception;
use html_writer;
use local_wunderbyte_table\wunderbyte_table;
use mod_booking\booking_bookit;
use mod_booking\booking_option;
use mod_booking\option\dates_handler;
use mod_booking\output\col_availableplaces;
use mod_booking\output\col_teacher;
use mod_booking\price;
use mod_booking\singleton_service;
use moodle_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Search results for managers are shown in a table (student search results use the template searchresults_student).
 */
class calendar_table extends wunderbyte_table {

    public function col_text($values) {

        $title = format_text($values->text);
        $title = strip_tags($title);
        if (!empty($values->titleprefix)) {
            $title = $values->titleprefix . ' - ' . $title;
        }
        return $title;
    }

    public function col_more($values)  {

        $booking = singleton_service::get_instance_of_booking_by_bookingid($values->bookingid);
        $buyforuser = price::return_user_to_buy_for();

        if ($booking) {
            $url = new moodle_url('/mod/booking/optionview.php', ['optionid' => $values->id,
                                                                  'cmid' => $booking->cmid,
                                                                  'userid' => $buyforuser->id]);
        } else {
            $url = '#';
        }
        return "<a href='$url' target='_blank' class=''>mehr</a>";
    }

    public function col_coursestarttime($values) {
        $coursestarttime = $values->coursestarttime;
        if (empty($coursestarttime)) {
            return '';
        }

        switch (current_language()) {
            case 'de':
                $renderedcoursestarttime = date('d.m.Y', $coursestarttime);
                break;
            default:
                $renderedcoursestarttime = date('M d, Y', $coursestarttime);
                break;
        }
        return $renderedcoursestarttime;
    }
}
