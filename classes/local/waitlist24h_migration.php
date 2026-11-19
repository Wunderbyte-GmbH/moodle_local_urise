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
 * Sets the waiting list behaviour of the priced booking options in the "24 hour mode".
 *
 * @package     local_urise
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_urise\local;

/**
 * Sets the waiting list behaviour of the priced booking options in the "24 hour mode".
 *
 * An option is in the "24 hour mode" if it only asks for a confirmation on the waiting list
 * (waitforconfirmation = 2) and the offer of a freed seat grants that confirmation
 * (confirmationonnotification is set). Such an option with a price is set to "remove from the
 * waiting list when the offer expires" (waitlistrecycling = 3).
 *
 * @package     local_urise
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class waitlist24h_migration {
    /** @var int waitforconfirmation: confirmation only on the waiting list. */
    public const WAITFORCONFIRMATION_WAITINGLIST = 2;

    /** @var int waitlistrecycling: remove the person from the waiting list when the offer expires. */
    public const WAITLISTRECYCLING_REMOVE = 3;

    /**
     * Runs the migration.
     *
     * @return int the number of options that were changed
     */
    public static function run(): int {
        global $DB;

        $changed = 0;

        $sql = "SELECT bo.id, bo.json
                  FROM {booking_options} bo
                 WHERE bo.waitlistrecycling <> :remove
                       AND EXISTS (SELECT 1
                                     FROM {booking_prices} bp
                                    WHERE bp.area = 'option' AND bp.itemid = bo.id AND bp.price > 0)";
        $rs = $DB->get_recordset_sql($sql, ['remove' => self::WAITLISTRECYCLING_REMOVE]);
        foreach ($rs as $option) {
            $json = json_decode($option->json ?? '');
            if (
                (int)($json->waitforconfirmation ?? 0) !== self::WAITFORCONFIRMATION_WAITINGLIST
                || empty($json->confirmationonnotification)
            ) {
                continue;
            }
            $DB->set_field('booking_options', 'waitlistrecycling', self::WAITLISTRECYCLING_REMOVE, ['id' => $option->id]);
            $changed++;
        }
        $rs->close();

        if ($changed > 0) {
            \cache_helper::purge_by_definition('mod_booking', 'bookingoptionsettings');
        }

        return $changed;
    }
}
