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
 * Event observers.
 *
 * @package     local_urise
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_urise;

use cache_helper;
use local_wunderbyte_table\event\template_switched;
use local_wunderbyte_table\wunderbyte_table;
use local_urise\shortcodes;

/**
 * Event observer for local_urise.
 */
class observer {
    /**
     * Observer for the payment_added event
     */
    public static function payment_added() {
        cache_helper::purge_by_event('setbackcachedpaymenttable');
    }

    /**
     * Observer for the payment_completed event
     */
    public static function payment_completed() {
        cache_helper::purge_by_event('setbackcachedpaymenttable');
    }

    /**
     * Observer for the payment_successful event
     */
    public static function payment_successful() {
        cache_helper::purge_by_event('setbackcachedpaymenttable');
    }

    /**
     * Event handler for user creation.
     *
     * @param \core\event\user_created $event
     * @return void
     */
    public static function user_created(\core\event\user_created $event) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/accesslib.php');

        $roleid = get_config('local_urise', 'roleforselfregisteredusers');
        if (empty($roleid)) {
            return;
        }

        // Get the user data from the event.
        $userdata = $event->get_record_snapshot('user', $event->objectid);

        // Check if the user was created via email self-registration.
        if ($userdata->auth === 'email') {
            // Assign the role to the user in the system context.
            $context = \context_system::instance();
            role_assign($roleid, $userdata->id, $context->id);
        }
    }

    /**
     * React on template_switched which is triggered by template switcher.
     *
     * @param template_switched $event
     */
    public static function template_switched(template_switched $event) {
        $data = $event->get_data();
        $encodedtable = $data["other"]["tablecachehash"];
        $template = $data["other"]["template"];
        // We currently don't need the viewparam in urise, because we only support 2 templates currently.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $viewparam = $data["other"]["viewparam"]; */
        // Only apply this for Booking templates!
        if (
            !empty($encodedtable)
            && in_array($template, [
                'local_urise/table_card',
                'local_urise/table_list',
            ])
        ) {
            $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
            $columns = array_keys($table->columns);
            unset($columns['id']);

            // Not necessary in urise, we only need this if we support different viewparams for the same template.
            // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            /* $table->unset_template_data(); */

            switch ($template) {
                case 'local_urise/table_list':
                    shortcodes::generate_table_for_list($table);
                    break;
                case 'local_urise/table_card':
                default:
                    shortcodes::generate_table_for_cards($table);
                    break;
            }
            $table->return_encoded_table(true);
        }
    }
}
