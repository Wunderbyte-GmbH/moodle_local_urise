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
 * This class contains a list of webservice functions related to the Shopping Cart Module by Wunderbyte.
 *
 * @package    local_berta
 * @copyright  2024 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_berta\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;
use context_coursecat;
use local_berta\dashboard;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External Service for shopping cart.
 *
 * @package   local_berta
 * @copyright 2024 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Georg Maißer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_parent_content extends external_api {

    /**
     * Describes the paramters for add_item_to_cart.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
          'coursecategoryid'  => new external_value(PARAM_TEXT, 'oe', VALUE_DEFAULT),
        ]);
    }


    /**
     * Webservice for shopping_cart class to add a new item to the cart.
     *
     * @param string $oe
     * @param string $area
     *
     * @return array
     */
    public static function execute(string $oe): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'coursecategoryid' => $oe,
        ]);

        require_login();

        if ($oe == 0) {
            $returnarray = [
                'id' => 0,
                'name' => 'General',
                'coursecount' => 0,
                'description' => 'General overview of all organizational entitites',
                'path' => 'overall',
            ];

            $records = dashboard::return_all_parents();

            foreach ($records as $record) {
              $context = context_coursecat::instance($record->id);
                if (!has_capability('local/berta:view', $context)) {
                    continue;
                }
                $returnarray['coursecount'] += $record->coursecount;
            }
            return $returnarray;
        }

        $sql = "SELECT coca.id, coca.name, coca.coursecount,
            coca.description, coca.path
            FROM {course_categories} coca
            WHERE coca.parent = 0
            AND coca.id=:oe";

        $params = ['oe' => $oe];

        $record = $DB->get_record_sql($sql, $params);

        return (array)$record;
    }

    /**
     * Returns array of items.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
                array(
                    'id' => new external_value(PARAM_TEXT, 'Item id'),
                    'name' => new external_value(PARAM_TEXT, 'Item name'),
                    'coursecount' => new external_value(PARAM_TEXT, 'Coursecount'),
                    'description' => new external_value(PARAM_TEXT, 'description'),
                    'path' => new external_value(PARAM_TEXT, 'path'),
                )
        );
    }
}
