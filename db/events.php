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
 * Event observers.
 *
 * @package local_urise
 * @category event
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$pluginname = "local_urise";
$paymentplugins = [
    "paygw_payunity",
    "paygw_mpay24",
    "paygw_unigraz",
    "paygw_payone",
];
$events = [
    "payment_added",
    "payment_completed",
    "payment_successful",
];

$observers = [];
foreach ($paymentplugins as $paymentplugin) {
    foreach ($events as $event) {
        $observers[] = [
            'eventname' => "\\{$paymentplugin}\\event\\{$event}",
            'callback' => "\\{$pluginname}\\observer::{$event}",
        ];
    }
}

$observers[] = [
    'eventname' => '\core\event\user_created',
    'callback' => "\local_urise\observer::user_created",
];

$observers[] = [
    'eventname' => '\local_wunderbyte_table\event\template_switched',
    'callback' => "\local_urise\observer::template_switched",
];
