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
 * @package     local_urise
 * @category    admin
 * @copyright   2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

if ($hassiteconfig) {
    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf

     // TODO: Define the plugin settings page - {@link https://docs.moodle.org/dev/Admin_settings}.

     $settings = new admin_settingpage('urise', '');
     $ADMIN->add('localplugins', new admin_category('local_urise', get_string('pluginname', 'local_urise')));
     $ADMIN->add('local_urise', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(
            new admin_setting_heading('shortcodessetdefaultinstance',
                get_string('shortcodessetdefaultinstance', 'local_urise'),
                get_string('shortcodessetdefaultinstancedesc', 'local_urise')));

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
                get_string('shortcodesnobookinginstance', 'local_urise'),
                get_string('shortcodesnobookinginstancedesc', 'local_urise')
            ));
        } else {
            // Show select for cmids of booking instances.
            $settings->add(
                new admin_setting_configselect('local_urise/shortcodessetinstance',
                    get_string('shortcodessetinstance', 'local_urise'),
                    get_string('shortcodessetinstancedesc', 'local_urise'),
                    $defaultcmid, $allowedinstances));
        }

        if (!empty($multiinstances)) {
            // Booking default instances.
            $componentname = 'local_urise';
            $settings->add(new admin_setting_configmultiselect(
                    $componentname . '/multibookinginstances',
                    get_string('multibookinginstances', $componentname),
                    get_string('multibookinginstances_desc', $componentname),
                    [],
                    $multiinstances)
            );
        }

        $settings->add(
            new admin_setting_configtext('local_urise/shortcodesarchivecmids',
                get_string('shortcodesarchivecmids', 'local_urise'),
                get_string('shortcodesarchivecmids_desc', 'local_urise'), ''));

        // Shortcode lists.
        $settings->add(
            new admin_setting_heading('shortcodelists',
                get_string('shortcodelists', 'local_urise'),
                get_string('shortcodelists_desc', 'local_urise')));

        $settings->add(
            new admin_setting_configcheckbox('local_urise/shortcodelists_showdescriptions',
                get_string('shortcodelists_showdescriptions', 'local_urise'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_urise/uriseshortcodesshowstart',
                get_string('uriseshortcodes:showstart', 'local_urise'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_urise/uriseshortcodesshowend',
                get_string('uriseshortcodes:showend', 'local_urise'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_urise/uriseshortcodesshowbookablefrom',
                get_string('uriseshortcodes:showbookablefrom', 'local_urise'), '', 0));

        $settings->add(
            new admin_setting_configcheckbox('local_urise/uriseshortcodesshowbookableuntil',
                get_string('uriseshortcodes:showbookableuntil', 'local_urise'), '', 0));

        $showfiltercoursetimesetting = new admin_setting_configcheckbox('local_urise/uriseshortcodesshowfiltercoursetime',
            get_string('uriseshortcodes:showfiltercoursetime', 'local_urise'), '', 0);
        $showfiltercoursetimesetting->set_updatedcallback(function() {
            cache_helper::purge_by_event('setbackoptionstable');
        });
        $settings->add($showfiltercoursetimesetting);

        $showfilterbookingtimesetting = new admin_setting_configcheckbox('local_urise/uriseshortcodesshowfilterbookingtime',
            get_string('uriseshortcodes:showfilterbookingtime', 'local_urise'), '', 0);
        $showfilterbookingtimesetting->set_updatedcallback(function() {
            cache_helper::purge_by_event('setbackoptionstable');
        });
        $settings->add($showfilterbookingtimesetting);

        $collapsedescriptionoptions = [
            0 => get_string('collapsedescriptionoff', 'local_urise'),
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
            new admin_setting_configselect(
                'local_urise/collapsedescriptionmaxlength',
                get_string('collapsedescriptionmaxlength', 'local_urise'),
                get_string('collapsedescriptionmaxlength_desc', 'local_urise'),
                300, $collapsedescriptionoptions));

        $settings->add(new admin_setting_configtextarea(
            'local_urise/organisationfilter',
            get_string('organisationfilterdefinition', 'local_urise'),
            get_string('organisationfilterdefinition_desc', 'local_urise'),
            '',
            PARAM_TEXT,
            60,
            10
        ));
        // CONTRACT MANAGEMENT.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $settings->add(
            new admin_setting_heading('contractmanagement_heading',
                get_string('contractmanagementsettings', 'local_urise'),
                get_string('contractmanagementsettings_desc', 'local_urise')));

        $settings->add(
            new admin_setting_configtextarea('local_urise/contractformula',
                get_string('contractformula', 'local_urise'),
                get_string('contractformula_desc', 'local_urise'), '', PARAM_TEXT, 60, 10)); */
    }
}
