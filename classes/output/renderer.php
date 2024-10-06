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

namespace local_urise\output;

use mod_booking\output\bookingoption_description;
use mod_booking\output\renderer as OutputRenderer;
use plugin_renderer_base;


/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the booking module.
 *
 * @package local_urise
 * @copyright 2024 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Function to print booking option single view on optionview.php
     * @param bookingoption_description $data
     * @return string
     */
    public function render_bookingoption_description_view(bookingoption_description $data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('local_urise/bookingoption_description_view', $data);
        return $o;
    }

    /**
     * Function to render the dashboard
     * @param dashboard $data
     * @return string
     */
    public function render_dashboard(dashboard $data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('local_urise/dashboard', $data);
        return $o;
    }

    /**
     * Function to render the card_content_stats1
     * @param mixed $data
     * @return string
     */
    public function render_card_content($data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('local_urise/dashboard_card_content', $data);
        return $o;
    }

    /** Function to render the cards table
     * @param any $data
     * @param string $data
     * @return string
     */
    public function render_userinformation($data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('local_urise/userinformation', $data);
        return $o;
    }

    /** Function to render the cards table
     * @param any $data
     * @param string $data
     * @return string
     */
    public function render_table($data, string $templatename) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template($templatename, $data);
        return $o;
    }

    public function render_col_availableplaces($data) {
        $o = '';
        $templatedata = $data->export_for_template($this);
        $templatedata['showmaxanswers'] = $data->showmaxanswers;
        $o .= $this->render_from_template('local_urise/col_availableplaces', $templatedata);
        return $o;
    }

    /**
     * Render function.
     * @param object $data
     * @return string
     */
    public function render_col_coursestarttime($data) {
        $o = '';
        // Check if multiple dates.
        $data = $data->export_for_template($this);
        if (!empty($data['datestrings']) && count($data['datestrings']) > 1) {
            $data['firstDate'] = $data['datestrings'][0]['datestring'];
        }
        $o .= $this->render_from_template('local_urise/col_coursestarttime', $data);
        return $o;

    }

    /** Function to render the teacher column.
     * @param any $data
     * @return string
     */
    public function render_col_teacher($data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('local_urise/col_teacher', $data);
        return $o;
    }

    /** Function to render the overview cards in user dashboard
     * @param any $data
     * @param string $data
     * @return string
     */
    public function render_user_dashboard_overview($data) {
        $o = '';
        $o .= $this->render_from_template('local_urise/userdashboardoverview', $data);
        return $o;
    }

    /** Function to render the transactions list.
     * @param any $data
     * @return string
     */
    public function render_transactions_list($page): string {
        $o = '';
        $data = $page->export_for_template($this);
        $o .= $this->render_from_template('local_urise/transactions_list', $data);
        return $o;
    }

    /**
     * Function to render booking option menu for local_urise.
     * @param any $data
     * @return string
     */
    public function render_urise_bookingoption_menu($data): string {
        $o = '';
        $data = (array)$data;
        $o .= $this->render_from_template('local_urise/urise_bookingoption_menu', $data);
        return $o;
    }

}
