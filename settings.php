<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     local_berta
 * @category    admin
 * @copyright   2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

if ($hassiteconfig) {
    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf

     // TODO: Define the plugin settings page - {@link https://docs.moodle.org/dev/Admin_settings}.

     $settings = new admin_settingpage('berta', '');
     $ADMIN->add('localplugins', new admin_category('local_berta', get_string('pluginname', 'local_berta')));
     $ADMIN->add('local_berta', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(
            new admin_setting_heading('shortcodessetdefaultinstance',
                get_string('shortcodessetdefaultinstance', 'local_berta'),
                get_string('shortcodessetdefaultinstancedesc', 'local_berta')));

        $allowedinstances = [];
        $multiinstances = [];

        if ($records = $DB->get_records_sql(
            "SELECT cm.id cmid, b.id bookingid, b.name bookingname
            FROM {course_modules} cm
            LEFT JOIN {booking} b
            ON b.id = cm.instance
            WHERE cm.module IN (
                SELECT id
                FROM {modules} m
                WHERE m.name = 'booking'
            )"
        )) {
            foreach ($records as $record) {
                $allowedinstances[$record->cmid] = "$record->bookingname (ID: $record->cmid)";
                $multiinstances[$record->bookingid] = $record->bookingname;
                $defaultcmid = $record->cmid; // Last cmid will be the default one.
            }
        }

        if (empty($allowedinstances)) {
            // If we have no instances, show an explanation text.
            $settings->add(new admin_setting_description(
                'shortcodesnobookinginstance',
                get_string('shortcodesnobookinginstance', 'local_berta'),
                get_string('shortcodesnobookinginstancedesc', 'local_berta')
            ));
        } else {
            // Show select for cmids of booking instances.
            $settings->add(
                new admin_setting_configselect('local_berta/shortcodessetinstance',
                    get_string('shortcodessetinstance', 'local_berta'),
                    get_string('shortcodessetinstancedesc', 'local_berta'),
                    $defaultcmid, $allowedinstances));
        }

        if (!empty($multiinstances)) {
            // Booking default instances.
            $componentname = 'local_berta';
            $settings->add(new admin_setting_configmultiselect(
                    $componentname . '/multibookinginstances',
                    get_string('multibookinginstances', $componentname),
                    get_string('multibookinginstances_desc', $componentname),
                    [],
                    $multiinstances)
            );
        }

        $settings->add(
            new admin_setting_configtext('local_berta/shortcodesarchivecmids',
                get_string('shortcodesarchivecmids', 'local_berta'),
                get_string('shortcodesarchivecmids_desc', 'local_berta'), ''));

        // Shortcode lists.
        $settings->add(
            new admin_setting_heading('shortcodelists',
                get_string('shortcodelists', 'local_berta'),
                get_string('shortcodelists_desc', 'local_berta')));

        $settings->add(
            new admin_setting_configcheckbox('local_berta/shortcodelists_showdescriptions',
                get_string('shortcodelists_showdescriptions', 'local_berta'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_berta/bertashortcodesshowstart',
                get_string('bertashortcodes:showstart', 'local_berta'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_berta/bertashortcodesshowend',
                get_string('bertashortcodes:showend', 'local_berta'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_berta/bertashortcodesshowbookablefrom',
                get_string('bertashortcodes:showbookablefrom', 'local_berta'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_berta/bertashortcodesshowbookableuntil',
                get_string('bertashortcodes:showbookableuntil', 'local_berta'), '', 0));

        $showfiltercoursetimesetting = new admin_setting_configcheckbox('local_berta/bertashortcodesshowfiltercoursetime',
            get_string('bertashortcodes:showfiltercoursetime', 'local_berta'), '', 0);
        $showfiltercoursetimesetting->set_updatedcallback(function() {
            cache_helper::purge_by_event('setbackoptionstable');
        });
        $settings->add($showfiltercoursetimesetting);

        $showfilterbookingtimesetting = new admin_setting_configcheckbox('local_berta/bertashortcodesshowfilterbookingtime',
            get_string('bertashortcodes:showfilterbookingtime', 'local_berta'), '', 0);
        $showfilterbookingtimesetting->set_updatedcallback(function() {
            cache_helper::purge_by_event('setbackoptionstable');
        });
        $settings->add($showfilterbookingtimesetting);

        $collapsedescriptionoptions = [
            0 => get_string('collapsedescriptionoff', 'local_berta'),
            100 => "100",
            200 => "200",
            300 => "300",
            400 => "400",
            500 => "500",
            600 => "600",
            700 => "700",
            800 => "800",
            900 => "900",
        ];
        $settings->add(
            new admin_setting_configselect('local_berta/collapsedescriptionmaxlength',
                get_string('collapsedescriptionmaxlength', 'local_berta'),
                get_string('collapsedescriptionmaxlength_desc', 'local_berta'),
                300, $collapsedescriptionoptions));

        // CONTRACT MANAGEMENT.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $settings->add(
            new admin_setting_heading('contractmanagement_heading',
                get_string('contractmanagementsettings', 'local_berta'),
                get_string('contractmanagementsettings_desc', 'local_berta')));

        $settings->add(
            new admin_setting_configtextarea('local_berta/contractformula',
                get_string('contractformula', 'local_berta'),
                get_string('contractformula_desc', 'local_berta'), '', PARAM_TEXT, 60, 10)); */
    }
}
