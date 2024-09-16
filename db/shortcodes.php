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
 * Shortcodes for mod booking
 *
 * @package local_urise
 * @subpackage db
 * @since Moodle 3.11
 * @copyright 2024 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$shortcodes = [
    'unifiedlist' => [
        'callback' => 'local_urise\shortcodes::unifiedlist',
        'wraps' => false,
        'description' => 'shortcodes::unifiedlist',
    ],
    'unifiedcards' => [
        'callback' => 'local_urise\shortcodes::unifiedcards',
        'wraps' => false,
        'description' => 'shortcodes::unifiedlist',
    ],
    'unifiedmybookingslist' => [
        'callback' => 'local_urise\shortcodes::unifiedmybookingslist',
        'wraps' => false,
        'description' => 'shortcodes::unifiedlist',
    ],
    'unifiedtrainercourseslist' => [
        'callback' => 'local_urise\shortcodes::mytaughtcourses',
        'wraps' => false,
        'description' => 'shortcodeslistofmytaughtbookingoptionsascards',
    ],
    'calendarblock' => [
        'callback' => 'local_urise\shortcodes::calendarblock',
        'wraps' => false,
        'description' => 'shortcodes::calendarblock',
    ],
    'navbarhtml' => [
        'callback' => 'local_urise\shortcodes::navbarhtml',
        'wraps' => false,
        'description' => 'shortcodes::navbarhtml',
    ],
    'filterview' => [
        'callback' => 'local_urise\shortcodes::filterview',
        'wraps' => false,
        'description' => 'shortcodes::filterview',
    ],
];
