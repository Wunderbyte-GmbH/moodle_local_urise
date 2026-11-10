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
use core_external\external_api;
use local_urise\external\megamenu_edit;
use local_urise\form\megamenu_link_form;
use local_urise\form\megamenu_tab_form;
use moodle_exception;
use required_capability_exception;

/**
 * Tests for editing the mega menu draft.
 *
 * @package local_urise
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_urise\megamenu\editor
 * @covers \local_urise\megamenu\menu
 * @covers \local_urise\external\megamenu_edit
 * @covers \local_urise\form\megamenu_form
 */
final class editor_test extends advanced_testcase {
    /**
     * Setup.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * A small menu with two tabs.
     *
     * @return array
     */
    private function menu(): array {
        return menu::normalise([
            'enabled' => true,
            'tabs' => [
                [
                    'id' => 'phd',
                    'label' => ['de' => 'PhD'],
                    'blocks' => [
                        ['id' => 'b1', 'type' => 'linkgrid', 'links' => [
                            ['id' => 'l1', 'label' => ['de' => 'Eins'], 'type' => 'search', 'search' => 'eins'],
                            ['id' => 'l2', 'label' => ['de' => 'Zwei'], 'type' => 'search', 'search' => 'zwei'],
                        ]],
                        ['id' => 'b2', 'type' => 'chips', 'links' => []],
                    ],
                ],
                ['id' => 'postdocs', 'label' => ['de' => 'PostDocs']],
            ],
        ]);
    }

    /**
     * Elements are found by paths, missing ones are reported.
     */
    public function test_get(): void {
        $menu = $this->menu();

        $this->assertSame('Zwei', editor::get($menu, ['tabs', 'phd', 'blocks', 'b1', 'links', 'l2'])['label']['de']);
        $this->assertNull(editor::get($menu, ['tabs', 'phd', 'blocks', 'nope']));
        $this->assertNull(editor::get($menu, ['tabs', 'phd', 'evil', 'x']));

        $this->expectException(moodle_exception::class);
        editor::get_ref($menu, ['tabs', 'missing']);
    }

    /**
     * Upsert adds new elements with a new id and updates existing ones, keeping their children.
     */
    public function test_upsert(): void {
        $menu = editor::upsert($this->menu(), ['tabs', 'phd', 'blocks', 'b1', 'links'], ['label' => ['de' => 'Neu']]);
        $links = editor::get($menu, ['tabs', 'phd', 'blocks', 'b1'])['links'];
        $this->assertCount(3, $links);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $links[2]['id']);

