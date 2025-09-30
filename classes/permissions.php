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

namespace local_urise;

use context;
/**
 * Helper functions for payment stuff.
 *
 * @package local_urise
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permissions {
    /**
     * Checks a capability in all possible contexts. It's expensive.
     * @param string $capability
     * @param int $contextlevel
     * @return bool
     */
    public static function has_capability_anywhere($capability = 'local/urise:viewdashboard', $contextlevel = CONTEXT_COURSECAT) {

        global $DB;

        $sql = "SELECT ctx.id AS contextid
            FROM {context} ctx
            JOIN {course_categories} cat ON ctx.instanceid = cat.id
            WHERE ctx.contextlevel = :contextlevel";

        $contextids = $DB->get_fieldset_sql($sql, ['contextlevel' => $contextlevel]);

        foreach ($contextids as $contextid) {
            $context = context::instance_by_id($contextid);
            if (has_capability($capability, $context)) {
                $hascapability = true;
                return true;
            }
        }
        return false;
    }
}
