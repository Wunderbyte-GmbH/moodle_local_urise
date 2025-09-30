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

namespace local_urise\form;

use context_module;
use MoodleQuickForm;
use mod_booking\booking_option;
use mod_booking\form\option_form;
use mod_booking\singleton_service;
use moodle_exception;
use stdClass;

/**
 * Modal form to allow simplified access to availability conditions for urise.
 *
 * @package     local_urise
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class easy_availability_modal_form extends \core_form\dynamic_form {
    /**
     * Get context for dynamic submission
     *
     * @return \context
     *
     */
    protected function get_context_for_dynamic_submission(): \context {
        $settings = singleton_service::get_instance_of_booking_option_settings($this->_ajaxformdata['optionid']);
        return context_module::instance($settings->cmid);
    }

    /**
     * {@inheritdoc}
     * @see moodleform::definition()
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'optionid');
        $mform->setType('optionid', PARAM_INT);

        $optionid = $this->_ajaxformdata['optionid'];

        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        $titlewithprefix = $settings->get_title_with_prefix();

        $mform->addElement('html', get_string('easyavailability:heading', 'local_urise', $titlewithprefix));

        if (self::form_has_incompatible_conditions($optionid)) {
            // The form has incompatible conditions.
            $mform->addElement('html', get_string('easyavailability:formincompatible', 'local_urise'));
        }

        // EDIT AVAILABILITY.
        $mform->addElement('header', 'availabilityheader', get_string('editavailability', 'local_urise'));
        $mform->setExpanded('availabilityheader', false);

        // The form is not locked and can be used normally.
        $mform->addElement(
            'date_time_selector',
            'bookingopeningtime',
            get_string('easyavailability:openingtime', 'local_urise')
        );
        $mform->setType('bookingopeningtime', PARAM_INT);

        $mform->addElement(
            'date_time_selector',
            'bookingclosingtime',
            get_string('easyavailability:closingtime', 'local_urise')
        );
        $mform->setType('bookingclosingtime', PARAM_INT);

        $mform->addElement('html', '<hr>');

        // Add the selectusers condition:
        // Select users who can override booking_time condition.
        $mform->addElement(
            'advcheckbox',
            'bo_cond_selectusers_restrict',
            get_string('easyavailability:selectusers', 'local_urise')
        );

        $mform->addElement('checkbox', 'selectusersoverbookcheckbox', get_string('easyavailability:overbook', 'local_urise'));
        $mform->setDefault('selectusersoverbookcheckbox', 'checked');
        $mform->hideIf('selectusersoverbookcheckbox', 'bo_cond_selectusers_restrict', 'notchecked');

        $options = [
            'multiple' => true,
            'noselectionstring' => get_string('choose...', 'mod_booking'),
            'ajax' => 'local_shopping_cart/form_users_selector',
            'valuehtmlcallback' => function ($value) {
                global $OUTPUT;
                $user = singleton_service::get_instance_of_user((int)$value);
                $details = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ];
                return $OUTPUT->render_from_template(
                    'mod_booking/form-user-selector-suggestion',
                    $details
                );
            },
        ];
        $mform->addElement(
            'autocomplete',
            'bo_cond_selectusers_userids',
            get_string('bo_cond_selectusers_userids', 'mod_booking'),
            [],
            $options
        );
        $mform->hideIf('bo_cond_selectusers_userids', 'bo_cond_selectusers_restrict', 'notchecked');

        $mform->addElement('html', '<hr>');

        // Add the previouslybooked condition:
        // Users who previously booked a certain option can override booking_time condition.
        $mform->addElement(
            'advcheckbox',
            'bo_cond_previouslybooked_restrict',
            get_string('easyavailability:previouslybooked', 'local_urise')
        );
        $mform->addElement(
            'checkbox',
            'previouslybookedoverbookcheckbox',
            get_string('easyavailability:overbook', 'local_urise')
        );
        $mform->setDefault('previouslybookedoverbookcheckbox', 'checked');
        $mform->hideIf('previouslybookedoverbookcheckbox', 'bo_cond_previouslybooked_restrict', 'notchecked');

        $previouslybookedoptions = [
            'tags' => false,
            'multiple' => false,
            'noselectionstring' => get_string('choose...', 'mod_booking'),
            'ajax' => 'mod_booking/form_booking_options_selector',
            'valuehtmlcallback' => function ($value) {
                global $OUTPUT;
                $optionsettings = singleton_service::get_instance_of_booking_option_settings((int)$value);
                $instancesettings = singleton_service::get_instance_of_booking_settings_by_cmid($optionsettings->cmid);

                $details = (object)[
                    'id' => $optionsettings->id,
                    'titleprefix' => $optionsettings->titleprefix,
                    'text' => $optionsettings->text,
                    'instancename' => $instancesettings->name,
                ];
                return $OUTPUT->render_from_template(
                    'mod_booking/form_booking_options_selector_suggestion',
                    $details
                );
            },
        ];
        $mform->addElement(
            'autocomplete',
            'bo_cond_previouslybooked_optionid',
            get_string('bo_cond_previouslybooked_optionid', 'mod_booking'),
            [],
            $previouslybookedoptions
        );
        $mform->setType('bo_cond_previouslybooked_optionid', PARAM_INT);
        $mform->hideIf('bo_cond_previouslybooked_optionid', 'bo_cond_previouslybooked_restrict', 'notchecked');

        // EDIT DESCRIPTION.
        $mform->addElement('header', 'descriptionheader', get_string('editdescription', 'local_urise'));
        $mform->setExpanded('descriptionheader', false);

        $mform->addElement('editor', 'description', get_string('description', 'core'));
        $mform->setType('description', PARAM_CLEANHTML);
    }

    /**
     * Check access for dynamic submission.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {

        $context = $this->get_context_for_dynamic_submission();
        $optionid = $this->_ajaxformdata['optionid'];

        // The simplified availability menu.
        $alloweditavailability = (
            // Admin capability.
            has_capability('mod/booking:updatebooking', $context) ||
            // Or: Everyone with the urise editavailability capability.
            has_capability('local/urise:editavailability', $context) ||
            // Or: Teachers can edit the availability of their own option.
            (has_capability('mod/booking:limitededitownoption', $context) && $this->check_if_teacher($optionid)) ||
            (has_capability('mod/booking:addeditownoption', $context) && $this->check_if_teacher($optionid))
        );
        if (!$alloweditavailability) {
            throw new moodle_exception('norighttoaccess', 'local_urise');
        }
    }


    /**
     * Set data for dynamic submission
     *
     * @return void
     *
     */
    public function set_data_for_dynamic_submission(): void {

        $data = new stdClass();

        /* If availability conditions are already in DB, we have to load them
        and translate them into the easy availability format.
        If the conditions in DB are somehow not compatible with the easy form,
        then we have to lock the form. */

        $data->optionid = $this->_ajaxformdata['optionid'];

        booking_option::purge_cache_for_option($data->optionid);
        $settings = singleton_service::get_instance_of_booking_option_settings($data->optionid);

        // The booking option description.
        $data->description = ['text' => $settings->description, 'format' => FORMAT_HTML];

        $data->bookingopeningtime = $settings->bookingopeningtime ?? $this->_ajaxformdata['bookingopeningtime'];
        $data->bookingclosingtime = $settings->bookingclosingtime ?? $this->_ajaxformdata['bookingclosingtime'];

        if (!empty($settings->availability)) {
            $availabilityarray = json_decode($settings->availability);
            foreach ($availabilityarray as $av) {
                switch ($av->id) {
                    case MOD_BOOKING_BO_COND_JSON_SELECTUSERS:
                        if (!empty($av->userids)) {
                            $data->bo_cond_selectusers_restrict = true;
                            $data->bo_cond_selectusers_userids = $av->userids;
                        }
                        if (
                            in_array(MOD_BOOKING_BO_COND_FULLYBOOKED, $av->overrides ?? []) &&
                            in_array(MOD_BOOKING_BO_COND_NOTIFYMELIST, $av->overrides ?? [])
                        ) {
                            $data->selectusersoverbookcheckbox = true;
                        } else {
                            $data->selectusersoverbookcheckbox = false;
                        }
                        break;
                    case MOD_BOOKING_BO_COND_JSON_PREVIOUSLYBOOKED:
                        if (!empty($av->optionid)) {
                            $data->bo_cond_previouslybooked_restrict = true;
                            $data->bo_cond_previouslybooked_optionid = (int)$av->optionid;
                        }
                        if (
                            in_array(MOD_BOOKING_BO_COND_FULLYBOOKED, $av->overrides ?? []) &&
                            in_array(MOD_BOOKING_BO_COND_NOTIFYMELIST, $av->overrides ?? [])
                        ) {
                            $data->previouslybookedoverbookcheckbox = true;
                        } else {
                            $data->previouslybookedoverbookcheckbox = false;
                        }
                        break;
                }
            }
        }

        $this->set_data($data);
    }

    /**
     * Process dynamic submission
     *
     * @return bool
     *
     */
    public function process_dynamic_submission(): bool {

        // We get the data prepared by set_data_for_dynamic_submission().
        $data = $this->get_data();
        $optionid = $data->optionid;

        // Prepare option values.
        booking_option::purge_cache_for_option($optionid);
        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        $cmid = $settings->cmid;
        $context = context_module::instance($cmid);
        $optionvalues = $settings->return_settings_as_stdclass();
        $optionvalues->optionid = $optionid;

        // Now we can modify our data.
        $optionvalues->description = $data->description['text'];
        $optionvalues->restrictanswerperiodopening = true;
        $optionvalues->restrictanswerperiodclosing = true;
        $optionvalues->bookingopeningtime = $data->bookingopeningtime;
        $optionvalues->bookingclosingtime = $data->bookingclosingtime;

        // Select users condition.
        if ($data->bo_cond_selectusers_restrict == 1 && !empty(($data->bo_cond_selectusers_userids))) {
            $optionvalues->bo_cond_selectusers_restrict = $data->bo_cond_selectusers_restrict;
            $optionvalues->bo_cond_selectusers_userids = $data->bo_cond_selectusers_userids;
            $optionvalues->bo_cond_selectusers_overrideconditioncheckbox = true; // Can be hardcoded here.
            $optionvalues->bo_cond_selectusers_overrideoperator = 'OR'; // Can be hardcoded here.

            // We always override these conditions, so users are always allowed to book outside time restrictions.
            $optionvalues->bo_cond_selectusers_overridecondition = [
                MOD_BOOKING_BO_COND_BOOKING_TIME,
                MOD_BOOKING_BO_COND_OPTIONHASSTARTED,
            ];

            // If the overbook checkbox has been checked, we also add the conditions so the user(s) can overbook.
            if (!empty($data->selectusersoverbookcheckbox)) {
                $optionvalues->bo_cond_selectusers_overridecondition[] = MOD_BOOKING_BO_COND_FULLYBOOKED;
                $optionvalues->bo_cond_selectusers_overridecondition[] = MOD_BOOKING_BO_COND_NOTIFYMELIST;
            }
        } else {
            $optionvalues->bo_cond_selectusers_restrict = 0;
        }

        // Previously booked condition.
        if ($data->bo_cond_previouslybooked_restrict == 1 && !empty(($data->bo_cond_previouslybooked_optionid))) {
            $optionvalues->bo_cond_previouslybooked_restrict = $data->bo_cond_previouslybooked_restrict;
            $optionvalues->bo_cond_previouslybooked_optionid = $data->bo_cond_previouslybooked_optionid;
            $optionvalues->bo_cond_previouslybooked_overrideconditioncheckbox = true; // Can be hardcoded here.
            $optionvalues->bo_cond_previouslybooked_overrideoperator = 'OR'; // Can be hardcoded here.
            // We always override these 2 conditions, so users are always allowed to book outside time restrictions.
            $optionvalues->bo_cond_previouslybooked_overridecondition = [
                MOD_BOOKING_BO_COND_BOOKING_TIME,
                MOD_BOOKING_BO_COND_OPTIONHASSTARTED,
            ];

            // If the overbook checkbox has been checked, we also add the conditions so the user(s) can overbook.
            if (!empty($data->previouslybookedoverbookcheckbox)) {
                $optionvalues->bo_cond_previouslybooked_overridecondition[] = MOD_BOOKING_BO_COND_FULLYBOOKED;
                $optionvalues->bo_cond_previouslybooked_overridecondition[] = MOD_BOOKING_BO_COND_NOTIFYMELIST;
            }
        } else {
            $optionvalues->bo_cond_previouslybooked_restrict = 0;
        }

        // Third param is 2 (MOD_BOOKING_UPDATE_OPTIONS_PARAM_REDUCED).
        if (booking_update_options($optionvalues, $context, 2)) {
            return true;
        }

        return false;
    }

    /**
     * Validation
     *
     * @param mixed $data
     * @param mixed $files
     *
     * @return array
     *
     */
    public function validation($data, $files): array {
        $errors = [];

        if ($data['bookingopeningtime'] >= $data['bookingclosingtime']) {
            $errors['bookingopeningtime'] = get_string('error:starttime', 'local_urise');
            $errors['bookingclosingtime'] = get_string('error:endtime', 'local_urise');
        }

        return $errors;
    }

    /**
     *  Get page url for dynamic submission
     *
     * @return \moodle_url
     *
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/local/urise/dashboard.php');
    }

    /**
     * Check if logged in user is a teacher of the option.
     * @param int $optionid
     * @return bool true if it's a teacher, false if not
     */
    private function check_if_teacher(int $optionid) {
        global $USER;
        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        if (in_array($USER->id, $settings->teacherids)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Helper function to check if form has incompatible conditions.
     * @param int $optionid option id
     * @return bool true if form is locked, else false
     */
    private static function form_has_incompatible_conditions(int $optionid) {
        $settings = singleton_service::get_instance_of_booking_option_settings($optionid);
        $formlocked = false; // Unlocked by default.
        // We have to lock the form, if there are conditions not supported by the easy form.
        if (!empty($settings->availability)) {
            $availabilityarray = json_decode($settings->availability);
            foreach ($availabilityarray as $av) {
                if (
                    !in_array($av->id, [
                    MOD_BOOKING_BO_COND_JSON_CUSTOMFORM, // Custom form needs to be compatible with the easy form.
                    MOD_BOOKING_BO_COND_JSON_SELECTUSERS,
                    MOD_BOOKING_BO_COND_JSON_PREVIOUSLYBOOKED])
                ) {
                    $formlocked = true;
                }
            }
        }
        return $formlocked;
    }
}
