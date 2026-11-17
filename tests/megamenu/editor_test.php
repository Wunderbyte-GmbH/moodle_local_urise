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
use mod_booking\customfield\hierarchy_manager;
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
     * The tab form stores the base filter and keeps its values when it is switched off.
     */
    public function test_tab_form_base_filter(): void {
        $this->setAdminUser();
        $this->create_filter_field();
        menu::save($this->menu(), 0, true);

        $submit = function (array $values, int $revision): megamenu_tab_form {
            $formdata = megamenu_tab_form::mock_ajax_submit($values + [
                'path' => json_encode(['tabs', 'phd']),
                'revision' => $revision,
                'type' => 'panel',
                'label_en' => 'PhD',
                'color' => 'blue',
                'icon' => '',
                'newsid' => 0,
            ]);
            $form = new megamenu_tab_form(null, null, 'post', '', [], true, $formdata);
            $form->set_data_for_dynamic_submission();
            return $form;
        };

        $form = $submit(['basefilterenabled' => 1, 'basefiltercolumn' => 'format'], 1);
        $this->assertFalse($form->is_validated());

        $form = $submit(['basefilterenabled' => 1, 'basefiltercolumn' => 'format', 'basefilter_format' => ['2']], 1);
        $this->assertTrue($form->is_validated());
        $structure = $form->process_dynamic_submission();
        $this->assertSame(
            ['enabled' => true, 'filters' => ['format' => ['2']]],
            editor::get(menu::get(true), ['tabs', 'phd'])['basefilter']
        );
        $this->assertStringContainsString(get_string('hybrid', 'local_urise'), $structure['tabs'][0]['basefilter']);

        $form = $submit(['basefilterenabled' => 0], 2);
        $this->assertTrue($form->is_validated());
        $structure = $form->process_dynamic_submission();
        $this->assertSame(
            ['enabled' => false, 'filters' => ['format' => ['2']]],
            editor::get(menu::get(true), ['tabs', 'phd'])['basefilter']
        );
        $this->assertSame('', $structure['tabs'][0]['basefilter']);
    }

    /**
     * The link form offers to ignore the base filter of its tab.
     */
    public function test_link_form_ignores_base_filter(): void {
        $this->setAdminUser();
        $this->create_filter_field();
        $menu = $this->menu();
        $menu['tabs'][0]['basefilter'] = ['enabled' => true, 'filters' => ['format' => ['2']]];
        menu::save($menu, 0, true);

        $formdata = megamenu_link_form::mock_ajax_submit([
            'path' => json_encode(['tabs', 'phd', 'cta']),
            'revision' => 1,
            'label_en' => 'All in person',
            'type' => 'filter',
            'filtercolumn' => 'format',
            'filter_format' => ['1'],
            'nobasefilter' => 1,
        ]);
        $form = new megamenu_link_form(null, null, 'post', '', [], true, $formdata);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $form->process_dynamic_submission();

        $cta = editor::get(menu::get(true), ['tabs', 'phd', 'cta']);
        $this->assertTrue($cta['nobasefilter']);
        $this->assertSame(['format' => ['1']], $cta['filters']);
    }

    /**
     * The options come from the catalogue until mod_booking stores a hierarchy with the field.
     */
    public function test_filter_sources(): void {
        $this->setAdminUser();
        $this->create_filter_field();
        $fieldid = $this->create_competence_field();

        $columns = filteroptions::get_columns();
        $this->assertSame('catalogue', $columns['format']['source']);
        $this->assertSame(get_string('hybrid', 'local_urise'), $columns['format']['options']['2']);
        $this->assertSame([], $columns['format']['groups']);
        $this->assertSame('catalogue', $columns['kompetenzen']['source']);
        $this->assertSame(
            ['1', '2', '3', '4', '5', '6', '7'],
            $columns['kompetenzen']['groups'][get_string('lehrkompetenzen', 'local_urise')]
        );

        if (!class_exists(hierarchy_manager::class)) {
            $this->markTestSkipped('mod_booking has no hierarchy manager');
        }
        hierarchy_manager::save($fieldid, [
            ['id' => 1, 'label' => 'Research', 'parentid' => 0],
            ['id' => 2, 'label' => 'Writing', 'parentid' => 1],
            ['id' => 3, 'label' => 'Publishing', 'parentid' => 1],
        ]);
        filteroptions::reset();
        $column = filteroptions::get_columns()['kompetenzen'];
        $this->assertSame('hierarchy', $column['source']);
        $this->assertSame(['1' => 'Research', '2' => 'Writing', '3' => 'Publishing'], $column['options']);
        $this->assertSame(['Research' => ['1', '2', '3']], $column['groups']);
    }

    /**
     * The shortcode on the catalogue page decides which filters the editor offers.
     */
    public function test_filters_follow_shortcode(): void {
        $this->setAdminUser();
        $this->create_filter_field();
        $this->create_competence_field();
        $this->assertSame(['kompetenzen', 'format'], array_keys(filteroptions::get_columns()));

        $category = $this->getDataGenerator()->create_category();
        $this->getDataGenerator()->create_block('html', [
            'parentcontextid' => \context_coursecat::instance($category->id)->id,
            'configdata' => base64_encode(serialize((object)[
                'text' => '<p>[unifiedcards perpage=4]</p>[unifiedcards filter=1 onlyfilterforcolumns="kompetenzen,coursetime"]',
            ])),
        ]);
        $menu = $this->menu();
        $menu['catalogueurl'] = '/course/index.php?categoryid=' . $category->id;
        menu::save($menu, 0, true);
        filteroptions::reset();

        $this->assertSame(['kompetenzen'], array_keys(filteroptions::get_columns()));
        $this->assertSame([], filteroptions::get_loadable_columns());
    }

    /**
     * A group chosen in the link form is stored as its options.
     */
    public function test_link_form_expands_groups(): void {
        $this->setAdminUser();
        $this->create_competence_field();
        menu::save($this->menu(), 0, true);

        $choices = filteroptions::get_form_options(filteroptions::get_columns()['kompetenzen']);
        $this->assertSame(
            get_string('megamenu:wholegroup', 'local_urise', get_string('lehrkompetenzen', 'local_urise')),
            $choices['group:0']
        );

        $formdata = megamenu_link_form::mock_ajax_submit([
            'path' => json_encode(['tabs', 'phd', 'cta']),
            'revision' => 1,
            'label_en' => 'Teaching',
            'type' => 'filter',
            'filtercolumn' => 'kompetenzen',
            'filter_kompetenzen' => ['group:0', '7', '8'],
        ]);
        $form = new megamenu_link_form(null, null, 'post', '', [], true, $formdata);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $form->process_dynamic_submission();

        $this->assertSame(
            ['kompetenzen' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            editor::get(menu::get(true), ['tabs', 'phd', 'cta'])['filters']
        );
    }

    /**
     * Creates the booking option custom field "kompetenzen".
     *
     * @return int The id of the field.
     */
    private function create_competence_field(): int {
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');
        $category = $generator->create_category(['component' => 'mod_booking', 'area' => 'booking']);
        $field = $generator->create_field([
            'categoryid' => $category->get('id'),
            'shortname' => 'kompetenzen',
            'name' => 'Competences',
            'type' => 'dynamicformat',
            'configdata' => ['dynamicsql' => "SELECT '8' AS id, 'Writing' AS data", 'multiselect' => 1],
        ]);
        \cache::make('mod_booking', 'customfields')->purge();
        filteroptions::reset();
        return $field->get('id');
    }

    /**
     * Creates the booking option custom field "format" with two options.
     */
    private function create_filter_field(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');
        $category = $generator->create_category(['component' => 'mod_booking', 'area' => 'booking']);
        $generator->create_field([
            'categoryid' => $category->get('id'),
            'shortname' => 'format',
            'name' => 'Format',
            'type' => 'select',
            'configdata' => ['options' => "In person\nOnline"],
        ]);
        \cache::make('mod_booking', 'customfields')->purge();
        filteroptions::reset();
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

    /**
     * The tab form adds a new colour and an uploaded icon, which is cleaned.
     */
    public function test_tab_form_new_color_and_icon(): void {
        global $USER;

        $this->setAdminUser();
        menu::save($this->menu(), 0, true);

        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" onload="alert(1)">'
            . '<script>alert(1)</script><style>.a{fill:red}</style>'
            . '<defs><clipPath id="c"><rect width="24" height="24"/></clipPath></defs>'
            . '<g clip-path="url(#c)"><path d="M2 2L22 22" style="stroke:#ff0000;fill:none" onclick="x()"/>'
            . '<a href="javascript:alert(1)"><circle cx="12" cy="12" r="4" fill="url(#g)"/></a></g>'
            . '<image href="https://example.com/x.png"/><text>Hi</text></svg>';
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'Mein Icon.svg',
        ], $svg);

        $formdata = [
            'path' => json_encode(['tabs', 'phd']),
            'revision' => 1,
            'type' => 'panel',
            'label_en' => 'PhD',
            'color' => 'new',
            'newcolor' => '#F1B3B4',
            'icon' => 'new',
            'newiconfile' => $draftitemid,
            'newsid' => 0,
        ];
        $form = new megamenu_tab_form(null, null, 'post', '', [], true, megamenu_tab_form::mock_ajax_submit($formdata));
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $structure = $form->process_dynamic_submission();

        $menu = menu::get(true);
        $tab = editor::get($menu, ['tabs', 'phd']);
        $this->assertSame('xf1b3b4', $tab['color']);
        $this->assertSame('#F1B3B4', $structure['tabs'][0]['colorname']);
        $this->assertStringContainsString('.lexa-mm-color-xf1b3b4{--lexa-mm-accent:#F1B3B4;', $structure['customcss']);

        $this->assertSame([$tab['icon']], array_keys($menu['icons']));
        $icon = $menu['icons'][$tab['icon']];
        $this->assertSame('Mein Icon', $icon['name']);
        foreach (['script', 'style', 'onload', 'onclick', 'href', 'image', 'text', 'ff0000', 'alert'] as $unwanted) {
            $this->assertStringNotContainsString($unwanted, $icon['svg']);
        }
        $this->assertStringContainsString('viewBox="0 0 24 24"', $icon['svg']);
        $this->assertStringContainsString('stroke="white"', $icon['svg']);
        $this->assertStringContainsString('clip-path="url(#' . $tab['icon'] . '-1)"', $icon['svg']);

        $exported = menu::export_for_template(true);
        $this->assertSame($icon['svg'], $exported['tabs'][0]['iconsvg']);
        // The text colour is darkened until it is readable on the tint.
        $this->assertSame('#F1B3B4', $exported['tabs'][0]['colors']['accent']);
        $this->assertNotSame('#F1B3B4', $exported['tabs'][0]['colors']['text']);

        // The new choices are offered to the other entries, and dropped when no entry uses them any more.
        $form = new megamenu_tab_form(null, null, 'post', '', [], true, ['path' => json_encode(['tabs', 'phd'])]);
        $html = $form->render();
        $this->assertStringContainsString('value="xf1b3b4"', $html);
        $this->assertStringContainsString('value="' . $tab['icon'] . '"', $html);

        $menu['tabs'][0]['icon'] = 'owl';
        $saved = menu::save($menu, $menu['revision'], true);
        $this->assertSame([], $saved['icons']);
    }

    /**
     * A new colour or icon that is not usable is refused.
     */
    public function test_tab_form_refuses_invalid_appearance(): void {
        $this->setAdminUser();
        menu::save($this->menu(), 0, true);

        $formdata = [
            'path' => json_encode(['tabs', 'phd']),
            'revision' => 1,
            'type' => 'panel',
            'label_en' => 'PhD',
            'color' => 'new',
            'newcolor' => 'red;}body{display:none',
            'icon' => 'new',
            'newiconfile' => file_get_unused_draft_itemid(),
            'newsid' => 0,
        ];
        $form = new megamenu_tab_form(null, null, 'post', '', [], true, megamenu_tab_form::mock_ajax_submit($formdata));
        $form->set_data_for_dynamic_submission();
        $this->assertFalse($form->is_validated());

        $this->assertNull(\local_urise\megamenu\appearance::import_icon('<html><body>no</body></html>', 'x'));
        $this->assertNull(\local_urise\megamenu\appearance::import_icon('<svg xmlns="http://www.w3.org/2000/svg"/>', 'x'));
        $this->assertSame('', \local_urise\megamenu\appearance::css(['blue', 'x12', 'x</style>']));
    }

    /**
     * "Add colour" shows the form again with the new colour in the list and selected, saving keeps it.
     */
    public function test_tab_form_add_buttons(): void {
        global $USER;

        $this->setAdminUser();
        menu::save($this->menu(), 0, true);
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'Stern.svg',
        ], '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 2L22 22" stroke="black"/></svg>');

        $formdata = [
            'path' => json_encode(['tabs', 'phd']),
            'revision' => 1,
            'type' => 'panel',
            'label_en' => 'PhD',
            'color' => 'new',
            'newcolor' => '#8a2be2',
            'icon' => 'new',
            'newiconfile' => $draftitemid,
            'newsid' => 0,
            'addcolor' => 'x',
            'addicon' => 'x',
        ];
        $form = new megamenu_tab_form(null, null, 'post', '', [], true, megamenu_tab_form::mock_ajax_submit($formdata));
        $form->set_data_for_dynamic_submission();
        $html = $form->render();
        $this->assertMatchesRegularExpression('/value="x8a2be2"[^>]*checked|checked[^>]*value="x8a2be2"/', $html);
        $this->assertMatchesRegularExpression('/name="pendingicons"[^>]*value="\{&quot;(u[0-9a-f]{10})&quot;/', $html);
        $this->assertSame(2, substr_count($html, 'alert-success'));
        $this->assertStringContainsString('#8A2BE2', $html);
        $this->assertStringContainsString('Stern', $html);
        // Nothing is saved before the dialog is saved.
        $this->assertSame('blue', editor::get(menu::get(true), ['tabs', 'phd'])['color']);

        preg_match('/name="pendingicons"[^>]*value="([^"]*)"/', $html, $matches);
        $pendingicons = html_entity_decode($matches[1]);
        $iconid = array_key_first(json_decode($pendingicons, true));
        unset($formdata['addcolor'], $formdata['addicon']);
        $formdata = ['color' => 'x8a2be2', 'icon' => $iconid, 'pendingcolors' => 'x8a2be2', 'pendingicons' => $pendingicons]
            + $formdata;
        $form = new megamenu_tab_form(null, null, 'post', '', [], true, megamenu_tab_form::mock_ajax_submit($formdata));
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $form->process_dynamic_submission();

        $menu = menu::get(true);
        $tab = editor::get($menu, ['tabs', 'phd']);
        $this->assertSame('x8a2be2', $tab['color']);
        $this->assertSame($iconid, $tab['icon']);
        $this->assertSame('Stern', $menu['icons'][$iconid]['name']);
    }
}
