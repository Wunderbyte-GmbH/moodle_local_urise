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
use mod_booking\booking_answers;
use mod_booking\local\modechecker;

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
class urise_table extends wunderbyte_table {

    /** @var array $displayoptions */
    private $displayoptions = [];

    public function set_display_options($displayoptions) {

        // Units, e.g. "(UE: 1,3)".
        if (isset($displayoptions['showunits'])) { // Do not use empty here!!
            $this->displayoptions['showunits'] = (bool) $displayoptions['showunits'];
            // We need this for mustache tow work.
            if (!$this->displayoptions['showunits']) {
                unset($this->displayoptions['showunits']);
            }
        }

        // Max. answers.
        if (!isset($displayoptions['showmaxanwers'])) { // Do not use empty here!!
            $this->displayoptions['showmaxanwers'] = true; // Max. answers are shown by default.
        } else {
            $this->displayoptions['showmaxanwers'] = (bool) $displayoptions['showmaxanwers'];
            // We need this for mustache tow work.
            if (!$this->displayoptions['showmaxanwers']) {
                unset($this->displayoptions['showmaxanwers']);
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * invisible value. It's called 'invisibleoption' so it does not interfere with
     * the bootstrap class 'invisible'.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $invisible Returns visibility of the booking option as string.
     * @throws coding_exception
     */
    public function col_invisibleoption($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        if (!empty($settings->invisible)) {
            return get_string('invisibleoption', 'local_urise');
        } else {
            return '';
        }
    }

    /**
     * Returns the image url
     *
     * @param mixed $values
     *
     * @return string
     *
     */
    public function col_image($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);
        if (
            !isloggedin()
            || isguestuser()
            || empty($settings->imageurl)
        ) {
            return '';
        }

        return $settings->imageurl;
    }

    /**
     * This function is called for each data row to allow processing of the
     * teacher value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $string Return name of the booking option.
     * @throws dml_exception
     */
    public function col_teacher($values) {

        // Render col_teacher using a template.
        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);
        $data = new col_teacher($values->id, $settings);

        $numitems = count($data->teachers);
        $i = 0;
        foreach ($data->teachers as $key => &$value) {
            if (++$i === $numitems) {
                $value['last'] = true;
            } else {
                $value['last'] = false;
            }
        }
        $output = singleton_service::get_renderer('local_urise');
        return $output->render_col_teacher($data);;
    }

       /**
        * This function is called for each data row to allow processing of the
        * kurssprache value.
        *
        * @param object $values Contains object with all the values of record.
        * @return string $string Return name of the booking option.
        * @throws dml_exception
        */
    public function col_kurssprache($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        if (isset($settings->customfieldsfortemplates) && isset($settings->customfieldsfortemplates['kurssprache'])) {
            $value = $settings->customfieldsfortemplates['kurssprache']['value'];
            return format_string($value);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * kurssprache value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $string Return name of the booking option.
     * @throws dml_exceptiond
     */
    public function col_format($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        if (isset($settings->customfieldsfortemplates) && isset($settings->customfieldsfortemplates['format'])) {
                $value = $settings->customfieldsfortemplates['format']['value'];
                return format_string($value);
        }

    }

    //     /**
    //      * This function is called for each data row to allow processing of the
    //      * category value.
    //      *
    //      * @param object $values Contains object with all the values of record.
    //      * @return string $string Return name of the booking option.
    //      * @throws dml_exceptiond
    //      */
    // public function col_category($values) {

    //     $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

    //     if (isset($settings->customfields) && isset($settings->customfields['category'])) {
    //         if (strpos($settings->customfields['category'], ',') !== false) {
    //             // Split the string by commas into an array
    //             $values = explode(',', $settings->customfields['category']);
    //             // Loop over each value in the array
    //             $string = '';
    //             foreach ($values as $value) {
    //                 // Optionally trim whitespace from each value
    //                 $value = trim($value);
    //                 $stringpart = "<span class='bg-secondary pl-1 pr-1 mr-1 rounded category'>
    //                 $value
    //                 </span>";
    //                 $string = $string . $stringpart;
    //             }
    //             return $string;
    //         } else {
    //             $value = $settings->customfields['category'];
    //             $string = "<span class='bg-secondary pl-1 pr-1 mr-1 rounded category'>
    //             $value
    //             </span>";
    //             return $string;
    //         }
    //     }

    // }

    /**
     * This function is called for each data row to allow processing of the
     * price value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $string Return name of the booking option.
     * @throws dml_exception
     */
    public function col_price($values) {
        // Render col_price using a template.
        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);
        $buyforuser = price::return_user_to_buy_for();

        return booking_bookit::render_bookit_button($settings, $buyforuser->id);
    }

    /**
     * This function is called for each data row to allow processing of the
     * text value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $string Return name of the booking option.
     * @throws dml_exception
     */
    public function col_text($values) {

        global $PAGE;

        $booking = singleton_service::get_instance_of_booking_by_bookingid($values->bookingid);

        $url = $this->col_url($values);

        $title = format_text($values->text);
        if (!empty($values->titleprefix)) {
            $title = $values->titleprefix . ' - ' . $title;
        }

        $context = context_module::instance($booking->cmid);

        if (!isloggedin() || isguestuser() || !$url) {
            return html_writer::tag('div', $title, ['class' => 'urise-table-option-title']);
        }

        if (!$this->is_downloading()) {
            $title = html_writer::tag(
                'a',
                $title,
                [
                    'href' => $url,
                    'target' => '_blank',
                ]
            );
            $title = html_writer::tag(
                'div',
                $title,
                [
                    'class' => 'urise-table-option-title',
                ]
            );
        }

        return $title;
    }

    /**
     * This function is called for each data row to allow processing of the
     * text value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string|bool $string Return name of the booking option or false.
     * @throws dml_exception
     */
    public function col_url($values) {
        global $PAGE;

        $booking = singleton_service::get_instance_of_booking_by_bookingid($values->bookingid);
        $buyforuser = price::return_user_to_buy_for();

        $context = context_module::instance($booking->cmid);

        if (
            $booking
            && isloggedin()
            && !isguestuser()
        ) {
            if (!modechecker::is_ajax_or_webservice_request()) {
                $returnurl = $PAGE->url->out(false);
            } else {
                $returnurl = '/';
            }

            // The current page is not /mod/booking/optionview.php.
            $url = new moodle_url("/mod/booking/optionview.php", [
                "optionid" => (int)$values->id,
                "cmid" => (int)$booking->cmid,
                "userid" => (int)$buyforuser->id,
                'returnto' => 'url',
                'returnurl' => $returnurl,
            ]);
        } else {
            return false;
        }

        return $url->out(false);
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

    /**
     * This function is called for each data row to allow processing of the
     * description value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $ret the return string
     * @throws coding_exception
     */
    public function col_description($values) {

        $fulldescription = $values->description;
        $ret = $fulldescription;

        if (!empty(get_config('local_urise', 'collapsedescriptionmaxlength'))) {

            $maxlength = (int)get_config('local_urise', 'collapsedescriptionmaxlength');

            // Show collapsible for long descriptions.
            $shortdescription = strip_tags($fulldescription, '<br>');
            if (strlen($shortdescription) > $maxlength) {
                $shortdescription = substr($shortdescription, 0, $maxlength) . '...';

                $ret =
                    '<div>
                        <a data-toggle="collapse" href="#collapseDescription' . $values->id . '" role="button"
                            aria-expanded="false" aria-controls="collapseDescription">
                            <i class="fa fa-info-circle" aria-hidden="true"></i>&nbsp;' .
                            get_string('showdescription', 'local_urise') . '...</a>
                    </div>
                    <div class="collapse" id="collapseDescription' . $values->id . '">
                        <div class="card card-body border-1 mt-1 mb-1 mr-3">' . $fulldescription . '</div>
                    </div>';
            }
        }

        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * booking value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $coursestarttime Returns course start time as a readable string.
     * @throws coding_exception
     */
    public function col_bookings($values) {

        global $PAGE;

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);
        // Render col_bookings using a template.

        $buyforuser = price::return_user_to_buy_for();

        $data = new col_availableplaces($values, $settings, $buyforuser);
        if (!empty($this->displayoptions['showmaxanwers'])) {
            $data->showmaxanswers = $this->displayoptions['showmaxanwers'];
        }
        $output = singleton_service::get_renderer('local_urise');
        return $output->render_col_availableplaces($data);
    }

    /**
     * This function is called for each data row to allow processing of the
     * minanswers value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string containing the minanswers description and value
     * @throws coding_exception
     */
    public function col_minanswers($values) {
        $ret = null;
        if (!empty($values->minanswers)) {
            $ret = get_string('minanswers', 'mod_booking') . ": $values->minanswers";
        }
        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * location value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string location
     * @throws coding_exception
     */
    public function col_location($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        if (isset($settings->entity) && (count($settings->entity) > 0)) {

            $url = new moodle_url('/local/entities/view.php', ['id' => $settings->entity['id']]);
            // Full name of the entity (NOT the shortname).

            if (!empty($settings->entity['parentname'])) {
                $nametobeshown = $settings->entity['parentname'] . " (" . $settings->entity['name'] . ")";
            } else {
                $nametobeshown = $settings->entity['name'];
            }

            return html_writer::tag('a', $nametobeshown, ['href' => $url->out(false)]);
        }

        return $settings->location;
    }

    /**
     * This function is called for each data row to allow processing of the
     * sports value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $sports Returns rendered sport.
     * @throws coding_exception
     */
    public function col_kompetenzen($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        if (isset($settings->customfields) && isset($settings->customfields['kompetenzen'])) {
            if (is_array($settings->customfields['kompetenzen'])) {

                $returnorgas = [];
                foreach ($settings->customfields['kompetenzen'] as $orgaid) {
                    $organisations = shortcodes::get_kompetenzen();

                    if (isset($organisations[$orgaid])) {
                        $returnorgas[] = html_writer::tag(
                            'span',
                            $organisations[$orgaid]['localizedname'],
                            ['class' => 'bg-secondary pl-1 pr-1 mr-1 rounded category']
                        );

                    }
                }

                return implode("", $returnorgas);
            } else {
                $value = $settings->customfields['kompetenzen'];
                $message = "<span class='bg-secondary orga'>$value</span>";
                return $message;
            }
        }

        $context = context_module::instance($settings->cmid);

        // The error message should only be shown to admins.
        if (has_capability('moodle/site:config', $context)) {

            $message = get_string('youneedcustomfieldsport', 'local_urise');

            $message = "<div class='alert alert-danger'>$message</div>";

            return $message;
        }

        // Normal users won't notice the problem.
        return '';
    }

    /**
     * This function is called for each data row to allow processing of the
     * sports value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $sports Returns rendered sport.
     * @throws coding_exception
     */
    public function col_organisation($values) {
        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        if (isset($settings->customfields) && isset($settings->customfields['organisation'])) {
            if (is_array($settings->customfields['organisation'])) {
                $returnorgas = [];
                $strlen = 0;
                foreach ($settings->customfields['organisation'] as $orgaid) {
                    $organisations = shortcodes::organisations();

                    if (isset($organisations[$orgaid])) {
                        $returnorgas[] = html_writer::tag(
                            'span',
                            $organisations[$orgaid]['localizedname'],
                            ['class' => 'bg-secondary pl-1 pr-1 mr-1 rounded category']
                        );
                        $strlen += strlen($organisations[$orgaid]['localizedname']);
                    }
                }

                if (count($returnorgas) > 1 && $strlen > 70) {
                    $output = html_writer::tag(
                        'span',
                        get_string('jointevent', 'local_urise'),
                        ['class' => 'bg-secondary pl-1 pr-1 mr-1 rounded category']
                    );
                } else {
                    $output = implode(" ", $returnorgas);
                }
                return $output;
            } else {
                $value = $settings->customfields['organisation'];
                $message = "<span class='bg-secondary orga'>$value</span>";
                return $message;
            }
        }

        // Normal users won't notice the problem.
        return '';
    }



    /**
     * This function is called for each data row to allow processing of the
     * booking option tags (botags).
     *
     * @param object $values Contains object with all the values of record.
     * @return string $sports Returns course start time as a readable string.
     * @throws coding_exception
     */
    public function col_botags($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        $botagsstring = '';

        if (isset($settings->customfields) && isset($settings->customfields['botags'])) {
            $botagsarray = $settings->customfields['botags'];
            if (!empty($botagsarray)) {

                if (!is_array($botagsarray)) {
                    $botagsarray = (array)$botagsarray;
                }
                foreach ($botagsarray as $botag) {
                    if (!empty($botag)) {
                        $botagsstring .=
                            "<span class='urise-table-botag rounded-sm bg-info text-light pl-2 pr-2 pt-1 pb-1 mr-1 d-inline-block text-center lexa-caption'>
                            $botag
                            </span>";
                    } else {
                        continue;
                    }
                }
                if (!empty($botagsstring)) {
                    return $botagsstring;
                } else {
                    return '';
                }
            }
        }
        return '';
    }

    /**
     * This function is called for each data row to allow processing of the
     * associated Moodle course.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a link to the Moodle course - if there is one
     * @throws coding_exception
     */
    public function col_course($values) {

        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);
        $ret = '';

        $moodleurl = new moodle_url('/course/view.php', ['id' => $settings->courseid]);
        $courseurl = $moodleurl->out(false);
        // If we download, we want to return the plain URL.
        if ($this->is_downloading()) {
            return $courseurl;
        }

        $buyforuser = price::return_user_to_buy_for();

        $answersobject = singleton_service::get_instance_of_booking_answers($settings);
        $status = $answersobject->user_status($buyforuser->id);

        $isteacherofthisoption = booking_check_if_teacher($values);

        if (!empty($settings->cmid)) {
            $context = context_module::instance($settings->cmid);
        } else {
            $context = $this->get_context();
        }

        // When we have this seeting, we never show the link here:
        if (get_config('booking', 'linktomoodlecourseonbookedbutton')
            && (!has_capability('mod/booking:updatebooking', $context)
            && !$isteacherofthisoption)) {
            return '';
        }

        if (!empty($settings->courseid) && (
                $status == 0 // MOD_BOOKING_STATUSPARAM_BOOKED.
                || has_capability('mod/booking:updatebooking', $context) ||
                $isteacherofthisoption)) {
            // The link will be shown to everyone who...
            // ...has booked this option.
            // ...is a teacher of this option.
            // ...has the system-wide "updatebooking" capability (admins).
            $gotomoodlecourse = get_string('tocoursecontent', 'local_urise');
            $ret = "<a href='$courseurl' target='_self' class='btn btn-nolabel p-1 ml-2 w-100'>
                <i class='fa fa-graduation-cap fa-fw' aria-hidden='true'></i>&nbsp;&nbsp;$gotomoodlecourse
            </a>";
        }

        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * dayofweektime value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $dayofweektime String for date series, e.g. "Mon, 16:00 - 17:00"
     * @throws coding_exception
     */
    public function col_dayofweektime($values) {

        $ret = '';
        $settings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);

        if (!empty($settings->dayofweektime)) {
            $localweekdays = dates_handler::get_localized_weekdays(current_language());
            $dayinfo = dates_handler::prepare_day_info($settings->dayofweektime);
            if (isset($dayinfo['day']) && $dayinfo['starttime'] && $dayinfo['endtime']) {
                $ret = $localweekdays[$dayinfo['day']] . ', '.$dayinfo['starttime'] . ' - ' . $dayinfo['endtime'];
            } else if (!empty($settings->dayofweektime)) {
                $ret = $settings->dayofweektime;
            } else {
                $ret = get_string('datenotset', 'mod_booking');
            }
        }

        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * courseendtime value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $courseendtime Returns course end time as a readable string.
     * @throws coding_exception
     */
    public function col_coursedates($values) {

        // Prepare date string.
        if ($values->coursestarttime != 0) {
            $returnarray[] = userdate($values->coursestarttime, get_string('strftimedatetime'));
        }

        // Prepare date string.
        if ($values->courseendtime != 0) {
            $returnarray[] = userdate($values->courseendtime, get_string('strftimedatetime'));
        }

        return implode(' - ', $returnarray);
    }

    /**
     * This function is called for each data row to add a link
     * for managing responses (booking_answers).
     *
     * @param object $values Contains object with all the values of record.
     * @return string $link Returns a link to report.php (manage responses).
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function col_manageresponses($values) {
        global $CFG, $DB;

        // Link is empty on default.
        $link = '';

        $settings = singleton_service::get_instance_of_booking_option_settings($values->optionid, $values);
        $bookinganswers = singleton_service::get_instance_of_booking_answers($settings, 0);

        if (booking_answers::count_places($bookinganswers->usersonlist) > 0) {
            // Add a link to redirect to the booking option.
            $link = new moodle_url(
                $CFG->wwwroot . '/mod/booking/report.php',
                [
                    'id' => $values->cmid,
                    'optionid' => $values->optionid,
                ]
            );
            // Use html_entity_decode to convert "&amp;" to a simple "&" character.
            if ($CFG->version >= 2023042400) {
                // Moodle 4.2 needs second param.
                $link = html_entity_decode($link->out(), ENT_QUOTES);
            } else {
                // Moodle 4.1 and older.
                $link = html_entity_decode($link->out(), ENT_COMPAT);
            }

            if (!$this->is_downloading()) {
                // Only format as a button if it's not an export.
                $link = '<a href="' . $link . '" class="btn btn-secondary">'
                    . get_string('bstmanageresponses', 'mod_booking')
                    . '</a>';
            }
        }
        // Do not show a link if there are no answers.

        return $link;
    }

    /**
     * This function is called for each data row to allow processing of the
     * "bookingopeningtime" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string containing the booking opening time
     * @throws coding_exception
     */
    public function col_bookingopeningtime($values) {
        $bookingopeningtime = $values->bookingopeningtime;
        if (empty($bookingopeningtime)) {
            return '';
        }

        switch (current_language()) {
            case 'de':
                $renderedbookingopeningtime = date('d.m.Y, H:i', $bookingopeningtime);
                break;
            default:
                $renderedbookingopeningtime = date('M d, Y, H:i', $bookingopeningtime);
                break;
        }

        if ($this->is_downloading()) {
            $ret = $renderedbookingopeningtime;
        } else {
            $ret = get_string('bookingopeningtime', 'mod_booking') . ": " . $renderedbookingopeningtime;
        }
        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * "col_bookingclosingtime" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string containing the booking closing time
     * @throws coding_exception
     */
    public function col_bookingclosingtime($values) {
        $bookingclosingtime = $values->bookingclosingtime;
        if (empty($bookingclosingtime)) {
            return '';
        }

        switch (current_language()) {
            case 'de':
                $renderedbookingclosingtime = date('d.m.Y, H:i', $bookingclosingtime);
                break;
            default:
                $renderedbookingclosingtime = date('M d, Y, H:i', $bookingclosingtime);
                break;
        }

        if ($this->is_downloading()) {
            $ret = $renderedbookingclosingtime;
        } else {
            $ret = get_string('bookingclosingtime', 'mod_booking') . ": " . $renderedbookingclosingtime;
        }
        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * "coursestarttime" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string containing the course start time
     * @throws coding_exception
     */
    public function col_coursestarttime($values) {
        $coursestarttime = $values->coursestarttime;
        if (empty($coursestarttime)) {
            return '';
        }

        switch (current_language()) {
            case 'de':
                $renderedcoursestarttime = date('d.m.Y, H:i', $coursestarttime);
                break;
            default:
                $renderedcoursestarttime = date('M d, Y, H:i', $coursestarttime);
                break;
        }

        if ($this->is_downloading()) {
            $ret = $renderedcoursestarttime;
        } else {
            $ret = get_string('coursestarttime', 'mod_booking') . ": " . $renderedcoursestarttime;
        }
        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * "courseendtime" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string containing the course end time
     * @throws coding_exception
     */
    public function col_courseendtime($values) {
        $courseendtime = $values->courseendtime;
        if (empty($courseendtime)) {
            return '';
        }

        switch (current_language()) {
            case 'de':
                $renderedcourseendtime = date('d.m.Y, H:i', $courseendtime);
                break;
            default:
                $renderedcourseendtime = date('M d, Y, H:i', $courseendtime);
                break;
        }

        if ($this->is_downloading()) {
            $ret = $renderedcourseendtime;
        } else {
            $ret = get_string('courseendtime', 'mod_booking') . ": " . $renderedcourseendtime;
        }
        return $ret;
    }


    /**
     * This function is called for each data row to allow processing of the
     * showdates value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string containing collapsible dates
     * @throws coding_exception
     */
    public function col_showdates($values) {

        // NOTE: Do not use $this->cmid and $this->context because it might be that booking options come from different instances!
        // So we always need to retrieve them via singleton service for the current booking option ($values->id).
        $optionid = $values->id;
        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        $cmid = $settings->cmid;
        $booking = singleton_service::get_instance_of_booking_by_cmid($cmid);

        $ret = '';
        if ($this->is_downloading()) {
            $datestrings = dates_handler::return_array_of_sessions_datestrings($optionid);
            $ret = implode(' | ', $datestrings);
        } else {
            // Use the renderer to output this column.
            $lang = current_language();

            $cachekey = "sessiondates$optionid$lang";
            $cache = cache::make($this->cachecomponent, $this->rawcachename);

            if (!$ret = $cache->get($cachekey)) {
                $data = new \mod_booking\output\col_coursestarttime($optionid, $booking);
                $output = singleton_service::get_renderer('mod_booking');
                $ret = $output->render_col_coursestarttime($data);
                $cache->set($cachekey, $ret);
            }
        }
        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * responsiblecontact value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $string Return a link to the responsible contact's user profile.
     * @throws dml_exception
     */
    public function col_responsiblecontact($values) {
        $settings = singleton_service::get_instance_of_booking_option_settings($values->id);
        $ret = '';
        if (empty($settings->responsiblecontact)) {
            return $ret;
        }
        if ($user = singleton_service::get_instance_of_user($settings->responsiblecontact)) {
            $userstring = "$user->firstname $user->lastname";
            $emailstring = " ($user->email)";
            if ($this->is_downloading()) {
                $ret = $userstring . $emailstring;
            } else {
                $profileurl = new moodle_url('/user/profile.php', ['id' => $settings->responsiblecontact]);
                $ret = get_string('responsible', 'mod_booking')
                    . ":&nbsp;" . html_writer::link($profileurl, $userstring);
            }
        }
        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * action button.
     *
     * @param object $values Contains object with all the values of record.
     * @return string $action Returns formatted action button.
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function col_action($values) {

        $booking = singleton_service::get_instance_of_booking_by_bookingid($values->bookingid, $values);

        $data = new stdClass();

        $data->optionid = $values->id;
        $data->componentname = 'mod_booking';
        $data->cmid = $booking->cmid;

        // We will have a number of modals on this site, therefore we have to distinguish them.
        // This is in case we render modal.
        $data->modalcounter = $values->id;
        $data->modaltitle = $values->text;

        $buyforuser = price::return_user_to_buy_for();

        $data->userid = $buyforuser->id;

        // Get the URL to edit the option.
        if (!empty($values->id)) {
            $bosettings = singleton_service::get_instance_of_booking_option_settings($values->id, $values);
            if (!empty($bosettings)) {

                $context = context_module::instance($bosettings->cmid);

                // ONLY users with the mod/booking:updatebooking capability can edit options.
                $allowedit = has_capability('mod/booking:updatebooking', $context);
                if ($allowedit) {
                    if (isset($bosettings->editoptionurl)) {
                        // Get the URL to edit the option.
                        $data->editoptionurl = $this->add_return_url($bosettings->editoptionurl);
                    }
                }

                // Send e-mail to all booked users menu entry.
                $allowsendmailtoallbookedusers = (
                    get_config('booking', 'teachersallowmailtobookedusers') && (
                        has_capability('mod/booking:updatebooking', $context) ||
                        (has_capability('mod/booking:addeditownoption', $context) && booking_check_if_teacher($values)) ||
                        (has_capability('mod/booking:limitededitownoption', $context) && booking_check_if_teacher($values))
                    )
                );
                if ($allowsendmailtoallbookedusers) {
                    $mailtolink = booking_option::get_mailto_link_for_partipants($values->id);
                    if (!empty($mailtolink)) {
                        $data->sendmailtoallbookedusers = true;
                        $data->mailtobookeduserslink = $mailtolink;
                    }
                }

                // The simplified availability menu.
                $alloweditavailability = (
                    has_capability('local/urise:editavailability', $context) &&
                    (has_capability('mod/booking:updatebooking', $context) ||
                    (has_capability('mod/booking:addeditownoption', $context) && booking_check_if_teacher($values)) ||
                    (has_capability('mod/booking:limitededitownoption', $context) && booking_check_if_teacher($values)))
                );
                if ($alloweditavailability) {
                    $data->editavailability = true;
                }

                $canviewreports = (
                    has_capability('mod/booking:viewreports', $context)
                    || (has_capability('mod/booking:limitededitownoption', $context) && booking_check_if_teacher($values))
                    || has_capability('mod/booking:updatebooking', $context)
                );

                // If the user has no capability to editoptions, the URLs will not be added.
                if ($canviewreports) {

                    if (isset($bosettings->manageresponsesurl)) {
                        // Get the URL to manage responses (answers) for the option.
                        $data->manageresponsesurl = $bosettings->manageresponsesurl;
                    }

                    if (isset($bosettings->optiondatesteachersurl)) {
                        // Get the URL for the optiondates-teachers-report.
                        $data->optiondatesteachersurl = $bosettings->optiondatesteachersurl;
                    }
                }
            }
        }

        if (has_capability('local/shopping_cart:cashier', $context)) {
            // If booking option is already cancelled, we want to show the "undo cancel" button instead.
            if ($values->status == 1) {
                $data->showundocancel = true;
                $data->undocancellink = html_writer::link('#',
                '<i class="fa fa-undo fa-fw" aria-hidden="true"></i> ' .
                    get_string('undocancelthisbookingoption', 'mod_booking'),
                    [
                        'class' => 'dropdown-item undocancelallusers',
                        'data-id' => $values->id,
                        'data-componentname' => 'mod_booking',
                        'data-area' => 'option',
                        'onclick' =>
                            "require(['mod_booking/confirm_cancel'], function(init) {
                                init.init('" . $values->id . "', '" . $values->status . "');
                            });"
                    ]);
            } else {
                // Else we show the default cancel button.
                // We do NOT set $data->undocancel here.
                $data->showcancel = true;
                $data->cancellink = html_writer::link('#',
                '<i class="fa fa-ban fa-fw" aria-hidden="true"></i> ' .
                    get_string('cancelallusers', 'mod_booking'),
                    [
                        'class' => 'dropdown-item cancelallusers',
                        'data-id' => $values->id,
                        'data-componentname' => 'mod_booking',
                        'data-area' => 'option',
                        'onclick' =>
                            "require(['local_shopping_cart/menu'], function(menu) {
                                menu.confirmCancelAllUsersAndSetCreditModal('" . $values->id . "', 'mod_booking', 'option');
                            });"
                    ]);
            }
        } else {
            $data->showcancel = null;
            $data->showundocancel = null;
        }

        $output = singleton_service::get_renderer('local_urise');
        return $output->render_urise_bookingoption_menu($data);
    }

    /**
     * Override wunderbyte_table function and use own renderer.
     *
     * @return void
     */
    public function finish_html() {
        $table = new \local_wunderbyte_table\output\table($this);
        $output = singleton_service::get_renderer('mod_booking');
        echo $output->render_bookingoptions_wbtable($table);
    }

    private function add_return_url(string $urlstring):string {

        $returnurl = $this->baseurl->out();

        $urlcomponents = parse_url($urlstring);

        parse_str($urlcomponents['query'], $params);

        $url = new moodle_url(
            $urlcomponents['path'],
            array_merge(
                $params, [
                'returnto' => 'url',
                'returnurl' => $returnurl
                ]
            )
        );

        return $url->out(false);
    }
}
