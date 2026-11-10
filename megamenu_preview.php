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

/**
 * Preview of the mega menu draft, shown in the editor. Clicking an element opens its form in the editor.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_urise\megamenu\menu;

require_once(__DIR__ . '/../../config.php');

$tab = optional_param('tab', '', PARAM_ALPHANUMEXT);
// The desktop panel, or the mobile drawer (view=mobile).
$view = optional_param('view', 'desktop', PARAM_ALPHA) === 'mobile' ? 'mobile' : 'desktop';
$context = context_system::instance();

require_login(0, false);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/urise/megamenu_preview.php', ['tab' => $tab, 'view' => $view]));
require_capability('local/urise:canedit', $context);

$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('megamenu:preview', 'local_urise'));
$PAGE->add_body_class('local-urise-megamenu-preview');
$PAGE->add_body_class('local-urise-megamenu-preview-' . $view);

$megamenu = menu::export_for_template(true, true, $tab);
// The mega menu is rendered by theme_lexa.
$template = core_component::get_component_directory('theme_lexa') . '/templates/'
    . ($view === 'mobile' ? 'megamenu_mobile.mustache' : 'megamenu.mustache');

echo $OUTPUT->header();
if (empty($megamenu)) {
    echo $OUTPUT->notification(get_string('megamenu:empty', 'local_urise'), 'info', false);
} else if (!file_exists($template)) {
    echo $OUTPUT->notification(get_string('megamenu:nopreview', 'local_urise'), 'warning', false);
} else if ($view === 'mobile') {
    $megamenu['menulabel'] = get_string('megamenu:preview', 'local_urise');
    $megamenu['sitename'] = format_string($SITE->shortname, true, ['context' => $context]);
    $megamenu['logourl'] = (string)$OUTPUT->get_compact_logo_url(200, 40);
    echo $OUTPUT->render_from_template('local_urise/megamenu_preview_mobile', $megamenu);
    $PAGE->requires->js_call_amd('theme_lexa/megamenu', 'init');
    $PAGE->requires->js_call_amd('local_urise/megamenu_preview', 'init', ['mobile']);
} else {
    $megamenu['preview'] = true;
    $megamenu['label'] = get_string('megamenu:preview', 'local_urise');
    echo $OUTPUT->render_from_template('theme_lexa/megamenu', $megamenu);
    $PAGE->requires->js_call_amd('local_urise/megamenu_preview', 'init', ['desktop']);
}
echo $OUTPUT->footer();
