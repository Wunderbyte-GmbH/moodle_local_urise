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
use local_urise\megamenu\appearance;
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
    /** @var array|null Icon uploaded with this submission. */
    private $newicon = null;

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

        $menu = menu::get(true);
        [$pendingcolors, $pendingicons, $notices] = $this->take_pending();
        $mform->addElement('hidden', 'pendingcolors');
        $mform->setType('pendingcolors', PARAM_RAW);
        $mform->addElement('hidden', 'pendingicons');
        $mform->setType('pendingicons', PARAM_RAW);
        $swatch = fn($name, $class, $content = '') => \html_writer::span(
            $content . \html_writer::span($name, 'visually-hidden'),
            'mm-pick ' . $class,
            ['title' => $name]
        );
        $notice = fn($notice) => \html_writer::div(
            \html_writer::div('', 'col-md-3') . \html_writer::div(
                \html_writer::div(
                    $notice['text'],
                    'alert mb-0 alert-' . ($notice['ok'] ? 'success' : 'danger'),
                    ['role' => 'status']
                ),
                'col-md-9'
            ),
            'mb-3 row fitem mm-pick-notice'
        );

        $custom = array_unique(array_merge(appearance::custom_colors($menu), $pendingcolors));
        $css = appearance::css($custom);
        if ($css !== '') {
            $mform->addElement('html', \html_writer::tag('style', $css));
        }
        $colors = [];
        foreach (array_merge(array_keys(menu::COLORS), $custom) as $color) {
            $label = $swatch(appearance::color_name($color), 'mm-pick-color mm-pick-' . $color);
            $colors[] = $mform->createElement('radio', 'color', '', $label, $color);
        }
        $label = $swatch(get_string('megamenu:color_new', 'local_urise'), 'mm-pick-color mm-pick-new', '+');
        $colors[] = $mform->createElement('radio', 'color', '', $label, 'new');
        $mform->addGroup($colors, 'colorgroup', get_string('megamenu:color', 'local_urise'), ' ', false);
        $mform->addHelpButton('colorgroup', 'megamenu:color', 'local_urise');
        $mform->setDefault('color', 'blue');
        if (isset($notices['color'])) {
            $mform->addElement('html', $notice($notices['color']));
        }
        // Shown by the styles while "new colour" is chosen.
        $mform->addElement('html', \html_writer::div(
            \html_writer::div(
                \html_writer::label(get_string('megamenu:newcolor', 'local_urise'), 'id_mm_newcolor', false),
                'col-md-3 col-form-label'
            ) . \html_writer::div(
                \html_writer::empty_tag('input', [
                    'type' => 'color',
                    'name' => 'newcolor',
                    'id' => 'id_mm_newcolor',
                    'value' => '#7b328f',
                    'class' => 'form-control form-control-color',
                ]),
                'col-md-9'
            ),
            'mb-3 row fitem mm-newcolor'
        ));
        $mform->registerNoSubmitButton('addcolor');
        $mform->addElement('submit', 'addcolor', get_string('megamenu:addcolor', 'local_urise'));
        $mform->hideIf('addcolor', 'color', 'neq', 'new');

        $icons = [];
        $names = ['' => get_string('none')];
        foreach (menu::ICONS as $icon) {
            $names[$icon] = get_string('megamenu:icon_' . $icon, 'local_urise');
        }
        $uploaded = $menu['icons'] + $pendingicons;
        foreach ($names + array_map(fn($icon) => $icon['name'], $uploaded) as $icon => $name) {
            $icon = (string)$icon;
            $label = $swatch($name, 'mm-pick-icon' . ($icon === '' ? ' mm-pick-none' : ''), menu::icon_svg($icon, $uploaded));
            $icons[] = $mform->createElement('radio', 'icon', '', $label, $icon);
        }
        $label = $swatch(get_string('megamenu:icon_new', 'local_urise'), 'mm-pick-icon mm-pick-none mm-pick-new', '+');
        $icons[] = $mform->createElement('radio', 'icon', '', $label, 'new');
        $mform->addGroup($icons, 'icongroup', get_string('megamenu:icon', 'local_urise'), ' ', false);
        $mform->addHelpButton('icongroup', 'megamenu:icon', 'local_urise');
        $mform->setDefault('icon', '');
        if (isset($notices['icon'])) {
            $mform->addElement('html', $notice($notices['icon']));
        }
        $mform->addElement(
            'filepicker',
            'newiconfile',
            get_string('megamenu:newicon', 'local_urise'),
            null,
            ['accepted_types' => ['.svg'], 'maxbytes' => appearance::MAXICONBYTES]
        );
        $mform->addHelpButton('newiconfile', 'megamenu:newicon', 'local_urise');
        $mform->hideIf('newiconfile', 'icon', 'neq', 'new');
        $mform->registerNoSubmitButton('addicon');
        $mform->addElement('submit', 'addicon', get_string('megamenu:addicon', 'local_urise'));
        $mform->hideIf('addicon', 'icon', 'neq', 'new');

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
        if (($data['color'] ?? '') === 'new' && $this->get_new_color() === '') {
            $errors['colorgroup'] = get_string('megamenu:newcolorinvalid', 'local_urise');
        }
        if (($data['icon'] ?? '') === 'new' && !$this->get_new_icon((int)($data['newiconfile'] ?? 0))) {
            $errors['newiconfile'] = get_string('megamenu:newiconinvalid', 'local_urise');
        }
        return $errors;
    }

    /**
     * Returns the colours and icons added in this dialog that are not saved yet.
     *
     * "Add colour" and "add icon" show the form again: the new choice joins the list and is selected.
     *
     * @return array [string[] colours, array icons id => {name, svg}, array notices color|icon => {ok, text}]
     */
    private function take_pending(): array {
        $data = is_array($this->_ajaxformdata) ? $this->_ajaxformdata : [];
        $colors = array_filter(explode(',', (string)($data['pendingcolors'] ?? '')), [appearance::class, 'is_custom_color']);
        $icons = $this->parse_pending_icons((string)($data['pendingicons'] ?? ''));
        $notices = [];

        if (!empty($data['addcolor'])) {
            $color = $this->get_new_color();
            if ($color !== '') {
                $colors[] = $color;
                $data['color'] = $color;
            }
            $notices['color'] = [
                'ok' => $color !== '',
                'text' => $color === ''
                    ? get_string('megamenu:newcolorinvalid', 'local_urise')
                    : get_string('megamenu:appearanceadded', 'local_urise', appearance::color_name($color)),
            ];
        }
        if (!empty($data['addicon'])) {
            $icon = $this->get_new_icon((int)($data['newiconfile'] ?? 0));
            if ($icon) {
                $icons[$icon['id']] = ['name' => $icon['name'], 'svg' => $icon['svg']];
                $data['icon'] = $icon['id'];
            }
            $notices['icon'] = [
                'ok' => !empty($icon),
                'text' => $icon
                    ? get_string('megamenu:appearanceadded', 'local_urise', s($icon['name']))
                    : get_string('megamenu:newiconinvalid', 'local_urise'),
            ];
        }

        $colors = array_values(array_unique($colors));
        if (!empty($data)) {
            $data['pendingcolors'] = implode(',', $colors);
            $data['pendingicons'] = json_encode($icons);
            $this->_ajaxformdata = $data;
        }
        return [$colors, $icons, $notices];
    }

    /**
     * Reads the icons added in this dialog, cleaning them again.
     *
     * @param string $json
     * @return array id => {name, svg}
     */
    private function parse_pending_icons(string $json): array {
        $icons = [];
        $raw = json_decode($json, true);
        foreach (is_array($raw) ? $raw : [] as $id => $icon) {
            $svg = preg_match('/^u[0-9a-f]{10}$/', (string)$id) && is_array($icon)
                ? appearance::clean_svg((string)($icon['svg'] ?? ''), (string)$id)
                : null;
            if ($svg !== null) {
                $icons[$id] = ['name' => clean_param($icon['name'] ?? '', PARAM_TEXT), 'svg' => $svg];
            }
        }
        return $icons;
    }

    /**
     * Returns the id of the colour chosen as new colour.
     *
     * @return string Empty if it is not a colour.
     */
    private function get_new_color(): string {
        return appearance::color_id((string)($this->_ajaxformdata['newcolor'] ?? ''));
    }

    /**
     * Returns the uploaded icon.
     *
     * @param int $draftitemid
     * @return array|null Null if there is no usable file.
     */
    private function get_new_icon(int $draftitemid): ?array {
        global $USER;

        $context = \context_user::instance($USER->id);
        $files = get_file_storage()->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id DESC', false);
        $file = reset($files);
        if (!$file || $file->get_filesize() > appearance::MAXICONBYTES) {
            return null;
        }
        return appearance::import_icon($file->get_content(), pathinfo($file->get_filename(), PATHINFO_FILENAME));
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
        $color = $data->color ?? 'blue';
        $icon = $data->icon ?? '';
        if ($icon === 'new') {
            $this->newicon = $this->get_new_icon((int)($data->newiconfile ?? 0));
            $icon = $this->newicon['id'] ?? '';
        } else {
            $pending = $this->parse_pending_icons((string)($data->pendingicons ?? ''));
            if (isset($pending[$icon])) {
                $this->newicon = ['id' => $icon] + $pending[$icon];
            }
        }
        return $item + [
            'type' => $data->type,
            'label' => $this->texts_from_form($data, 'label'),
            'color' => $color === 'new' ? $this->get_new_color() : $color,
            'icon' => $icon,
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
        if ($this->newicon) {
            $menu['icons'][$this->newicon['id']] = ['name' => $this->newicon['name'], 'svg' => $this->newicon['svg']];
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
