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
 * Editor of the mega menu draft.
 *
 * @module     local_urise/megamenu_editor
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import Modal from 'core/modal';
import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import SortableList from 'core/sortable_list';
import Templates from 'core/templates';
import {getString} from 'core/str';

const SELECTORS = {
    STRUCTURE: '[data-region="megamenu-editor-structure"]',
    PREVIEW: '[data-region="megamenu-preview"]',
    FRAME: '[data-region="megamenu-preview-frame"]',
    VIEW: '[data-action="mm-view"]',
    DATA: '[data-region="megamenu-editor-data"]',
    TABCONTENT: '[data-region="mm-tabcontent"]',
    PICKER: '[data-region="megamenu-picker"]',
    ITEM: '[data-path]',
};

/** @var {string[]} Lists that can be sorted, each with its own drag handle. */
const LISTTYPES = ['tabs', 'blocks', 'cards', 'links', 'secondarylinks'];

const FORMS = {
    tab: 'local_urise\\form\\megamenu_tab_form',
    block: 'local_urise\\form\\megamenu_block_form',
    card: 'local_urise\\form\\megamenu_card_form',
    link: 'local_urise\\form\\megamenu_link_form',
    settings: 'local_urise\\form\\megamenu_settings_form',
};

/** @var {string} Prefix of the messages exchanged with the preview, see local_urise/megamenu_preview. */
const MESSAGEPREFIX = 'local_urise_megamenu_';

/** @var {Object} Width the preview is rendered at per view. The desktop is scaled to fit, the phone is shown 1:1. */
const FRAMEWIDTHS = {desktop: 1420, mobile: 375};

/** @var {number} Height of the phone preview; its content scrolls. */
const PHONEHEIGHT = 812;

/** @var {string} Where the chosen view is remembered. */
const VIEWSTORAGE = 'local_urise_megamenu_previewview';

let root = null;
let selectedTab = '';
let frameHeight = 640;
let view = 'desktop';

/**
 * Returns the current draft revision.
 *
 * @returns {number}
 */
const getRevision = () => parseInt(root.querySelector(SELECTORS.DATA).dataset.revision, 10);

/**
 * Returns the preview frame.
 *
 * @returns {HTMLIFrameElement|null}
 */
const getFrame = () => root.querySelector(SELECTORS.FRAME);

/**
 * Scales the preview to the width of the editor.
 */
const scalePreview = () => {
    const frame = getFrame();
    if (!frame) {
        return;
    }
    const wrapper = root.querySelector(SELECTORS.PREVIEW);
    wrapper.classList.toggle('mm-view-mobile', view === 'mobile');
    const width = FRAMEWIDTHS[view];
    if (view === 'mobile') {
        frame.style.width = `${width}px`;
        frame.style.height = `${PHONEHEIGHT}px`;
        frame.style.transform = '';
        frame.scrolling = 'yes';
        wrapper.style.height = '';
        return;
    }
    const scale = Math.min(1, wrapper.clientWidth / width);
    frame.scrolling = 'no';
    frame.style.width = `${width}px`;
    frame.style.height = `${frameHeight}px`;
    frame.style.transform = `scale(${scale})`;
    wrapper.style.height = `${Math.ceil(frameHeight * scale)}px`;
};

/**
 * Loads the preview again, showing the selected tab.
 */
const reloadPreview = () => {
    const frame = getFrame();
    if (frame) {
        const url = new URL(frame.dataset.src);
        url.searchParams.set('tab', selectedTab);
        url.searchParams.set('view', view);
        frame.src = url.toString();
    }
};

/**
 * Switches the preview between desktop and phone.
 *
 * @param {string} newview 'desktop' or 'mobile'.
 */
