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

use moodle_exception;
use moodle_url;

/**
 * Editing operations on the mega menu draft and the data for the editor page.
 *
 * Elements are addressed by paths of list names and ids, e.g.
 * ['tabs', 'phd', 'blocks', 'phd-phases', 'cards', 'phd-phase1', 'links', 'x'] for a link in a card,
 * ['tabs', 'phd', 'blocks'] for the list of blocks of a tab or ['tabs', 'phd', 'cta'] for the CTA of a tab.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor {
    /** @var string[] Lists of elements with ids. */
    public const LISTS = ['tabs', 'blocks', 'cards', 'links', 'secondarylinks'];

    /** @var string[] Single elements of a tab. */
    public const SLOTS = ['cta', 'link'];

    /**
     * Returns a reference to the element, list or slot at the path.
     *
     * @param array $menu
     * @param array $path
     * @return mixed
     * @throws moodle_exception If the path does not exist.
     */
    public static function &get_ref(array &$menu, array $path) {
        $node = &$menu;
        $path = array_values($path);
        for ($i = 0; $i < count($path); $i += 2) {
            $name = (string)$path[$i];
            if (!in_array($name, self::LISTS, true) && !in_array($name, self::SLOTS, true)) {
                throw new moodle_exception('megamenu:notfound', 'local_urise');
            }
            if (!array_key_exists($name, $node)) {
                if (in_array($name, self::SLOTS, true) && $i === count($path) - 1) {
                    $node[$name] = null;
                } else {
                    throw new moodle_exception('megamenu:notfound', 'local_urise');
                }
            }
            $node = &$node[$name];
            if ($i + 1 >= count($path)) {
                break;
            }
            $index = self::find_index($node, (string)$path[$i + 1]);
            $node = &$node[$index];
        }
        return $node;
    }

    /**
     * Returns the element at the path, or null.
     *
     * @param array $menu
     * @param array $path
     * @return array|null
     */
    public static function get(array $menu, array $path): ?array {
        try {
            $node = self::get_ref($menu, $path);
        } catch (moodle_exception $e) {
            return null;
        }
        return is_array($node) ? $node : null;
    }

    /**
     * Adds an element to a list or replaces the element with the same id.
     *
     * Values missing in $item are kept, so forms don't need to send child lists.
     *
     * @param array $menu
     * @param array $listpath Path of the list, e.g. ['tabs', 'phd', 'blocks'].
     * @param array $item The element, with the id of an existing one to update it.
     * @return array The menu.
     */
    public static function upsert(array $menu, array $listpath, array $item): array {
        if (!in_array(end($listpath), self::LISTS, true)) {
            throw new moodle_exception('megamenu:notfound', 'local_urise');
        }
        $list = &self::get_ref($menu, $listpath);
        $id = (string)($item['id'] ?? '');
        foreach ($list as $index => $existing) {
            if ($id !== '' && ($existing['id'] ?? '') === $id) {
                $list[$index] = array_replace($existing, $item);
                return $menu;
            }
        }
        if ($id === '') {
            $item['id'] = self::new_id();
        }
        $list[] = $item;
        return $menu;
    }

    /**
     * Sets or removes a single element of a tab, e.g. its CTA.
     *
     * @param array $menu
     * @param array $slotpath e.g. ['tabs', 'phd', 'cta'].
     * @param array|null $item
     * @return array The menu.
     */
    public static function set_slot(array $menu, array $slotpath, ?array $item): array {
        if (!in_array(end($slotpath), self::SLOTS, true)) {
            throw new moodle_exception('megamenu:notfound', 'local_urise');
        }
        $slot = &self::get_ref($menu, $slotpath);
        if ($item !== null && empty($item['id'])) {
            $item['id'] = $slot['id'] ?? self::new_id();
        }
        $slot = $item;
        return $menu;
    }

    /**
     * Deletes the element at the path.
     *
     * @param array $menu
     * @param array $path Path of an element in a list, or of a slot.
     * @return array The menu.
     */
    public static function delete(array $menu, array $path): array {
        if (in_array(end($path), self::SLOTS, true)) {
            return self::set_slot($menu, $path, null);
        }
        $id = (string)array_pop($path);
        $list = &self::get_ref($menu, $path);
        array_splice($list, self::find_index($list, $id), 1);
        return $menu;
    }

    /**
     * Moves an element within its list or into another list of the same kind.
     *
     * @param array $menu
     * @param array $path Path of the element.
     * @param array $targetlistpath Path of the list to move it to.
     * @param int $position Position in the target list, counted without the moved element.
     * @return array The menu.
     */
    public static function move(array $menu, array $path, array $targetlistpath, int $position): array {
        $sourcelistpath = $path;
        $id = (string)array_pop($sourcelistpath);
        if (end($sourcelistpath) !== end($targetlistpath)) {
            throw new moodle_exception('megamenu:notfound', 'local_urise');
        }

        $source = &self::get_ref($menu, $sourcelistpath);
        [$item] = array_splice($source, self::find_index($source, $id), 1);
        unset($source);

        $target = &self::get_ref($menu, $targetlistpath);
        $position = max(0, min($position, count($target)));
        array_splice($target, $position, 0, [$item]);
        return $menu;
    }

    /**
     * Returns the index of the element with the id.
     *
     * @param mixed $list
     * @param string $id
     * @return int
     * @throws moodle_exception If there is none.
     */
    private static function find_index($list, string $id): int {
        foreach ((array)$list as $index => $item) {
            if (($item['id'] ?? null) === $id) {
                return $index;
            }
        }
        throw new moodle_exception('megamenu:notfound', 'local_urise');
    }

    /**
     * Creates an id for a new element.
     *
     * @return string
     */
    public static function new_id(): string {
        return strtolower(random_string(10));
    }

    /**
     * Returns the data for the editor page.
     *
     * @param string $selectedtab Id of the tab to show.
     * @return array
     */
    public static function export_structure(string $selectedtab = ''): array {
        $menu = menu::get(true);
        $newsheadlines = self::get_news_headlines($menu['tabs']);

        $tabs = [];
        foreach ($menu['tabs'] as $tab) {
            $tabpath = ['tabs', $tab['id']];
            $blocks = [];
            foreach ($tab['blocks'] as $block) {
                $blockpath = array_merge($tabpath, ['blocks', $block['id']]);
                $cards = [];
                foreach ($block['cards'] as $card) {
                    $cardpath = array_merge($blockpath, ['cards', $card['id']]);
                    $cards[] = [
                        'id' => $card['id'],
                        'title' => self::label($card['title']),
                        'subtitle' => menu::text($card['subtitle']),
                        'path' => self::path($cardpath),
                        'links' => self::export_links($card['links'], array_merge($cardpath, ['links'])),
                        'linkspath' => self::path(array_merge($cardpath, ['links'])),
                    ];
                }
                $blocks[] = [
                    'id' => $block['id'],
                    'title' => menu::text($block['title']),
                    'typename' => get_string('megamenu:blocktype_' . $block['type'], 'local_urise'),
                    'iscardrow' => $block['type'] === 'cardrow',
                    'boxed' => $block['boxed'],
                    'path' => self::path($blockpath),
                    'cards' => $cards,
                    'cardspath' => self::path(array_merge($blockpath, ['cards'])),
                    'links' => self::export_links($block['links'], array_merge($blockpath, ['links'])),
                    'linkspath' => self::path(array_merge($blockpath, ['links'])),
                ];
            }

            $tabs[] = [
                'id' => $tab['id'],
                'label' => self::label($tab['label']),
                'typename' => get_string('megamenu:tabtype_' . $tab['type'], 'local_urise'),
                'isoverview' => $tab['type'] === 'overview',
                'islink' => $tab['type'] === 'link',
                'haspanel' => $tab['type'] !== 'link',
                'color' => $tab['color'],
                'colorname' => get_string('megamenu:color_' . $tab['color'], 'local_urise'),
                'heading' => menu::text($tab['heading']),
                'intro' => menu::text($tab['intro']),
                'hint' => menu::text($tab['hint']),
                'news' => empty($tab['news']['id']) ? null : [
                    'headline' => $newsheadlines[$tab['news']['id']] ?? get_string('megamenu:newsmissing', 'local_urise'),
                ],
                'path' => self::path($tabpath),
                'link' => $tab['link'] ? self::export_link($tab['link'], array_merge($tabpath, ['link'])) : null,
                'linkpath' => self::path(array_merge($tabpath, ['link'])),
                'blocks' => $blocks,
                'blockspath' => self::path(array_merge($tabpath, ['blocks'])),
                'cta' => $tab['cta'] ? self::export_link($tab['cta'], array_merge($tabpath, ['cta'])) : null,
                'ctapath' => self::path(array_merge($tabpath, ['cta'])),
                'secondarylinks' => self::export_links($tab['secondarylinks'], array_merge($tabpath, ['secondarylinks'])),
                'secondarylinkspath' => self::path(array_merge($tabpath, ['secondarylinks'])),
                'selected' => false,
            ];
        }

        $ids = array_column($tabs, 'id');
        $selected = in_array($selectedtab, $ids, true) ? array_search($selectedtab, $ids) : 0;
        if (!empty($tabs)) {
            $tabs[$selected]['selected'] = true;
        }

        return [
            'revision' => $menu['revision'],
            'enabled' => $menu['enabled'],
            'haschanges' => menu::has_unpublished_changes(),
            'previewurl' => (new moodle_url('/', ['megamenupreview' => 1]))->out(false),
            'previewframeurl' => (new moodle_url('/local/urise/megamenu_preview.php'))->out(false),
            'tabs' => $tabs,
            'hastabs' => !empty($tabs),
            'tabspath' => self::path(['tabs']),
        ];
    }

    /**
     * Returns the tiles to choose a new block from, each with an example rendered like in the mega menu.
     *
     * @return array
     */
    public static function export_component_picker(): array {
        global $OUTPUT;

        $de = current_language() === 'de';
        $link = fn($label) => ['label' => $label, 'url' => '#'];
        $examples = [
            'cardrow' => ['type' => 'cardrow', 'boxed' => false, 'sample' => ['iscardrow' => true, 'cards' => [
                ['title' => $de ? 'Eingangsphase' : 'Entry phase', 'subtitle' => 'Year 1',
                    'links' => [$link('Datenmanagement'), $link('Funding Your PhD')]],
                ['title' => $de ? 'Forschungsphase' : 'Research phase', 'subtitle' => 'Year 2',
                    'links' => [$link('Presentation Skills')]],
            ]]],
            'linkgrid_boxed' => ['type' => 'linkgrid', 'boxed' => true, 'sample' => ['islinkgrid' => true, 'boxed' => true,
                'title' => $de ? 'Themen für alle Communities' : 'Topics for all communities',
                'links' => [$link('Digital Accessibility'), $link('Artificial Intelligence'), $link('IT Services & Tools')]]],
            'linkgrid_plain' => ['type' => 'linkgrid', 'boxed' => false, 'sample' => ['islinkgrid' => true,
                'links' => [$link('Führung & Supervision'), $link('Funding Research'), $link('Lehre'), $link('Kommunikation')]]],
            'chips' => ['type' => 'chips', 'boxed' => false, 'sample' => ['ischips' => true,
                'title' => $de ? 'Weitere Schwerpunkte' : 'More topics',
                'links' => [$link('Artificial Intelligence'), $link('Digital Accessibility'), $link('IT Services & Tools')]]],
        ];

        // The examples are rendered by theme_lexa, which renders the mega menu.
        $template = \core_component::get_component_directory('theme_lexa') . '/templates/megamenu_block.mustache';
        $tiles = [];
        foreach ($examples as $key => $example) {
            $tiles[] = [
                'type' => $example['type'],
                'boxed' => (int)$example['boxed'],
                'name' => get_string('megamenu:blocktype_' . $key, 'local_urise'),
                'description' => get_string('megamenu:blocktype_' . $key . '_desc', 'local_urise'),
                'samplehtml' => file_exists($template)
                    ? $OUTPUT->render_from_template('theme_lexa/megamenu_block', $example['sample'])
                    : '',
            ];
        }
        return $tiles;
    }

    /**
     * Exports links for the editor.
     *
     * @param array $links
     * @param array $listpath
     * @return array
     */
    private static function export_links(array $links, array $listpath): array {
        return array_map(fn($link) => self::export_link($link, array_merge($listpath, [$link['id']])), $links);
    }

    /**
     * Exports a link for the editor, with a description of its target.
     *
     * @param array $link
     * @param array $path
     * @return array
     */
    private static function export_link(array $link, array $path): array {
        switch ($link['type']) {
            case 'filter':
                $target = filteroptions::describe($link['filters']);
                break;
            case 'search':
                $target = $link['search'] === '' ? '' : '"' . $link['search'] . '"';
                break;
            default:
                $target = $link['url'];
        }
        return [
            'id' => $link['id'],
            'label' => self::label($link['label']),
            'typename' => get_string('megamenu:linktype_' . $link['type'], 'local_urise'),
            'target' => $target === '' ? get_string('megamenu:notarget', 'local_urise') : $target,
            'hastarget' => $target !== '',
            'path' => self::path($path),
        ];
    }

    /**
     * Returns the text in the current language or a placeholder.
     *
     * @param array $texts
     * @return string
     */
    private static function label(array $texts): string {
        $text = menu::text($texts);
        return $text === '' ? get_string('megamenu:untitled', 'local_urise') : $text;
    }

    /**
     * Encodes a path for data attributes.
     *
     * @param array $path
     * @return string
     */
    private static function path(array $path): string {
        return json_encode(array_values($path));
    }

    /**
     * Returns the headlines of the news items used by the tabs.
     *
     * @param array $tabs
     * @return array id => headline
     */
    private static function get_news_headlines(array $tabs): array {
        global $DB;

        $ids = array_filter(array_map(fn($tab) => $tab['news']['id'] ?? 0, $tabs));
        if (empty($ids)) {
            return [];
        }
        $headlines = [];
        foreach ($DB->get_records_list('local_wb_news', 'id', array_unique($ids), '', 'id, headline') as $record) {
            $headlines[$record->id] = trim(strip_tags(format_string($record->headline)));
        }
        return $headlines;
    }
}
