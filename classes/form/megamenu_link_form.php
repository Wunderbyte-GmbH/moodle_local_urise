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
use local_urise\megamenu\filteroptions;
use local_urise\megamenu\menu;
use MoodleQuickForm;

/**
 * Edits a link of the mega menu: a topic link, a chip, a CTA, a secondary link or the target of a tab.
 *
 * Filter links show the catalogue filtered by the chosen values, search links show the search results.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class megamenu_link_form extends megamenu_form {
    /**
     * Adds the fields.
     *
     * @param MoodleQuickForm $mform
     */
    protected function define_fields(MoodleQuickForm $mform): void {
        $this->add_texts($mform, 'label', get_string('megamenu:label', 'local_urise'));

        $types = [];
        foreach (['filter', 'search', 'url'] as $type) {
            $types[$type] = get_string('megamenu:linktype_' . $type, 'local_urise');
        }
        $mform->addElement('select', 'type', get_string('megamenu:linktype', 'local_urise'), $types);
        $mform->addHelpButton('type', 'megamenu:linktype', 'local_urise');

        $columns = filteroptions::get_columns();
        $mform->addElement(
            'select',
            'filtercolumn',
            get_string('megamenu:filtercolumn', 'local_urise'),
            array_map(fn($column) => $column['name'], $columns)
        );
        $mform->hideIf('filtercolumn', 'type', 'neq', 'filter');
        foreach ($columns as $shortname => $column) {
            $mform->addElement(
                'autocomplete',
                "filter_{$shortname}",
                get_string('megamenu:filtervalues', 'local_urise'),
                $column['options'],
                ['multiple' => true]
            );
            $mform->hideIf("filter_{$shortname}", 'type', 'neq', 'filter');
            $mform->hideIf("filter_{$shortname}", 'filtercolumn', 'neq', $shortname);
        }

        $mform->addElement('text', 'search', get_string('megamenu:search', 'local_urise'), ['size' => 60]);
        $mform->setType('search', PARAM_TEXT);
        $mform->addHelpButton('search', 'megamenu:search', 'local_urise');
        $mform->hideIf('search', 'type', 'neq', 'search');

        $mform->addElement('text', 'url', get_string('megamenu:url', 'local_urise'), ['size' => 60]);
        $mform->setType('url', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('url', 'megamenu:url', 'local_urise');
        $mform->hideIf('url', 'type', 'neq', 'url');
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

        switch ($data['type']) {
            case 'filter':
                $column = $data['filtercolumn'] ?? '';
                if (empty($data["filter_{$column}"])) {
                    $errors["filter_{$column}"] = get_string('megamenu:filterrequired', 'local_urise');
                }
                break;
            case 'search':
                if (trim($data['search'] ?? '') === '') {
                    $errors['search'] = get_string('required');
                }
                break;
            default:
                $url = trim($data['url'] ?? '');
                $link = menu::normalise(['tabs' => [['cta' => ['type' => 'url', 'url' => $url]]]])['tabs'][0]['cta'];
                if ($link['url'] === '') {
                    $errors['url'] = get_string('megamenu:urlinvalid', 'local_urise');
                }
        }
        return $errors;
    }

    /**
     * Turns a link into form data.
     *
     * @param array $item
     * @return array
     */
    protected function to_form(array $item): array {
        $filters = $item['filters'] ?? [];
        $data = [
            'type' => $item['type'] ?? 'filter',
            'url' => $item['url'] ?? '',
            'search' => $item['search'] ?? '',
            'filtercolumn' => array_key_first($filters) ?? array_key_first(filteroptions::get_columns()),
        ];
        foreach ($filters as $column => $values) {
            $data["filter_{$column}"] = $values;
        }
        return array_merge($data, $this->texts_to_form($item['label'] ?? [], 'label'));
    }

    /**
     * Turns form data into a link.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function from_form(\stdClass $data): array {
        $column = $data->filtercolumn ?? '';
        $filters = [];
        if ($data->type === 'filter' && !empty($data->{"filter_{$column}"})) {
            $filters[$column] = array_values((array)$data->{"filter_{$column}"});
        }
        return [
            'type' => $data->type,
            'label' => $this->texts_from_form($data, 'label'),
            'url' => $data->type === 'url' ? trim($data->url) : '',
            'search' => $data->type === 'search' ? trim($data->search) : '',
            'filters' => $filters,
        ];
    }
}