        $menu = editor::upsert($menu, ['tabs'], ['id' => 'phd', 'label' => ['de' => 'Doctoral Candidates']]);
        $tab = editor::get($menu, ['tabs', 'phd']);
        $this->assertSame('Doctoral Candidates', $tab['label']['de']);
        $this->assertCount(2, $tab['blocks']);
    }

    /**
     * Slots are set and removed.
     */
    public function test_set_slot(): void {
        $menu = editor::set_slot($this->menu(), ['tabs', 'phd', 'cta'], ['type' => 'url', 'url' => '/x']);
        $this->assertSame('/x', editor::get($menu, ['tabs', 'phd', 'cta'])['url']);

        $menu = editor::delete($menu, ['tabs', 'phd', 'cta']);
        $this->assertNull(editor::get($menu, ['tabs', 'phd', 'cta']));
    }

    /**
     * Elements are deleted with their children.
     */
    public function test_delete(): void {
        $menu = editor::delete($this->menu(), ['tabs', 'phd', 'blocks', 'b1']);
        $this->assertSame(['b2'], array_column(editor::get($menu, ['tabs', 'phd'])['blocks'], 'id'));
    }

    /**
     * Elements move within their list and into other lists of the same kind.
     */
    public function test_move(): void {
        $menu = editor::move($this->menu(), ['tabs', 'postdocs'], ['tabs'], 0);
        $this->assertSame(['postdocs', 'phd'], array_column($menu['tabs'], 'id'));

        $menu = editor::move($menu, ['tabs', 'phd', 'blocks', 'b1', 'links', 'l2'], ['tabs', 'phd', 'blocks', 'b2', 'links'], 0);
        $this->assertSame(['l1'], array_column(editor::get($menu, ['tabs', 'phd', 'blocks', 'b1'])['links'], 'id'));
        $this->assertSame(['l2'], array_column(editor::get($menu, ['tabs', 'phd', 'blocks', 'b2'])['links'], 'id'));

        // A link can't become a block.
        $this->expectException(moodle_exception::class);
        editor::move($menu, ['tabs', 'phd', 'blocks', 'b1', 'links', 'l1'], ['tabs', 'phd', 'blocks'], 0);
    }

    /**
     * Edits go to the draft until they are published, and can be discarded.
     */
    public function test_draft_publish_discard(): void {
        menu::save($this->menu(), 0);
        $this->assertFalse(menu::has_unpublished_changes());

        $draft = menu::get(true);
        $saved = menu::save(editor::delete($draft, ['tabs', 'postdocs']), $draft['revision'], true);
        $this->assertTrue(menu::has_unpublished_changes());
        $this->assertCount(2, menu::get()['tabs']);
        $this->assertCount(1, menu::get(true)['tabs']);

        menu::publish($saved['revision']);
        $this->assertFalse(menu::has_unpublished_changes());
        $this->assertCount(1, menu::get()['tabs']);

        $draft = menu::get(true);
        menu::save(editor::delete($draft, ['tabs', 'phd']), $draft['revision'], true);
        menu::discard_draft();
        $this->assertCount(1, menu::get(true)['tabs']);
    }

    /**
     * Publishing a draft that changed in the meantime is refused.
     */
    public function test_publish_conflict(): void {
        $draft = menu::get(true);
        menu::save($this->menu(), $draft['revision'], true);

        $this->expectException(moodle_exception::class);
        menu::publish($draft['revision']);
    }

    /**
     * The draft of the preview is exported even if the menu is disabled.
     */
    public function test_export_draft(): void {
        $menu = $this->menu();
        $menu['enabled'] = false;
        menu::save($menu, 0, true);

        $this->assertNull(menu::export_for_template());
        $this->assertCount(2, menu::export_for_template(true)['tabs']);
    }

    /**
     * The export for the editor preview contains the paths of the elements and keeps links without target.
     */
    public function test_export_for_editor(): void {
        $menu = $this->menu();
        $menu = editor::upsert($menu, ['tabs', 'phd', 'blocks', 'b2', 'links'], ['id' => 'empty', 'type' => 'url']);
        menu::save($menu, 0, true);

        $export = menu::export_for_template(true, true, 'postdocs');
        $this->assertFalse($export['tabs'][0]['active']);
        $this->assertTrue($export['tabs'][0]['default']);
        $this->assertTrue($export['tabs'][1]['active']);
        $this->assertSame('["tabs","phd"]', $export['tabs'][0]['editpath']);

        [$grid, $chips] = $export['tabs'][0]['blocks'];
        $this->assertSame('linkgrid-0', $grid['animkey']);
        $this->assertSame('chips-0', $chips['animkey']);
        $this->assertSame('["tabs","phd","blocks","b1","links","l2"]', $grid['links'][1]['editpath']);
        $this->assertTrue($chips['links'][0]['incomplete']);

        // The live menu leaves out links without target and has no paths.
        menu::publish(menu::get(true)['revision']);
        $export = menu::export_for_template();
        $this->assertSame([], $export['tabs'][0]['blocks'][1]['links']);
        $this->assertSame('', $export['tabs'][0]['editpath']);
    }

    /**
     * The link form adds a link to the draft and returns the editor structure.
     */
    public function test_link_form(): void {
        $this->setAdminUser();
        menu::save($this->menu(), 0, true);
        $path = json_encode(['tabs', 'phd', 'blocks', 'b2', 'links']);

        $formdata = [
            'path' => $path,
            'revision' => 1,
            'label_en' => 'Funding',
            'type' => 'search',
            'search' => 'Funding Your PhD',
            'url' => '',
        ];
        $formdata = megamenu_link_form::mock_ajax_submit($formdata);
        $form = new megamenu_link_form(null, null, 'post', '', [], true, $formdata);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $structure = $form->process_dynamic_submission();

        $link = editor::get(menu::get(true), ['tabs', 'phd', 'blocks', 'b2'])['links'][0];
        $this->assertSame(['en' => 'Funding'], $link['label']);
        $this->assertSame('Funding Your PhD', $link['search']);
        $this->assertSame(2, $structure['revision']);
        $this->assertTrue($structure['tabs'][0]['selected']);
    }

    /**
     * The tab form stores the icon and the colour of an entry.
     */
    public function test_tab_form_icon(): void {
        $this->setAdminUser();
        menu::save($this->menu(), 0, true);

        $formdata = [
            'path' => json_encode(['tabs', 'phd']),
            'revision' => 1,
            'type' => 'panel',
            'label_en' => 'PhD',
            'color' => 'petrol',
            'icon' => 'owl',
            'newsid' => 0,
        ];
        $formdata = megamenu_tab_form::mock_ajax_submit($formdata);
        $form = new megamenu_tab_form(null, null, 'post', '', [], true, $formdata);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $form->process_dynamic_submission();

        $tab = editor::get(menu::get(true), ['tabs', 'phd']);
        $this->assertSame('petrol', $tab['color']);
        $this->assertSame('owl', $tab['icon']);
        // The blocks are kept by the upsert.
        $this->assertSame(['b1', 'b2'], array_column($tab['blocks'], 'id'));
    }

    /**
     * The web service moves elements, checks the revision and the capability.
     */
    public function test_webservice(): void {
        $this->setAdminUser();
        menu::save($this->menu(), 0, true);

        $result = megamenu_edit::execute('move', 1, json_encode(['tabs', 'postdocs']), json_encode(['tabs']), 0);
        $result = external_api::clean_returnvalue(megamenu_edit::execute_returns(), $result);
        $structure = json_decode($result['structure'], true);
        $this->assertSame(['postdocs', 'phd'], array_column($structure['tabs'], 'id'));
        $this->assertSame(2, $structure['revision']);

        try {
            megamenu_edit::execute('delete', 1, json_encode(['tabs', 'phd']));
            $this->fail('Outdated revision accepted');
        } catch (moodle_exception $e) {
            $this->assertSame('megamenu:conflict', $e->errorcode);
        }

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(required_capability_exception::class);
        megamenu_edit::execute('discard', 2);
    }
}
