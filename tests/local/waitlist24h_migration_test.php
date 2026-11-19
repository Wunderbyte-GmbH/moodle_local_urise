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

namespace local_urise\local;

/**
 * Tests for the waiting list behaviour set on priced options in the "24 hour mode".
 *
 * @package     local_urise
 * @category    test
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \local_urise\local\waitlist24h_migration
 */
final class waitlist24h_migration_test extends \advanced_testcase {
    /**
     * Only priced options in the 24 hour mode are set to "remove when the offer expires".
     */
    public function test_only_priced_options_in_the_24_hour_mode_are_changed(): void {
        global $DB;

        $this->resetAfterTest();

        $mode = ['waitforconfirmation' => 2, 'confirmationonnotification' => 2];

        $priced = $this->create_option(80, $mode);
        $pricedforall = $this->create_option(80, ['waitforconfirmation' => 2, 'confirmationonnotification' => 1]);
        $alreadyset = $this->create_option(80, $mode, 3);
        $unchanged = [
            'free option in the mode' => $this->create_option(0, $mode),
            'direct booking' => $this->create_option(80, null),
            'direct booking with other json' => $this->create_option(80, ['foo' => 'bar']),
            'always confirm' => $this->create_option(80, ['waitforconfirmation' => 1, 'confirmationonnotification' => 2]),
            'confirmed by the organiser' => $this->create_option(80, ['waitforconfirmation' => 2]),
            'other waiting list behaviour, not in the mode' => $this->create_option(80, null, 1),
        ];
        $before = $DB->get_records('booking_options', null, 'id', 'id, json, waitlistrecycling');

        $this->assertSame(2, waitlist24h_migration::run());

        foreach ([$priced, $pricedforall, $alreadyset] as $optionid) {
            $option = $DB->get_record('booking_options', ['id' => $optionid]);
            $this->assertEquals(3, $option->waitlistrecycling);
            $this->assertSame($before[$optionid]->json, $option->json);
        }
        foreach ($unchanged as $case => $optionid) {
            $option = $DB->get_record('booking_options', ['id' => $optionid]);
            $this->assertEquals($before[$optionid]->waitlistrecycling, $option->waitlistrecycling, $case);
            $this->assertSame($before[$optionid]->json, $option->json, $case);
        }

        // A second run has nothing left to do.
        $this->assertSame(0, waitlist24h_migration::run());
    }

    /**
     * Creates a booking option directly in the database.
     *
     * @param float $price price of the default price category, 0 for no price
     * @param array|null $json content of the json column
     * @param int $waitlistrecycling
     * @return int id of the option
     */
    private function create_option(float $price, ?array $json, int $waitlistrecycling = 0): int {
        global $DB;

        $optionid = $DB->insert_record('booking_options', (object)[
            'bookingid' => 0,
            'text' => 'Option',
            'description' => '',
            'maxanswers' => 1,
            'maxoverbooking' => 5,
            'waitlistrecycling' => $waitlistrecycling,
            'json' => $json === null ? null : json_encode($json),
        ]);
        if ($price > 0) {
            $DB->insert_record('booking_prices', (object)[
                'itemid' => $optionid,
                'area' => 'option',
                'pricecategoryidentifier' => 'default',
                'price' => $price,
                'currency' => 'EUR',
            ]);
        }

        return $optionid;
    }
}
