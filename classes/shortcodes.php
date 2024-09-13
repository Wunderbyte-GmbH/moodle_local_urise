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
 * Shortcodes for local_urise
 *
 * @package local_urise
 * @subpackage db
 * @since Moodle 3.11
 * @copyright 2024 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_urise;

use Closure;
use coding_exception;
use context_system;
use context_module;
use dml_exception;
use local_wunderbyte_table\filters\types\hierarchicalfilter;
use local_wunderbyte_table\wunderbyte_table;
use mod_booking\customfield\booking_handler;
use mod_booking\output\page_allteachers;
use local_urise\output\userinformation;
use local_urise\table\urise_table;
use local_urise\table\calendar_table;
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
     * Retrungs the array of organisations.
     *
     * @return [type]
     *
     */
    public static function organisations() {
        $filterstring = get_config('local_urise', 'organisationfilter');
        $pattern = '/get_string\("([^"]*)", "([^"]*)"\)/';
        $outputstring = preg_replace_callback($pattern, fn($a) => '"' . get_string($a[1], $a[2]) . '"', $filterstring);

        $filter = json_decode($outputstring, true) ?? [];

        return $filter;
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
        $output = $PAGE->get_renderer('local_urise');
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
      * @return string
      */
    public static function unifiedlist($shortcode, $args, $content, $env, $next) {
        return self::unifiedview($shortcode, $args, $content, $env, $next, false);
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
     * @return string
     */
    public static function unifiedcards($shortcode, $args, $content, $env, $next) {
        return self::unifiedview($shortcode, $args, $content, $env, $next, true);
    }

    /**
     * Prints calenderblock.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return mixed
     */
    public static function calendarblock($shortcode, $args, $content, $env, $next) {

        self::fix_args($args);

        // Get bookingids.
        $booking = self::get_booking($args);

        $bookingids = explode(',', get_config('local_urise', 'multibookinginstances'));
        $bookingids = array_filter($bookingids, fn($a) => !empty($a));

        $table = self::inittableforcalendar();

        $wherearray = ['bookingid' => $bookingids];

        $additionalwhere = self::set_wherearray_from_arguments($args, $wherearray) ?? '';

        if (!empty($additionalwhere)) {
            $additionalwhere .= " AND ";
        }
        // Additional where has to be added here. We add the param later.
        if (empty($args['all'])) {
            $additionalwhere .= " (courseendtime > :timenow OR courseendtime = 0) ";
        }

        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, null, [], $additionalwhere);
        } else {
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, null, [], $additionalwhere);
        }

        $params['timenow'] = strtotime('today 00:00');
        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->tabletemplate = 'local_urise/urise_calendar';
        $table->define_sortablecolumns(['coursestarttime']);
        $table->sortable(true, 'coursestarttime', SORT_ASC);
        $table->infinitescroll = 0;
        $table->use_pages = true;

        $out = $table->outhtml(4, true);

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
     * @param string $rendertype
     * @return mixed
     */
    public static function unifiedview($shortcode, $args, $content, $env, $next, $renderascard = false) {
        global $DB;

        self::fix_args($args);
        $booking = self::get_booking($args);

        $bookingids = explode(',', get_config('local_urise', 'multibookinginstances'));
        $bookingids = array_filter($bookingids, fn($a) => !empty($a));

        if (empty($bookingids)) {
            return get_string('nobookinginstancesselected', 'local_urise');
        }

        if (!isset($args['image']) || !$showimage = ($args['image'])) {
            $showimage = false;
        }

        if (empty($args['countlabel'])) {
            $args['countlabel'] = false;
        }

        if (empty($args['filterontop'])) {
            $args['filterontop'] = false;
        }

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 100;
        } else {
            $infinitescrollpage = 0;
        }

        if (empty($args['initcourses']) || $args['initcourses'] == "false") {
            $table = self::inittableforcourses(false);
        } else {
            $table = self::inittableforcourses();
        }

        $table->showcountlabel = $args['countlabel'];

        if (empty($args['reload'])) {
            $args['reload'] = false;
        }
        $table->showreloadbutton = $args['reload'];

        $infinitescrollpage = is_numeric($args['infinitescrollpage'] ?? '') ? (int)$args['infinitescrollpage'] : 30;

        $wherearray = ['bookingid' => $bookingids];

        // Additional where condition for both card and list views
        $additionalwhere = self::set_wherearray_from_arguments($args, $wherearray) ?? '';

        // Additional where has to be added here. We add the param later.
        if (empty($args['all'])) {
            if (!empty($additionalwhere)) {
                $additionalwhere .= " AND ";
            }
            $additionalwhere .= " (courseendtime > :timenow OR courseendtime = 0) ";
        }

        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            [$fields, $from, $where, $params, $filter] =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, null, [], $additionalwhere);
        } else {
            [$fields, $from, $where, $params, $filter] =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, null, [], $additionalwhere);
        }

        $params['timenow'] = strtotime('today 00:00');
        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = true;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');
            $table->add_subcolumns('cardimage', ['image']);
        }

        if (empty($args['showpagination'])) {
            $args['showpagination'] = true;
        }

        self::set_table_options_from_arguments($table, $args);

        if ($renderascard) {
            self::generate_table_for_cards($table, $args);
            if ($args['showpagination'] == "true") {
                $table->tabletemplate = 'local_urise/table_card';
            } else {
                $table->tabletemplate = 'local_urise/table_card_no_pagination';
            }
        } else {
            self::generate_table_for_list($table, $args);
            $table->cardsort = true;
            $table->infinitescroll = $infinitescrollpage;
            $table->tabletemplate = 'local_urise/table_list';
        }

        $table->showfilterontop = $args['filterontop'];
        $table->showcountlabel = true;

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {
            [$idstring, $encodedtable, $out] = $table->lazyouthtml($perpage, true);
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
     * @return string
     */
    public static function unifiedmybookingslist($shortcode, $args, $content, $env, $next) {

        global $USER;

        self::fix_args($args);
        $booking = self::get_booking($args);

        $bookingids = explode(',', get_config('local_urise', 'multibookinginstances'));
        $bookingids = array_filter($bookingids, fn($a) => !empty($a));

        if (empty($bookingids)) {
            return get_string('nobookinginstancesselected', 'local_urise');
        }

        if (!isset($args['image']) || !$showimage = ($args['image'])) {
            $showimage = false;
        }

        if (empty($args['countlabel'])) {
            $args['countlabel'] = false;
        }

        if (empty($args['filterontop'])) {
            $args['filterontop'] = false;
        }

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 100;
        } else {
            $infinitescrollpage = 0;
        }

        if (empty($args['initcourses']) || $args['initcourses'] == "false") {
            $table = self::inittableforcourses(false);
        } else {
            $table = self::inittableforcourses();
        }

        $table->showcountlabel = $args['countlabel'];

        if (empty($args['reload'])) {
            $args['reload'] = false;
        }
        $table->showreloadbutton = $args['reload'];

        $infinitescrollpage = is_numeric($args['infinitescrollpage'] ?? '') ? (int)$args['infinitescrollpage'] : 30;

        $wherearray = ['bookingid' => $bookingids];

        // Additional where condition for both card and list views
        $additionalwhere = self::set_wherearray_from_arguments($args, $wherearray) ?? '';

        $additionalwhere .= ' ((waitinglist <> ' . MOD_BOOKING_STATUSPARAM_DELETED . ' AND status = 0) OR (waitinglist = ' . MOD_BOOKING_STATUSPARAM_DELETED . ' AND status = 1))';

        // Additional where has to be added here. We add the param later.
        if (empty($args['all'])) {
            if (!empty($additionalwhere)) {
                $additionalwhere .= " AND ";
            }
            $additionalwhere .= " (courseendtime > :timenow OR courseendtime = 0) ";
        }

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            [$fields, $from, $where, $params, $filter] =
                booking::get_options_filter_sql(
                    0,
                    0,
                    '',
                    null,
                    null,
                    [],
                    $wherearray,
                    $USER->id,
                    [
                        MOD_BOOKING_STATUSPARAM_BOOKED,
                        MOD_BOOKING_STATUSPARAM_RESERVED,
                        MOD_BOOKING_STATUSPARAM_WAITINGLIST,
                        MOD_BOOKING_STATUSPARAM_NOTIFYMELIST,
                        MOD_BOOKING_STATUSPARAM_DELETED,
                    ],
                    $additionalwhere
                );
        } else {
            [$fields, $from, $where, $params, $filter] =
                booking::get_options_filter_sql(
                    0,
                    0,
                    '',
                    null,
                    null,
                    [],
                    $wherearray,
                    $USER->id,
                    [
                        MOD_BOOKING_STATUSPARAM_BOOKED,
                        MOD_BOOKING_STATUSPARAM_RESERVED,
                        MOD_BOOKING_STATUSPARAM_WAITINGLIST,
                        MOD_BOOKING_STATUSPARAM_NOTIFYMELIST,
                        MOD_BOOKING_STATUSPARAM_DELETED,
                    ],
                    $additionalwhere
                );
        }

        $params['timenow'] = strtotime('today 00:00');
        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = empty($args['showpagination']) ? false : true;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');
            $table->add_subcolumns('cardimage', ['image']);
        }

        if (empty($args['showpagination'])) {
            $args['showpagination'] = true;
        }

        self::set_table_options_from_arguments($table, $args);
        if (!empty($args['cards'])) {
            self::generate_table_for_cards($table, $args);
            $table->tabletemplate = 'local_urise/table_card';
        } else {
            self::generate_table_for_list($table, $args);
            $table->infinitescroll = $infinitescrollpage;
            $table->tabletemplate = 'local_urise/table_list';
        }

        $table->showfilterontop = $args['filterontop'];
        $table->showcountlabel = true;

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {
            [$idstring, $encodedtable, $out] = $table->lazyouthtml($perpage, true);
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
    public static function mytaughtcourses($shortcode, $args, $content, $env, $next) {

        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/mod/booking/lib.php');

        self::fix_args($args);

        $bookingids = explode(',', get_config('local_urise', 'multibookinginstances'));

        $bookingids = array_filter($bookingids, fn($a) => !empty($a));

        if (empty($bookingids)) {
            return get_string('nobookinginstancesselected', 'local_urise');
        }

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        if (!isset($args['image']) || !$showimage = ($args['image'])) {
            $showimage = false;
        }

        if (empty($args['countlabel'])) {
            $args['countlabel'] = false;
        }

        if (empty($args['reload'])) {
            $args['reload'] = false;
        }

        if (empty($args['filterontop'])) {
            $args['filterontop'] = false;
        }

        $infinitescrollpage = is_numeric($args['infinitescrollpage'] ?? '') ? (int)$args['infinitescrollpage'] : 30;

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 100;
        }

        $table = self::inittableforcourses();

        $table->showcountlabel = $args['countlabel'];
        $table->showreloadbutton = $args['reload'];

        $wherearray = ['bookingid' => $bookingids];

        if (!empty($category)) {
            $wherearray['organisation'] = $category;
        };

        // We want to check for the currently logged in user...
        // ... if (s)he is teaching courses.
        $teacherid = $USER->id;

        // This is the important part: We only filter for booking options where the current user is a teacher!
        // Also we only want to show courses for the currently set booking instance (semester instance).
        list($fields, $from, $where, $params, $filter) =
            booking::get_all_options_of_teacher_sql($teacherid, (int)$booking->id);

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = true;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');

            $table->add_subcolumns('cardimage', ['image']);
        }

        self::set_table_options_from_arguments($table, $args);
        if (!empty($args['cards'])) {
            self::generate_table_for_cards($table, $args);
            $table->tabletemplate = 'local_urise/table_card';
        } else {
            self::generate_table_for_list($table, $args);
            $table->tabletemplate = 'local_urise/table_list';
        }

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = $infinitescrollpage;

        $table->showfilterontop = $args['filterontop'];
        $table->showfilterbutton = false;

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
            $args['id'] = get_config('local_urise', 'shortcodessetinstance');
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

        $output = $PAGE->get_renderer('local_urise');
        return $output->render_user_dashboard_overview($data);

    }

    /**
     * Init the table for calendar.
     *
     * @return wunderbyte_table
     *
     */
    private static function inittableforcalendar() {

        global $PAGE, $USER;

        $tablename = bin2hex(random_bytes(12));

        // It's important to have the baseurl defined, we use it as a return url at one point.
        $baseurl = $PAGE->url ?? new moodle_url('');

        $table = new calendar_table($tablename);

        $table->define_baseurl($baseurl->out());
        // Without defining sorting won't work!
        $table->define_columns(['text']);
        $table->add_subcolumns('main', ['text', 'category', 'more']);
        $table->add_subcolumns('header', ['coursestarttime']);
        $table->add_classes_to_subcolumns('main', ['columnclass' => 'text-primary mt-3'], ['text']);
        return $table;
    }

    /**
     * Init the table.
     *
     * @return wunderbyte_table
     *
     */
    private static function inittableforcourses($addcols = true) {

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

        $table = new urise_table($tablename);

        $table->define_baseurl($baseurl->out());
        $table->cardsort = true;
        // Without defining sorting won't work!
        if ($addcols == true) {
            $table->define_columns([
                'titleprefix',
                'coursestarttime',
                'courseendtime',
                'location',
                'bookingopeningtime',
                'bookingclosingtime',
            ]);
        }
        return $table;
    }

    /**
     * Navbarhtml shortcode.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function navbarhtml($shortcode, $args, $content, $env, $next) {

        if (!empty($args['category']) && $args['category'] === 'communities') {
            $html = get_config('local_urise', 'extrashortcodeone');
        }
        if (!empty($args['category']) && $args['category'] === 'support') {
            $html = get_config('local_urise', 'extrashortcodetwo');
        }

        $html = format_text($html);

        return $html ?? 'no html or category not set correctly';
    }

    /**
     * Define filtercolumns.
     *
     * @param mixed $table
     * @param mixed $args
     *
     * @return void
     *
     */
    private static function define_filtercolumns(&$table, $args) {

        if (!empty($args['onlyfilterforcolumns'])) {
            // If you want to turn off filter for booking- and coursetime, do it via pluginsettings.
            $filtercolumns = explode(',', $args['onlyfilterforcolumns']);
        } else {
            $filtercolumns = [];
        }

        if (empty($filtercolumns) || in_array('zgcommunities', $filtercolumns)) {
            $standardfilter = new standardfilter('zgcommunities', get_string('zgcommunities', 'local_urise'));
            $standardfilter->add_options([
                "Wissenschaftliches Personal" => get_string('wissenschaftlichespersonal', 'local_urise'),
                "PhD Students" => get_string('phdstudents', 'local_urise'),
                "PostDoc" => get_string('postdoc', 'local_urise'),
                "Allgemeines Personal" => get_string('allgemeinespersonal', 'local_urise'),
                "Führungskräfte" => get_string('fuehrungskraefte', 'local_urise'),
                "Studierende" => get_string('studierende', 'local_urise'),
                "Interessierte Öffentlichkeit" => get_string('interessierteoeffentlichkeit', 'local_urise'),
            ]);
            $table->add_filter($standardfilter);
        }

        if (empty($filtercolumns) || in_array('bibliothekszielgruppe', $filtercolumns)) {
            $standardfilter = new standardfilter('bibliothekszielgruppe', get_string('bibliothekszielgruppe', 'local_urise'));
            $standardfilter->add_options(self::get_bibliothekszielgruppe());
            $table->add_filter($standardfilter);
        }

        if (empty($filtercolumns) || in_array('kompetenzen', $filtercolumns)) {
            $hierarchicalfilter = new hierarchicalfilter('kompetenzen', get_string('competency', 'local_urise'));
            $hierarchicalfilter->add_options(self::get_kompetenzen());
            $table->add_filter($hierarchicalfilter);
        }

        if (empty($filtercolumns) || in_array('organisation', $filtercolumns)) {
            $hierarchicalfilter = new hierarchicalfilter('organisation', get_string('organisationfilter', 'local_urise'));
            $hierarchicalfilter->add_options(self::organisations());
            $table->add_filter($hierarchicalfilter);
        }

        if (empty($filtercolumns) || in_array('kurssprache', $filtercolumns)) {
            $standardfilter = new standardfilter('kurssprache', get_string('kurssprache', 'local_urise'));
            $standardfilter->add_options([
                1 => get_string('german', 'local_urise'),
                2 => get_string('english', 'local_urise'),
            ]);
            $table->add_filter($standardfilter);
        }

        if (empty($filtercolumns) || in_array('format', $filtercolumns)) {
            $standardfilter = new standardfilter('format', get_string('format', 'local_urise'));
            $standardfilter->add_options([
                1 => get_string('onsite', 'local_urise'),
                2 => get_string('hybrid', 'local_urise'),
                3 => get_string('blendedlearningonsite', 'local_urise'),
                4 => get_string('blendedlearningonline', 'local_urise'),
                5 => get_string('blendedlearningonline', 'local_urise'),
                6 => get_string('onsite', 'local_urise'),
                7 => get_string('selfpaced', 'local_urise'),
            ]);
            $table->add_filter($standardfilter);
        }

        if (empty($filtercolumns) || in_array('dayofweek', $filtercolumns)) {
            $standardfilter = new standardfilter('dayofweek', get_string('dayofweek', 'local_urise'));
            $standardfilter->add_options([
                'monday' => get_string('monday', 'mod_booking'),
                'tuesday' => get_string('tuesday', 'mod_booking'),
                'wednesday' => get_string('wednesday', 'mod_booking'),
                'thursday' => get_string('thursday', 'mod_booking'),
                'friday' => get_string('friday', 'mod_booking'),
                'saturday' => get_string('saturday', 'mod_booking'),
                'sunday' => get_string('sunday', 'mod_booking'),
            ]);
            $table->add_filter($standardfilter);
        }

        if (empty($filtercolumns) || in_array('location', $filtercolumns)) {
            $standardfilter = new standardfilter('location', get_string('location', 'mod_booking'));
            $table->add_filter($standardfilter);
        }

        if (get_config('local_urise', 'uriseshortcodesshowfiltercoursetime')) {

            $datepicker = new datepicker(
                'coursestarttime',
                get_string('timefilter:coursetime', 'mod_booking'),
                'courseendtime'
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

        if (get_config('local_urise', 'uriseshortcodesshowfilterbookingtime')) {

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

    /**
     * Get booking from shortcode arguments.
     *
     * @param mixed $args
     *
     * @return [type]
     *
     */
    private static function get_booking($args) {
        self::fix_args($args);
        // If the id argument was not passed on, we have a fallback in the connfig.
        if (!isset($args['id'])) {
            $args['id'] = get_config('local_urise', 'shortcodessetinstance');
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

    /**
     * Set table from shortcodes arguments.
     *
     * @param mixed $table
     * @param mixed $args
     *
     * @return [type]
     *
     */
    private static function set_table_options_from_arguments(&$table, $args) {
        self::fix_args($args);

        /** @var urise_table $table */
        $table->set_display_options($args);

        if (!empty($args['filter'])) {
            self::define_filtercolumns($table, $args);
        }

        if (!empty($args['search'])) {
            $table->define_fulltextsearchcolumns([
                'titleprefix', 'text', 'organisation', 'description', 'location',
                'teacherobjects', 'botags', 'inhalte', 'zielgruppe']);
        }

        if (!empty($args['sort'])) {
            $sortablecolumns = [
                'titleprefix' => get_string('titleprefix', 'local_urise'),
                'text' => get_string('coursename', 'local_urise'),
                'organisation' => get_string('organisation', 'local_urise'),
                'location' => get_string('location', 'local_urise'),
            ];
            if (get_config('local_urise', 'uriseshortcodesshowstart')) {
                $sortablecolumns['coursestarttime'] = get_string('coursestarttime', 'mod_booking');
            }
            if (get_config('local_urise', 'uriseshortcodesshowend')) {
                $sortablecolumns['courseendtime'] = get_string('courseendtime', 'mod_booking');
            }
            if (get_config('local_urise', 'uriseshortcodesshowbookablefrom')) {
                $sortablecolumns['bookingopeningtime'] = get_string('bookingopeningtime', 'mod_booking');
            }
            if (get_config('local_urise', 'uriseshortcodesshowbookableuntil')) {
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

        if (!empty($args['showfilterbutton'])) {
            $table->showfilterbutton = true;
        } else {
            $table->showfilterbutton = false;
        }
    }

    /**
     * Sets columns for calendar.
     *
     * @return wunderbyte_table
     *
     */
    private static function generate_table_for_calendar(&$table, $args) {
        self::fix_args($args);
        $table->add_subcolumns('main', ['text']);
        $table->add_subcolumns('header', ['coursestarttime']);
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

        $table->add_subcolumns('url', ['url']);
        $table->add_subcolumns('cardimage', ['image']);
        $table->set_tableclass('cardimageclass', 'imagecontainer');
        $table->add_subcolumns('cardheader', ['botags', 'action', 'bookings']);
        $table->add_subcolumns('cardlist', ['showdates', 'kurssprache', 'format', 'kompetenzen', 'organisation', 'course']);
        $table->add_subcolumns('cardfooter', ['price']);

        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa-regular fa-message fa-fw text-primary mr-2'],
         ['kurssprache']);
         $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa-solid fa-computer fa-fw  text-primary mr-2'],
         ['format']);
         $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa-solid fa-hashtag fa-fw  text-primary mr-2'],
         ['kompetenzen']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-clock-o text-primary fa-fw  showdatesicon mr-2'], ['showdates']);
        $table->add_classes_to_subcolumns('cardlist', ['columnclass' => 'd-flex align-item-center'], ['showdates']);
        // $table->add_classes_to_subcolumns('cardfooter', ['columnclass' => 'mt-auto'], ['price']);
        $table->add_classes_to_subcolumns('cardheader', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardheader', ['columnvalueclass' => 'mr-auto'], ['botags']);
        $table->add_classes_to_subcolumns('cardheader', ['columnvalueclass' => 'ml-auto'], ['bookings']);
        // $table->add_classes_to_subcolumns('cardlist', ['columnvalueclass' =>
        // 'bg-secondary orga'], ['organisation']);

        $table->add_subcolumns('cardbody', ['text', 'description']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'mr-auto'], ['text']);

        $table->add_classes_to_subcolumns('cardlist', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardfooter', ['columnkeyclass' => 'd-none']);
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

        // Columns.

        $subcolumnsleftside = ['text', 'description'];
        $subcolumnsfooter = ['kurssprache', 'format', 'kompetenzen'];
        $subcolumnsinfo = ['showdates'];

        // Check if we should add the description.
        if (get_config('local_urise', 'shortcodelists_showdescriptions')) {
            $subcolumnsleftside[] = 'description';
        }

        if (!empty($args['showminanswers'])) {
            $subcolumnsinfo[] = 'minanswers';
        }

        $table->define_cache('mod_booking', 'bookingoptionstable');

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);

        $table->add_subcolumns('cardimage', ['image']);

        $table->set_tableclass('cardimageclass', 'customimg');

        // $table->add_subcolumns('top', ['organisation', 'action']);
        $table->add_subcolumns('top', ['botags', 'action', 'bookings' ]);
        // $table->add_subcolumns('top', ['botags', 'bookings' ]);
        $table->add_subcolumns('leftside', $subcolumnsleftside);
        $table->add_subcolumns('info', $subcolumnsinfo);
        $table->add_subcolumns('footer', $subcolumnsfooter );

        $table->add_subcolumns('rightside', ['organisation', 'invisibleoption', 'course', 'price']);
        // $table->add_subcolumns('rightside', ['organisation', 'invisibleoption', 'price']);

        $table->add_classes_to_subcolumns('top', ['columnkeyclass' => 'd-none']);
        // $table->add_classes_to_subcolumns('top', ['columniclassbefore' => 'fa-solid fa-people-group'], ['bookings']);
        // $table->add_classes_to_subcolumns('top', ['columnclass' => 'border border-2 border-dark p-1 rounded d-flex align-items-center'], ['bookings']);
        $table->add_classes_to_subcolumns('top', ['columnclass' => 'mr-auto text-uppercase'], ['botags']);
        // $table->add_classes_to_subcolumns('top', ['columnclass' => 'text-left col-md-8'], ['organisation']);
        // $table->add_classes_to_subcolumns('top', ['columnvalueclass' =>
        //     'organisation-badge rounded-sm text-gray-800 mt-2'], ['organisation']);
        // $table->add_classes_to_subcolumns('top', ['columnclass' => 'text-right col-md-2 position-relative pr-0'], ['action']);

        $table->add_classes_to_subcolumns('leftside', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-1 title'], ['text']);
        if (get_config('local_urise', 'shortcodelists_showdescriptions')) {
            $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-3 col-md-auto'], ['description']);
        }

        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-clock-o text-primary
        showdatesicon'], ['showdates']);
        $table->add_classes_to_subcolumns('info', ['columnclassinner' => 'align-items-center'], ['showdates']);
        if (get_config('local_urise', 'uriseshortcodesshowend')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-stop'], ['courseendtime']);
        }
        if (get_config('local_urise', 'uriseshortcodesshowbookablefrom')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-forward'], ['bookingopeningtime']);
        }
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('locationalt', 'local_urise')], ['location']);
        $table->add_classes_to_subcolumns('cardimage', ['cardimagealt' => get_string('imagealt', 'local_urise')], ['image']);

        $table->add_classes_to_subcolumns('rightside',
            ['columnvalueclass' => 'text-right mb-auto align-self-end shortcodes_option_info_invisible '],
            ['invisibleoption']);
        $table->add_classes_to_subcolumns('rightside', ['columnclass' =>
             'theme-text-color bold ml-auto'], ['price']);
            //  $table->add_classes_to_subcolumns('rightside', ['columnvalueclass' =>
            //  'bg-secondary orga mb-2'], ['organisation']);

        $table->add_classes_to_subcolumns('footer', ['columniclassbefore' => 'fa-regular fa-message text-primary'],
         ['kurssprache']);
         $table->add_classes_to_subcolumns('footer', ['columniclassbefore' => 'fa-solid fa-computer text-primary'],
         ['format']);
         $table->add_classes_to_subcolumns('footer', ['columniclassbefore' => 'fa-solid fa-hashtag text-primary'],
         ['kompetenzen']);

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

    /**
     * Modify there wherearray via arguments.
     *
     * @param array $args
     *
     * @return string
     *
     */
    private static function set_wherearray_from_arguments(array &$args, &$wherearray) {

        global $DB;

        $customfields = booking_handler::get_customfields();
        // Set given customfields (shortnames) as arguments.
        $fields = [];
        $additonalwhere = '';
        if (!empty($customfields) && !empty($args)) {
            foreach ($args as $key => $value) {
                foreach ($customfields as $customfield) {
                    if ($customfield->shortname == $key) {
                        $configdata = json_decode($customfield->configdata ?? '[]');

                        if (!empty($configdata->multiselect)) {
                            if (!empty($additonalwhere)) {
                                $additonalwhere .= " AND ";
                            }

                            $values = explode(',', $value);

                            if (!empty($values)) {
                                $additonalwhere .= " ( ";
                            }

                            foreach ($values as $vkey => $vvalue) {

                                $additonalwhere .= $vkey > 0 ? ' OR ' : '';
                                $vvalue = "'%$vvalue%'";
                                $additonalwhere .= " $key LIKE $vvalue ";
                            }

                            if (!empty($values)) {
                                $additonalwhere .= " ) ";
                            }

                        } else {
                            $argument = strip_tags($argument);
                            $argument = trim($argument);
                            $wherearray[$key] = $value;
                        }

                        break;
                    }
                }
            }
        }

        return $additonalwhere;
    }

    /**
     * Get kompetenzen filter
     *
     * @return array
     *
     */
    public static function get_kompetenzen() {

        return [
            'explode' => ',',
            '1' => [
                'parent' => get_string('lehrkompetenzen', 'local_urise'),
                'localizedname' => get_string('lehrkonzeptionplanung', 'local_urise'),
            ],
            '2' => [
                'parent' => get_string('lehrkompetenzen', 'local_urise'),
                'localizedname' => get_string('lehrundlernmethoden', 'local_urise'),
            ],
            '3' => [
                'parent' => get_string('lehrkompetenzen', 'local_urise'),
                'localizedname' => get_string('erstellunglehrlernmaterialien', 'local_urise'),
            ],
            '4' => [
                'parent' => get_string('lehrkompetenzen', 'local_urise'),
                'localizedname' => get_string('lehrenmitdigitalentechnologien', 'local_urise'),
            ],
            '5' => [
                'parent' => get_string('lehrkompetenzen', 'local_urise'),
                'localizedname' => get_string('pruefenbeurteilen', 'local_urise'),
            ],
            '6' => [
                'parent' => get_string('lehrkompetenzen', 'local_urise'),
                'localizedname' => get_string('betreuungschriftlicherarbeiten', 'local_urise'),
            ],
            '7' => [
                'parent' => get_string('lehrkompetenzen', 'local_urise'),
                'localizedname' => get_string('weiterentwicklungderlehre', 'local_urise'),
            ],
            '8' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('wissenschaftlichesarbeiten', 'local_urise'),
            ],
            '9' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('wissenschaftlichespublizieren', 'local_urise'),
            ],
            '10' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('openscience', 'local_urise'),
            ],
            '11' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('wissensaustauschinnovation', 'local_urise'),
            ],
            '12' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('wissenschaftlicheintegritaet', 'local_urise'),
            ],
            '13' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('networkinginderwissenschaft', 'local_urise'),
            ],
            '14' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('interdisziplinaereforschung', 'local_urise'),
            ],
            '15' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('forschungsfoerderung', 'local_urise'),
            ],
            '16' => [
                'parent' => get_string('forschungskompetenzen', 'local_urise'),
                'localizedname' => get_string('karriereentwicklungplanung', 'local_urise'),
            ],
            '17' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('praesentation', 'local_urise'),
            ],
            '18' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('gespraechsverhandlungsfuehrung', 'local_urise'),
            ],
            '19' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('feedback', 'local_urise'),
            ],
            '20' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('moderation', 'local_urise'),
            ],
            '21' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('sprachkenntnisse', 'local_urise'),
            ],
            '22' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('konfliktmanagement', 'local_urise'),
            ],
            '23' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('informationskommunikation', 'local_urise'),
            ],
            '24' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('genderdiversitaetskompetenz', 'local_urise'),
            ],
            '25' => [
                'parent' => get_string('kommunikationkooperation', 'local_urise'),
                'localizedname' => get_string('kooperationskompetenz', 'local_urise'),
            ],
            '26' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('veranstaltungsorganisation', 'local_urise'),
            ],
            '27' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('arbeitsorganisation', 'local_urise'),
            ],
            '28' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('selbstorganisation', 'local_urise'),
            ],
            '29' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('servicekundinnenorientierung', 'local_urise'),
            ],
            '30' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('loesungszukunftsorientierung', 'local_urise'),
            ],
            '31' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('ressourceneffizienz', 'local_urise'),
            ],
            '32' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('changekompetenz', 'local_urise'),
            ],
            '33' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('gesundheitsorientierung', 'local_urise'),
            ],
            '34' => [
                'parent' => get_string('selbstundarbeitsorganisation', 'local_urise'),
                'localizedname' => get_string('lernkompetenz', 'local_urise'),
            ],
            '35' => [
                'parent' => get_string('digitalkompetenzen', 'local_urise'),
                'localizedname' => get_string('itsecurity', 'local_urise'),
            ],
            '36' => [
                'parent' => get_string('digitalkompetenzen', 'local_urise'),
                'localizedname' => get_string('digitaleinteraktion', 'local_urise'),
            ],
            '37' => [
                'parent' => get_string('digitalkompetenzen', 'local_urise'),
                'localizedname' => get_string('umgangmitinformationenunddaten', 'local_urise'),
            ],
            '38' => [
                'parent' => get_string('digitalkompetenzen', 'local_urise'),
                'localizedname' => get_string('technologienutzung', 'local_urise'),
            ],
            '39' => [
                'parent' => get_string('fuehrungskompetenzen', 'local_urise'),
                'localizedname' => get_string('educationalleadershipandmanagement', 'local_urise'),
            ],
            '40' => [
                'parent' => get_string('sonstige', 'local_urise'),
                'localizedname' => get_string('sonstigekompetenzen', 'local_urise'),
            ],
        ];
    }

    /**
     * Get Bibliothekszielgruppen filter
     *
     * @return array
     *
     */
    public static function get_bibliothekszielgruppe() {

        return [
            'explode' => ',',
            '1' => get_string('students', 'local_urise'),
            '2' => get_string('doctoralcandidates', 'local_urise'),
            '3' => get_string('lecturers', 'local_urise'),
            '4' => get_string('researchers', 'local_urise'),
            '5' => get_string('pupilsandteachers', 'local_urise'),
            '6' => get_string('generalpublic', 'local_urise'),
        ];

    }
}
