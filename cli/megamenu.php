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
 * Exports, imports or seeds the mega menu.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_urise\megamenu\menu;
use local_urise\megamenu\seed;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    ['export' => false, 'import' => '', 'seed' => false, 'force' => false, 'help' => false],
    ['e' => 'export', 'i' => 'import', 's' => 'seed', 'f' => 'force', 'h' => 'help']
);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if ($options['help'] || !($options['export'] || $options['import'] || $options['seed'])) {
    echo "Exports, imports or seeds the mega menu of local_urise.

Options:
-e, --export         Print the current menu as JSON.
-i, --import=FILE    Replace the menu with the JSON in FILE.
-s, --seed           Replace the menu with the example content from the screen design.
-f, --force          Allow --import and --seed to replace a menu that already has tabs.
-h, --help           Print this help.

Example:
\$ php local/urise/cli/megamenu.php --export > megamenu.json
\$ php local/urise/cli/megamenu.php --import=megamenu.json --force
";
    exit(0);
}

$current = menu::get();

if ($options['export']) {
    echo json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

if (!empty($current['tabs']) && !$options['force']) {
    cli_error('The mega menu already has ' . count($current['tabs']) . ' tabs. Use --force to replace it.');
}

if ($options['seed']) {
    $new = seed::get();
} else {
    if (!is_readable($options['import'])) {
        cli_error("Cannot read {$options['import']}.");
    }
    $new = json_decode(file_get_contents($options['import']), true);
    if (!is_array($new)) {
        cli_error("{$options['import']} does not contain valid JSON.");
    }
}

$saved = menu::save($new, $current['revision']);
cli_writeln('Saved mega menu revision ' . $saved['revision'] . ' with ' . count($saved['tabs']) . ' tabs.');
