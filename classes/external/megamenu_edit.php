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

namespace local_urise\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use local_urise\megamenu\editor;
use local_urise\megamenu\menu;

/**
 * Deletes or moves elements of the mega menu draft, publishes or discards it.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class megamenu_edit extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'action' => new external_value(PARAM_ALPHA, 'delete, move, publish or discard'),
            'revision' => new external_value(PARAM_INT, 'Draft revision the action is based on'),
            'path' => new external_value(PARAM_RAW, 'JSON path of the element', VALUE_DEFAULT, '[]'),
            'targetpath' => new external_value(PARAM_RAW, 'JSON path of the target list for move', VALUE_DEFAULT, '[]'),
            'position' => new external_value(PARAM_INT, 'Position in the target list for move', VALUE_DEFAULT, 0),
            'selectedtab' => new external_value(PARAM_ALPHANUMEXT, 'Tab to show afterwards', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Executes the action.
     *
     * @param string $action
     * @param int $revision
     * @param string $path
     * @param string $targetpath
     * @param int $position
     * @param string $selectedtab
     * @return array
     */
    public static function execute(
        string $action,
        int $revision,
        string $path = '[]',
        string $targetpath = '[]',
        int $position = 0,
        string $selectedtab = ''
    ): array {
        [
            'action' => $action,
            'revision' => $revision,
            'path' => $path,
            'targetpath' => $targetpath,
            'position' => $position,
            'selectedtab' => $selectedtab,
        ] = self::validate_parameters(self::execute_parameters(), [
            'action' => $action,
            'revision' => $revision,
            'path' => $path,
            'targetpath' => $targetpath,
            'position' => $position,
            'selectedtab' => $selectedtab,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/urise:canedit', $context);

        $path = self::decode_path($path);
        switch ($action) {
            case 'delete':
                menu::save(editor::delete(menu::get(true), $path), $revision, true);
                break;
            case 'move':
                $menu = editor::move(menu::get(true), $path, self::decode_path($targetpath), $position);
                menu::save($menu, $revision, true);
                break;
            case 'publish':
                menu::publish($revision);
                break;
            case 'discard':
                menu::discard_draft();
                break;
            default:
                throw new invalid_parameter_exception('Unknown action ' . $action);
        }

        return ['structure' => json_encode(editor::export_structure($selectedtab))];
    }

    /**
     * Decodes a JSON path.
     *
     * @param string $json
     * @return array
     */
    private static function decode_path(string $json): array {
        $path = json_decode($json, true);
        if (!is_array($path)) {
            throw new invalid_parameter_exception('Invalid path');
        }
        return array_values(array_map('strval', $path));
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'structure' => new external_value(PARAM_RAW, 'JSON of the editor structure'),
        ]);
    }
}
