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

use context_coursecat;
use core_customfield\field_controller;
use local_urise\shortcodes;
use local_wunderbyte_table\filters\types\customfieldfilter;
use mod_booking\customfield\booking_handler;
use moodle_url;

/**
 * The filters of the catalogue that mega menu links can use, with readable names for their values.
 *
 * The filters are the custom field filters the shortcode on the catalogue page defines. Their values
 * are what the booking options store, filter links pass them to the catalogue as wbtfilter. A field
 * with a hierarchy stored by mod_booking takes its options from there.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filteroptions {
    /** @var string[] Keys of a filter definition that are settings, not options. */
    private const SETTINGS = [
        'localizedname',
        'wbfilterclass',
        'wbbypasscache',
        'showalloptions',
        'explode',
        'jsonattribute',
        'json',
    ];

    /** @var string Prefix of the form value that stands for all options of a group. */
    private const GROUPPREFIX = 'group:';

    /** @var array|null Columns of this request. */
    private static ?array $columns = null;

    /**
     * Returns the filter columns with their options.
     *
     * @return array shortname => ['name' => string, 'options' => [value => label], 'groups' => [label => values],
     *      'source' => string]
     */
    public static function get_columns(): array {
        if (self::$columns !== null) {
            return self::$columns;
        }

        $fields = [];
        foreach (booking_handler::get_customfields() as $field) {
            $fields[$field->shortname] = $field;
        }

        // The catalogue expects the field of every filter it defines, so only ask for filters of existing fields.
        $args = self::get_shortcode_args(menu::get(true)['catalogueurl']);
        $wanted = array_keys($fields);
        if (!empty($args['onlyfilterforcolumns'])) {
            $wanted = array_intersect(array_map('trim', explode(',', $args['onlyfilterforcolumns'])), $wanted);
        }
        $args['onlyfilterforcolumns'] = implode(',', $wanted) ?: '-';

        $columns = [];
        foreach (shortcodes::get_filter_definitions($args) as $shortname => $definition) {
            if (!isset($fields[$shortname]) || !is_a($definition['wbfilterclass'] ?? '', customfieldfilter::class, true)) {
                continue;
            }
            $name = trim(html_entity_decode(strip_tags((string)($definition['localizedname'] ?? $shortname)), ENT_QUOTES));
            $source = 'catalogue';
            $hierarchy = self::get_stored_hierarchy($fields[$shortname]);
            if ($hierarchy !== null) {
                $source = 'hierarchy';
                $definition = $hierarchy;
            }
            [$options, $groups] = self::parse_definition($definition, $shortname);
            if (!empty($options)) {
                $columns[$shortname] = ['name' => $name, 'options' => $options, 'groups' => $groups, 'source' => $source];
            }
        }

        self::$columns = $columns;
        return self::$columns;
    }

    /**
     * Returns the arguments of the shortcode that shows the catalogue with its filters.
     *
     * @param string $catalogueurl
     * @return array Empty if the page is no category page or has no such shortcode.
     */
    private static function get_shortcode_args(string $catalogueurl): array {
        global $DB;

        $categoryid = (int)(new moodle_url($catalogueurl))->param('categoryid');
        $context = $categoryid ? context_coursecat::instance($categoryid, IGNORE_MISSING) : null;
        if (!$context) {
            return [];
        }

        $blocks = $DB->get_records('block_instances', ['blockname' => 'html', 'parentcontextid' => $context->id], 'id');
        foreach ($blocks as $block) {
            $config = unserialize_object(base64_decode($block->configdata));
            preg_match_all('/\[unified\w*\s([^\]]*)\]/', $config->text ?? '', $shortcodes);
            foreach ($shortcodes[1] as $shortcode) {
                preg_match_all('/(\w+)=(?:"([^"]*)"|(\S+))/', $shortcode, $matches, PREG_SET_ORDER);
                $args = [];
                foreach ($matches as $match) {
                    $args[$match[1]] = $match[3] ?? $match[2];
                }
                if (!empty($args['filter'])) {
                    return $args;
                }
            }
        }
        return [];
    }

    /**
     * Returns the options of a field whose hierarchy mod_booking stores, in the format of a filter definition.
     *
     * @param \stdClass $field Record of the custom field.
     * @return array|null Null if the field has no stored hierarchy.
     */
    private static function get_stored_hierarchy(\stdClass $field): ?array {
        $manager = '\\mod_booking\\customfield\\hierarchy_manager';
        if ($field->type !== 'dynamicformat' || !class_exists($manager)) {
            return null;
        }
        $controller = field_controller::create($field->id);
        if (empty($controller->get_configdata_property($manager::CONFIGKEY_HIERARCHY))) {
            return null;
        }
        // A field without groups has no filter options there, its own options are the stored rows.
        $options = $manager::get_filter_options($field->id);
        if (empty($options)) {
            $options = \customfield_dynamicformat\field_controller::get_options_array($controller);
            unset($options['']);
        }
        return $options;
    }

    /**
     * Splits a filter definition into the options and the groups they belong to.
     *
     * @param array $definition
     * @param string $shortname
     * @return array [[value => label], [group label => values]]
     */
    private static function parse_definition(array $definition, string $shortname): array {
        $options = [];
        $groups = [];
        foreach ($definition as $value => $option) {
            $value = (string)$value;
            if (in_array($value, self::SETTINGS, true) || $value === "{$shortname}_wb_checked") {
                continue;
            }
            $label = is_array($option) ? ($option['localizedname'] ?? '') : $option;
            // Labels are plain text, templates and forms escape them.
            $label = trim(html_entity_decode(strip_tags((string)$label), ENT_QUOTES));
            if ($value === '' || $value === '0' || $label === '' || $label === 'wbt_suppress') {
                continue;
            }
            $options[$value] = $label;
            if (is_array($option) && ($option['parent'] ?? '') !== '') {
                $groups[trim(html_entity_decode(strip_tags($option['parent']), ENT_QUOTES))][] = $value;
            }
        }
        return [$options, $groups];
    }

    /**
     * Returns the choices of a column for a form: every group as a whole, then the single options.
     *
     * @param array $column
     * @return array value => label
     */
    public static function get_form_options(array $column): array {
        $choices = [];
        foreach (array_keys($column['groups']) as $index => $group) {
            $choices[self::GROUPPREFIX . $index] = get_string('megamenu:wholegroup', 'local_urise', $group);
        }
        return $choices + $column['options'];
    }

    /**
     * Replaces the groups chosen in a form with their options.
     *
     * @param string $column
     * @param array $values
     * @return string[]
     */
    public static function expand_groups(string $column, array $values): array {
        $groups = array_values(self::get_columns()[$column]['groups'] ?? []);
        $result = [];
        foreach ($values as $value) {
            $value = (string)$value;
            if (strpos($value, self::GROUPPREFIX) === 0) {
                $result = array_merge($result, $groups[(int)substr($value, strlen(self::GROUPPREFIX))] ?? []);
            } else {
                $result[] = $value;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * Returns the columns whose options can be loaded as links. Columns with groups cannot yet.
     *
     * @return array shortname => ['name' => string, 'options' => [value => label]]
     */
    public static function get_loadable_columns(): array {
        return array_filter(self::get_columns(), fn($column) => empty($column['groups']));
    }

    /**
     * Builds one filter link per option of a column, labelled in every installed language.
     *
     * @param string $column
     * @param array|null $values The options to use, or null for all of them.
     * @return array Links without ids.
     */
    public static function build_links(string $column, ?array $values = null): array {
        if (!array_key_exists($column, self::get_loadable_columns())) {
            return [];
        }

        $labels = [];
        $previous = force_current_language(current_language());
        foreach (array_keys(get_string_manager()->get_list_of_translations()) as $lang) {
            force_current_language($lang);
            self::reset();
            foreach (self::get_columns()[$column]['options'] ?? [] as $value => $label) {
                $labels[(string)$value][$lang] = $label;
            }
        }
        force_current_language($previous);
        self::reset();

        $links = [];
        foreach ($labels as $value => $label) {
            if ($values !== null && !in_array((string)$value, array_map('strval', $values), true)) {
                continue;
            }
            $links[] = [
                'type' => 'filter',
                'label' => $label,
                'url' => '',
                'filters' => [$column => [(string)$value]],
                'search' => '',
            ];
        }
        return $links;
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
