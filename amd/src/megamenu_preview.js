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
 * The mega menu preview inside the editor (megamenu_preview.php in an iframe of megamenu.php).
 *
 * Clicking an element asks the editor to open its form, switching a community is reported to the editor,
 * and the editor can switch communities. Links don't navigate.
 *
 * @module     local_urise/megamenu_preview
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {showTab, showMobileTab} from 'theme_lexa/megamenu';
import {getStrings} from 'core/str';

/** @var {string} Prefix of the message types exchanged with the editor. */
export const MESSAGEPREFIX = 'local_urise_megamenu_';

/** @var {Array} Component names shown on hover, by the class of the element. */
const COMPONENTS = [
    ['lexa-megamenu-heading', 'heading'],
    ['lexa-megamenu-intro', 'intro'],
    ['lexa-megamenu-hint', 'hint'],
    ['lexa-megamenu-card-header', 'card'],
    ['lexa-megamenu-cardlink', 'cardlink'],
    ['lexa-megamenu-blocktitle', 'blocktitle'],
    ['lexa-megamenu-chipstitle', 'chipstitle'],
    ['lexa-megamenu-topiclink', 'topiclink'],
    ['lexa-megamenu-chip', 'chip'],
    ['lexa-megamenu-news', 'news'],
    ['lexa-megamenu-cta', 'cta'],
    ['lexa-megamenu-secondary', 'secondary'],
    ['lexa-megamenu-navitem', 'navlink'],
    // Mobile.
    ['lexa-mm-mobile-title', 'tabtitle'],
    ['lexa-mm-mobile-intro', 'intro'],
    ['lexa-mm-mobile-cardheader', 'card'],
    ['lexa-mm-mobile-blockheader', 'blocktitle'],
    ['lexa-mm-mobile-cardlink', 'cardlink'],
    ['lexa-mm-mobile-listlink', 'topiclink'],
    ['lexa-mm-mobile-row', 'navlink'],
];

/** @var {string} 'desktop' or 'mobile'. */
let view = 'desktop';

let names = {};
let label = null;

/**
 * Shows the component name above the element under the mouse.
 *
 * @param {MouseEvent} e
 */
const handleMouseover = (e) => {
    const editable = e.target.closest('[data-editpath]');
    const component = editable && COMPONENTS.find(([cssclass]) => editable.classList.contains(cssclass));
    if (!component) {
        label.hidden = true;
        return;
    }
    const rect = editable.getBoundingClientRect();
    label.textContent = names[component[1]];
    label.style.left = `${rect.left + window.scrollX}px`;
    label.style.top = `${rect.top + window.scrollY}px`;
    label.hidden = false;
};

/**
 * Sends a message to the editor.
 *
 * @param {string} type
 * @param {Object} data
 */
const post = (type, data = {}) => {
    window.parent.postMessage(Object.assign({type: MESSAGEPREFIX + type}, data), window.location.origin);
};

/**
 * Handles clicks before the mega menu does.
 *
 * @param {MouseEvent} e
 */
const handleClick = (e) => {
    const editable = e.target.closest('[data-editpath]');
    if (editable) {
        e.preventDefault();
        e.stopPropagation();
        post('edit', {form: editable.dataset.editform, path: editable.dataset.editpath});
        return;
    }
    const select = e.target.closest('[data-action="lexa-megamenu-select"], [data-action="lexa-megamenu-drill"]');
    if (select) {
        // The mega menu switches the panel itself.
        post('select', {tab: select.dataset.tab});
        return;
    }
    if (e.target.closest('a')) {
        e.preventDefault();
    }
};

/**
 * Handles messages of the editor.
 *
 * @param {MessageEvent} e
 */
const handleMessage = (e) => {
    if (e.source !== window.parent || e.origin !== window.location.origin) {
        return;
    }
    if (e.data.type === MESSAGEPREFIX + 'select') {
        if (view === 'mobile') {
            showMobileTab(e.data.tab);
        } else {
            showTab(e.data.tab);
        }
    }
};

/**
 * Initialises the preview.
 *
 * @param {string} previewview 'desktop' or 'mobile'.
 */
export const init = async(previewview = 'desktop') => {
    view = previewview;
    if (window.parent === window) {
        return;
    }
    document.addEventListener('click', handleClick, true);
    window.addEventListener('message', handleMessage);

    const strings = await getStrings(COMPONENTS.map(([, key]) => ({key: 'megamenu:component_' + key, component: 'local_urise'})));
    names = Object.fromEntries(COMPONENTS.map(([, key], index) => [key, strings[index]]));
    label = document.createElement('div');
    label.className = 'mm-preview-label';
    label.hidden = true;
    document.body.append(label);
    document.addEventListener('mouseover', handleMouseover);

    const reportHeight = () => post('height', {height: document.documentElement.scrollHeight});
    new ResizeObserver(reportHeight).observe(document.body);
    reportHeight();
};
