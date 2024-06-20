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
 * @package local_berta
 * @subpackage db
 * @since Moodle 3.11
 * @copyright 2024 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$shortcodes = [
    'unifiedlist' => [
        'callback' => 'local_berta\shortcodes::unifiedlist',
        'wraps' => false,
        'description' => 'shortcodes::unifiedlist',
    ],
    'unifiedcards' => [
        'callback' => 'local_berta\shortcodes::unifiedcards',
        'wraps' => false,
        'description' => 'shortcodes::unifiedlist',
    ],
    'unifiedmybookingslist' => [
        'callback' => 'local_berta\shortcodes::unifiedmybookingslist',
        'wraps' => false,
        'description' => 'shortcodes::unifiedlist',
    ],
];
