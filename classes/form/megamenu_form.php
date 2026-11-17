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

use context;
use context_system;
use core_form\dynamic_form;
use local_urise\megamenu\editor;
use local_urise\megamenu\filteroptions;
use local_urise\megamenu\menu;
use moodle_url;
use MoodleQuickForm;

/**
 * Base of the modal forms that edit an element of the mega menu draft.
 *
 * The form gets the path of the element (to edit it), of a list (to add an element) or of a slot,
 * and the draft revision it was opened on. It returns the new editor structure.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class megamenu_form extends dynamic_form {
    /**
     * Adds the fields of the element.
     *
     * @param MoodleQuickForm $mform
     */
    abstract protected function define_fields(MoodleQuickForm $mform): void;

    /**
     * Turns an element into form data.
     *
     * @param array $item The element, or an empty array for a new one.
     * @return array
     */
    abstract protected function to_form(array $item): array;

    /**
     * Turns the submitted form data into an element.
     *
     * @param \stdClass $data
     * @return array
     */
    abstract protected function from_form(\stdClass $data): array;

    /**
     * Form definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'path');
        $mform->setType('path', PARAM_RAW);
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $this->define_fields($mform);
    }

    /**
     * Returns the context of the form.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Checks the permission to edit the mega menu.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('local/urise:canedit', context_system::instance());
    }

    /**
     * Returns the page of the form.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/urise/megamenu.php');
    }

    /**
     * Loads the element into the form.
     */
    public function set_data_for_dynamic_submission(): void {
        $path = $this->get_path();
        $menu = menu::get(true);
        $item = $this->is_new($path) ? [] : (editor::get($menu, $path) ?? []);

        $this->set_data(array_merge($this->to_form($item), [
            'path' => json_encode($path),
            'revision' => $this->optional_int('revision', $menu['revision']),
        ]));
    }

    /**
     * Saves the element to the draft.
     *
     * @return array The new editor structure.
     */
    public function process_dynamic_submission(): array {
        $data = $this->get_data();
        $path = json_decode($data->path, true);
        $item = $this->from_form($data);

        $menu = $this->apply(menu::get(true), $path, $item);
        $saved = menu::save($menu, (int)$data->revision, true);

        // Show the tab that was edited, or the new one.
        $lasttab = end($saved['tabs']);
        return editor::export_structure((string)($path[1] ?? ($lasttab['id'] ?? '')));
    }

    /**
     * Applies the element to the menu.
     *
     * @param array $menu
     * @param array $path
     * @param array $item
     * @return array
     */
    protected function apply(array $menu, array $path, array $item): array {
        $last = end($path);
        if (in_array($last, editor::SLOTS, true)) {
            return editor::set_slot($menu, $path, $item);
        }
        if (in_array($last, editor::LISTS, true)) {
            unset($item['id']);
            return editor::upsert($menu, $path, $item);
        }
        $item['id'] = (string)array_pop($path);
        return editor::upsert($menu, $path, $item);
    }

    /**
     * Adds the fields to load the options of a catalogue filter as links.
     *
     * @param MoodleQuickForm $mform
     */
    protected function add_filter_loader(MoodleQuickForm $mform): void {
        $columns = filteroptions::get_loadable_columns();
        if (empty($columns)) {
            return;
        }
        $names = ['' => get_string('megamenu:loadnone', 'local_urise')] + array_map(fn($column) => $column['name'], $columns);
        $mform->addElement('select', 'loadcolumn', get_string('megamenu:loadcolumn', 'local_urise'), $names);
        $mform->addHelpButton('loadcolumn', 'megamenu:loadcolumn', 'local_urise');

        $mform->addElement('advcheckbox', 'loadall', get_string('megamenu:loadall', 'local_urise'));
        $mform->setDefault('loadall', 1);
        $mform->hideIf('loadall', 'loadcolumn', 'eq', '');

        foreach ($columns as $shortname => $column) {
            $mform->addElement(
                'autocomplete',
                "load_{$shortname}",
                get_string('megamenu:loadvalues', 'local_urise'),
                $column['options'],
                ['multiple' => true]
            );
            $mform->hideIf("load_{$shortname}", 'loadcolumn', 'neq', $shortname);
            $mform->hideIf("load_{$shortname}", 'loadall', 'checked');
        }
    }

    /**
     * Validates the fields of the filter loader.
     *
     * @param array $data
     * @param array $errors
     * @return array
     */
    protected function validate_filter_loader(array $data, array $errors): array {
        $column = $data['loadcolumn'] ?? '';
        if ($column !== '' && empty($data['loadall']) && empty($data["load_{$column}"])) {
            $errors["load_{$column}"] = get_string('megamenu:filterrequired', 'local_urise');
        }
        return $errors;
    }

    /**
     * Returns the links chosen in the filter loader.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function links_from_filter_loader(\stdClass $data): array {
        $column = (string)($data->loadcolumn ?? '');
        if ($column === '') {
            return [];
        }
        $values = empty($data->loadall) ? array_values((array)($data->{"load_{$column}"} ?? [])) : null;
        return filteroptions::build_links($column, $values);
    }

    /**
     * Adds loaded links to the links an element already has. A filter that is linked already is skipped.
     *
     * @param array $menu
     * @param array $path Path the form was opened with.
     * @param array $item
     * @param array $loaded
     * @return array The element.
     */
    protected function append_loaded_links(array $menu, array $path, array $item, array $loaded): array {
        if (empty($loaded)) {
            return $item;
        }
        $links = $this->is_new($path) ? [] : (editor::get($menu, $path)['links'] ?? []);
        $known = array_map(fn($link) => json_encode($link['filters'] ?? []), $links);
        foreach ($loaded as $link) {
            if (!in_array(json_encode($link['filters']), $known, true)) {
                $link['id'] = editor::new_id();
                $links[] = $link;
            }
        }
        $item['links'] = $links;
        return $item;
    }

    /**
     * Whether the path points to a list, so a new element is added.
     *
     * @param array $path
     * @return bool
     */
    protected function is_new(array $path): bool {
        return in_array(end($path), editor::LISTS, true);
    }

    /**
     * Returns the path the form was opened with.
     *
     * @return array
     */
    protected function get_path(): array {
        $path = json_decode((string)($this->_ajaxformdata['path'] ?? '[]'), true);
        return is_array($path) ? array_values(array_map('strval', $path)) : [];
    }

    /**
     * Returns an int argument the form was opened with.
     *
     * @param string $name
     * @param int $default
     * @return int
     */
    protected function optional_int(string $name, int $default): int {
        return isset($this->_ajaxformdata[$name]) ? (int)$this->_ajaxformdata[$name] : $default;
    }

    /**
     * Returns the installed languages.
     *
     * @return array code => name
     */
    protected function get_languages(): array {
        return get_string_manager()->get_list_of_translations();
    }

    /**
     * Adds one field per language for a text.
     *
     * @param MoodleQuickForm $mform
     * @param string $name
     * @param string $label
     * @param string $type text or textarea
     */
    protected function add_texts(MoodleQuickForm $mform, string $name, string $label, string $type = 'text'): void {
        foreach ($this->get_languages() as $lang => $langname) {
            $attributes = $type === 'textarea' ? ['rows' => 3, 'cols' => 60] : ['size' => 60];
            $mform->addElement($type, "{$name}_{$lang}", "$label ($langname)", $attributes);
            $mform->setType("{$name}_{$lang}", PARAM_TEXT);
        }
    }

    /**
     * Turns texts into form data.
     *
     * @param array $texts
     * @param string $name
     * @return array
     */
    protected function texts_to_form(array $texts, string $name): array {
        $data = [];
        foreach (array_keys($this->get_languages()) as $lang) {
            $data["{$name}_{$lang}"] = $texts[$lang] ?? '';
        }
        return $data;
    }

    /**
     * Turns form data into texts.
     *
     * @param \stdClass|array $data
     * @param string $name
     * @return array
     */
    protected function texts_from_form($data, string $name): array {
        $data = (array)$data;
        $texts = [];
        foreach (array_keys($this->get_languages()) as $lang) {
            $texts[$lang] = trim((string)($data["{$name}_{$lang}"] ?? ''));
        }
        return array_filter($texts, fn($text) => $text !== '');
    }

    /**
     * Requires the text in at least one language.
     *
     * @param array $data
     * @param string $name
     * @param array $errors
     * @return array
     */
    protected function validate_texts_required(array $data, string $name, array $errors): array {
        if (empty($this->texts_from_form($data, $name))) {
            $first = array_key_first($this->get_languages());
            $errors["{$name}_{$first}"] = get_string('megamenu:textrequired', 'local_urise');
        }
        return $errors;
    }
}
