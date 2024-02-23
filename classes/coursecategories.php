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

use dml_exception;

/**
 * Manage coursecategories in berta.
 *
 * @package local_berta
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursecategories {

    /**
     * Returns coursecategories.
     * When 0, it returns all coursecateogries, else only the specific one.
     * @param int $categoryid
     * @param bool $onlyparents
     * @return array
     * @throws dml_exception
     */
    public static function return_course_categories(int $categoryid = 0, $onlyparents = true) {
        global $DB;

        $wherearray = [];

        if (!empty($categoryid)) {
            $wherearray[] = 'coca.id = ' . $categoryid;
        }

        if ($onlyparents) {
            $wherearray[] = 'coca.parent = 0';
        }
        if (!empty($wherearray)) {
            $where = 'WHERE ' . implode(' AND ', $wherearray);
        }

        $sql = "SELECT coca.id,
                       coca.name,
                       coca.description,
                       coca.path,
                       coca.coursecount,
                       c.id as contextid
                FROM {course_categories} coca
                JOIN {context} c ON c.instanceid=coca.id AND c.contextlevel = 40
                $where";

        return $DB->get_records_sql($sql);
    }
}
