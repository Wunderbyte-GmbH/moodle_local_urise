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
use custom_menu;
use local_urise\megamenu\menu;
use MoodleQuickForm;

/**
 * Edits the settings of the mega menu: whether it is shown, which menu item opens it and the catalogue page.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class megamenu_settings_form extends megamenu_form {
    /**
     * Adds the fields.
     *
     * @param MoodleQuickForm $mform
     */
    protected function define_fields(MoodleQuickForm $mform): void {
        $mform->addElement('advcheckbox', 'enabled', get_string('megamenu:enabled', 'local_urise'));
        $mform->addHelpButton('enabled', 'megamenu:enabled', 'local_urise');

        $mform->addElement('select', 'attachurl', get_string('megamenu:attachurl', 'local_urise'), $this->get_menu_items());
        $mform->addHelpButton('attachurl', 'megamenu:attachurl', 'local_urise');

        $mform->addElement('text', 'catalogueurl', get_string('megamenu:catalogueurl', 'local_urise'), ['size' => 60]);
        $mform->setType('catalogueurl', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('catalogueurl', 'megamenu:catalogueurl', 'local_urise');
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $url = trim($data['catalogueurl'] ?? '');
        if (menu::normalise(['catalogueurl' => $url])['catalogueurl'] !== $url) {
            $errors['catalogueurl'] = get_string('megamenu:urlinvalid', 'local_urise');
        }
        return $errors;
    }

    /**
     * Turns the settings into form data.
     *
     * @param array $item Unused, the settings are part of the menu itself.
     * @return array
     */
    protected function to_form(array $item): array {
        $menu = menu::get(true);
        return [
            'enabled' => (int)$menu['enabled'],
            'attachurl' => $menu['attachurl'],
            'catalogueurl' => $menu['catalogueurl'],
        ];
    }

    /**
     * Turns form data into settings.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function from_form(\stdClass $data): array {
        return [
            'enabled' => !empty($data->enabled),
            'attachurl' => $data->attachurl,
            'catalogueurl' => trim($data->catalogueurl),
        ];
    }

    /**
     * Applies the settings to the menu.
     *
     * @param array $menu
     * @param array $path
     * @param array $item
     * @return array
     */
    protected function apply(array $menu, array $path, array $item): array {
        return array_replace($menu, $item);
    }

    /**
     * Returns the top level items of the custom menu that have a link.
     *
     * @return array local url => text
     */
    private function get_menu_items(): array {
        global $CFG;

        $items = [];
        $menu = menu::get(true);
        if (!empty($CFG->custommenuitems)) {
            foreach (custom_menu::convert_text_to_menu_nodes($CFG->custommenuitems, current_language()) as $node) {
                $url = $node->get_url();
                if ($url && !$node->has_children()) {
                    $items[$url->out_as_local_url(false)] = format_string($node->get_text());
                }
            }
        }
        // Keep the current value selectable even if the menu item was removed.
        if (!isset($items[$menu['attachurl']])) {
            $items[$menu['attachurl']] = $menu['attachurl'];
        }
        return $items;
    }
}
