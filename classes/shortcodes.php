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
 * Shortcodes for local_berta
 *
 * @package local_berta
 * @subpackage db
 * @since Moodle 3.11
 * @copyright 2024 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_berta;

use Closure;
use coding_exception;
use context_system;
use dml_exception;
use mod_booking\output\page_allteachers;
use local_berta\output\userinformation;
use local_berta\table\berta_table;
use local_shopping_cart\shopping_cart;
use local_shopping_cart\shopping_cart_credits;
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\standardfilter;
use mod_booking\booking;
use mod_booking\singleton_service;
use moodle_url;
use stdClass;

/**
 * Deals with local_shortcodes regarding booking.
 */
class shortcodes {

    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function userinformation($shortcode, $args, $content, $env, $next) {

        global $USER, $PAGE;

        self::fix_args($args);

        $userid = $args['userid'] ?? 0;
        // If the id argument was not passed on, we have a fallback in the connfig.
        $context = context_system::instance();
        if (empty($userid) && has_capability('local/shopping_cart:cashier', $context)) {
            $userid = shopping_cart::return_buy_for_userid();
        } else if (!has_capability('local/shopping_cart:cashier', $context)) {
            $userid = $USER->id;
        }

        if (!isset($args['fields'])) {

            $args['fields'] = '';
        }

        $data = new userinformation($userid, $args['fields']);
        $output = $PAGE->get_renderer('local_berta');
        return $output->render_userinformation($data);
    }

    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function unifiedlist($shortcode, $args, $content, $env, $next) {

        global $DB;

        self::fix_args($args);

        $booking = self::get_booking($args);

        $bookingids = explode(',', get_config('local_berta', 'multibookinginstances'));

        if (!isset($args['organisation']) || !$category = ($args['organisation'])) {
            $organisation = '';
        }

        if (!isset($args['image']) || !$showimage = ($args['image'])) {
            $showimage = false;
        }

        if (empty($args['countlabel'])) {
            $args['countlabel'] = false;
        }

        $infinitescrollpage = is_numeric($args['infinitescrollpage'] ?? '') ? (int)$args['infinitescrollpage'] : 30;

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 100;
        } else {
            $infinitescrollpage = 0;
        }

        $table = self::inittableforcourses($booking);

        $table->showcountlabel = $args['countlabel'];
        $wherearray = ['bookingid' => $bookingids];

        if (!empty($organisation)) {
            $wherearray['organisation'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        }

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = true;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');

            $table->add_subcolumns('cardimage', ['image']);
        }

        self::set_table_options_from_arguments($table, $args);
        self::generate_table_for_list($table, $args);

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = $infinitescrollpage;

