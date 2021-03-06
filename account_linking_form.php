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
 * Account linking form.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/formslib.php");

class account_linking_form extends moodleform {

    /**
     * Define account linking form.
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        $name = get_string('alexaskill_accountlinking_pin', 'local_alexaskill');
        $options = array('maxlength' => 4);
        $mform->addElement('password', 'pin', $name, $options);
        $mform->setType('pin', PARAM_TEXT);
        $mform->addHelpButton('pin', 'alexaskill_accountlinking_pin', 'local_alexaskill');

        $mform->addElement('hidden', 'service');
        $mform->setType('service', PARAM_TEXT);

        $mform->addElement('hidden', 'state');
        $mform->setType('state', PARAM_TEXT);

        $mform->addElement('hidden', 'response_type');
        $mform->setType('response_type', PARAM_TEXT);

        $mform->addElement('hidden', 'redirect_uri');
        $mform->setType('redirect_uri', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('alexaskill_accountlinking_submit', 'local_alexaskill'));
    }

    /**
     * Validate account linking form data.
     * Some code taken from login/token.php
     *
     * @param $data data submitted
     * @param $files files submitted
     * @return $errors array of error message to display on form
     */
    public function validation($data, $files) {
        global $DB;
        $errors = array();

        // Some content copied from login/token.php
        // Check if the service exists and is enabled.
        $serviceshortname = $data['service'];
        $service = $DB->get_record('external_services', array('shortname' => $serviceshortname, 'enabled' => 1));
        if (empty($service)) {
            // No external service found, display error message on form.
            $errors['pin'] = get_string('servicenotavailable', 'webservice');
            // Have to return $errors here to stop running of validation, $service is used in token retrieval.
            return $errors;
        }

        // Redirect URI is not valid, display error message and log.
        if (isset($data['redirect_uri'])
                && stripos(get_config('local_alexaskill', 'alexaskill_redirecturis'), $data['redirect_uri']) === false) {
            $errors['pin'] = get_string('alexaskill_accountlinking_plugin_error', 'local_alexaskill');
            debugging('Amazon Alexa skill redirect URI does not match configured settings.', DEBUG_DEVELOPER);
        }

        // Response type is not valid, display error message and log.
        if (isset($data['response_type']) && $data['response_type'] != 'token') {
            $errors['pin'] = get_string('alexaskill_accountlinking_plugin_error', 'local_alexaskill');
            debugging('The response_type argument should always be token for implicit grant.', DEBUG_DEVELOPER);
        }

        // Make sure token exists or a new one can be created.
        try {
            $token = external_generate_token_for_current_user($service);
            external_log_token_request($token);
        } catch (moodle_exception $e) {
            // If exception is thrown, display error message on form.
            $errors['pin'] = get_string($e->errorcode, $e->module);
        }

        // If user enters PIN, make sure it is 4-digits in length.
        $pinlength = strlen($data['pin']);
        if ($data['pin'] != '' && ($pinlength < 4 || $pinlength > 4 || !is_numeric($data['pin']))) {
            $errors['pin'] = get_string('alexaskill_accountlinking_pin_error', 'local_alexaskill');
        }

        // Make sure user profile field exists.
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        if (empty($field)) {
            // PIN field has not been configured, display error message and log.
            $errors['pin'] = get_string('alexaskill_accountlinking_plugin_error', 'local_alexaskill');
            debugging('Amazon Alexa skill PIN user profile field has not been configured. See local/alexaskill/db/install.php.', DEBUG_DEVELOPER);
        }

        // Make sure state, reponse_type and redirect_uri were included as query strings.
        if (empty($data['state']) || empty($data['response_type']) || empty($data['redirect_uri'])) {
            $errors['pin'] = get_string('alexaskill_accountlinking_plugin_error', 'local_alexaskill');
            debugging('Account linking request missing required argument.', DEBUG_DEVELOPER);
        }

        return $errors;
    }
}