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

use local_urise\megamenu\menu;
use MoodleQuickForm;

/**
 * Edits a block in the middle column of a mega menu tab.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class megamenu_block_form extends megamenu_form {
    /**
     * Adds the fields.
     *
     * @param MoodleQuickForm $mform
     */
    protected function define_fields(MoodleQuickForm $mform): void {
        $types = [];
        foreach (menu::BLOCKTYPES as $type) {
            $types[$type] = get_string('megamenu:blocktype_' . $type, 'local_urise');
        }
        $mform->addElement('select', 'type', get_string('megamenu:blocktype', 'local_urise'), $types);
        $mform->addHelpButton('type', 'megamenu:blocktype', 'local_urise');

        $this->add_texts($mform, 'title', get_string('megamenu:title', 'local_urise'));
        foreach (array_keys($this->get_languages()) as $lang) {
            $mform->hideIf("title_{$lang}", 'type', 'eq', 'cardrow');
        }

        $mform->addElement('advcheckbox', 'boxed', get_string('megamenu:boxed', 'local_urise'));
        $mform->addHelpButton('boxed', 'megamenu:boxed', 'local_urise');
        $mform->hideIf('boxed', 'type', 'neq', 'linkgrid');
    }

    /**
     * Turns a block into form data.
     *
     * @param array $item
     * @return array
     */
    protected function to_form(array $item): array {
        // A new block gets the type chosen in the component picker of the editor.
        $type = $item['type'] ?? clean_param($this->_ajaxformdata['type'] ?? 'linkgrid', PARAM_ALPHA);
        $boxed = isset($item['type']) ? !empty($item['boxed']) : !empty($this->_ajaxformdata['boxed']);
        return array_merge(
            ['type' => in_array($type, menu::BLOCKTYPES, true) ? $type : 'linkgrid', 'boxed' => (int)$boxed],
            $this->texts_to_form($item['title'] ?? [], 'title')
        );
    }

    /**
     * Turns form data into a block. Cards and links are kept by the upsert.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function from_form(\stdClass $data): array {
        return [
            'type' => $data->type,
            'title' => $this->texts_from_form($data, 'title'),
            'boxed' => !empty($data->boxed),
        ];
    }
}
