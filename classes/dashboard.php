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

namespace local_berta;

/**
 * Helper functions for payment stuff.
 *
 * @package local_berta
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard {

    /**
     * Generate a list of all Sports.
     *
     * @return array
     */
    public static function return_all_parents() {
        global $DB;

        $sql = "SELECT coca.id, coca.name, coca.coursecount
            FROM {course_categories} coca
            WHERE coca.parent = 0";

        return $DB->get_records_sql($sql);
    }
}
