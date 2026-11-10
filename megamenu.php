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
 * Editor for the mega menu.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_urise\megamenu\editor;

require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();
$url = new moodle_url('/local/urise/megamenu.php');

require_login(0, false);
$PAGE->set_context($context);
$PAGE->set_url($url);
require_capability('local/urise:canedit', $context);

$title = get_string('megamenu:editor', 'local_urise');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('dashboard', 'local_urise'), new moodle_url('/local/urise/dashboard.php'));
$PAGE->navbar->add($title, $url);

$structure = editor::export_structure(optional_param('tab', '', PARAM_ALPHANUMEXT));
$structure['componentpicker'] = editor::export_component_picker();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_urise/megamenu_editor', $structure);
echo $OUTPUT->footer();
