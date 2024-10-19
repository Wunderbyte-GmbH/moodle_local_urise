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

/*
 * @package    local_urise
 * @author     Bernhard Fischer
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Modal form to manage booking option tags (botags).
 *
 * @module     local_urise
 * @copyright  2024 Wunderbyte GmbH
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import DynamicForm from 'core_form/dynamicform';

const SELECTORS = {
    USERSELECTORFORM: '[data-id="urise-selectuserformcontainer"]',
};

/**
 * Init the user selector form.
 *
 */
export function init() {

    const element = document.querySelector(SELECTORS.USERSELECTORFORM);

    // Initialize the form.
    const dynamicForm = new DynamicForm(
        element,
        'local_shopping_cart\\form\\dynamic_select_users'
    );

    // When form is submitted - remove it from DOM:
    dynamicForm.addEventListener(dynamicForm.events.FORM_SUBMITTED, e => {
        const response = e.detail;

        if (response.redirecturl) {

            // We use the class from shopping cart, so we need to change the url here.
            let url = new URL(response.redirecturl);

            // Extract the search parameters (query string)
            let params = url.search;

            // Base URL
            let baseUrl = '/local/urise/meinekurse.php';

            // Append the parameters to the base URL
            let newUrl = baseUrl + params;

            location.href = newUrl;
        } else {
            dynamicForm.load();
        }
    });

    dynamicForm.load();
}
