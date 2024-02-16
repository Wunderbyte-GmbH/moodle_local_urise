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
 * Webservice to reload table.
 *
 * @package     local_berta
 * @category    upgrade
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
        'local_berta_get_all_itemids' => array(
                'classname' => 'local_berta\external\get_all_itemids',
                'classpath' => '',
                'description' => 'Get all item ids',
                'type' => 'write',
                'capabilities' => '',
                'ajax' => 1
        ),
        'local_berta_get_parent_categories' => array(
                'classname' => 'local_berta\external\get_parent_categories',
                'classpath' => '',
                'description' => 'Get all parent categories',
                'type' => 'read',
                'capabilities' => '',
                'ajax' => 1
        ),
        'local_berta_get_parent_content' => array(
                'classname' => 'local_berta\external\get_parent_content',
                'classpath' => '',
                'description' => 'Get content of parent category',
                'type' => 'read',
                'capabilities' => '',
                'ajax' => 1
        ),
        'local_berta_set_parent_content' => array(
          'classname' => 'mod_booking\external\save_option_field_config',
          'classpath' => '',
          'description' => 'Set content of parent category',
          'type' => 'read',
          'capabilities' => '',
          'ajax' => 1
        ),
);