const setView = (newview) => {
    view = FRAMEWIDTHS[newview] ? newview : 'desktop';
    root.querySelectorAll(SELECTORS.VIEW).forEach((button) => {
        const active = button.dataset.view === view;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    try {
        window.localStorage.setItem(VIEWSTORAGE, view);
    } catch (e) {
        // Storage may be unavailable, the view is then not remembered.
    }
    scalePreview();
    reloadPreview();
};

/**
 * Shows the content of a tab.
 *
 * @param {string} tab
 * @param {boolean} fromPreview Whether the tab was chosen in the preview, which already shows it.
 */
const selectTab = (tab, fromPreview = false) => {
    if (!fromPreview && tab !== selectedTab) {
        const frame = getFrame();
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage({type: MESSAGEPREFIX + 'select', tab}, window.location.origin);
        }
    }
    selectedTab = tab;
    root.querySelectorAll(SELECTORS.TABCONTENT).forEach((content) => {
        content.hidden = content.dataset.tab !== tab;
    });
    root.querySelectorAll('[data-listtype="tabs"] > li').forEach((item) => {
        const button = item.querySelector('[data-action="mm-select"]');
        item.classList.toggle('selected', !!button && button.dataset.tab === tab);
    });
};

/**
 * Renders the structure again.
 *
 * @param {Object} structure
 * @param {boolean} keepTab Whether to keep the selected tab, instead of the one the structure selects.
 */
const render = async(structure, keepTab = true) => {
    const {html, js} = await Templates.renderForPromise('local_urise/megamenu_editor_structure', structure);
    Templates.replaceNodeContents(root.querySelector(SELECTORS.STRUCTURE), html, js);

    const tabs = structure.tabs.map((tab) => tab.id);
    if (keepTab && tabs.includes(selectedTab)) {
        selectTab(selectedTab, true);
    } else {
        selectedTab = (structure.tabs.find((tab) => tab.selected) || {}).id || '';
    }
    reloadPreview();
};

/**
 * Calls the web service for delete, move, publish and discard.
 *
 * @param {Object} args
 */
const edit = async(args) => {
    try {
        const response = await Ajax.call([{
            methodname: 'local_urise_megamenu_edit',
            args: Object.assign({revision: getRevision(), selectedtab: selectedTab}, args),
        }])[0];
        await render(JSON.parse(response.structure));
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Opens the form of an element.
 *
 * @param {string} formname tab, block, card, link or settings.
 * @param {string} jsonpath Path of the element, list or slot.
 * @param {HTMLElement|null} returnFocus
 * @param {Object} extraArgs More arguments for the form, e.g. the type of a new block.
 */
const openForm = async(formname, jsonpath, returnFocus = null, extraArgs = {}) => {
    const path = JSON.parse(jsonpath);
    const addsTab = path.length === 1 && path[0] === 'tabs';
    const form = new ModalForm({
        formClass: FORMS[formname],
        args: Object.assign({path: jsonpath, revision: getRevision()}, extraArgs),
        modalConfig: {title: await getString('megamenu:form_' + formname, 'local_urise')},
        returnFocus,
    });
    form.addEventListener(form.events.FORM_SUBMITTED, (e) => render(e.detail, !addsTab));
    form.show();
};

/**
 * Lets the editor choose the kind of a new block from examples, then opens its form.
 *
 * @param {HTMLElement} button
 */
const pickBlock = async(button) => {
    const modal = await Modal.create({
        title: getString('megamenu:choosecomponent', 'local_urise'),
        body: root.querySelector(SELECTORS.PICKER).innerHTML,
        large: true,
        show: true,
        removeOnClose: true,
    });
    modal.getRoot()[0].addEventListener('click', (e) => {
        const pick = e.target.closest('[data-action="mm-pick"]');
        if (pick) {
            modal.destroy();
            openForm('block', button.dataset.path, button, {type: pick.dataset.type, boxed: pick.dataset.boxed});
        }
    });
};

/**
 * Asks before deleting an element.
 *
 * @param {HTMLElement} button
 */
const confirmDelete = async(button) => {
    try {
        await Notification.saveCancelPromise(
            getString('delete'),
            getString('megamenu:confirmdelete', 'local_urise', button.dataset.name || ''),
            getString('delete')
        );
    } catch (e) {
        return;
    }
    edit({action: 'delete', path: button.dataset.path});
};

/**
 * Asks before publishing or discarding the draft.
 *
 * @param {string} action publish or discard
 */
const confirmDraftAction = async(action) => {
    try {
        await Notification.saveCancelPromise(
            getString('megamenu:' + action, 'local_urise'),
            getString('megamenu:confirm' + action, 'local_urise'),
            getString('megamenu:' + action, 'local_urise')
        );
    } catch (e) {
        return;
    }
    edit({action});
};

/**
 * Handles clicks on the buttons of the editor.
 *
 * @param {MouseEvent} e
 */
const handleClick = (e) => {
    const button = e.target.closest('[data-action^="mm-"]');
    if (!button || !root.contains(button)) {
        return;
    }
    switch (button.dataset.action) {
        case 'mm-select':
            selectTab(button.dataset.tab);
            break;
        case 'mm-edit':
            openForm(button.dataset.form, button.dataset.path, button);
            break;
        case 'mm-addblock':
            pickBlock(button);
            break;
        case 'mm-delete':
            confirmDelete(button);
            break;
        case 'mm-publish':
            confirmDraftAction('publish');
            break;
        case 'mm-discard':
            confirmDraftAction('discard');
            break;
        default:
            return;
    }
    e.preventDefault();
};

/**
 * Makes the lists sortable by drag and drop, also with the keyboard.
 *
 * Links can be moved between the blocks and cards of a tab.
 */
const initSortable = () => {
    LISTTYPES.forEach((type) => {
        const selector = `${root.dataset.selector} [data-listtype="${type}"]`;
        const list = new SortableList(selector, {
            moveHandlerSelector: `[data-drag="${type}"]`,
            // Elements move only within the entry that is shown, not into hidden ones.
            targetListSelector: type === 'tabs' ? selector
                : `${root.dataset.selector} [data-region="mm-tabcontent"]:not([hidden]) [data-listtype="${type}"]`,
        });
        list.getElementName = (element) => Promise.resolve(element.find('.mm-link-label, button, strong').first().text().trim());
    });

    $(root).on(SortableList.EVENTS.DROP, SELECTORS.ITEM, (e, info) => {
        e.stopPropagation();
        if (!info.positionChanged) {
            return;
        }
        const element = info.element.get(0);
        edit({
            action: 'move',
            path: element.dataset.path,
            targetpath: info.targetList.get(0).dataset.listpath,
            position: [...element.parentElement.children].indexOf(element),
        });
    });
};

/**
 * Handles messages of the preview.
 *
 * @param {MessageEvent} e
 */
const handleMessage = (e) => {
    const frame = getFrame();
    if (!frame || e.source !== frame.contentWindow || e.origin !== window.location.origin || !e.data.type) {
        return;
    }
    switch (e.data.type) {
        case MESSAGEPREFIX + 'edit':
            if (FORMS[e.data.form]) {
                openForm(e.data.form, e.data.path, frame);
            }
            break;
        case MESSAGEPREFIX + 'select':
            selectTab(e.data.tab, true);
            break;
        case MESSAGEPREFIX + 'height':
            frameHeight = e.data.height;
            scalePreview();
            break;
    }
};

/**
 * Initialises the editor.
 *
 * @param {string} selector Selector of the editor root.
 */
export const init = (selector) => {
    root = document.querySelector(selector);
    if (!root) {
        return;
    }
    root.dataset.selector = selector;
    const selected = root.querySelector('[data-listtype="tabs"] > li.selected [data-action="mm-select"]');
    selectedTab = selected ? selected.dataset.tab : '';

    root.addEventListener('click', handleClick);
    root.addEventListener('click', (e) => {
        const button = e.target.closest(SELECTORS.VIEW);
        if (button && root.contains(button)) {
            setView(button.dataset.view);
        }
    });
    window.addEventListener('message', handleMessage);
    window.addEventListener('resize', scalePreview);
    let stored = null;
    try {
        stored = window.localStorage.getItem(VIEWSTORAGE);
    } catch (e) {
        stored = null;
    }
    if (stored && stored !== view) {
        setView(stored);
    } else {
        scalePreview();
    }
    initSortable();
};
