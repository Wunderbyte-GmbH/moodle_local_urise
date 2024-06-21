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
use context_module;
use dml_exception;
use local_wunderbyte_table\filters\types\hierarchicalfilter;
use local_wunderbyte_table\wunderbyte_table;
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
     * KOMPETENZEN
     *
     * @var array]
     */
    public const KOMPETENZEN = [
        'Lehrkonzeption & -planung' => [
            'parent' => 'Lehrkompetenzen',
        ],
        'Lehr- & Lernmethoden' => [
            'parent' => 'Lehrkompetenzen',
        ],
        'Erstellung Lehr-/Lernmaterialien' => [
            'parent' => 'Lehrkompetenzen',
        ],
        'Lehren mit digitalen Technologien' => [
            'parent' => 'Lehrkompetenzen',
        ],
        'Prüfen & Beurteilen' => [
            'parent' => 'Lehrkompetenzen',
        ],
        'Betreuung schriftlicher Arbeiten' => [
            'parent' => 'Lehrkompetenzen',
        ],
        'Weiterentwicklung der Lehre' => [
            'parent' => 'Lehrkompetenzen',
        ],
        'Wissenschaftliches Arbeiten' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Wissenschaftliches Publizieren' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Open Science' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Wissensaustausch & Innovation' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Wissenschaftliche Integrität' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Networking in der Wissenschaft' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Interdisziplinäre Forschung' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Forschungsförderung' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Karriereentwicklung & -planung' => [
            'parent' => 'Forschungskompetenzen',
        ],
        'Präsentation' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Gesprächs- und Verhandlungsführung' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Feedback' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Moderation' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Sprachkenntnisse' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Konfliktmanagement' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Information & Kommunikation' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Gender- & Diversitätskompetenz' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Kooperationskompetenz' => [
            'parent' => 'Kommunikations- und Kooperationskompetenzen',
        ],
        'Veranstaltungsorganisation' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Arbeitsorganisation' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Selbstorganisation' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Service- & Kund*innenorientierung' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Lösungs- & Zukunftsorientierung' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Ressourceneffizienz' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Change-Kompetenz' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Gesundheitsorientierung' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'Lernkompetenz' => [
            'parent' => 'Selbst- und Arbeitsorganisation',
        ],
        'IT Security' => [
            'parent' => 'Digitalkompetenzen',
        ],
        'Digitale Interaktion' => [
            'parent' => 'Digitalkompetenzen',
        ],
        'Umgang mit Informationen & Daten' => [
            'parent' => 'Digitalkompetenzen',
        ],
        'Technologienutzung' => [
            'parent' => 'Digitalkompetenzen',
        ],
        'Educational Leadership and Management' => [
            'parent' => 'Führungskompetenzen',
        ],
    ];

    /**
     * KOMPETENZEN
     *
     * @var array]
     */
    public const ORGANISATIONEN = [
        'FB Afrikawissenschaften und Orientalistik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Alte Geschichte' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Anglistik und Amerikanistik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Archälogie und Numismatik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Astronomie' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Bildungswissenschaft, Sprachwissenschaft und Vergleichende Literaturwissenschaft' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Biologie und Botanik, Standort Biologie' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Biologie und Botanik, Standort Botanik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Byzantistik und Neogräzistik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Geographie und Regionalforschung' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Germanistik, Nederlandistik und Skandinavistik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Geschichtswissenschaften' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Klassische Philologie, Mittel - und Neulatein' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Kultur - und Sozialanthropologie' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Kunstgeschichte' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Musikwissenschaft' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Ostasienwissenschaften' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Osteuropäische Geschichte und Slawistik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Pharmazie und Ernährungswissenschaften' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Philosophie und Psychologie' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'ZB Physik und Chemie' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Publizistik- und Kommunikationswissenschaft und Informatik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Rechtswissenschaften' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Soziologie und Politikwissenschaft' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Sportwissenschaft' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Südasien-, Tibet- und Buddhismuskunde' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Theater-, Film- und Medienwissenschaft' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Theologie' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Translationswissenschaft' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Wirtschaftswissenschaften und Mathematik' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'FB Zeitgeschichte' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'Forschungsunterstützungs- und Publikationservices' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'Hauptbibliothek' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'Universitätsarchiv' => [
            'parent' => 'Bibliotheks - und Archivwesen',
        ],
        'Advanced Research School in Law and Jurisprudence' => [
            'parent' => 'Doctoral Schools',
        ],
        'Doctoral School Microbiology and Environmental Science' => [
            'parent' => 'Doctoral Schools',
        ],
        'Doctoral School of Philological and Cultural Studies' => [
            'parent' => 'Doctoral Schools',
        ],
        'Oskar Morgenstern Doctoral School' => [
            'parent' => 'Doctoral Schools',
        ],
        'UniVie Doctoral School Computer Science' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School of Historical and Cultural Studies' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School of Philosophy' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School of Social Sciences' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School in Chemistry' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School of Ecology and Evolution' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna International School in Earth and Space Sciences' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna School of Mathematics | Joint Doctoral School mit der TU Wien' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School in Physics' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School of Pharmaceutical, Nutritional and Sport Sciences' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna BioCenter PhD Program, joint doctoral school of the University of Vienna and the Medical University of Vienna' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School in Cognition, Behavior, and Neuroscience - from Biology to Psychology and the Humanities' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School of Theology and Research on Religion' => [
            'parent' => 'Doctoral Schools',
        ],
        'Vienna Doctoral School in Education' => [
            'parent' => 'Doctoral Schools',
        ],
        'Forschungsservice und Nachwuchsförderung' => [
            'parent' => 'Forschungsservice und Nachwuchsförderung',
        ],
        'Organisationskultur und Gleichstellung' => [
            'parent' => 'Personalwesen und Frauenförderung',
        ],
        'Personalentwicklung und Recruiting' => [
            'parent' => 'Personalwesen und Frauenförderung',
        ],
        'Center for Teaching and Learning' => [
            'parent' => 'Studienservice und Lehrwesen',
        ],
        'Koordination Studienservices' => [
            'parent' => 'Studienservice und Lehrwesen',
        ],
        'Zentraler Informatikdienst' => [
            'parent' => 'Zentraler Informatikdienst',
        ],
    ];

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
    public static function unifiedcards($shortcode, $args, $content, $env, $next) {

        // TODO: Define capability.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if (!has_capability('moodle/site:config', $env->context)) {
            return '';
        } */
        self::fix_args($args);
        $booking = self::get_booking($args);

        $bookingids = explode(',', get_config('local_berta', 'multibookinginstances'));

        foreach ($bookingids as $key => $value) {
            if (empty($value)) {
                unset($bookingids[$key]);
            }
        }

        if (empty($bookingids)) {
            return get_string('nobookinginstancesselected', 'local_berta');
        }

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

        self::generate_table_for_cards($table, $args);

        self::set_table_options_from_arguments($table, $args);

        $table->tabletemplate = 'local_berta/table_card';

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
    public static function unifiedlist($shortcode, $args, $content, $env, $next) {

        global $DB;

        self::fix_args($args);

        $bookingids = explode(',', get_config('local_berta', 'multibookinginstances'));

        $bookingids = array_filter($bookingids, fn($a) => !empty($a));

        if (empty($bookingids)) {
            return get_string('nobookinginstancesselected', 'local_berta');
        }

        if (!isset($args['organisation']) || !$category = ($args['organisation'])) {
            $organisation = '';
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

        $table = self::inittableforcourses();

        $table->showcountlabel = $args['countlabel'];
        $table->showreloadbutton = $args['reload'];

        $wherearray = ['bookingid' => $bookingids];

        if (!empty($organisation)) {
            $wherearray['organisation'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, null, [], $wherearray);
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, null, [], $wherearray);
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
        $table->showfilterontop = false;

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

        $bookingids = explode(',', get_config('local_berta', 'multibookinginstances'));

        $bookingids = array_filter($bookingids, fn($a) => !empty($a));

        if (empty($bookingids)) {
            return get_string('nobookinginstancesselected', 'local_berta');
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
                    null,
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

    /**
     * Init the table.
     *
     * @return wunderbyte_table
     *
     */
    private static function inittableforcourses() {

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

    /**
     * Define filtercolumns.
     *
     * @param mixed $table
     *
     * @return void
     *
     */
    private static function define_filtercolumns(&$table) {

        $hierarchicalfilter = new hierarchicalfilter('organisation', get_string('organisation', 'local_berta'));
        $hierarchicalfilter->add_options(self::ORGANISATIONEN);
        $table->add_filter($hierarchicalfilter);

        $hierarchicalfilter = new hierarchicalfilter('kompetenzen', get_string('competency', 'local_berta'));
        $hierarchicalfilter->add_options(self::KOMPETENZEN);
        $table->add_filter($hierarchicalfilter);

        $standardfilter = new standardfilter('dayofweek', get_string('dayofweek', 'local_berta'));
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
        $table->set_tableclass('cardimageclass', 'imagecontainer');
        $table->add_subcolumns('cardheader', ['botags', 'bookings']);
        $table->add_subcolumns('cardlist', ['showdates', 'kurssprache', 'format', 'category', 'organisation']);
        $table->add_subcolumns('cardfooter', ['price']);

        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa-regular fa-message fa-fw text-primary mr-2'],
         ['kurssprache']);
         $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa-solid fa-computer fa-fw  text-primary mr-2'],
         ['format']);
         $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa-solid fa-hashtag fa-fw  text-primary mr-2'],
         ['category']);
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
        $subcolumnsfooter = ['kurssprache', 'format', 'category'];
        $subcolumnsinfo = ['showdates'];

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

        $table->add_subcolumns('cardimage', ['image']);

        $table->set_tableclass('cardimageclass', 'customimg');

        // $table->add_subcolumns('top', ['organisation', 'action']);
        $table->add_subcolumns('top', ['botags', 'action', 'bookings' ]);
        // $table->add_subcolumns('top', ['botags', 'bookings' ]);
        $table->add_subcolumns('leftside', $subcolumnsleftside);
        $table->add_subcolumns('info', $subcolumnsinfo);
        $table->add_subcolumns('footer', $subcolumnsfooter );

        $table->add_subcolumns('rightside', ['organisation', 'invisibleoption', 'course', 'price']);
        $table->add_subcolumns('rightside', ['organisation', 'invisibleoption', 'price']);

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
        if (get_config('local_berta', 'shortcodelists_showdescriptions')) {
            $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-3 col-md-auto'], ['description']);
        }

        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-clock-o text-primary
        showdatesicon'], ['showdates']);
        $table->add_classes_to_subcolumns('info', ['columnclassinner' => 'align-items-center'], ['showdates']);
        if (get_config('local_berta', 'bertashortcodesshowend')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-stop'], ['courseendtime']);
        }
        if (get_config('local_berta', 'bertashortcodesshowbookablefrom')) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-forward'], ['bookingopeningtime']);
        }
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('locationalt', 'local_berta')], ['location']);
        $table->add_classes_to_subcolumns('cardimage', ['cardimagealt' => get_string('imagealt', 'local_berta')], ['image']);

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
         ['category']);

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
