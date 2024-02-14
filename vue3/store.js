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
 * Validate if the string does excist.
 *
 * @package     local_berta
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Import needed libraries
import { createStore } from 'vuex';
import moodleAjax from 'core/ajax';
import moodleStorage from 'core/localstorage';
import Notification from 'core/notification';
import $ from 'jquery';

// Defining store for application
export function createAppStore() {
    return createStore({
        state() {
            return {
                strings: {},
                tabs: [],
                content: [],
                configlist: [],
            };
        },
        mutations: {
            // Mutations are synchronous.
            setStrings(state, strings) {
                state.strings = strings;
            },
            setTabs(state, tabs) {
                state.tabs = tabs;
            },
            setContent(state, content) {
              state.content = content;
            },
            setConfigList(state, configlist) {
              state.configlist = configlist;
            },
        },
        actions: {
            // Actions are asynchronous.
            async loadLang(context) {
                const lang = $('html').attr('lang').replace(/-/g, '_');
                context.commit('setLang', lang);
            },
            async loadComponentStrings(context) {
                const lang = $('html').attr('lang').replace(/-/g, '_');
                const cacheKey = 'local_berta/strings/' + lang;
                const cachedStrings = moodleStorage.get(cacheKey);
                if (cachedStrings) {
                    context.commit('setStrings', JSON.parse(cachedStrings));
                } else {
                    const request = {
                        methodname: 'core_get_component_strings',
                        args: {
                            'component': 'local_adele',
                            lang,
                        },
                    };
                    const loadedStrings = await moodleAjax.call([request])[0];
                    let strings = {};
                    loadedStrings.forEach((s) => {
                        strings[s.stringid] = s.string;
                    });
                    context.commit('setStrings', strings);
                    moodleStorage.set(cacheKey, JSON.stringify(strings));
                }

            },
            async fetchTabs(context) {
                const tabs = await ajax('local_berta_get_parent_categories',
                    { });
                context.commit('setTabs', tabs);
            },
            async fetchParentContent(context, index) {
                const params = { coursecategoryid: index };
                const content = await ajax('local_berta_get_parent_content', params);
                context.commit('setContent', content);
                const configlist = await ajax('mod_booking_get_option_field_config', params);
                context.commit('setConfigList', configlist);
            },
        }
    });
}

/**
 * Single ajax call to Moodle.
 */
export async function ajax(method, args) {
    const request = {
        methodname: method,
        args: Object.assign( args ),
    };

    try {
        return await moodleAjax.call([request])[0];
    } catch (e) {
        Notification.exception(e);
        throw e;
    }
}