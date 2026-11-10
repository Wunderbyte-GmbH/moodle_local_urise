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

namespace local_urise\megamenu;

use core_customfield\field_controller;

/**
 * The filters of the catalogue that mega menu links can use, with readable names for their values.
 *
 * The values are what the booking option custom fields store: the 1-based line for select fields,
 * the id for dynamic fields. Filter links pass them to the catalogue as wbtfilter.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filteroptions {
    /** @var string[] Custom fields the catalogue (category 6) offers as filters. */
    public const COLUMNS = [
        'zgcommunities',
        'kompetenzen',
        'bibliothekszielgruppe',
        'organisation',
        'format',
        'kurssprache',
        'reihenprogramm',
    ];

    /** @var array|null Columns of this request. */
    private static ?array $columns = null;

    /**
     * Returns the filter columns with their options.
     *
     * @return array shortname => ['name' => string, 'options' => [value => label]]
     */
    public static function get_columns(): array {
        global $DB;

        if (self::$columns !== null) {
            return self::$columns;
        }

        [$insql, $params] = $DB->get_in_or_equal(self::COLUMNS, SQL_PARAMS_NAMED);
        $params['component'] = 'mod_booking';
        $params['area'] = 'booking';
        $records = $DB->get_records_sql(
            "SELECT f.*
               FROM {customfield_field} f
               JOIN {customfield_category} c ON c.id = f.categoryid
              WHERE c.component = :component AND c.area = :area AND f.shortname $insql",
            $params
        );

        $columns = [];
        foreach ($records as $record) {
            $field = field_controller::create(0, $record);
            $options = self::get_field_options($field);
            if (!empty($options)) {
                $name = trim(html_entity_decode(strip_tags(format_string($record->name)), ENT_QUOTES));
                $columns[$record->shortname] = ['name' => $name, 'options' => $options];
            }
        }

        // Keep the order of COLUMNS.
        self::$columns = array_filter(array_replace(array_fill_keys(self::COLUMNS, null), $columns));
        return self::$columns;
    }

    /**
     * Returns the options of a custom field.
     *
     * @param field_controller $field
     * @return array value => label
     */
    private static function get_field_options(field_controller $field): array {
        switch ($field->get('type')) {
            case 'select':
                $options = $field->get_options();
                break;
            case 'dynamicformat':
            case 'dynamic':
                $class = '\\customfield_' . $field->get('type') . '\\field_controller';
                $options = class_exists($class) ? $class::get_options_array($field) : [];
                break;
            default:
                $options = [];
        }

        $result = [];
        foreach ($options as $value => $label) {
            // Labels are plain text, templates and forms escape them.
            $label = trim(html_entity_decode(strip_tags($label), ENT_QUOTES));
            if ((string)$value !== '' && (string)$value !== '0' && $label !== '') {
                $result[(string)$value] = $label;
            }
        }
        return $result;
    }

    /**
     * Describes filters for editors, e.g. "Interessant für: PhD Students, PostDocs".
     *
     * @param array $filters column => values
     * @return string
     */
    public static function describe(array $filters): string {
        $columns = self::get_columns();
        $parts = [];
        foreach ($filters as $column => $values) {
            $name = $columns[$column]['name'] ?? $column;
            $labels = array_map(fn($value) => $columns[$column]['options'][$value] ?? $value, $values);
            $parts[] = $name . ': ' . implode(', ', $labels);
        }
        return implode('; ', $parts);
    }

    /**
     * Resets the columns of this request, for tests.
     */
    public static function reset(): void {
        self::$columns = null;
    }
}
