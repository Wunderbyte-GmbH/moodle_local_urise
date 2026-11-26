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
use cache_helper;
use context_course;
use local_wunderbyte_table\external\execute_action;
use local_wunderbyte_table\external\load_data;
use mod_booking\singleton_service;
use mod_booking_generator;
use stdClass;
use tool_mocktesttime\time_mock;

/**
 * Tests of the template switcher of the course shortcodes of local_urise.
 *
 * The definition of the course table is cached once and shared by everybody who loads the table.
 * The template a user chooses (cards, list, text list) and the arrangement of the columns that
 * belongs to it apply to this user only.
 *
 * @package local_urise
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_urise\shortcodes::unifiedview
 * @covers \local_urise\table\urise_table::apply_template_layout
 * @covers \local_urise\observer::template_switched
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class template_choice_test extends advanced_testcase {
    /** @var string The cards template, default of the shortcode in these tests. */
    private const CARDS = 'local_urise/table_card';

    /** @var string The list template. */
    private const LISTTEMPLATE = 'local_urise/table_list';

    /** @var stdClass First user. */
    private stdClass $usera;

    /** @var stdClass Second user. */
    private stdClass $userb;

    /**
     * Tests set up: a booking instance with options and two users who can see them.
     */
    public function setUp(): void {
        global $PAGE;

        parent::setUp();
        $this->resetAfterTest(true);
        time_mock::init();
        time_mock::set_mock_time(strtotime('now'));
        singleton_service::destroy_instance();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $bookingmanager = $this->getDataGenerator()->create_user();
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

        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');
        foreach (['Option one', 'Option two', 'Option three'] as $text) {
            $plugingenerator->create_option((object) ['text' => $text, 'bookingid' => $booking->id]);
        }

        // Two users who are allowed to see everything, so the tests are about the display only.
        $this->usera = $this->getDataGenerator()->create_user();
        $this->userb = $this->getDataGenerator()->create_user();
        set_config('siteadmins', implode(',', [get_admin()->id, $this->usera->id, $this->userb->id]));

        $PAGE->set_context(context_course::instance($course->id));
        $PAGE->set_course($course);
        $PAGE->set_url(new \moodle_url('/local/urise/tests/template_choice_test.php'));
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        cache_helper::purge_by_event('changesinwunderbytetable');
        $_POST = [];
        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_booking');
        $plugingenerator->teardown();
    }

    /**
     * Render the cards shortcode with the template switcher as the current user, like a page request does.
     *
     * @return string the hash of the cached table definition
     */
    private function render_page(): string {
        $env = new stdClass();
        $next = function () {
        };
        $args = ['all' => 1, 'switchtemplates' => 1];
        [$table] = shortcodes::unifiedview('unifiedcards', $args, null, $env, $next, self::CARDS);
        $table->printtable(100, $table->useinitialsbar, $table->downloadhelpbutton);
        return $table->tablecachehash;
    }

    /**
     * Load the table through the data webservice as the current user, like filtering or paging does.
     *
     * @param string $hash
     * @return array with the keys template and row (the first row)
     */
    private function load(string $hash): array {
        $result = load_data::execute($hash, 0, '', '', '', 0, 0, '', '');
        $content = json_decode($result['content'], true);
        return [
            'template' => $result['template'],
            'row' => $content['table']['rows'][0] ?? [],
        ];
    }

    /**
     * Assert that a loaded table is the cards view with the columns arranged for cards.
     *
     * @param array $loaded
     * @return void
     */
    private function assert_cards(array $loaded): void {
        $this->assertSame(self::CARDS, $loaded['template']);
        $this->assertArrayHasKey('cardbody', $loaded['row']);
        $this->assertArrayNotHasKey('leftside', $loaded['row']);
    }

    /**
     * Assert that a loaded table is the list view with the columns arranged for the list.
     *
     * @param array $loaded
     * @return void
     */
    private function assert_list(array $loaded): void {
        $this->assertSame(self::LISTTEMPLATE, $loaded['template']);
        $this->assertArrayHasKey('leftside', $loaded['row']);
    }

    /**
     * A user who switches to the list gets the list, everybody else keeps the cards of the shortcode.
     */
    public function test_switching_the_template_only_changes_the_table_of_this_user(): void {
        $this->setUser($this->userb);
        $hash = $this->render_page();
        $this->assert_cards($this->load($hash));

        $this->setUser($this->usera);
        $this->assertSame($hash, $this->render_page(), 'Both users share one cached table definition.');
        $result = execute_action::execute(
            'switchtemplates',
            $hash,
            0,
            json_encode(['selectedValue' => self::LISTTEMPLATE . ' 0'])
        );
        $this->assertSame(1, $result['success']);
        $this->assert_list($this->load($hash));

        // The other user still gets the cards, with the columns arranged for cards.
        $this->setUser($this->userb);
        $this->assert_cards($this->load($hash));

        // And the first user keeps the list.
        $this->setUser($this->usera);
        $this->assert_list($this->load($hash));
    }

    /**
     * The table definition that gets cached does not depend on who opens the page first:
     * a user who prefers the list does not make the list the template of everybody else.
     */
    public function test_first_visitor_with_a_preference_does_not_define_the_table_of_others(): void {
        $this->setUser($this->usera);
        set_user_preference('wbtable_chosen_template_' . $this->return_uniqueid(), self::LISTTEMPLATE);
        set_user_preference('wbtable_chosen_template_viewparam_' . $this->return_uniqueid(), 0);
        cache_helper::purge_by_event('setbackencodedtables');

        // The user with the list preference is the first one to open the page.
        $hash = $this->render_page();
        $this->assert_list($this->load($hash));

        $this->setUser($this->userb);
        $this->assertSame($hash, $this->render_page(), 'Both users share one cached table definition.');
        $this->assert_cards($this->load($hash));
    }

    /**
     * The unique id of the table of the shortcode on the current page.
     *
     * @return string
     */
    private function return_uniqueid(): string {
        $env = new stdClass();
        $next = function () {
        };
        [$table] = shortcodes::unifiedview('unifiedcards', ['all' => 1, 'switchtemplates' => 1], null, $env, $next, self::CARDS);
        return $table->uniqueid;
    }
}
