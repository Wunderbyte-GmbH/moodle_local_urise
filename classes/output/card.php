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
 * This file contains the definition for the renderable classes for the booking instance
 *
 * @package   local_urise
 * @copyright 2024 Georg Maißer {@link http://www.wunderbyte.at}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_urise\output;

use moodle_url;
use renderer_base;
use renderable;
use stdClass;
use templatable;

/**
 * This class prepares data for displaying a booking option instance
 *
 * @package local_urise
 * @copyright 2024 Georg Maißer {@link http://www.wunderbyte.at}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class card implements renderable, templatable {
    /** @var string $title */
    public $title = '';

    /** @var string $content */
    public $content = '';

    /** @var string $footer */
    public $footer = '';

     /** @var moodle_url $img */
     public $img = null;

     /** @var moodle_url $img */
     public $link = null;

     /** @var string $headerbgcolor */
     public $headerbgcolor = '';

    /**
     * Constructor.
     */
    public function construct($title = null, $content = null, $footer = null, $headerbgcolor = "bg-primary") {

        $this->title = $title ?? "dummy title";
        $this->content = $content ?? "dummy content";
        $this->footer = $footer ?? "dummy footer";
        $this->headerbgcolor = $headerbgcolor;
    }

    /**
     * Export for template.
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {

        $returnarray = [
                'title' => $this->title,
                'content' => $this->content,
                'footer' => $this->footer,
                'headerbgcolor' => $this->headerbgcolor,
        ];

        return $returnarray;
    }
}