        $table->tabletemplate = 'local_berta/table_list';
        $table->showcountlabel = true;
        $table->showreloadbutton = true;

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);

        return $out;
    }

    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function unifiedmybookingslist($shortcode, $args, $content, $env, $next) {

        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/mod/booking/lib.php');

        self::fix_args($args);

        $booking = self::get_booking($args);

        $bookingids = explode(',', get_config('local_berta', 'multibookinginstances'));

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        if (!isset($args['image']) || !$showimage = ($args['image'])) {
            $showimage = false;
        }

        if (empty($args['countlabel'])) {
            $args['countlabel'] = false;
        }

        $infinitescrollpage = is_numeric($args['infinitescrollpage'] ?? '') ? (int)$args['infinitescrollpage'] : 30;

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 100;
        }

        $table = self::inittableforcourses($booking);

        $table->showcountlabel = $args['countlabel'];
        $wherearray = ['bookingid' => $bookingids];

        if (!empty($category)) {
            $wherearray['organisation'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0,
                    0,
                    '',
                    null,
                    $booking->context,
                    [],
                    $wherearray,
                    $USER->id,
                    [
                        MOD_BOOKING_STATUSPARAM_BOOKED,
                        MOD_BOOKING_STATUSPARAM_RESERVED,
                        MOD_BOOKING_STATUSPARAM_WAITINGLIST,
                        MOD_BOOKING_STATUSPARAM_NOTIFYMELIST,
                    ]
                );
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0,
                    0,
                    '',
                    null,
                    $booking->context,
                    [],
                    $wherearray,
                    $USER->id,
                    [
                        MOD_BOOKING_STATUSPARAM_BOOKED,
                        MOD_BOOKING_STATUSPARAM_RESERVED,
                        MOD_BOOKING_STATUSPARAM_WAITINGLIST,
                        MOD_BOOKING_STATUSPARAM_NOTIFYMELIST,
                    ]
                );
        }

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = true;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');

            $table->add_subcolumns('cardimage', ['image']);
        }

        self::set_table_options_from_arguments($table, $args);
        if (!empty($args['cards'])) {
            self::generate_table_for_cards($table, $args);
            $table->tabletemplate = 'local_berta/table_card';
        } else {
            self::generate_table_for_list($table, $args);
            $table->tabletemplate = 'local_berta/table_list';
        }

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = $infinitescrollpage;

        $table->showcountlabel = true;
        $table->showreloadbutton = true;

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);

        return $out;
    }

    /**
     * Prints out user dashboard overview as cards.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function userdashboardcards($shortcode, $args, $content, $env, $next) {
        global $DB, $PAGE, $USER;
        self::fix_args($args);
        // If the id argument was not passed on, we have a fallback in the connfig.
        if (!isset($args['id'])) {
            $args['id'] = get_config('local_berta', 'shortcodessetinstance');
        }

        // To prevent misconfiguration, id has to be there and int.
        if (!(isset($args['id']) && $args['id'] && is_int((int)$args['id']))) {
            return 'Set id of booking instance';
        }

        if (!$booking = singleton_service::get_instance_of_booking_by_cmid($args['id'])) {
            return 'Couldn\'t find right booking instance ' . $args['id'];
        }

        $user = $USER;

        $booked = $booking->get_user_booking_count($USER);
        $asteacher = $DB->get_fieldset_select('booking_teachers', 'optionid',
            "userid = {$USER->id} AND bookingid = $booking->id ");
        $credits = shopping_cart_credits::get_balance($USER->id);

        $data['booked'] = $booked;
        $data['teacher'] = count($asteacher);
        $data['credits'] = $credits[0];

        $output = $PAGE->get_renderer('local_berta');
        return $output->render_user_dashboard_overview($data);

    }

    private static function inittableforcourses($booking) {

        global $PAGE, $USER;

        $tablename = bin2hex(random_bytes(12));

        // It's important to have the baseurl defined, we use it as a return url at one point.
        $baseurl = $PAGE->url ?? new moodle_url('');

        // On the cashier page, we want to buy for different users...
        // ...else we always want to buy for ourselves.
        if (strpos($baseurl->out(), "cashier.php") !== false) {
            $buyforuserid = null;
        } else {
            $buyforuserid = $USER->id;
        }

        $table = new berta_table($tablename);

        $table->define_baseurl($baseurl->out());
        $table->cardsort = true;
        // Without defining sorting won't work!
        $table->define_columns(['titleprefix']);
        return $table;
    }

    private static function define_filtercolumns(&$table) {

        $standardfilter = new standardfilter('organisation', get_string('organisation', 'local_berta'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('dayofweek', get_string('dayofweek', 'local_berta'));
        $standardfilter->add_options([
            'monday' => get_string('monday', 'mod_booking'),
            'tuesday' => get_string('tuesday', 'mod_booking'),
            'wednesday' => get_string('wednesday', 'mod_booking'),
            'thursday' => get_string('thursday', 'mod_booking'),
            'friday' => get_string('friday', 'mod_booking'),
            'saturday' => get_string('saturday', 'mod_booking'),
            'sunday' => get_string('sunday', 'mod_booking')
        ]);
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('location', get_string('location', 'mod_booking'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('botags', get_string('tags', 'core'));
        $table->add_filter($standardfilter);

        if (get_config('local_berta', 'bertashortcodesshowfiltercoursetime')) {

            $datepicker = new datepicker(
                'coursestarttime',
                get_string('timefilter:coursetime', 'mod_booking'),
                'columntimeend'
            );
            $datepicker->add_options(
                'in between',
                '<',
                get_string('apply_filter', 'local_wunderbyte_table'),
                'now',
                'now + 1 year'
            );

            $table->add_filter($datepicker);
        }

        if (get_config('local_berta', 'bertashortcodesshowfilterbookingtime')) {

            $datepicker = new datepicker(
                'bookingopeningtime',
                get_string('bookingopeningtime', 'mod_booking'),
                'bookingclosingtime',
                get_string('bookingclosingtime', 'mod_booking')
            );
            $datepicker->add_options(
                'in between',
                '<',
                get_string('apply_filter', 'local_wunderbyte_table'),
                'now',
                'now + 1 year'
            );

            $table->add_filter($datepicker);
        }
    }

    private static function get_booking($args) {
        self::fix_args($args);
        // If the id argument was not passed on, we have a fallback in the connfig.
        if (!isset($args['id'])) {
            $args['id'] = get_config('local_berta', 'shortcodessetinstance');
        }

        // To prevent misconfiguration, id has to be there and int.
        if (!(isset($args['id']) && $args['id'] && is_int((int)$args['id']))) {
            return 'Set id of booking instance';
        }

        if (!$booking = singleton_service::get_instance_of_booking_by_cmid($args['id'])) {
            return 'Couldn\'t find right booking instance ' . $args['id'];
        }

        return $booking;
    }

    private static function set_table_options_from_arguments(&$table, $args) {
        self::fix_args($args);

        /** @var berta_table $table */
        $table->set_display_options($args);

        if (!empty($args['filter'])) {
            self::define_filtercolumns($table);
        }

        if (!empty($args['search'])) {
            $table->define_fulltextsearchcolumns([
                'titleprefix', 'text', 'organisation', 'description', 'location',
                'teacherobjects', 'botags']);
        }

        if (!empty($args['sort'])) {
            $sortablecolumns = [
                'titleprefix' => get_string('titleprefix', 'local_berta'),
                'text' => get_string('coursename', 'local_berta'),
                'organisation' => get_string('organisation', 'local_berta'),
                'location' => get_string('location', 'local_berta'),
            ];
            if (get_config('local_berta', 'bertashortcodesshowstart')) {
                $sortablecolumns['coursestarttime'] = get_string('coursestarttime', 'mod_booking');
            }
            if (get_config('local_berta', 'bertashortcodesshowend')) {
                $sortablecolumns['courseendtime'] = get_string('courseendtime', 'mod_booking');
            }
            if (get_config('local_berta', 'bertashortcodesshowbookablefrom')) {
                $sortablecolumns['bookingopeningtime'] = get_string('bookingopeningtime', 'mod_booking');
            }
            if (get_config('local_berta', 'bertashortcodesshowbookableuntil')) {
                $sortablecolumns['bookingclosingtime'] = get_string('bookingclosingtime', 'mod_booking');
            }
            $table->define_sortablecolumns($sortablecolumns);
        }

        $defaultorder = SORT_ASC; // Default.
        if (!empty($args['sortorder'])) {
            if (strtolower($args['sortorder']) === "desc") {
                $defaultorder = SORT_DESC;
            }
        }
        if (!empty($args['sortby'])) {
            $table->sortable(true, $args['sortby'], $defaultorder);
        } else {
            $table->sortable(true, 'text', $defaultorder);
        }

        if (isset($args['requirelogin']) && $args['requirelogin'] == "false") {
            $table->requirelogin = false;
        }
    }

    /**
     * Generate table for card design.
     * @param mixed $table
     * @param mixed $args
     * @return [type]
     */
    private static function generate_table_for_cards(&$table, $args) {
        self::fix_args($args);
        $table->define_cache('mod_booking', 'bookingoptionstable');

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);

        $table->add_subcolumns('cardimage', ['image']);
        $table->add_subcolumns('optioninvisible', ['invisibleoption']);

        $table->add_subcolumns('cardbody', ['action', 'invisibleoption', 'organisation', 'text', 'botags']);
        $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'float-right m-1'], ['action']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'font-size-sm'], ['botags']);
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['columnvalueclass' => 'text-center shortcodes_option_info_invisible'],
            ['invisibleoption']
        );
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'organisation-badge rounded-sm text-gray-800 mt-2'],
            ['organisation']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'm-0 mt-1 mb-1 h5'], ['text']);

        // Subcolumns.
        $subcolumns = ['teacher', 'dayofweektime', 'location', 'institution', 'responsiblecontact'];
        if (get_config('local_berta', 'bertashortcodesshowstart')) {
            $subcolumns[] = 'coursestarttime';
        }
        if (get_config('local_berta', 'bertashortcodesshowend')) {
            $subcolumns[] = 'courseendtime';
        }
        if (get_config('local_berta', 'bertashortcodesshowbookablefrom')) {
            $subcolumns[] = 'bookingopeningtime';
        }
        if (get_config('local_berta', 'bertashortcodesshowbookableuntil')) {
            $subcolumns[] = 'bookingclosingtime';
        }
        $subcolumns[] = 'bookings';
        if (!empty($args['showminanswers'])) {
            $subcolumns[] = 'minanswers';
        }

        $table->add_subcolumns('cardlist', $subcolumns);
        $table->add_classes_to_subcolumns('cardlist', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardlist', ['columnvalueclass' => 'text-secondary']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'text-secondary']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-map-marker'], ['location']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-building-o'], ['institution']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-user'], ['responsiblecontact']);

        if (get_config('local_berta', 'bertashortcodesshowstart')) {
            $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-play'], ['coursestarttime']);
        }
        if (get_config('local_berta', 'bertashortcodesshowend')) {
            $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-stop'], ['courseendtime']);
        }
        if (get_config('local_berta', 'bertashortcodesshowbookablefrom')) {
            $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-forward'], ['bookingopeningtime']);
        }
        if (get_config('local_berta', 'bertashortcodesshowbookableuntil')) {
            $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-step-forward'],
                ['bookingclosingtime']);
        }

        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-clock-o'], ['dayofweektime']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-users'], ['bookings']);
        if (!empty($args['showminanswers'])) {
            $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-arrow-up'], ['minanswers']);
        }

        // Set additional descriptions.
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('teacheralt', 'local_berta')], ['teacher']);
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('locationalt', 'local_berta')], ['location']);
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('dayofweekalt', 'local_berta')], ['dayofweektime']);
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('bookingsalt', 'local_berta')], ['bookings']);
        $table->add_classes_to_subcolumns('cardimage', ['cardimagealt' => get_string('imagealt', 'local_berta')], ['image']);

        $table->add_subcolumns('cardfooter', ['course', 'price']);
        $table->add_classes_to_subcolumns('cardfooter', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardfooter', ['columnclass' => 'theme-text-color bold '], ['price']);
        $table->set_tableclass('cardimageclass', 'w-100');

        $table->is_downloading('', 'List of booking options');
    }

    /**
     * Generate table for list.
     * @param mixed $table
     * @param mixed $args
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    private static function generate_table_for_list(&$table, $args) {

        self::fix_args($args);

        $subcolumnsleftside = ['text'];
        $subcolumnsinfo = ['teacher', 'dayofweektime', 'location', 'institution'];
        if (get_config('local_berta', 'bertashortcodesshowstart')) {
            $subcolumnsinfo[] = 'coursestarttime';
        }
        if (get_config('local_berta', 'bertashortcodesshowend')) {
            $subcolumnsinfo[] = 'courseendtime';
        }
        if (get_config('local_berta', 'bertashortcodesshowbookablefrom')) {
            $subcolumnsinfo[] = 'bookingopeningtime';
        }
        if (get_config('local_berta', 'bertashortcodesshowbookableuntil')) {
            $subcolumnsinfo[] = 'bookingclosingtime';
        }
        $subcolumnsinfo[] = 'bookings';

        // Check if we should add the description.
        if (get_config('local_berta', 'shortcodelists_showdescriptions')) {
            $subcolumnsleftside[] = 'description';
        }

        if (!empty($args['showminanswers'])) {
            $subcolumnsinfo[] = 'minanswers';
        }

        $table->define_cache('mod_booking', 'bookingoptionstable');

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);

        $table->add_subcolumns('top', ['organisation', 'action']);
        $table->add_subcolumns('leftside', $subcolumnsleftside);
        $table->add_subcolumns('info', $subcolumnsinfo);

        $table->add_subcolumns('rightside', ['botags', 'invisibleoption', 'course', 'price']);

        $table->add_classes_to_subcolumns('top', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('top', ['columnclass' => 'text-left col-md-8'], ['organisation']);
        $table->add_classes_to_subcolumns('top', ['columnvalueclass' =>
            'organisation-badge rounded-sm text-gray-800 mt-2'], ['organisation']);
        $table->add_classes_to_subcolumns('top', ['columnclass' => 'text-right col-md-2 position-relative pr-0'], ['action']);

        $table->add_classes_to_subcolumns('leftside', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-1 h3 col-md-auto'], ['text']);
        if (get_config('local_berta', 'shortcodelists_showdescriptions')) {
            $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-3 col-md-auto'], ['description']);
        }
        $table->add_classes_to_subcolumns('info', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('info', ['columnclass' => 'text-left text-secondary font-size-sm pr-2']);
        $table->add_classes_to_subcolumns('info', ['columnvalueclass' => 'd-flex'], ['teacher']);
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-clock-o'], ['dayofweektime']);
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-map-marker'], ['location']);
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-building-o'], ['institution']);
        if (get_config('local_berta', 'bertashortcodesshowstart')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-play'], ['coursestarttime']);
        }
        if (get_config('local_berta', 'bertashortcodesshowend')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-stop'], ['courseendtime']);
        }
        if (get_config('local_berta', 'bertashortcodesshowbookablefrom')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-forward'], ['bookingopeningtime']);
        }
        if (get_config('local_berta', 'bertashortcodesshowbookableuntil')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-step-forward'], ['bookingclosingtime']);
        }
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-ticket'], ['bookings']);
        if (!empty($args['showminanswers'])) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-arrow-up'], ['minanswers']);
        }

        // Set additional descriptions.
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('teacheralt', 'local_berta')], ['teacher']);
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('dayofweekalt', 'local_berta')], ['dayofweektime']);
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('locationalt', 'local_berta')], ['location']);
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('bookingsalt', 'local_berta')], ['bookings']);

        $table->add_classes_to_subcolumns('rightside',
            ['columnvalueclass' => 'text-right mb-auto align-self-end shortcodes_option_info_invisible '],
            ['invisibleoption']);
        $table->add_classes_to_subcolumns('rightside', ['columnclass' => 'text-right mb-auto align-self-end '], ['botags']);
        $table->add_classes_to_subcolumns('rightside', ['columnclass' =>
            'text-right mt-auto w-100 align-self-end theme-text-color bold '], ['price']);

        // Override naming for columns. one could use getstring for localisation here.
        $table->add_classes_to_subcolumns(
            'top',
            ['keystring' => get_string('tableheader_text', 'booking')],
            ['organisation']
        );
        $table->add_classes_to_subcolumns(
            'leftside',
            ['keystring' => get_string('tableheader_text', 'booking')],
            ['text']
        );
        $table->add_classes_to_subcolumns(
            'info',
            ['keystring' => get_string('tableheader_maxanswers', 'booking')],
            ['maxanswers']
        );
        $table->add_classes_to_subcolumns(
            'info',
            ['keystring' => get_string('tableheader_maxoverbooking', 'booking')],
            ['maxoverbooking']
        );

        $table->is_downloading('', 'List of booking options');
    }

    /**
     * Helper function to remove quotation marks from args.
     * @param array &$args reference to arguments array
     */
    private static function fix_args(array &$args) {
        foreach ($args as $key => &$value) {
            // Get rid of quotation marks.
            $value = str_replace('"', '', $value);
            $value = str_replace("'", "", $value);
        }
    }
}
