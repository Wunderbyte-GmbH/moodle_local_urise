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

use MoodleQuickForm;

/**
 * Edits a card of a card row, e.g. a phase of the doctoral candidates.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class megamenu_card_form extends megamenu_form {
    /**
     * Adds the fields.
     *
     * @param MoodleQuickForm $mform
     */
    protected function define_fields(MoodleQuickForm $mform): void {
        $this->add_texts($mform, 'title', get_string('megamenu:title', 'local_urise'));
        $this->add_texts($mform, 'subtitle', get_string('megamenu:subtitle', 'local_urise'));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        return $this->validate_texts_required($data, 'title', parent::validation($data, $files));
    }

    /**
     * Turns a card into form data.
     *
     * @param array $item
     * @return array
     */
    protected function to_form(array $item): array {
        return array_merge(
            $this->texts_to_form($item['title'] ?? [], 'title'),
            $this->texts_to_form($item['subtitle'] ?? [], 'subtitle')
        );
    }

    /**
     * Turns form data into a card. Its links are kept by the upsert.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function from_form(\stdClass $data): array {
        return [
            'title' => $this->texts_from_form($data, 'title'),
            'subtitle' => $this->texts_from_form($data, 'subtitle'),
        ];
    }
}
