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

namespace local_urise\form;

use local_urise\megamenu\editor;
use local_urise\megamenu\filteroptions;
use local_urise\megamenu\menu;
use MoodleQuickForm;

/**
 * Edits a tab of the mega menu: an entry in the left column and its content.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class megamenu_tab_form extends megamenu_form {
    /**
     * Adds the fields.
     *
     * @param MoodleQuickForm $mform
     */
    protected function define_fields(MoodleQuickForm $mform): void {
        $types = [];
        foreach (menu::TABTYPES as $type) {
            $types[$type] = get_string('megamenu:tabtype_' . $type, 'local_urise');
        }
        $mform->addElement('select', 'type', get_string('megamenu:tabtype', 'local_urise'), $types);
        $mform->addHelpButton('type', 'megamenu:tabtype', 'local_urise');

        $this->add_texts($mform, 'label', get_string('megamenu:label', 'local_urise'));

        $colors = [];
        foreach (array_keys(menu::COLORS) as $color) {
            $colors[$color] = get_string('megamenu:color_' . $color, 'local_urise');
        }
        $mform->addElement('select', 'color', get_string('megamenu:color', 'local_urise'), $colors);
        $mform->addHelpButton('color', 'megamenu:color', 'local_urise');

        $icons = ['' => get_string('none')];
        foreach (menu::ICONS as $icon) {
            $icons[$icon] = get_string('megamenu:icon_' . $icon, 'local_urise');
        }
        $mform->addElement('select', 'icon', get_string('megamenu:icon', 'local_urise'), $icons);
        $mform->addHelpButton('icon', 'megamenu:icon', 'local_urise');

        $this->add_texts($mform, 'heading', get_string('megamenu:heading', 'local_urise'));
        $this->add_texts($mform, 'intro', get_string('megamenu:intro', 'local_urise'), 'textarea');
        $this->add_texts($mform, 'hint', get_string('megamenu:hint', 'local_urise'), 'textarea');
        foreach (array_keys($this->get_languages()) as $lang) {
            foreach (['heading', 'intro'] as $name) {
                $mform->hideIf("{$name}_{$lang}", 'type', 'eq', 'link');
            }
            $mform->hideIf("hint_{$lang}", 'type', 'neq', 'overview');
        }

        $mform->addElement(
            'autocomplete',
            'newsid',
            get_string('megamenu:news', 'local_urise'),
            [0 => get_string('none')] + $this->get_news_options()
        );
        $mform->addHelpButton('newsid', 'megamenu:news', 'local_urise');
        $mform->hideIf('newsid', 'type', 'eq', 'link');

        $this->add_texts($mform, 'kicker', get_string('megamenu:kicker', 'local_urise'));
        foreach (array_keys($this->get_languages()) as $lang) {
            $mform->hideIf("kicker_{$lang}", 'type', 'eq', 'link');
            $mform->hideIf("kicker_{$lang}", 'newsid', 'eq', 0);
        }

        $columns = filteroptions::get_columns();
        if (empty($columns)) {
            return;
        }
        $mform->addElement('advcheckbox', 'basefilterenabled', get_string('megamenu:basefilter', 'local_urise'));
        $mform->addHelpButton('basefilterenabled', 'megamenu:basefilter', 'local_urise');
        $mform->addElement(
            'select',
            'basefiltercolumn',
            get_string('megamenu:basefiltercolumn', 'local_urise'),
            array_map(fn($column) => $column['name'], $columns)
        );
        $mform->hideIf('basefiltercolumn', 'basefilterenabled');
        foreach ($columns as $shortname => $column) {
            $mform->addElement(
                'autocomplete',
                "basefilter_{$shortname}",
                get_string('megamenu:filtervalues', 'local_urise'),
                filteroptions::get_form_options($column),
                ['multiple' => true]
            );
            $mform->hideIf("basefilter_{$shortname}", 'basefilterenabled');
            $mform->hideIf("basefilter_{$shortname}", 'basefiltercolumn', 'neq', $shortname);
        }
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = $this->validate_texts_required($data, 'label', parent::validation($data, $files));
        $column = $data['basefiltercolumn'] ?? '';
        if (!empty($data['basefilterenabled']) && empty($data["basefilter_{$column}"])) {
            $errors["basefilter_{$column}"] = get_string('megamenu:filterrequired', 'local_urise');
        }
        return $errors;
    }

    /**
     * Turns a tab into form data.
     *
     * @param array $item
     * @return array
     */
    protected function to_form(array $item): array {
        $filters = $item['basefilter']['filters'] ?? [];
        $base = [
            'basefilterenabled' => empty($item['basefilter']['enabled']) ? 0 : 1,
            'basefiltercolumn' => array_key_first($filters) ?? array_key_first(filteroptions::get_columns()),
        ];
        foreach ($filters as $column => $values) {
            $base["basefilter_{$column}"] = $values;
        }
        return array_merge(
            [
                'type' => $item['type'] ?? 'panel',
                'color' => $item['color'] ?? 'blue',
                'icon' => $item['icon'] ?? '',
                'newsid' => $item['news']['id'] ?? 0,
            ],
            $base,
            $this->texts_to_form($item['label'] ?? [], 'label'),
            $this->texts_to_form($item['heading'] ?? [], 'heading'),
            $this->texts_to_form($item['intro'] ?? [], 'intro'),
            $this->texts_to_form($item['hint'] ?? [], 'hint'),
            $this->texts_to_form($item['news']['kicker'] ?? [], 'kicker')
        );
    }

    /**
     * Turns form data into a tab. Blocks, links and the CTA are kept by the upsert.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function from_form(\stdClass $data): array {
        $newsid = (int)($data->newsid ?? 0);
        $item = [];
        if (!empty($data->basefilterenabled)) {
            $column = (string)($data->basefiltercolumn ?? '');
            $item['basefilter'] = [
                'enabled' => true,
                'filters' => [$column => filteroptions::expand_groups($column, (array)($data->{"basefilter_{$column}"} ?? []))],
            ];
        }
        return $item + [
            'type' => $data->type,
            'label' => $this->texts_from_form($data, 'label'),
            'color' => $data->color ?? 'blue',
            'icon' => $data->icon ?? '',
            'heading' => $this->texts_from_form($data, 'heading'),
            'intro' => $this->texts_from_form($data, 'intro'),
            'hint' => $this->texts_from_form($data, 'hint'),
            'news' => $newsid ? ['id' => $newsid, 'kicker' => $this->texts_from_form($data, 'kicker')] : null,
        ];
    }

    /**
     * Applies the tab. A base filter that is switched off keeps its values.
     *
     * @param array $menu
     * @param array $path
     * @param array $item
     * @return array
     */
    protected function apply(array $menu, array $path, array $item): array {
        if (!isset($item['basefilter'])) {
            $existing = $this->is_new($path) ? [] : (editor::get($menu, $path) ?? []);
            $item['basefilter'] = ['enabled' => false, 'filters' => $existing['basefilter']['filters'] ?? []];
        }
        return parent::apply($menu, $path, $item);
    }

    /**
     * Returns the news items editors can choose from.
     *
     * @return array id => "Headline (News instance)"
     */
    private function get_news_options(): array {
        global $DB;

        $records = $DB->get_records_sql(
            "SELECT n.id, n.headline, i.name
               FROM {local_wb_news} n
               JOIN {local_wb_news_instance} i ON i.id = n.instanceid
              WHERE n.instanceid > 0
           ORDER BY i.name, n.sortorder"
        );
        $options = [];
        foreach ($records as $record) {
            $headline = trim(strip_tags(format_string($record->headline)));
            $options[$record->id] = ($headline === '' ? '#' . $record->id : $headline)
                . ' (' . trim(strip_tags(format_string($record->name))) . ')';
        }
        return $options;
    }
}
