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

use advanced_testcase;
use moodle_exception;
use moodle_url;

/**
 * Tests for the mega menu storage and export.
 *
 * @package local_urise
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_urise\megamenu\menu
 * @covers \local_urise\megamenu\seed
 */
final class menu_test extends advanced_testcase {
    /**
     * Setup.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * An empty config gives an empty, disabled menu that is not rendered.
     */
    public function test_empty_menu(): void {
        $menu = menu::get();

        $this->assertFalse($menu['enabled']);
        $this->assertSame(0, $menu['revision']);
        $this->assertSame([], $menu['tabs']);
        $this->assertSame(menu::DEFAULTCATALOGUEURL, $menu['catalogueurl']);
        $this->assertNull(menu::export_for_template());
    }

    /**
     * Invalid values are cleaned or dropped, missing values get defaults.
     */
    public function test_normalise(): void {
        $menu = menu::normalise([
            'enabled' => 1,
            'catalogueurl' => 'javascript:alert(1)',
            'tabs' => [
                'not a tab',
                [
                    'id' => 'Invalid ID!',
                    'type' => 'unknown',
                    'color' => 'pink',
                    'icon' => 'unicorn',
                    'label' => ['de' => ' <b>Angebot</b> ', 'en' => '', 'EVIL' => 'x'],
                    'blocks' => [[
                        'type' => 'chips',
                        'links' => [[
                            'type' => 'filter',
                            'label' => ['de' => 'PhD'],
                            'filters' => ['zgcommunities' => [2, ''], 'bad column' => ['1']],
                        ]],
                    ]],
                ],
            ],
        ]);

        $this->assertTrue($menu['enabled']);
        $this->assertSame(menu::DEFAULTCATALOGUEURL, $menu['catalogueurl']);
        $this->assertCount(1, $menu['tabs']);

        $tab = $menu['tabs'][0];
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $tab['id']);
        $this->assertSame('panel', $tab['type']);
        $this->assertSame('blue', $tab['color']);
        $this->assertSame('', $tab['icon']);
        $this->assertSame(['de' => 'Angebot'], $tab['label']);
        $this->assertNull($tab['link']);
        $this->assertSame(['zgcommunities' => ['2']], $tab['blocks'][0]['links'][0]['filters']);
    }

    /**
     * Saving increments the revision and logs the previous version.
     */
    public function test_save_and_revision(): void {
        global $DB;

        $saved = menu::save(['enabled' => true, 'tabs' => [$this->panel('first')]], 0);
        $this->assertSame(1, $saved['revision']);

        $saved = menu::save(['enabled' => true, 'tabs' => [$this->panel('second')]], 1);
        $this->assertSame(2, $saved['revision']);
        $this->assertSame('second', menu::get()['tabs'][0]['id']);

        $log = $DB->get_records('config_log', ['plugin' => 'local_urise', 'name' => menu::CONFIGNAME], 'id ASC');
        $this->assertCount(2, $log);
        $last = end($log);
        $this->assertSame('first', json_decode($last->oldvalue, true)['tabs'][0]['id']);
    }

    /**
     * Saving on an outdated revision is refused.
     */
    public function test_save_conflict(): void {
        menu::save(['tabs' => [$this->panel('first')]], 0);

        $this->expectException(moodle_exception::class);
        menu::save(['tabs' => [$this->panel('second')]], 0);
    }

    /**
     * Filter and search links point to the catalogue with wbtfilter and wbtsearch.
     */
    public function test_resolve_url(): void {
        $catalogue = '/course/index.php?categoryid=6';

        $url = menu::resolve_url(
            ['type' => 'filter', 'filters' => ['zgcommunities' => ['2', '3']], 'url' => '', 'search' => ''],
            $catalogue
        );
        $params = (new moodle_url($url))->params();
        $this->assertSame('6', $params['categoryid']);
        $this->assertSame('{"zgcommunities":["2","3"]}', $params['wbtfilter']);

        $url = menu::resolve_url(['type' => 'search', 'search' => 'Funding Your PhD', 'url' => '', 'filters' => []], $catalogue);
        $this->assertSame('Funding Your PhD', (new moodle_url($url))->param('wbtsearch'));

        $this->assertSame('', menu::resolve_url(['type' => 'filter', 'filters' => [], 'url' => '', 'search' => ''], $catalogue));
        $this->assertSame('', menu::resolve_url(['type' => 'search', 'search' => '', 'url' => '', 'filters' => []], $catalogue));
    }

    /**
     * Texts fall back to the site language and then to any language.
     */
    public function test_text_fallback(): void {
        global $CFG;
        $CFG->lang = 'de';

        force_current_language('en');
        $this->assertSame('Offers', menu::text(['de' => 'Angebot', 'en' => 'Offers']));
        $this->assertSame('Angebot', menu::text(['de' => 'Angebot']));
        $this->assertSame('Oferta', menu::text(['es' => 'Oferta']));
        $this->assertSame('', menu::text([]));
    }

    /**
     * The export resolves texts, links and news, and activates the first tab with a panel.
     */
    public function test_export_for_template(): void {
        global $DB;
        force_current_language('de');

        $instanceid = $DB->insert_record('local_wb_news_instance', (object)[
            'name' => 'Test',
            'template' => 'grid',
            'userid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $newsid = $DB->insert_record('local_wb_news', (object)[
            'instanceid' => $instanceid,
            'headline' => 'Writing Retreat',
            'bgimage' => 'https://example.com/image.jpg',
            'btnlink' => '',
            'subheadline' => '',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'json' => '',
            'userid' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $link = [
            'type' => 'link',
            'id' => 'ai',
            'label' => ['de' => 'KI'],
            'link' => ['type' => 'search', 'label' => ['de' => 'KI'], 'search' => 'KI'],
        ];
        $panel = $this->panel('phd');
        $panel['icon'] = 'owl';
        $panel['news'] = ['id' => $newsid];
        $panel['blocks'] = [[
            'type' => 'linkgrid',
            'links' => [
                ['type' => 'url', 'label' => ['de' => 'Ohne Ziel']],
                ['type' => 'url', 'label' => ['de' => 'FAQ'], 'url' => '/course/index.php?categoryid=83'],
            ],
        ]];
        menu::save(['enabled' => true, 'tabs' => [$link, $panel]], 0);

        $export = menu::export_for_template();
        [$linktab, $paneltab] = $export['tabs'];

        $this->assertTrue($linktab['islink']);
        $this->assertFalse($linktab['active']);
        $this->assertStringContainsString('wbtsearch=KI', $linktab['url']);

        $this->assertTrue($paneltab['active']);
        $this->assertSame('PhD', $paneltab['label']);
        $this->assertSame(menu::COLORS['yellow'], $paneltab['colors']);
        $this->assertSame('owl', $paneltab['icon']);
        $this->assertStringStartsWith('<svg', $paneltab['iconsvg']);
        $this->assertSame('', $linktab['iconsvg']);
        // The link without a target is left out.
        $this->assertCount(1, $paneltab['blocks'][0]['links']);
        $this->assertSame('FAQ', $paneltab['blocks'][0]['links'][0]['label']);

        $this->assertSame('Writing Retreat', $paneltab['news']['headline']);
        $this->assertSame('PhD', $paneltab['news']['kicker']);
        $this->assertSame('https://example.com/image.jpg', $paneltab['news']['image']);
        $this->assertStringContainsString('/local/wb_news/newsview.php', $paneltab['news']['url']);
        $this->assertNotSame('', $paneltab['news']['date']);
    }

    /**
     * Every icon of the design exists as a file.
     */
    public function test_icons(): void {
        foreach (menu::ICONS as $icon) {
            $this->assertStringStartsWith('<svg', menu::icon_svg($icon), $icon);
        }
        $this->assertSame('', menu::icon_svg(''));
        $this->assertSame('', menu::icon_svg('../../config'));
    }

    /**
     * The example content is valid and complete.
     */
    public function test_seed(): void {
        $saved = menu::save(seed::get(), 0);

        $this->assertTrue($saved['enabled']);
        $this->assertCount(9, $saved['tabs']);
        $this->assertSame(seed::get()['tabs'][1]['blocks'][0]['cards'][0]['id'], $saved['tabs'][1]['blocks'][0]['cards'][0]['id']);

        // All ids are unique, so the editor can address every element.
        $ids = [];
        array_walk_recursive($saved, function ($value, $key) use (&$ids) {
            if ($key === 'id' && is_string($value)) {
                $ids[] = $value;
            }
        });
        $this->assertSame(count($ids), count(array_unique($ids)));

        $export = menu::export_for_template();
        $this->assertCount(9, $export['tabs']);
        $this->assertTrue($export['tabs'][0]['isoverview']);
        $this->assertTrue($export['tabs'][0]['active']);
    }

    /**
     * A simple panel tab.
     *
     * @param string $id
     * @return array
     */
    private function panel(string $id): array {
        return ['id' => $id, 'type' => 'panel', 'label' => ['de' => 'PhD'], 'color' => 'yellow'];
    }
}
