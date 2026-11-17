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

namespace local_urise;

use core\hook\output\before_standard_head_html_generation;
use local_urise\megamenu\appearance;
use local_urise\megamenu\menu;

/**
 * Hook callbacks of local_urise.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Adds the styles of the custom colours of the mega menu, live and draft.
     *
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        $colors = array_merge(appearance::custom_colors(menu::get()), appearance::custom_colors(menu::get(true)));
        $css = appearance::css($colors);
        if ($css !== '') {
            $hook->add_html('<style>' . $css . '</style>');
        }
    }
}
