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
 * Storage, validation and export of the mega menu.
 *
 * The whole menu is stored as one JSON document in the plugin config (local_urise/megamenu).
 * Editors work on a draft (local_urise/megamenu_draft), which is copied to the live menu when published.
 * Every save increments a revision, so parallel edits can be detected, and the previous
 * version is written to the config log.
 *
 * Structure (schema version 1):
 * menu  = {schemaversion, revision, enabled, attachurl, catalogueurl, tabs[]}
 * tab   = {id, type: overview|panel|link, label{}, link|null, color, heading{}, intro{}, hint{},
 *          blocks[], news: {id, kicker{}}|null, cta: link|null, secondarylinks[]}
 * block = {id, type: cardrow|linkgrid|chips, title{}, boxed, cards[], links[]}
 * card  = {id, title{}, subtitle{}, links[]}
 * link  = {id, type: url|filter|search, label{}, url, filters{column: [values]}, search}
 *
 * Texts ({}) are maps of language code => text, e.g. {"de": "Angebot", "en": "Our offers"}.
 * The link of a tab is the target of its entry in the left column: required for type link,
 * optional for overview ("Gesamtes Angebot"), not used for panel tabs, which switch the content.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class menu {
    /** @var string Config name the menu is stored under. */
    public const CONFIGNAME = 'megamenu';

    /** @var string Config name the draft is stored under. */
    public const DRAFTCONFIGNAME = 'megamenu_draft';

    /** @var int Current schema version. */
    public const SCHEMAVERSION = 1;

    /** @var string[] Allowed tab types. */
    public const TABTYPES = ['overview', 'panel', 'link'];

    /** @var string[] Allowed block types. */
    public const BLOCKTYPES = ['cardrow', 'linkgrid', 'chips'];

    /** @var string[] Allowed link types. */
    public const LINKTYPES = ['url', 'filter', 'search'];

    /**
     * Colour presets from the design: accent (bar, borders), tint (backgrounds), text (labels on tint).
     */
    public const COLORS = [
        'blue' => ['accent' => '#0063A6', 'tint' => '#E6EFF6', 'text' => '#0063A6'],
        'petrol' => ['accent' => '#1F7192', 'tint' => 'rgba(31, 113, 146, 0.3)', 'text' => '#1F7192'],
        // Yellow was darkened in the design (2026-10-20) for the contrast of white icons.
        'yellow' => ['accent' => '#D6B737', 'tint' => 'rgba(214, 183, 55, 0.3)', 'text' => '#63520B'],
        'purple' => ['accent' => '#CA8ADB', 'tint' => 'rgba(202, 138, 219, 0.3)', 'text' => '#7B328F'],
        'green' => ['accent' => '#A5C56C', 'tint' => 'rgba(165, 197, 108, 0.3)', 'text' => '#506923'],
        'teal' => ['accent' => '#2BA4A6', 'tint' => 'rgba(43, 164, 166, 0.3)', 'text' => '#0C6364'],
        'brown' => ['accent' => '#BEA97A', 'tint' => 'rgba(190, 169, 122, 0.3)', 'text' => '#695323'],
        'salmon' => ['accent' => '#E6936C', 'tint' => 'rgba(230, 147, 108, 0.3)', 'text' => '#8A4522'],
        'rose' => ['accent' => '#F1B3B4', 'tint' => 'rgba(241, 179, 180, 0.3)', 'text' => '#8F3A3C'],
    ];

    /**
     * Icons from the design (Figma "Megamenü", 2026-10-20), as files in pix/megamenu/<name>.svg.
     * They are white line icons, shown in a box with the colour of the entry.
     */
    public const ICONS = ['grid', 'owl', 'briefcase', 'person', 'employee', 'graduation', 'library', 'group', 'brain'];

    /** @var string Default target page for filter and search links: the main catalogue. */
    public const DEFAULTCATALOGUEURL = '/course/index.php?categoryid=6';

    /**
     * Returns the stored menu, normalised. An empty, disabled menu if nothing is stored yet.
     *
     * @param bool $draft Whether to return the draft. Without a draft, this is the live menu.
     * @return array
     */
    public static function get(bool $draft = false): array {
        $json = $draft ? get_config('local_urise', self::DRAFTCONFIGNAME) : false;
        if (empty($json)) {
            $json = get_config('local_urise', self::CONFIGNAME);
        }
        $raw = empty($json) ? [] : json_decode($json, true);
        return self::normalise(is_array($raw) ? $raw : []);
    }

    /**
     * Saves the menu.
     *
     * @param array $menu The menu as edited.
     * @param int $baserevision The revision the edit was based on.
     * @param bool $draft Whether to save the draft instead of the live menu.
     * @return array The saved, normalised menu with its new revision.
     * @throws moodle_exception If someone else saved in the meantime.
     */
    public static function save(array $menu, int $baserevision, bool $draft = false): array {
        $name = $draft ? self::DRAFTCONFIGNAME : self::CONFIGNAME;
        $oldjson = get_config('local_urise', $name);
        $current = self::get($draft);

        if ($current['revision'] !== $baserevision) {
            throw new moodle_exception('megamenu:conflict', 'local_urise');
        }

        $clean = self::normalise($menu);
        $clean['revision'] = $current['revision'] + 1;
        $newjson = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        set_config($name, $newjson, 'local_urise');
        // Keeps the previous version, so it can be restored.
        add_to_config_log($name, $oldjson === false ? null : $oldjson, $newjson, 'local_urise');

        return $clean;
    }

    /**
     * Makes the draft the live menu.
     *
     * @param int $draftrevision The revision of the draft that was reviewed.
     * @return array The published menu.
     * @throws moodle_exception If the draft was changed in the meantime.
     */
    public static function publish(int $draftrevision): array {
        $draft = self::get(true);
        if ($draft['revision'] !== $draftrevision) {
            throw new moodle_exception('megamenu:conflict', 'local_urise');
        }
        $published = self::save($draft, self::get()['revision']);
        unset_config(self::DRAFTCONFIGNAME, 'local_urise');
        return $published;
    }

    /**
     * Throws the draft away, so editing starts again from the live menu.
     */
    public static function discard_draft(): void {
        unset_config(self::DRAFTCONFIGNAME, 'local_urise');
    }

    /**
     * Whether the draft differs from the live menu.
     *
     * @return bool
     */
    public static function has_unpublished_changes(): bool {
        if (get_config('local_urise', self::DRAFTCONFIGNAME) === false) {
            return false;
        }
        $draft = self::get(true);
        $live = self::get();
        unset($draft['revision'], $live['revision']);
        return $draft !== $live;
    }

    /**
     * Brings any input into the current schema: fills defaults, cleans values and drops what is invalid.
     *
     * @param array $raw
     * @return array
     */
    public static function normalise(array $raw): array {
        $tabs = [];
        foreach (self::list($raw['tabs'] ?? []) as $tab) {
            $tabs[] = self::normalise_tab($tab);
        }

        return [
            'schemaversion' => self::SCHEMAVERSION,
            'revision' => max(0, (int)($raw['revision'] ?? 0)),
            'enabled' => !empty($raw['enabled']),
            'attachurl' => self::clean_url($raw['attachurl'] ?? '') ?: self::DEFAULTCATALOGUEURL,
            'catalogueurl' => self::clean_url($raw['catalogueurl'] ?? '') ?: self::DEFAULTCATALOGUEURL,
            'tabs' => $tabs,
        ];
    }

    /**
     * Normalises a tab.
     *
     * @param array $tab
     * @return array
     */
    private static function normalise_tab(array $tab): array {
        $type = in_array($tab['type'] ?? '', self::TABTYPES, true) ? $tab['type'] : 'panel';

        $blocks = [];
        foreach (self::list($tab['blocks'] ?? []) as $block) {
            $blocks[] = self::normalise_block($block);
        }

        $news = null;
        if (!empty($tab['news']['id'])) {
            $news = [
                'id' => (int)$tab['news']['id'],
                'kicker' => self::clean_texts($tab['news']['kicker'] ?? []),
            ];
        }

        return [
            'id' => self::clean_id($tab['id'] ?? ''),
            'type' => $type,
            'label' => self::clean_texts($tab['label'] ?? []),
            'link' => empty($tab['link']) || !is_array($tab['link']) ? null : self::normalise_link($tab['link']),
            'color' => array_key_exists($tab['color'] ?? '', self::COLORS) ? $tab['color'] : 'blue',
            'icon' => in_array($tab['icon'] ?? '', self::ICONS, true) ? $tab['icon'] : '',
            'heading' => self::clean_texts($tab['heading'] ?? []),
            'intro' => self::clean_texts($tab['intro'] ?? []),
            'hint' => self::clean_texts($tab['hint'] ?? []),
            'blocks' => $blocks,
            'news' => $news,
            'cta' => empty($tab['cta']) || !is_array($tab['cta']) ? null : self::normalise_link($tab['cta']),
            'secondarylinks' => self::normalise_links($tab['secondarylinks'] ?? []),
        ];
    }

    /**
     * Normalises a block.
     *
     * @param array $block
     * @return array
     */
    private static function normalise_block(array $block): array {
        $cards = [];
        foreach (self::list($block['cards'] ?? []) as $card) {
            $cards[] = [
                'id' => self::clean_id($card['id'] ?? ''),
                'title' => self::clean_texts($card['title'] ?? []),
                'subtitle' => self::clean_texts($card['subtitle'] ?? []),
                'links' => self::normalise_links($card['links'] ?? []),
            ];
        }

        return [
            'id' => self::clean_id($block['id'] ?? ''),
            'type' => in_array($block['type'] ?? '', self::BLOCKTYPES, true) ? $block['type'] : 'linkgrid',
            'title' => self::clean_texts($block['title'] ?? []),
            'boxed' => !empty($block['boxed']),
            'cards' => $cards,
            'links' => self::normalise_links($block['links'] ?? []),
        ];
    }

    /**
     * Normalises a list of links.
     *
     * @param mixed $links
     * @return array
     */
    private static function normalise_links($links): array {
        $result = [];
        foreach (self::list($links) as $link) {
            $result[] = self::normalise_link($link);
        }
        return $result;
    }

    /**
     * Normalises a link.
     *
     * @param array $link
     * @return array
     */
    private static function normalise_link(array $link): array {
        $filters = [];
        foreach ((array)($link['filters'] ?? []) as $column => $values) {
            if (!is_string($column) || !preg_match('/^[a-z0-9_]+$/', $column)) {
                continue;
            }
            $values = array_values(array_filter(
                array_map(fn($value) => clean_param((string)$value, PARAM_TEXT), (array)$values),
                fn($value) => $value !== ''
            ));
            if (!empty($values)) {
                $filters[$column] = $values;
            }
        }

        return [
            'id' => self::clean_id($link['id'] ?? ''),
            'type' => in_array($link['type'] ?? '', self::LINKTYPES, true) ? $link['type'] : 'url',
            'label' => self::clean_texts($link['label'] ?? []),
            'url' => self::clean_url($link['url'] ?? ''),
            'filters' => $filters,
            'search' => clean_param((string)($link['search'] ?? ''), PARAM_TEXT),
        ];
    }

    /**
     * Exports the menu for rendering in the current language.
     *
     * @param bool $draft Export the draft for the preview. It is shown even if the menu is disabled.
     * @param bool $edit Add the paths of the elements for the editor preview and keep links without target.
     * @param string $activetab Id of the tab to show, instead of the first one.
     * @return array|null Null if the menu is disabled or has no tabs.
     */
    public static function export_for_template(bool $draft = false, bool $edit = false, string $activetab = ''): ?array {
        $menu = self::get($draft);
        if ((!$menu['enabled'] && !$draft) || empty($menu['tabs'])) {
            return null;
        }

        $news = self::load_news($menu['tabs']);
        $ids = array_column(array_filter($menu['tabs'], fn($tab) => $tab['type'] !== 'link'), 'id');
        $activetab = in_array($activetab, $ids, true) ? $activetab : ($ids[0] ?? '');
        $context = ['menu' => $menu, 'edit' => $edit];

        $tabs = [];
        foreach ($menu['tabs'] as $tab) {
            $path = ['tabs', $tab['id']];
            $counts = [];
            $secondarylinks = self::export_links($tab['secondarylinks'], array_merge($path, ['secondarylinks']), $context);
            foreach ($secondarylinks as $index => $link) {
                // Secondary links at the same position match across tabs, for the switch animation.
                $secondarylinks[$index]['animkey'] = 'secondary-' . $index;
            }
            $tabs[] = [
                'id' => $tab['id'],
                'label' => self::text($tab['label']),
                'isoverview' => $tab['type'] === 'overview',
                'islink' => $tab['type'] === 'link',
                'haspanel' => $tab['type'] !== 'link',
                'active' => $tab['id'] === $activetab,
                // The first tab with a panel is shown again whenever the menu opens.
                'default' => $tab['id'] === ($ids[0] ?? ''),
                'url' => $tab['link'] ? self::resolve_url($tab['link'], $menu['catalogueurl']) : '',
                'linkeditpath' => self::edit_path(array_merge($path, ['link']), $context),
                'color' => $tab['color'],
                'colors' => self::COLORS[$tab['color']],
                'icon' => $tab['icon'],
                'iconsvg' => self::icon_svg($tab['icon']),
                'heading' => self::text($tab['heading']),
                'intro' => self::text($tab['intro']),
                'hint' => self::text($tab['hint']),
                'editpath' => self::edit_path($path, $context),
                'blocks' => array_map(function ($block) use ($path, $context, &$counts) {
                    // Blocks of the same type and position match across tabs, for the switch animation.
                    $counts[$block['type']] = ($counts[$block['type']] ?? -1) + 1;
                    return self::export_block($block, array_merge($path, ['blocks', $block['id']]), $context)
                        + ['animkey' => $block['type'] . '-' . $counts[$block['type']]];
                }, $tab['blocks']),
                'news' => self::export_news($tab, $news),
                'cta' => $tab['cta'] ? self::export_link($tab['cta'], array_merge($path, ['cta']), $context) : null,
                'secondarylinks' => $secondarylinks,
            ];
        }

        return [
            'attachurl' => self::out_url($menu['attachurl']),
            'tabs' => $tabs,
        ];
    }

    /**
     * Exports a block.
     *
     * @param array $block
     * @param array $path
     * @param array $context
     * @return array
     */
    private static function export_block(array $block, array $path, array $context): array {
        return [
            'id' => $block['id'],
            'iscardrow' => $block['type'] === 'cardrow',
            'islinkgrid' => $block['type'] === 'linkgrid',
            'ischips' => $block['type'] === 'chips',
            'title' => self::text($block['title']),
            'boxed' => $block['boxed'],
            'editpath' => self::edit_path($path, $context),
            'cards' => array_map(function ($card, $index) use ($path, $context) {
                $cardpath = array_merge($path, ['cards', $card['id']]);
                return [
                    'id' => $card['id'],
                    // On mobile the cards are accordions and only the first one is open.
                    'first' => $index === 0,
                    'title' => self::text($card['title']),
                    'subtitle' => self::text($card['subtitle']),
                    'editpath' => self::edit_path($cardpath, $context),
                    'links' => self::export_links($card['links'], array_merge($cardpath, ['links']), $context),
                ];
            }, $block['cards'], array_keys($block['cards'])),
            'links' => self::export_links($block['links'], array_merge($path, ['links']), $context),
        ];
    }

    /**
     * Exports links, leaving out those without label or target unless editing.
     *
     * @param array $links
     * @param array $listpath
     * @param array $context
     * @return array
     */
    private static function export_links(array $links, array $listpath, array $context): array {
        return array_values(array_filter(array_map(
            fn($link) => self::export_link($link, array_merge($listpath, [$link['id']]), $context),
            $links
        )));
    }

    /**
     * Exports a link.
     *
     * @param array $link
     * @param array $path
     * @param array $context
     * @return array|null Null if the link has no label or no target and the menu is not edited.
     */
    private static function export_link(array $link, array $path, array $context): ?array {
        $label = self::text($link['label']);
        $url = self::resolve_url($link, $context['menu']['catalogueurl']);
        if (!$context['edit'] && ($label === '' || $url === '')) {
            return null;
        }
        return [
            'id' => $link['id'],
            'label' => $label === '' ? '…' : $label,
            'url' => $url === '' ? '#' : $url,
            'incomplete' => $label === '' || $url === '',
            'editpath' => self::edit_path($path, $context),
        ];
    }

    /**
     * Returns the path of an element for the editor preview.
     *
     * @param array $path
     * @param array $context
     * @return string Empty if the menu is not edited.
     */
    private static function edit_path(array $path, array $context): string {
        return $context['edit'] ? json_encode(array_values($path)) : '';
    }

    /**
     * Builds the target URL of a link.
     *
     * Filter and search links point to the catalogue page. Its wunderbyte table picks up
     * the wbtfilter and wbtsearch parameters and shows the list already filtered.
     *
     * @param array $link
     * @param string $catalogueurl
     * @return string
     */
    public static function resolve_url(array $link, string $catalogueurl): string {
        switch ($link['type']) {
            case 'filter':
                if (empty($link['filters'])) {
                    return '';
                }
                $url = new moodle_url($catalogueurl);
                $url->param('wbtfilter', json_encode($link['filters'], JSON_UNESCAPED_UNICODE));
                return $url->out(false);
            case 'search':
                if ($link['search'] === '') {
                    return '';
                }
                $url = new moodle_url($catalogueurl);
                $url->param('wbtsearch', $link['search']);
                return $url->out(false);
            default:
                return self::out_url($link['url']);
        }
    }

    /**
     * Loads the news items referenced by the tabs with one query.
     *
     * @param array $tabs
     * @return array News records by id.
     */
    private static function load_news(array $tabs): array {
        global $DB;

        $ids = [];
        foreach ($tabs as $tab) {
            if (!empty($tab['news']['id'])) {
                $ids[] = $tab['news']['id'];
            }
        }
        if (empty($ids)) {
            return [];
        }
        return $DB->get_records_list(
            'local_wb_news',
            'id',
            array_unique($ids),
            '',
            'id, instanceid, headline, bgimage, btnlink, timecreated'
        );
    }

    /**
     * Exports the news card of a tab.
     *
     * @param array $tab
     * @param array $news News records by id.
     * @return array|null
     */
    private static function export_news(array $tab, array $news): ?array {
        $record = $news[$tab['news']['id'] ?? 0] ?? null;
        if (!$record) {
            return null;
        }

        $url = trim((string)$record->btnlink);
        if ($url === '') {
            $url = (new moodle_url('/local/wb_news/newsview.php', [
                'id' => $record->id,
                'instanceid' => $record->instanceid,
            ]))->out(false);
        }

        return [
            'kicker' => self::text($tab['news']['kicker']) ?: self::text($tab['label']),
            'headline' => trim(strip_tags(format_string($record->headline))),
            'image' => empty($record->bgimage) ? '' : $record->bgimage,
            'url' => $url,
            // The mobile card shows the date, like in the design.
            'date' => empty($record->timecreated) ? '' : userdate($record->timecreated, get_string('strftimedate', 'langconfig')),
        ];
    }

    /**
     * Returns the markup of an icon, to be inlined in the templates.
     *
     * @param string $name One of ICONS.
     * @return string Empty if there is no such icon.
     */
    public static function icon_svg(string $name): string {
        global $CFG;
        static $cache = [];

        if (!in_array($name, self::ICONS, true)) {
            return '';
        }
        if (!array_key_exists($name, $cache)) {
            $file = $CFG->dirroot . '/local/urise/pix/megamenu/' . $name . '.svg';
            $cache[$name] = is_readable($file) ? (string)file_get_contents($file) : '';
        }
        return $cache[$name];
    }

    /**
     * Picks the text for the current language, falling back to the site language and then any language.
     *
     * @param array $texts
     * @return string
     */
    public static function text(array $texts): string {
        global $CFG;

        foreach ([current_language(), $CFG->lang ?? 'de'] as $lang) {
            if (($texts[$lang] ?? '') !== '') {
                return $texts[$lang];
            }
        }
        foreach ($texts as $text) {
            if ($text !== '') {
                return $text;
            }
        }
        return '';
    }

    /**
     * Cleans a map of language => text.
     *
     * @param mixed $texts
     * @return array
     */
    private static function clean_texts($texts): array {
        $result = [];
        foreach ((array)$texts as $lang => $text) {
            if (!is_string($lang) || !preg_match('/^[a-z]{2,3}(_[a-z]+)?$/', $lang)) {
                continue;
            }
            $text = trim(clean_param((string)$text, PARAM_TEXT));
            if ($text !== '') {
                $result[$lang] = $text;
            }
        }
        return $result;
    }

    /**
     * Keeps local URLs starting with / and absolute http(s) URLs, everything else becomes ''.
     *
     * @param mixed $url
     * @return string
     */
    private static function clean_url($url): string {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return clean_param($url, PARAM_LOCALURL);
        }
        if (preg_match('#^https?://#i', $url)) {
            return clean_param($url, PARAM_URL);
        }
        return '';
    }

    /**
     * Turns a stored URL into an absolute one.
     *
     * @param string $url
     * @return string
     */
    private static function out_url(string $url): string {
        return $url === '' ? '' : (new moodle_url($url))->out(false);
    }

    /**
     * Keeps a valid id or creates a new one.
     *
     * @param mixed $id
     * @return string
     */
    private static function clean_id($id): string {
        $id = (string)$id;
        return preg_match('/^[a-z0-9_-]{1,40}$/', $id) ? $id : strtolower(random_string(10));
    }

    /**
     * Returns only the array entries of a list.
     *
     * @param mixed $list
     * @return array
     */
    private static function list($list): array {
        return array_values(array_filter((array)$list, 'is_array'));
    }
}
