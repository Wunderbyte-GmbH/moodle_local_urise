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

namespace local_urise\task;

use core\task\scheduled_task;
use local_urise\sap_daily_sums;

/**
 * Scheduled task creates SAP files in Moodle data directory.
 * @package local_urise
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_sap_files extends scheduled_task {
    /**
     * Get name of the task.
     * @return string
     */
    public function get_name() {
        return get_string('create_sap_files', 'local_urise');
    }

    /**
     * Scheduled task that creates the SAP files needed for reporting.
     *
     */
    public function execute() {
        $now = time();
        $tendaysago = strtotime('-10 days', $now);
        sap_daily_sums::create_sap_files_from_date($tendaysago);
    }
}
