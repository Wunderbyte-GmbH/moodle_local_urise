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
use dml_exception;
use local_wunderbyte_table\filters\types\hierarchicalfilter;
use local_wunderbyte_table\wunderbyte_table;
use mod_booking\customfield\booking_handler;
use local_urise\table\urise_table;
use local_urise\table\calendar_table;
use local_shopping_cart\shopping_cart;
use local_shopping_cart\shopping_cart_credits;
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\standardfilter;
use mod_booking\booking;
use mod_booking\singleton_service;
use moodle_url;

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
     * Prints out list of bookingoptions with images.
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
        [$table, $perpage] = self::unifiedview($shortcode, $args, $content, $env, $next, 'local_urise/table_list');
        if (empty($table)) {
            return get_string('nobookinginstancesselected', 'local_urise');
        }
        return self::generate_output($args, $table, $perpage);
    }

    /**
     * Prints out list of bookingoptions without images.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function unifiedtextlist($shortcode, $args, $content, $env, $next) {
        [$table, $perpage] = self::unifiedview($shortcode, $args, $content, $env, $next, 'local_urise/table_listtext');
        if (empty($table)) {
            return get_string('nobookinginstancesselected', 'local_urise');
        }
        return self::generate_output($args, $table, $perpage);
    }

    /**
     * Prints out cards of bookingoptions.
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
        [$table, $perpage] = self::unifiedview($shortcode, $args, $content, $env, $next, 'local_urise/table_card');
        if (empty($table)) {
            return get_string('nobookinginstancesselected', 'local_urise');
        }
        return self::generate_output($args, $table, $perpage);
    }

    /**
     * Unifiedview for List and Cards.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @param string $template default is 'local_urise/table_card'
     * @return array|string
     */
    public static function unifiedview($shortcode, $args, $content, $env, $next, $template = 'local_urise/table_card') {
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

        $table = self::inittableforcourses('unifiedview');

        if (empty($args['reload'])) {
            $args['reload'] = false;
        }
        $table->showreloadbutton = $args['reload'];

        // Currently not used.
        $infinitescrollpage = is_numeric($args['infinitescrollpage'] ?? '') ? (int)$args['infinitescrollpage'] : 30;

        // Pagination is on by default, but it can be turned off.
        if (
            isset($args['showpagination'])
            && (
                $args['showpagination'] == "false"
                || $args['showpagination'] == "0"
            )
        ) {
            $table->showpagination = false;
        } else {
            // By default, showpagination is turned on.
            $table->showpagination = true;
        }

        // Gotopage is on by default
        if (
            isset($args['gotopage'])
            && (
                $args['gotopage'] == "false"
                || $args['gotopage'] == "0"
            )
        ) {
            $table->gotopage = false;
        } else {
            // By default, showpagination is turned on.
            $table->gotopage = true;
        }

        if (!empty($args['showminanswers'])) {
            $subcolumnsinfo[] = 'minanswers';
        }

        $wherearray = ['bookingid' => $bookingids];

        // Additional where condition for both card and list views.
        $additionalwhere = self::set_wherearray_from_arguments($args, $wherearray) ?? '';

        // Additional where has to be added here. We add the param later.
        if (empty($args['all'])) {
            if (!empty($additionalwhere)) {
                $additionalwhere .= " AND ";
            }
            $additionalwhere .= " (courseendtime > :timenow OR courseendtime = 0) ";
        }

        $context = $booking->context;
        if (
            !empty($args['noinvisible'])
            && $args['noinvisible'] == 'true'
        ) {
            // When we don't add a context, the sql will exclude invisible ones.
            $context = null;
        }

        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
        }

        [$fields, $from, $where, $params, $filter] = self::get_sql_params($context, $wherearray, $additionalwhere);
        $params['timenow'] = strtotime('today 00:00');
        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = true;

        $table->showcountlabel = (!empty($args['countlabel']) && $args['countlabel'] == "false") ? false : true;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');
            $table->add_subcolumns('cardimage', ['image']);
            $table->add_subcolumns('ariasection', ['puretext']);
        }

        self::set_table_options_from_arguments($table, $args);

        if (!empty($args['switchtemplates'])) {
            // Template switcher is activated.
            $table->add_template_to_switcher(
                'local_urise/table_card',
                get_string('viewcards', 'local_urise'),
                $template == 'local_urise/table_card'
            );
            $table->add_template_to_switcher(
                'local_urise/table_list',
                get_string('viewlist', 'local_urise'),
                $template == 'local_urise/table_list'
            );
            $table->add_template_to_switcher(
                'local_urise/table_listtext',
                get_string('viewtextlist', 'local_urise'),
                $template == 'local_urise/table_listtext'
            );

            // If template switcher is active, we need to check if the user has already a saved preferred template.
            $chosentemplate = get_user_preferences('wbtable_chosen_template_' . $table->uniqueid);
            if (empty($chosentemplate)) {
                $chosentemplate = $template; // Fallback.
            }

            // Now we replace the original template with the chosen one.
            $template = $chosentemplate;
        }

        // Switch view type (cards view or list view).
        switch ($template) {
            case 'local_urise/table_list':
                self::generate_table_for_list($table);
                break;
            case 'local_urise/table_listtext':
                self::generate_table_for_textlist($table);
                break;
            case 'local_urise/table_card':
            default:
                self::generate_table_for_cards($table);
                break;
        }

        $table->showfilterontop = $args['filterontop'];

        return [$table, $perpage];
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

        global $USER, $PAGE;

        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $perpage = \mod_booking\shortcodes::check_perpage($args);
        $bookingparams = [MOD_BOOKING_STATUSPARAM_BOOKED,
        MOD_BOOKING_STATUSPARAM_RESERVED,
        MOD_BOOKING_STATUSPARAM_WAITINGLIST,
        MOD_BOOKING_STATUSPARAM_NOTIFYMELIST,
        MOD_BOOKING_STATUSPARAM_DELETED];

        if ($userid != $USER->id) {
            shopping_cart::buy_for_user($userid);
        }

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
        } else {
            $infinitescrollpage = 0;
        }

        if (!empty($args['initcourses']) && $args['initcourses'] == "false") {
            $table = self::inittableforcourses('unifiedmybookingslist', false);
        } else {
            $table = self::inittableforcourses('unifiedmybookingslist');
        }

        $table->showcountlabel = (!empty($args['countlabel']) && $args['countlabel'] == "false") ? false : true;


        if (empty($args['reload'])) {
            $args['reload'] = false;
        }
        $table->showreloadbutton = $args['reload'];

        $infinitescrollpage = is_numeric($args['infinitescrollpage'] ?? '') ? (int)$args['infinitescrollpage'] : 30;

        $wherearray = ['bookingid' => $bookingids];

        // Additional where condition for both card and list views.
        $additionalwhere = self::set_wherearray_from_arguments($args, $wherearray) ?? '';

        $additionalwhere .= ' ((waitinglist <> ' . MOD_BOOKING_STATUSPARAM_DELETED . ' AND status = 0)
            OR (waitinglist = ' . MOD_BOOKING_STATUSPARAM_DELETED . ' AND status = 1))';

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
        }
        [$fields, $from, $where, $params, $filter] =
            self::get_sql_params(null, $wherearray, $additionalwhere, $bookingparams, $userid);
        $params['timenow'] = strtotime('today 00:00');
        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        // Pagination is on by default, but it can be turned off.
        if (
            isset($args['showpagination'])
            && (
                $args['showpagination'] == "false"
                || $args['showpagination'] == "0"
            )
        ) {
            $table->showpagination = false;
        } else {
            // By default, showpagination is turned on.
            $table->showpagination = true;
        }
        $table->use_pages = $table->showpagination;

        if (!empty($args['showminanswers'])) {
            $subcolumnsinfo[] = 'minanswers';
        }

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');
            $table->add_subcolumns('cardimage', ['image']);
        }

        self::set_table_options_from_arguments($table, $args);
        if (!empty($args['cards'])) {
            self::generate_table_for_cards($table);
        } else {
            self::generate_table_for_list($table);
        }

        $table->showfilterontop = $args['filterontop'];

        $table->define_cache('mod_booking', 'mybookingoptionstable');

        return self::generate_output($args, $table, $perpage);
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
    public static function mytaughtcourses($shortcode, $args, $content, $env, $next) {

        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/mod/booking/lib.php');

        self::fix_args($args);

        $bookingids = explode(',', get_config('local_urise', 'multibookinginstances'));
        $perpage = \mod_booking\shortcodes::check_perpage($args);

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

        $table = self::inittableforcourses('mytaughtcourses');

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
        [$fields, $from, $where, $params, $filter] =
            booking::get_all_options_of_teacher_sql($teacherid, (int)$bookingids);

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = true;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');

            $table->add_subcolumns('cardimage', ['image']);
        }

        self::set_table_options_from_arguments($table, $args);
        if (!empty($args['cards'])) {
            self::generate_table_for_cards($table);
        } else {
            self::generate_table_for_list($table);
        }

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = $infinitescrollpage;

        $table->showfilterontop = $args['filterontop'];
        $table->showfilterbutton = false;

        return self::generate_output($args, $table, $perpage);
    }

    /**
     * Create an filter view from a table.
     *
     * @param mixed $shortcode
     * @param mixed $args
     * @param mixed $content
     * @param mixed $env
     * @param mixed $next
     *
     * @return [type]
     *
     */
    public static function filterview($shortcode, $args, $content, $env, $next) {

        [$table, $perpage] = self::unifiedview($shortcode, $args, $content, $env, $next);

        $table->tabletemplate = 'local_wunderbyte_table/filterview';

        if (!empty($args['customurl'])) {
            preg_match('/<a\s+href=["\']?([^"\'>]+)["\']?\s+.*?>/i', $args['customurl'], $matches);
            $baseurl = $matches[1] ?? '';
            $table->define_baseurl($baseurl);
        }

        $onlyfilterforcolumns = !empty($args['onlyfilterforcolumns']) ? explode(',', $args['onlyfilterforcolumns']) : [];

        return $table->filterouthtml($perpage, true, true, $onlyfilterforcolumns);
    }

    /**
     * Create an calendarview from a table.
     *
     * @param mixed $shortcode
     * @param mixed $args
     * @param mixed $content
     * @param mixed $env
     * @param mixed $next
     *
     * @return [type]
     *
     */
    public static function calendarview($shortcode, $args, $content, $env, $next) {

        // Calendar should normally be sorted by coursestarttime.
        if (empty($args['sortby'])) {
            $args['sortby'] = 'coursestarttime';
        }

        [$table, $perpage] = self::unifiedview($shortcode, $args, $content, $env, $next);

        $table->tabletemplate = 'local_wunderbyte_table/calendarview';
        $table->define_columns(['text']);
        $table->add_subcolumns('main', ['text', 'category', 'more']);
        $table->add_subcolumns('header', ['coursestarttime']);
        $table->add_classes_to_subcolumns('main', ['columnclass' => 'text-primary mt-3'], ['text']);

        return $table->calendarouthtml($perpage, true, true);
    }

    /**
     * Prints out user dashboard overview as cards.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
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
        $asteacher = $DB->get_fieldset_select(
            'booking_teachers',
            'optionid',
            "userid = {$USER->id} AND bookingid = $booking->id "
        );
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

        $tablename = 'inittableforcalendar' . $PAGE->context->instanceid;

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
     * @param string $tablename
     * @param bool $addcols optional add columns
     * @return wunderbyte_table
     *
     */
    private static function inittableforcourses(string $tablename, bool $addcols = true) {

        global $PAGE, $USER;

        // It's important to have the baseurl defined, we use it as a return url at one point.
        $baseurl = $PAGE->url ?? new moodle_url('');

        // On the cashier page, we want to buy for different users...
        // ...else we always want to buy for ourselves.
        if (strpos($baseurl->out(), "cashier.php") !== false) {
            $buyforuserid = null;
        } else {
            $buyforuserid = $USER->id;
        }

        // We add instanceid of current page context because we maybe want to use the shortcode more than once.
        $table = new urise_table($tablename . $PAGE->context->instanceid);

        $table->define_baseurl($baseurl->out());
        $table->cardsort = true;
        // Without defining sorting won't work!
        if ($addcols == true) {
            $table->define_columns([
                'titleprefix',
                'location',
                'bookingopeningtime',
                'bookingclosingtime',
            ]);

            $table->add_subcolumns('invisible', ['coursestarttime', 'courseendtime']);
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
     * @param urise_table $table
     * @param array $args
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
                'explode' => ',',
                1 => get_string('wissenschaftlichespersonal', 'local_urise'),
                2 => get_string('phdstudents', 'local_urise'),
                3 => get_string('postdoc', 'local_urise'),
                4 => get_string('allgemeinespersonal', 'local_urise'),
                5 => get_string('fuehrungskraefte', 'local_urise'),
                6 => get_string('studierende', 'local_urise'),
                7 => get_string('interessierteoeffentlichkeit', 'local_urise'),
                8 => get_string('studentmultipliers', 'local_urise'),
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
                0 => 'wbt_suppress',
                1 => get_string('german', 'local_urise'),
                2 => get_string('english', 'local_urise'),
                3 => get_string('germanenglish', 'local_urise'),
            ]);
            $table->add_filter($standardfilter);
        }

        if (empty($filtercolumns) || in_array('format', $filtercolumns)) {
            $standardfilter = new standardfilter('format', get_string('format', 'local_urise'));
            $standardfilter->add_options([
                0 => 'wbt_suppress',
                1 => get_string('onsite', 'local_urise'),
                2 => get_string('hybrid', 'local_urise'),
                3 => get_string('blendedlearningonsite', 'local_urise'),
                4 => get_string('blendedlearningonline', 'local_urise'),
                5 => get_string('blendedlearninghybrid', 'local_urise'),
                6 => get_string('online', 'local_urise'),
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

        if (empty($filtercolumns) || in_array('reihenprogramm', $filtercolumns)) {
            $standardfilter = new standardfilter('reihenprogramm', get_string('reihenprogramm', 'local_urise'));

            $options = [
                0 => 'wbt_suppress',
                1 => get_string('basicqualification', 'local_urise'),
                2 => get_string('teachingcompetence', 'local_urise'),
                3 => get_string('teachingconversations', 'local_urise'),
                4 => get_string('tailoredsupport', 'local_urise'),
                5 => get_string('qualificationforstudent', 'local_urise'),
                6 => get_string('coachingforstaff', 'local_urise'),
                7 => get_string('studyworkshops', 'local_urise'),
                8 => get_string('mentoring', 'local_urise'),
                9 => get_string('imoox', 'local_urise'),
                10 => get_string('basiswissenbiblio', 'local_urise'),
                11 => get_string('literatursuche', 'local_urise'),
                12 => get_string('orgauethikwissenschaft', 'local_urise'),
                13 => get_string('spezialwissenbiblio', 'local_urise'),
                14 => get_string('sciencecommunicationprogramme', 'local_urise'),
                15 => get_string('kompakttrainingfuehrungs', 'local_urise'),
            ];

            $standardfilter->add_options($options);
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
                'today 00:00',
                'today 00:00 1 year',
                ['within']
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
                'now + 1 year',
                ['within']
            );

            $table->add_filter($datepicker);
        }
    }

    /**
     * Get booking from shortcode arguments.
     *
     * @param array $args
     *
     * @return mixed
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
     * @param urise_table$table
     * @param array $args
     *
     * @return void
     *
     */
    private static function set_table_options_from_arguments(&$table, $args) {
        self::fix_args($args);

        $table->set_display_options($args);
        \mod_booking\shortcodes::set_common_table_options_from_arguments($table, $args);
        self::set_common_table_options_from_arguments($table, $args);
    }

    /**
     * Setting options from shortcodes arguments common for urise_table.
     *
     * @param urise_table $table reference to table
     * @param array $args
     *
     * @return void
     *
     */
    private static function set_common_table_options_from_arguments(&$table, $args) {
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
                'text' => get_string('coursename', 'local_urise'),
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
        if (!empty($args['showfilterbutton'])) {
            $table->showfilterbutton = true;
        } else {
            $table->showfilterbutton = false;
        }
    }

    /**
     * Sets columns for calendar.
     *
     * @return void
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
     * @return void
     */
    public static function generate_table_for_cards(&$table) {
        $table->define_cache('mod_booking', 'bookingoptionstable');

        $table->tabletemplate = 'local_urise/table_card';

        // We also need to set the user preference for the template.
        set_user_preference('wbtable_chosen_template_' . $table->uniqueid, 'local_urise/table_card');

        $table->add_subcolumns('ariasection', ['puretext']);

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);

        $table->add_subcolumns('url', ['url']);
        $table->add_subcolumns('cardimage', ['image']);
        $table->set_tableclass('cardimageclass', 'imageforcard');
        $table->add_subcolumns('cardheader', ['botags', 'action', 'bookings']);
        $table->add_subcolumns('cardfooter', ['price']);

        self::add_urise_infolist($table);

        $table->add_classes_to_subcolumns(
            'cardlist',
            ['columniclassbefore' => 'fa-regular fa-message fa-fw text-primary mr-2'],
            ['kurssprache']
        );
        $table->add_classes_to_subcolumns(
            'cardlist',
            ['columniclassbefore' => 'fa fa-clock-o text-primary fa-fw  showdatesicon mr-2'],
            ['umfang']
        );
        $table->add_classes_to_subcolumns(
            'cardlist',
            ['columniclassbefore' => 'fa-solid fa-computer fa-fw  text-primary mr-2'],
            ['format']
        );
        $table->add_classes_to_subcolumns(
            'cardlist',
            ['columniclassbefore' => 'fa-solid fa-hashtag fa-fw  text-primary mr-2'],
            ['kompetenzen']
        );
        $table->add_classes_to_subcolumns(
            'cardlist',
            ['columniclassbefore' => 'fa fa-calendar text-primary fa-fw  showdatesicon mr-2'],
            ['showdates']
        );
        $table->add_classes_to_subcolumns('cardlist', ['columnclass' => 'd-flex align-item-center'], ['showdates']);
        $table->add_classes_to_subcolumns('cardheader', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardheader', ['columnvalueclass' => 'mr-auto'], ['botags']);
        $table->add_classes_to_subcolumns('cardheader', ['columnvalueclass' => 'ml-auto'], ['bookings']);

        $table->add_subcolumns('cardbody', ['text', 'description']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'mr-auto'], ['text']);

        $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardfooter', ['columnkeyclass' => 'd-none']);
        $table->tabletemplate = 'local_urise/table_card';
    }

    /**
     * Generate table for list.
     * @param mixed $table
     * @param mixed $args
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function generate_table_for_list(&$table) {
        // Columns.
        $subcolumnsleftside = ['text', 'description'];
        $subcolumnsfooter = ['organisation'];
        $subcolumnsinfo = ['showdates', 'umfang'];

        // Check if we should add the description.
        if (get_config('local_urise', 'shortcodelists_showdescriptions')) {
            $subcolumnsleftside[] = 'description';
        }

        $table->cardsort = true;
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $table->infinitescroll = $infinitescrollpage; // We don't want this currently. */
        $table->tabletemplate = 'local_urise/table_list';

        // We also need to set the user preference for the template.
        set_user_preference('wbtable_chosen_template_' . $table->uniqueid, 'local_urise/table_list');

        $table->define_cache('mod_booking', 'bookingoptionstable');

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);

        $table->add_subcolumns('cardimage', ['image']);

        $table->set_tableclass('cardimageclass', 'imageforlist');

        $table->add_subcolumns('top', ['botags', 'action', 'bookings' ]);
        $table->add_subcolumns('leftside', $subcolumnsleftside);
        $table->add_subcolumns('info', $subcolumnsinfo);
        $table->add_subcolumns('footer', $subcolumnsfooter);

        $table->add_subcolumns('rightside', ['invisibleoption', 'course', 'price']);
        $table->add_classes_to_subcolumns('rightside', ['columnkeyclass' => 'd-none']);

        $table->add_classes_to_subcolumns('top', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('top', ['columnclass' => 'mr-auto text-uppercase'], ['botags']);

        $table->add_classes_to_subcolumns('leftside', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-1 title'], ['text']);
        if (get_config('local_urise', 'shortcodelists_showdescriptions')) {
            $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-3 col-md-auto'], ['description']);
        }

        $table->add_classes_to_subcolumns(
            'info',
            ['columniclassbefore' => 'fa fa-calendar text-primary fa-fw  showdatesicon'],
            ['showdates']
        );
        $table->add_classes_to_subcolumns(
            'info',
            ['columniclassbefore' => 'fa fa-clock-o text-primary fa-fw mr-2'],
            ['umfang']
        );
        $table->add_classes_to_subcolumns('info', ['columnclassinner' => 'align-items-center'], ['showdates']);
        if (get_config('local_urise', 'uriseshortcodesshowend')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-stop'], ['courseendtime']);
        }
        if (get_config('local_urise', 'uriseshortcodesshowbookablefrom')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-forward'], ['bookingopeningtime']);
        }
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('locationalt', 'local_urise')], ['location']);
        $table->add_classes_to_subcolumns('cardimage', ['cardimagealt' => get_string('imagealt', 'local_urise')], ['image']);

        // We still need to clean this up.
        $table->add_subcolumns('userinfolist', ['organisation', 'invisibleoption', 'price']);
        $table->add_classes_to_subcolumns(
            'uriseinfolist',
            ['columnvalueclass' => 'text-right mb-auto align-self-end shortcodes_option_info_invisible '],
            ['invisibleoption']
        );

        self::add_urise_infolist($table);
        // Unset some elements used in cards.
        unset($table->subcolumns['uriseinfolist']['course']);
        unset($table->subcolumns['uriseinfolist']['organisation']);
        unset($table->subcolumns['uriseinfolist']['showdates']);
        unset($table->subcolumns['uriseinfolist']['umfang']);

        $table->tabletemplate = 'local_urise/table_list';
        $table->is_downloading('', 'List of booking options');
    }

    /**
     * Generate table for textlist.
     * @param mixed $table
     * @param mixed $args
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function generate_table_for_textlist(&$table) {
        // Columns.
        $subcolumnsleftside = ['text', 'description'];
        $subcolumnsfooter = ['organisation'];
        $subcolumnsinfo = ['showdates', 'umfang'];

        // Check if we should add the description.
        if (get_config('local_urise', 'shortcodelists_showdescriptions')) {
            $subcolumnsleftside[] = 'description';
        }

        $table->cardsort = true;
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $table->infinitescroll = $infinitescrollpage; // We don't want this currently. */
        $table->tabletemplate = 'local_urise/table_listtext';

        // We also need to set the user preference for the template.
        set_user_preference('wbtable_chosen_template_' . $table->uniqueid, 'local_urise/table_listtext');

        $table->define_cache('mod_booking', 'bookingoptionstable');

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);


        $table->add_subcolumns('top', ['botags', 'action', 'bookings' ]);
        $table->add_subcolumns('leftside', $subcolumnsleftside);
        $table->add_subcolumns('info', $subcolumnsinfo);
        $table->add_subcolumns('footer', $subcolumnsfooter);

        $table->add_subcolumns('rightside', ['invisibleoption', 'course', 'price']);
        $table->add_classes_to_subcolumns('rightside', ['columnkeyclass' => 'd-none']);

        $table->add_classes_to_subcolumns('top', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('top', ['columnclass' => 'mr-auto text-uppercase'], ['botags']);

        $table->add_classes_to_subcolumns('leftside', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-1 title'], ['text']);
        if (get_config('local_urise', 'shortcodelists_showdescriptions')) {
            $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-3 col-md-auto'], ['description']);
        }

        $table->add_classes_to_subcolumns(
            'info',
            ['columniclassbefore' => 'fa fa-calendar text-primary fa-fw  showdatesicon'],
            ['showdates']
        );
        $table->add_classes_to_subcolumns(
            'info',
            ['columniclassbefore' => 'fa fa-clock-o text-primary fa-fw mr-2'],
            ['umfang']
        );
        $table->add_classes_to_subcolumns('info', ['columnclassinner' => 'align-items-center'], ['showdates']);
        if (get_config('local_urise', 'uriseshortcodesshowend')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-stop'], ['courseendtime']);
        }
        if (get_config('local_urise', 'uriseshortcodesshowbookablefrom')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-forward'], ['bookingopeningtime']);
        }
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('locationalt', 'local_urise')], ['location']);
        $table->add_classes_to_subcolumns('cardimage', ['cardimagealt' => get_string('imagealt', 'local_urise')], ['image']);

        // We still need to clean this up.
        $table->add_subcolumns('userinfolist', ['organisation', 'invisibleoption', 'price']);
        $table->add_classes_to_subcolumns(
            'uriseinfolist',
            ['columnvalueclass' => 'text-right mb-auto align-self-end shortcodes_option_info_invisible '],
            ['invisibleoption']
        );

        self::add_urise_infolist($table);
        // Unset some elements used in cards.
        unset($table->subcolumns['uriseinfolist']['course']);
        unset($table->subcolumns['uriseinfolist']['organisation']);
        unset($table->subcolumns['uriseinfolist']['showdates']);
        unset($table->subcolumns['uriseinfolist']['umfang']);

        $table->tabletemplate = 'local_urise/table_listtext';
        $table->is_downloading('', 'List of booking options');
    }

    /**
     * Add the urise infolist to the table.
     * @param mixed $table
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function add_urise_infolist(&$table) {
        $table->add_subcolumns('uriseinfolist', [
            'showdates', 'umfang', 'kurssprache', 'format', 'kompetenzen', 'organisation', 'course']);
        $table->add_classes_to_subcolumns(
            'uriseinfolist',
            ['columniclassbefore' => 'fa-regular fa-message fa-fw text-primary mr-2'],
            ['kurssprache']
        );
         $table->add_classes_to_subcolumns(
             'uriseinfolist',
             ['columniclassbefore' => 'fa fa-clock-o text-primary fa-fw  showdatesicon mr-2'],
             ['umfang']
         );
         $table->add_classes_to_subcolumns(
             'uriseinfolist',
             ['columniclassbefore' => 'fa-solid fa-computer fa-fw  text-primary mr-2'],
             ['format']
         );
         $table->add_classes_to_subcolumns(
             'uriseinfolist',
             ['columniclassbefore' => 'fa-solid fa-hashtag fa-fw  text-primary mr-2'],
             ['kompetenzen']
         );
        $table->add_classes_to_subcolumns(
            'uriseinfolist',
            ['columniclassbefore' => 'fa fa-calendar text-primary fa-fw  showdatesicon mr-2'],
            ['showdates']
        );
        $table->add_classes_to_subcolumns('uriseinfolist', ['columnclass' => 'd-flex align-item-center'], ['showdates']);
        $table->add_classes_to_subcolumns('uriseinfolist', ['columnkeyclass' => 'd-none']);
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
     * Modify the wherearray via arguments.
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
                            $wherearray[$key] = strip_tags(trim($value));
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
            '41' => [
                'parent' => get_string('fuehrungskompetenzen', 'local_urise'),
                'localizedname' => get_string('teamfuehrungentwicklung', 'local_urise'),
            ],
            '42' => [
                'parent' => get_string('fuehrungskompetenzen', 'local_urise'),
                'localizedname' => get_string('selbstfuehrung', 'local_urise'),
            ],
            '43' => [
                'parent' => get_string('fuehrungskompetenzen', 'local_urise'),
                'localizedname' => get_string('mitarbeitendefoerdern', 'local_urise'),
            ],
            '44' => [
                'parent' => get_string('fuehrungskompetenzen', 'local_urise'),
                'localizedname' => get_string('entscheidungskompetenzen', 'local_urise'),
            ],
            '45' => [
                'parent' => get_string('fuehrungskompetenzen', 'local_urise'),
                'localizedname' => get_string('strategischeplanungentwicklung', 'local_urise'),
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

    /**
     * Helperfunction to generate output
     *
     * @param mixed $args
     * @param urise_table $table
     * @param int $perpage
     *
     * @return string
     *
     */
    private static function generate_output($args, $table, $perpage) {
        if (!empty($args['lazy'])) {
            [$idstring, $encodedtable, $out] = $table->lazyouthtml($perpage, true);
            return $out;
        }
        return $table->outhtml($perpage, true);
    }

    /**
     * [Description for get_sql_params]
     *
     * @param mixed $context
     * @param mixed $wherearray
     * @param string $additionalwhere
     * @param array $bookingparams
     * @param int $userid
     *
     * @return [type]
     *
     */
    private static function get_sql_params($context, $wherearray, $additionalwhere, $bookingparams = [], $userid = null) {
        return  [$fields, $from, $where, $params, $filter] =
                booking::get_options_filter_sql(
                    0,
                    0,
                    '',
                    null,
                    $context,
                    [],
                    $wherearray,
                    $userid,
                    $bookingparams,
                    $additionalwhere
                );
    }
}
