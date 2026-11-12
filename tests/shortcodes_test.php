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

use advanced_testcase;
use context_course;
use context_system;
use mod_booking\singleton_service;
use mod_booking_generator;
use stdClass;
use tool_mocktesttime\time_mock;

/**
 * Tests of the course shortcodes of local_urise.
 *
 * @package local_urise
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class shortcodes_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        time_mock::init();
        time_mock::set_mock_time(strtotime('now'));
        singleton_service::destroy_instance();
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');
        $plugingenerator->teardown();
    }

    /**
     * The search of the course shortcodes finds teachers by their names only.
     *
     * The column teacherobjects is JSON ({"id":81, "firstname":"Pia", ...}), searching it found the ids of the
     * users (a search for "81" found the courses of the teacher with the user id 81) and the keys of the JSON.
     * So the search uses the column teachernames of mod_booking.
     *
     * @covers \local_urise\shortcodes::unifiedview
     * @covers \local_urise\shortcodes::set_common_table_options_from_arguments
     *
     * @dataProvider template_provider
     *
     * @param string $template template of the shortcode ([unifiedlist], [unifiedcards], [unifiedtextlist])
     */
    public function test_search_finds_teachers_by_name_only(string $template): void {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $bookingmanager = $this->getDataGenerator()->create_user();
        $teacher1 = $this->getDataGenerator()->create_user(['firstname' => 'Pia', 'lastname' => 'Berger']);
        $teacher2 = $this->getDataGenerator()->create_user(['firstname' => 'Ida', 'lastname' => 'Fischer']);
        $booking = $this->getDataGenerator()->create_module('booking', [
            'name' => 'Test Booking',
            'eventtype' => 'Test event',
            'course' => $course->id,
            'bookingmanager' => $bookingmanager->username,
        ]);
        $this->setAdminUser();

        // The shortcodes show the options of the booking instances chosen in the settings of local_urise.
        set_config('multibookinginstances', $booking->id, 'local_urise');
        set_config('shortcodessetinstance', $booking->cmid, 'local_urise');

        // The customfields among the search columns of local_urise.
        $bookingcat = $this->getDataGenerator()->create_custom_field_category([
            'name' => 'BookCustomCat1',
            'component' => 'mod_booking',
            'area' => 'booking',
            'itemid' => 0,
            'contextid' => context_system::instance()->id,
        ]);
        $bookingcat->save();
        foreach (['organisation', 'botags', 'inhalte', 'zielgruppe'] as $shortname) {
            $bookingfield = $this->getDataGenerator()->create_custom_field([
                'categoryid' => $bookingcat->get('id'),
                'name' => $shortname,
                'shortname' => $shortname,
                'type' => 'text',
                'configdata' => '',
            ]);
            $bookingfield->save();
        }

        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');
        $options = [
            ['text' => 'Option one', 'teachersforoption' => $teacher1->username],
            ['text' => 'Option two', 'teachersforoption' => $teacher1->username . ',' . $teacher2->username],
            ['text' => 'Option three'],
        ];
        foreach ($options as $option) {
            $record = (object) $option;
            $record->bookingid = $booking->id;
            $plugingenerator->create_option($record);
        }

        $PAGE->set_context(context_course::instance($course->id));
        $PAGE->set_course($course);
        $PAGE->set_url(new \moodle_url('/local/urise/tests/shortcodes_test.php'));

        $env = new stdClass();
        $next = function () {
        };
        $args = ['all' => 1, 'search' => 1];

        [$table] = shortcodes::unifiedview('unifiedlist', $args, null, $env, $next, $template);
        $this->assertContains('teachernames', $table->fulltextsearchcolumns);
        $this->assertNotContains('teacherobjects', $table->fulltextsearchcolumns);
        $table->printtable(100, $table->useinitialsbar, $table->downloadhelpbutton);
        $this->assertEquals(3, $table->totalrows, 'Without a search, all options are listed.');

        $searches = [
            // The names are found, in every order of the words.
            'Berger' => 2,
            'pia' => 2,
            'Pia Berger' => 2,
            'Berger, Pia' => 2,
            'Fischer' => 1,
            'Ida Berger' => 1,
            // The ids of the users and the keys of the JSON are not.
            (string) $teacher1->id => 0,
            (string) $teacher2->id => 0,
            'firstname' => 0,
            'lastname' => 0,
            'name' => 0,
            '"id"' => 0,
        ];
        foreach ($searches as $searchtext => $numberofrecords) {
            // Every search needs a table of its own, a search changes the sql of the table.
            [$searchtable] = shortcodes::unifiedview('unifiedlist', $args, null, $env, $next, $template);
            $searchtable->apply_searchtext((string) $searchtext);
            $searchtable->printtable(100, $searchtable->useinitialsbar, $searchtable->downloadhelpbutton);
            $this->assertEquals(
                $numberofrecords,
                $searchtable->totalrows,
                "Unexpected number of records when searching for '$searchtext'"
            );
        }
    }

    /**
     * Data provider for test_search_finds_teachers_by_name_only.
     *
     * @return array
     */
    public static function template_provider(): array {
        return [
            '[unifiedlist]' => ['local_urise/table_list'],
            '[unifiedcards]' => ['local_urise/table_card'],
            '[unifiedtextlist]' => ['local_urise/table_listtext'],
        ];
    }
}
