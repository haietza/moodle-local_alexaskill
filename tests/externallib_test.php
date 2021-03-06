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
 * External web service unit tests.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/alexaskill/externallib.php');

/**
 * External web service functions unit tests.
 *
 * @package    local_alexaskill
 * @category   external
 * @copyright  2018, Michelle Melton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_alexaskill
 */
class local_alexaskill_externallib_testcase extends externallib_advanced_testcase {
    // Web service test response.
    private $responsejson;

    /**
     * Tests set up.
     */
    protected function setUp() {
        set_config('alexaskill_applicationid', LOCAL_ALEXASKILL_TEST_CONFIG_APPLICATIONID, 'local_alexaskill');
        set_config('alexaskill_coursenameregex', LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEX, 'local_alexaskill');

        self::getMethod('initialize_response')->invokeArgs(null, array());

        $this->responsejson = array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'PlainText'
                        ),
                        'shouldEndSession' => true
                )
        );
    }

    /**
     * Tests tear down.
     */
    protected function tearDown() {
        unset($this->responsejson);
        local_alexaskill_external::$requestjson = null;
    }

    /**
     * Make private functions accessible for tests.
     *
     * @param string $methodname
     * @return ReflectionMethod
     */
    protected static function getMethod($methodname) {
        $class = new ReflectionClass('local_alexaskill_external');
        $method = $class->getMethod($methodname);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Test for response initialization.
     */
    public function test_initialize_response() {
        $this->resetAfterTest();
        $this->assertTrue($this->responsejson == local_alexaskill_external::$responsejson);
    }

    /**
     * Test for valid signature certificate URL.
     */
    public function test_signature_certificate_url_is_valid() {
        $this->resetAfterTest();
        $signaturecertificateurlisvalid = self::getMethod('signature_certificate_url_is_valid');

        $certurl = 'https://s3.amazonaws.com/echo.api/echo-api-cert.pem';
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertTrue($actual);
    }

    /**
     * Tests for invalid signature certificate URL.
     */
    public function test_signature_certificate_url_is_valid_invalid() {
        $this->resetAfterTest();
        $signaturecertificateurlisvalid = self::getMethod('signature_certificate_url_is_valid');

        $certurl = '';
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);

        $certurl = null;
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);

        $certurl = false;
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);

        $certurl = 'foo';
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);

        $certurl = 'http://s3.amazonaws.com/echo.api/echo-api-cert.pem';
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);

        $certurl = 'https://amazonaws.com/echo.api/echo-api-cert.pem';
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);

        $certurl = 'https://s3.amazonaws.com/amazon.api/echo-api-cert.pem';
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);

        $certurl = 'https://s3.amazonaws.com:22/echo.api/echo-api-cert.pem';
        $actual = $signaturecertificateurlisvalid->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);
    }

    /**
     * Test for development configuration, true.
     */
    public function test_is_development_site_true() {
        $this->resetAfterTest();
        $isdevelopmentsite = self::getMethod('is_development_site');

        set_config('alexaskill_development', 1, 'local_alexaskill');
        $actual = $isdevelopmentsite->invokeArgs(null, array());
        $this->assertTrue($actual == 1);
    }

    /**
     * Test for development configuration, false.
     */
    public function test_is_development_site_false() {
        $this->resetAfterTest();
        $isdevelopmentsite = self::getMethod('is_development_site');

        set_config('alexaskill_development', 0, 'local_alexaskill');
        $actual = $isdevelopmentsite->invokeArgs(null, array());
        $this->assertTrue($actual == 0);
    }

    /**
     * Test for development configuration, none.
     */
    public function test_is_development_site_none() {
        global $DB;
        $this->resetAfterTest();
        $isdevelopmentsite = self::getMethod('is_development_site');

        $DB->delete_records('config_plugins', array('plugin' => 'local_alexaskill', 'name' => 'alexaskill_development'));
        $actual = $isdevelopmentsite->invokeArgs(null, array());
        $this->assertTrue($actual == 0);
    }

    /**
     * Test for invalid signature.
     */
    public function test_signature_is_valid_invalid() {
        $this->resetAfterTest();
        $signatureisvalid = self::getMethod('signature_is_valid');

        $certurl = 'https://s3.amazonaws.com/echo.api/echo-api-cert.pem';
        $signature = 'fooencrypted';
        $request = 'foo';
        $actual = $signatureisvalid->invokeArgs(null, array($certurl, $signature, $request));
        $this->assertFalse($actual);
    }

    /**
     * Test signature_is_valid with unwriteable cert directory.
     */
    public function test_signature_is_valid_certdir_no_write() {
        global $CFG;
        $this->resetAfterTest();
        $signatureisvalid = self::getMethod('signature_is_valid');

        $certurl = 'https://s3.amazonaws.com/echo.api/echo-api-cert.pem';
        $signature = 'fooencrypted';
        $request = 'foo';
        $certdir = $CFG->dataroot . '/local_alexaskill';
        if (!file_exists($certdir)) {
            mkdir($certdir);
        }
        chmod($certdir, 0444);

        $actual = $signatureisvalid->invokeArgs(null, array($certurl, $signature, $request));
        $this->assertFalse($actual);
    }

    /**
     * Test for valid timestamp.
     */
    public function test_timestamp_is_valid() {
        $this->resetAfterTest();
        $timestampisvalid = self::getMethod('timestamp_is_valid');

        local_alexaskill_external::$requestjson['request']['timestamp'] = gmdate('Y-m-d\TH:i:s\Z', time());
        $actual = $timestampisvalid->invokeArgs(null, array());
        $this->assertTrue($actual);
    }

    /**
     * Tests for invalid timestamp.
     */
    public function test_timestamp_is_valid_invalid() {
        $this->resetAfterTest();
        $timestampisvalid = self::getMethod('timestamp_is_valid');

        local_alexaskill_external::$requestjson['request']['timestamp'] = '';
        $actual = $timestampisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);

        local_alexaskill_external::$requestjson['request']['timestamp'] = null;
        $actual = $timestampisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);

        local_alexaskill_external::$requestjson['request']['timestamp'] = false;
        $actual = $timestampisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);

        local_alexaskill_external::$requestjson['request']['timestamp'] = 'foo';
        $actual = $timestampisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);

        local_alexaskill_external::$requestjson['request']['timestamp'] = gmdate('Y-m-d\TH:i:s\Z', time() - 1000);
        $actual = $timestampisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test for valid application ID.
     */
    public function test_applicationid_is_valid_valid() {
        $this->resetAfterTest();
        $applicationidisvalid = self::getMethod('applicationid_is_valid');

        local_alexaskill_external::$requestjson['session']['application']['applicationId'] = LOCAL_ALEXASKILL_TEST_CONFIG_APPLICATIONID;
        $actual = $applicationidisvalid->invokeArgs(null, array());
        $this->assertTrue($actual);
    }

    /**
     * Tests for invalid application ID.
     */
    public function test_applicationid_is_valid_invalid() {
        $this->resetAfterTest();
        $applicationidisvalid = self::getMethod('applicationid_is_valid');

        local_alexaskill_external::$requestjson['session']['application']['applicationId'] = '';
        $actual = $applicationidisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);

        local_alexaskill_external::$requestjson['session']['application']['applicationId'] = null;
        $actual = $applicationidisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);

        local_alexaskill_external::$requestjson['session']['application']['applicationId'] = 'foo';
        $actual = $applicationidisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test pin_exists.
     */
    public function test_pin_exists() {
        global $DB;
        $this->resetAfterTest();
        $pinexists = self::getMethod('pin_exists');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '1234'));

        $actual = $pinexists->invokeArgs(null, array());
        $this->assertTrue($actual);
    }

    /**
     * Test pin_exists, invalid.
     */
    public function test_pin_exists_invalid() {
        global $DB;
        $this->resetAfterTest();
        $pinexists = self::getMethod('pin_exists');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $actual = $pinexists->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test pin_exists, invalid empty.
     */
    public function test_pin_exists_invalid_empty() {
        global $DB;
        $this->resetAfterTest();
        $pinexists = self::getMethod('pin_exists');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => ''));

        $actual = $pinexists->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test pin_exists, invalid no user preferences field.
     */
    public function test_pin_exists_invalid_no_field() {
        global $DB;
        $this->resetAfterTest();
        $pinexists = self::getMethod('pin_exists');

        $DB->delete_records('user_info_field', array('shortname' => 'amazonalexaskillpin'));

        $actual = $pinexists->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test process_pin, valid.
     */
    public function test_process_pin_valid() {
        global $DB, $SITE;
        $this->resetAfterTest();
        $processpin = self::getMethod('process_pin');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '1111'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1111';

        $actual = $processpin->invokeArgs(null, array());
        $this->assertNull($actual);
    }

    /**
     * Test process_pin, invalid.
     */
    public function test_process_pin_invalid() {
        global $DB, $SITE;
        $this->resetAfterTest();
        $processpin = self::getMethod('process_pin');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '9999'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1111';

        $actual = $processpin->invokeArgs(null, array());

        $this->responsejson['response']['outputSpeech']['text'] = "I'm sorry, that PIN is invalid. "
                . "You can use the Alexa app to relink your account and reset your PIN.";
        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test process_pin, invalid text.
     */
    public function test_process_pin_invalid_text() {
        global $DB, $SITE;
        $this->resetAfterTest();
        $processpin = self::getMethod('process_pin');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => 'foo'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1111';

        $actual = $processpin->invokeArgs(null, array());

        $this->responsejson['response']['outputSpeech']['text'] = "I'm sorry, that PIN is invalid. "
                . "You can use the Alexa app to relink your account and reset your PIN.";
        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test process_pin, invalid negative value.
     */
    public function test_process_pin_invalid_negative() {
        global $DB, $SITE;
        $this->resetAfterTest();
        $processpin = self::getMethod('process_pin');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '-1'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1111';

        $actual = $processpin->invokeArgs(null, array());

        $this->responsejson['response']['outputSpeech']['text'] = "I'm sorry, that PIN is invalid. "
                . "You can use the Alexa app to relink your account and reset your PIN.";
        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test process_pin, return request for PIN.
     */
    public function test_process_pin_request() {
        global $DB, $SITE;
        $this->resetAfterTest();
        $processpin = self::getMethod('process_pin');

        $actual = $processpin->invokeArgs(null, array());

        $this->responsejson['response']['outputSpeech']['text'] = 'Please say your Amazon Alexa PIN.';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'PlainText';
        $this->responsejson['response']['reprompt']['outputSpeech']['text'] = "I didn't quite catch that. Please say your PIN.";
        $this->responsejson['response']['shouldEndSession'] = false;
        $this->responsejson['response']['directives'] = array(
                array(
                        'type' => 'Dialog.ElicitSlot',
                        'slotToElicit' => 'pin'
                )
        );
        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test reqeust pin.
     */
    public function test_request_pin() {
        $this->resetAfterTest();
        $requestpin = self::getMethod('request_pin');

        $actual = $requestpin->invokeArgs(null, array());

        $this->responsejson['response']['outputSpeech']['text'] = 'Please say your Amazon Alexa PIN.';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'PlainText';
        $this->responsejson['response']['reprompt']['outputSpeech']['text'] = "I didn't quite catch that. Please say your PIN.";
        $this->responsejson['response']['shouldEndSession'] = false;
        $this->responsejson['response']['directives'] = array(
                array(
                        'type' => 'Dialog.ElicitSlot',
                        'slotToElicit' => 'pin'
                )
        );
        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test pin_is_valid.
     */
    public function test_pin_is_valid() {
        global $DB;
        $this->resetAfterTest();
        $pinisvalid = self::getMethod('pin_is_valid');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '1234'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1234';

        $actual = $pinisvalid->invokeArgs(null, array());

        $this->assertTrue($actual);
        $this->assertTrue(local_alexaskill_external::$responsejson['sessionAttributes']['pin'] == 'valid');
    }

    /**
     * Test pin_is_valid, invalid.
     */
    public function test_pin_is_valid_invalid() {
        global $DB;
        $this->resetAfterTest();
        $pinisvalid = self::getMethod('pin_is_valid');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '4321'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1234';

        $actual = $pinisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test pin_is_valid, invalid no field record.
     */
    public function test_pin_is_valid_invalid_no_field() {
        global $DB;
        $this->resetAfterTest();
        $pinisvalid = self::getMethod('pin_is_valid');

        $DB->delete_records('user_info_field', array('shortname' => 'amazonalexaskillpin'));

        $actual = $pinisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test pin_is_valid, invalid no pin record.
     */
    public function test_pin_is_valid_invalid_no_pin() {
        global $DB;
        $this->resetAfterTest();
        $pinisvalid = self::getMethod('pin_is_valid');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->delete_records('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id));

        $actual = $pinisvalid->invokeArgs(null, array());
        $this->assertFalse($actual);
    }

    /**
     * Test launch_request.
     */
    public function test_launch_request() {
        global $SITE;
        $this->resetAfterTest();
        $launchrequest = self::getMethod('launch_request');

        $actual = $launchrequest->invokeArgs(null, array('token' => ''));

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['ssml'] = "<speak>I didn't quite catch that. Which would you like?</speak>";
        $this->responsejson['response']['shouldEndSession'] = false;

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Welcome to ' . $SITE->fullname . '. You can get site announcements, '
                    . '<break time = "350ms"/>course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. '
                    . 'Which would you like?</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Hello. I can get you site announcements, <break time = "350ms"/>'
                . 'course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. Which would you like?</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test launch_request, logged in user.
     */
    public function test_launch_request_user() {
        global $SITE;
        $this->resetAfterTest();
        $launchrequest = self::getMethod('launch_request');

        $user = $this->getDataGenerator()->create_user(array('firstname' => 'Jane'));
        $this->setUser($user);

        $actual = $launchrequest->invokeArgs(null, array('token' => 'valid'));

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['ssml'] = "<speak>I didn't quite catch that. Which would you like?</speak>";
        $this->responsejson['response']['shouldEndSession'] = false;

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Welcome to ' . $SITE->fullname . ', Jane. You can get site announcements, '
                . '<break time = "350ms"/>course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. '
                        . 'Which would you like?</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Hello Jane. I can get you site announcements, <break time = "350ms"/>'
                . 'course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. Which would you like?</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_site_announcements, none.
     */
    public function test_get_site_announcements_0() {
        global $DB;
        $this->resetAfterTest();
        $getsiteannouncements = self::getMethod('get_announcements');

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => 1));

        $actual = $getsiteannouncements->invokeArgs(null, array(1, 'the site'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no announcements for the site.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but the site does not have any announcements.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_site_announcements, over course limit posts.
     */
    public function test_get_site_announcements_over_limit() {
        global $DB;
        $this->resetAfterTest();
        $getsiteannouncements = self::getMethod('get_announcements');

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => 1, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        $subject3 = 'Test subject 3';
        $message3 = 'Test message 3.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject3,
                'message' => $message3
        ));

        $subject4 = 'Test subject 4';
        $message4 = 'Test message 4.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject4,
                'message' => $message4
        ));

        // Frontpage users have guest role by default with mod/forum:viewdiscussion permission on course 1 announcements.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $this->assignUserCapability('mod/forum:viewdiscussion', 1);

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => 1));

        $actual = $getsiteannouncements->invokeArgs(null, array(1, 'the site'));

        $announcements = 'Subject: ' . $subject4 . '. Message: ' . $message4 . ' Subject: ' . $subject3 . '. Message: '
                . $message3 . ' Subject: ' . $subject2 . '. Message: ' . $message2 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the ' . $limit . ' most recent announcements for the site: '
                . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The ' . $limit . ' latest announcements for the site are: '
                . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_site_announcements, invalid limit.
     */
    public function test_get_site_announcements_limit_invalid() {
        global $DB;
        $this->resetAfterTest();
        $getsiteannouncements = self::getMethod('get_announcements');

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => 1, 'type' => 'news'));
        $subject = 'Test subject';
        $message = 'Test message.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject,
                'message' => $message
        ));

        // Frontpage users have guest role by default with mod/forum:viewdiscussion permission on course 1 announcements.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $this->assignUserCapability('mod/forum:viewdiscussion', 1);

        // For negative limit values, return 0 announcements.
        $limit = -1;
        $DB->set_field('course', 'newsitems', $limit, array('id' => 1));

        $actual = $getsiteannouncements->invokeArgs(null, array(1, 'the site'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no announcements for the site.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but the site does not have any announcements.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_site_announcements, post not visible.
     */
    public function test_get_site_announcements_invisible() {
        global $DB;
        $this->resetAfterTest();
        $getsiteannouncements = self::getMethod('get_announcements');

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => 1, 'type' => 'news'));
        $subject1 = 'Test subject1';
        $message1 = 'Test message1.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1,
                'timestart' => time() + 150
        ));

        $subject2 = 'Test subject2';
        $message2 = 'Test message2.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Frontpage users have guest role by default with mod/forum:viewdiscussion permission on course 1 announcements.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $this->assignUserCapability('mod/forum:viewdiscussion', 1);

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => 1));

        $actual = $getsiteannouncements->invokeArgs(null, array(1, 'the site'));

        $announcements = 'Subject: ' . $subject2 . '. Message: ' . $message2 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the 1 most recent announcements for the site: '
                . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The 1 latest announcements for the site are: '
                . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_site_announcements, no capability.
     */
    public function test_get_site_announcements_no_capability() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $getsiteannouncements = self::getMethod('get_announcements');

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => 1, 'type' => 'news'));
        $subject = 'Test subject';
        $message = 'Test message.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => 1,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject,
                'message' => $message
        ));

        // Set user as frontpage role and remove capability.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $CFG->defaultfrontpageroleid;
        $this->unassignUserCapability('mod/forum:viewdiscussion', 1, $roleid);

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => 1));

        $actual = $getsiteannouncements->invokeArgs(null, array(1, 'the site'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no announcements for the site.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but the site does not have any announcements.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test request_account_linking, invalid token.
     */
    public function test_request_account_linking() {
        global $SITE;
        $this->resetAfterTest();
        $requestaccountlinking = self::getMethod('request_account_linking');

        $task = 'test task';
        $actual = $requestaccountlinking->invokeArgs(null, array('task' => $task));

        $this->responsejson['response']['card']['type'] = 'LinkAccount';
        $this->responsejson['response']['outputSpeech']['text'] = 'You must have an account on ' . $SITE->fullname . ' to '
                . $task . '. Please use the Alexa app to link your Amazon account with your ' . $SITE->fullname . ' account.';

        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test get_course_announcements, no/invalid token.
     */
    public function test_get_course_announcements_invalid_token() {
        global $SITE;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => ''));

        $task = 'get course announcements';
        $this->responsejson['response']['card']['type'] = 'LinkAccount';
        $this->responsejson['response']['outputSpeech']['text'] = 'You must have an account on ' . $SITE->fullname . ' to '
                . $task . '. Please use the Alexa app to link your Amazon account with your ' . $SITE->fullname . ' account.';

        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test get_course_announcements, invalid pin.
     */
    public function test_get_course_announcements_invalid_pin() {
        global $SITE, $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '4321'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1234';
        local_alexaskill_external::$requestjson['session']['attributes']['pin'] = '';

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $this->responsejson['response']['outputSpeech']['text'] = "I'm sorry, that PIN is invalid. "
                . "You can use the Alexa app to relink your account and reset your PIN.";

        $expected = $this->responsejson;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test get_course_announcements, 0 courses.
     */
    public function test_get_course_announcements_0_courses() {
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you are not enrolled in any courses.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no active courses listed for you.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, 1 course 0 announcements.
     */
    public function test_get_course_announcements_1_course_0_announcements() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no announcements for ' . $coursename . '.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but ' . $coursename . ' does not have any announcements.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, more than 1 forum.
     */
    public function test_get_course_announcements_multiple_forums() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $roleid = $this->assignUserCapability('mod/forum:viewdiscussion', 1);

        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course->id, 'type' => 'news'));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course->id, 'type' => 'general'));

        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        $subject3 = 'Test subject 3';
        $message3 = 'Test message 3.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject3,
                'message' => $message3
        ));

        $subject4 = 'Test subject 4';
        $message4 = 'Test message 4.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject4,
                'message' => $message4
        ));

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $announcements = 'Subject: ' . $subject2 . '. Message: ' . $message2 . ' Subject: '
                . $subject1 . '. Message: ' . $message1 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the 2 most recent announcements for '
                . $coursename . ': ' . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The 2 latest announcements for ' . $coursename
                . ' are: ' . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, over course limit posts.
     */
    public function test_get_course_announcements_over_limit() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id, 'type' => 'news'));
        $roleid = $this->assignUserCapability('mod/forum:viewdiscussion', 1);

        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        $subject3 = 'Test subject 3';
        $message3 = 'Test message 3.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject3,
                'message' => $message3
        ));

        $subject4 = 'Test subject 4';
        $message4 = 'Test message 4.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject4,
                'message' => $message4
        ));

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $announcements = 'Subject: ' . $subject4 . '. Message: ' . $message4 . ' Subject: ' . $subject3 . '. Message: '
                . $message3 . ' Subject: ' . $subject2 . '. Message: ' . $message2 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the ' . $limit . ' most recent announcements for '
                . $coursename . ': ' . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The ' . $limit . ' latest announcements for ' . $coursename
            . ' are: ' . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, course limit > 5.
     */
    public function test_get_course_announcements_high_limit() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id, 'type' => 'news'));
        $roleid = $this->assignUserCapability('mod/forum:viewdiscussion', 1);

        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        $subject3 = 'Test subject 3';
        $message3 = 'Test message 3.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject3,
                'message' => $message3
        ));

        $subject4 = 'Test subject 4';
        $message4 = 'Test message 4.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject4,
                'message' => $message4
        ));

        $subject5 = 'Test subject 5';
        $message5 = 'Test message 5.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject5,
                'message' => $message5
        ));

        $subject6 = 'Test subject 6';
        $message6 = 'Test message 6.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject6,
                'message' => $message6
        ));

        $limit = 6;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $announcements = 'Subject: ' . $subject6 . '. Message: ' . $message6 . ' Subject: '
                . $subject5 . '. Message: ' . $message5 . ' Subject: ' . $subject4 . '. Message: ' . $message4 . ' Subject: '
                . $subject3 . '. Message: ' . $message3 . ' Subject: ' . $subject2 . '. Message: ' . $message2 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the 5 most recent announcements for '
                . $coursename . ': ' . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The 5 latest announcements for ' . $coursename
        . ' are: ' . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, 1+ courses prompt.
     */
    public function test_get_course_announcements_multiple_courses_prompt() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course 1 and forum post.
        $coursename1 = 'test course 1';
        $course1 = $this->getDataGenerator()->create_course(array('fullname' => $coursename1));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course1->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        // Create course 2 and forum post.
        $coursename2 = 'test course 2';
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => $coursename2));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id, 'type' => 'news'));
        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course2->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Create and enrol user as student, set capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');
        $roleid = $this->assignUserCapability('mod/forum:viewdiscussion', 1);

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['ssml'] = "<speak>I didn't quite catch that. Which would you like?</speak>";
        $this->responsejson['response']['shouldEndSession'] = false;
        $this->responsejson['response']['directives'] = array(
                array(
                        'type' => 'Dialog.ElicitSlot',
                        'slotToElicit' => 'course'
                )
        );

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Thanks. You can get announcements for the following courses: '
                . $coursename2 . ', <break time = "350ms"/> or ' . $coursename1 . '. Which would you like?</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Great. I can get announcements from the following courses for you: '
                . $coursename2 . ', <break time = "350ms"/> or ' . $coursename1 . '. Which would you like?</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, multiple courses, course known, COURSE slot match, valid.
     */
    public function test_get_course_announcements_multiple_courses_known_match_valid() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course 1 and forum post.
        $coursename1 = 'test course 1';
        $course1 = $this->getDataGenerator()->create_course(array('fullname' => $coursename1));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course1->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        // Create course 2 and forum post.
        $coursename2 = 'course 2';
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => $coursename2));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id, 'type' => 'news'));
        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course2->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Create and enrol user as student with capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['value'] = 'test course 1';
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['values'][0]['value']['name'] = 'test course 1';
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['status']['code'] = 'ER_SUCCESS_MATCH';

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course1->id));
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course2->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $announcements = 'Subject: ' . $subject1 . '. Message: ' . $message1 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the 1 most recent announcements for ' . $coursename1
        . ': ' . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The 1 latest announcements for ' . $coursename1 . ' are: '
                . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, multiple courses, course known, COURSE slot match, invalid.
     */
    public function test_get_course_announcements_multiple_courses_known_match_invalid() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course 1 and forum post.
        $coursename1 = 'course 1';
        $course1 = $this->getDataGenerator()->create_course(array('fullname' => $coursename1));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course1->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        // Create course 2 and forum post.
        $coursename2 = 'course 2';
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => $coursename2));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id, 'type' => 'news'));
        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course2->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Create and enrol user as student with capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['value'] = 'test';
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['values'][0]['value']['name'] = 'test course 3';
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['status']['code'] = 'ER_SUCCESS_MATCH';

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course1->id));
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course2->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no records for test.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but test does not have any records.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, multiple courses, course known, COURSE slot match, multiple matches.
     */
    public function test_get_course_announcements_multiple_courses_known_match_multiple() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course 1 and forum post.
        $coursename1 = 'test course 1';
        $course1 = $this->getDataGenerator()->create_course(array('fullname' => $coursename1));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course1->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        // Create course 2 and forum post.
        $coursename2 = 'test course 2';
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => $coursename2));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id, 'type' => 'news'));
        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course2->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Create and enrol user as student with capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['value'] = 'test';
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['values'][0]['value']['name'] = $coursename1;
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['values'][1]['value']['name'] = $coursename2;
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['status']['code'] = 'ER_SUCCESS_MATCH';

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course1->id));
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course2->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['ssml'] = "<speak>I didn't quite catch that. Which would you like?</speak>";
        $this->responsejson['response']['shouldEndSession'] = false;
        $this->responsejson['response']['directives'] = array(
                array(
                        'type' => 'Dialog.ElicitSlot',
                        'slotToElicit' => 'course'
                )
        );

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = "<speak>I'm sorry, I didn't quite catch that. You can get announcements for the following courses: "
                . $coursename2 . ', <break time = "350ms"/> or ' . $coursename1 . '. Which would you like?</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = "<speak>Sorry, I didn't catch that. I can get announcements from the following courses for you: "
                . $coursename2 . ', <break time = "350ms"/> or ' . $coursename1 . '. Which would you like?</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, invalid limit.
     */
    public function test_get_course_announcements_limit_invalid() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course and forum post.
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id, 'type' => 'news'));
        $subject = 'Test subject';
        $message = 'Test message.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject,
                'message' => $message
        ));

        // Create and enrol user as student with capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        // For negative limit values, return 0 announcements.
        $limit = -1;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no announcements for ' . $coursename . '.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but ' . $coursename . ' does not have any announcements.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, multiple courses, course known, no COURSE slot match, invalid.
     */
    public function test_get_course_announcements_multiple_courses_known_no_match_invalid() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course 1 and forum post.
        $coursename1 = 'test course 1';
        $course1 = $this->getDataGenerator()->create_course(array('fullname' => $coursename1));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course1->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        // Create course 2 and forum post.
        $coursename2 = 'test course 2';
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => $coursename2));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id, 'type' => 'news'));
        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course2->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Create and enrol user as student with capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['value'] = 'foo';
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['status']['code'] = 'ER_SUCCESS_NO_MATCH';

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course1->id));
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course2->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no records for foo.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but foo does not have any records.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, multiple courses, course known, no COURSE slot match, valid.
     */
    public function test_get_course_announcements_multiple_courses_known_no_match_valid() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course 1 and forum post.
        $coursename1 = 'foo course';
        $course1 = $this->getDataGenerator()->create_course(array('fullname' => $coursename1));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course1->id,
                'forum' => $forum1->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1
        ));

        // Create course 2 and forum post.
        $coursename2 = 'test course 2';
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => $coursename2));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id, 'type' => 'news'));
        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course2->id,
                'forum' => $forum2->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Create and enrol user as student with capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');

        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['value'] = 'foo';
        local_alexaskill_external::$requestjson['request']['intent']['slots']['course']['resolutions']['resolutionsPerAuthority'][0]['status']['code'] = 'ER_SUCCESS_NO_MATCH';

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course1->id));
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course2->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $announcements = 'Subject: ' . $subject1 . '. Message: ' . $message1 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the 1 most recent announcements for ' . $coursename1
        . ': ' . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The 1 latest announcements for ' . $coursename1 . ' are: '
                . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, post not visible.
     */
    public function test_get_course_announcements_invisible() {
        global $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        // Create course 1 and forum posts.
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id, 'type' => 'news'));
        $subject1 = 'Test subject 1';
        $message1 = 'Test message 1.';
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject1,
                'message' => $message1,
                'timestart' => time() + 150
        ));

        $subject2 = 'Test subject 2';
        $message2 = 'Test message 2.';
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject2,
                'message' => $message2
        ));

        // Create and enrol user as student with capabilities.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $announcements = 'Subject: ' . $subject2 . '. Message: ' . $message2 . ' ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Okay. Here are the 1 most recent announcements for ' . $coursename
            . ': ' . $announcements . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Sure. The 1 latest announcements for ' . $coursename . ' are: '
                . $announcements . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_course_announcements, no capability.
     */
    public function test_get_course_announcements_no_capability() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $getcourseannouncements = self::getMethod('get_course_announcements');

        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id, 'type' => 'news'));
        $subject = 'Test subject';
        $message = 'Test message.';
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(array(
                'course' => $course->id,
                'forum' => $forum->id,
                'userid' => '2',
                'name' => $subject,
                'message' => $message
        ));

        // Create and enrol user as student, remove capability.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'student'), 'id');
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);
        $this->unassignUserCapability('mod/forum:viewdiscussion', 1, $role->id);

        $limit = 3;
        $DB->set_field('course', 'newsitems', $limit, array('id' => $course->id));

        $actual = $getcourseannouncements->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, there are no announcements for ' . $coursename . '.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but ' . $coursename . ' does not have any announcements.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_grades, no/invalid token.
     */
    public function test_get_grades_invalid_token() {
        global $SITE;
        $this->resetAfterTest();
        $getgrades = self::getMethod('get_grades');

        $actual = $getgrades->invokeArgs(null, array('token' => ''));

        $task = 'get grades';
        $this->responsejson['response']['card']['type'] = 'LinkAccount';
        $this->responsejson['response']['outputSpeech']['text'] = 'You must have an account on ' . $SITE->fullname . ' to '
                . $task . '. Please use the Alexa app to link your Amazon account with your ' . $SITE->fullname . ' account.';

        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test get_grades, invalid pin.
     */
    public function test_get_grades_invalid_pin() {
        global $SITE, $DB;
        $this->resetAfterTest();
        $getgrades = self::getMethod('get_grades');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '4321'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1234';
        local_alexaskill_external::$requestjson['session']['attributes']['pin'] = '';

        $actual = $getgrades->invokeArgs(null, array('token' => 'valid'));

        $this->responsejson['response']['outputSpeech']['text'] = "I'm sorry, that PIN is invalid. "
                . "You can use the Alexa app to relink your account and reset your PIN.";

        $expected = $this->responsejson;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test get_grades, 0 grades.
     */
    public function test_get_grades_0() {
        $this->resetAfterTest();
        $getgrades = self::getMethod('get_grades');

        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $actual = $getgrades->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you have no overall grades posted.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no overall grades posted for your courses.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_grades, 1+ grades.
     */
    public function test_get_grades_1() {
        global $DB;
        $this->resetAfterTest();
        $getgrades = self::getMethod('get_grades');

        $coursename1 = 'test course 1';
        $course1 = $this->getDataGenerator()->create_course(array('fullname' => $coursename1));
        $coursename2 = 'test course 2';
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => $coursename2));

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        $gradeitem1 = $this->getDataGenerator()->create_grade_item(array('itemtype' => 'course', 'courseid' => $course1->id));
        $DB->insert_record('grade_grades', array('itemid' => $gradeitem1->id, 'userid' => $user->id, 'finalgrade' => 98));
        $gradeitem2 = $this->getDataGenerator()->create_grade_item(array('itemtype' => 'course', 'courseid' => $course2->id));
        $DB->insert_record('grade_grades', array('itemid' => $gradeitem2->id, 'userid' => $user->id, 'finalgrade' => 99));

        $actual = $getgrades->invokeArgs(null, array('token' => 'valid'));

        $grades = '<p>Your grade in ' . $coursename2 . ' is 99.00.</p> <p>Your grade in ' . $coursename1 . ' is 98.00.</p> ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Got it. Here are your overall course grades: ' . $grades . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Okay. These are your course grades overall: ' . $grades . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, no/invalid token.
     */
    public function test_get_due_dates_invalid_token() {
        global $SITE;
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $actual = $getduedates->invokeArgs(null, array('token' => ''));

        $task = 'get due dates';
        $this->responsejson['response']['card']['type'] = 'LinkAccount';
        $this->responsejson['response']['outputSpeech']['text'] = 'You must have an account on ' . $SITE->fullname . ' to '
                . $task . '. Please use the Alexa app to link your Amazon account with your ' . $SITE->fullname . ' account.';

        $expected = $this->responsejson;

        $this->assertTrue($expected == $actual);
    }

    /**
     * Test get_due_dates, invalid pin.
     */
    public function test_get_due_dates_invalid_pin() {
        global $SITE, $DB;
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $DB->insert_record('user_info_data', array('userid' => $user->id, 'fieldid' => $field->id, 'data' => '4321'));
        local_alexaskill_external::$requestjson['request']['intent']['slots']['pin']['value'] = '1234';
        local_alexaskill_external::$requestjson['session']['attributes']['pin'] = '';

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $this->responsejson['response']['outputSpeech']['text'] = "I'm sorry, that PIN is invalid. "
                . "You can use the Alexa app to relink your account and reset your PIN.";

        $expected = $this->responsejson;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test get_due_dates, valid 0 due dates.
     */
    public function test_get_due_dates_0() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you have no upcoming events in the next 3 weeks.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no upcoming events on your calendar for the next 3 weeks.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, valid over limit due dates.
     */
    public function test_get_due_dates_over_limit() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));
        $eventname3 = 'assignment 3';
        $eventdate3 = time() + (3 * 86400);
        $assignment3 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname3, 'duedate' => $eventdate3));
        $eventname4 = 'assignment 4';
        $eventdate4 = time() + (4 * 86400);
        $assignment4 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname4, 'duedate' => $eventdate4));
        $eventname5 = 'assignment 5';
        $eventdate5 = time() + (5 * 86400);
        $assignment5 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname5, 'duedate' => $eventdate5));
        $eventname6 = 'assignment 6';
        $eventdate6 = time() + (6 * 86400);
        $assignment6 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname6, 'duedate' => $eventdate6));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = 5;
        set_config('calendar_maxevents', $limit);
        $lookahead = 21;
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $duedates = '<p>' . $eventname1 . ' is due on ' . date('l F j Y g:i a', $eventdate1) . '.</p> <p>'
                . $eventname2 . ' is due on ' . date('l F j Y g:i a', $eventdate2) . '.</p> <p>'
                . $eventname3 . ' is due on ' . date('l F j Y g:i a', $eventdate3) . '.</p> <p>'
                . $eventname4 . ' is due on ' . date('l F j Y g:i a', $eventdate4) . '.</p> <p>'
                . $eventname5 . ' is due on ' . date('l F j Y g:i a', $eventdate5) . '.</p> ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Got it. Here are the next ' . $limit . ' upcoming events for 3 weeks: ' . $duedates . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Okay. The next ' . $limit . ' important dates for 3 weeks are: ' . $duedates . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, valid over lookahead due dates.
     */
    public function test_get_due_dates_over_lookahead() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (30 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = 5;
        set_config('calendar_maxevents', $limit);
        $lookahead = 21;
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $duedates = '<p>' . $eventname1 . ' is due on ' . date('l F j Y g:i a', $eventdate1) . '.</p> ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Got it. Here are the next 1 upcoming events for 3 weeks: ' . $duedates . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Okay. The next 1 important dates for 3 weeks are: ' . $duedates . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, before now.
     */
    public function test_get_due_dates_before_now() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() - (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = 5;
        set_config('calendar_maxevents', $limit);
        $lookahead = 21;
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $duedates = '<p>' . $eventname1 . ' is due on ' . date('l F j Y g:i a', $eventdate1) . '.</p> ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Got it. Here are the next 1 upcoming events for 3 weeks: ' . $duedates . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Okay. The next 1 important dates for 3 weeks are: ' . $duedates . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, limit and lookahead null.
     */
    public function test_get_due_dates_limit_lookahead_invalid_null() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = null;
        set_config('calendar_maxevents', $limit);
        $lookahead = null;
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you have no upcoming events in the next 3 weeks.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no upcoming events on your calendar for the next 3 weeks.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, limit and lookahead empty.
     */
    public function test_get_due_dates_limit_lookahead_invalid_empty() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = '';
        set_config('calendar_maxevents', $limit);
        $lookahead = '';
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you have no upcoming events in the next 3 weeks.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no upcoming events on your calendar for the next 3 weeks.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, limit and lookahead non-numeric.
     */
    public function test_get_due_dates_limit_lookahead_invalid_nonnumeric() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = 'foo';
        set_config('calendar_maxevents', $limit);
        $lookahead = 'foo';
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you have no upcoming events in the next 3 weeks.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no upcoming events on your calendar for the next 3 weeks.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, limit and lookahead < 0.
     */
    public function test_get_due_dates_limit_lookahead_invalid_negative() {
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $limit = -1;
        set_config('calendar_maxevents', $limit);
        $lookahead = -1;
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you have no upcoming events in the next 3 weeks.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no upcoming events on your calendar for the next 3 weeks.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, event not visible.
     */
    public function test_get_due_dates_invisible() {
        global $DB;
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2, 'visible' => 0, 'visibleold' => 0));

        $this->setUser(null);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $limit = 5;
        set_config('calendar_maxevents', $limit);
        $lookahead = 21;
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $duedates = '<p>' . $eventname1 . ' is due on ' . date('l F j Y g:i a', $eventdate1) . '.</p> ';

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>Got it. Here are the next 1 upcoming events for 3 weeks: ' . $duedates . '</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>Okay. The next 1 important dates for 3 weeks are: ' . $duedates . '</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_due_dates, no capability.
     */
    public function test_get_due_dates_no_capability() {
        global $DB;
        $this->resetAfterTest();
        $getduedates = self::getMethod('get_due_dates');

        $this->setAdminUser();
        $coursename = 'test course';
        $course = $this->getDataGenerator()->create_course(array('fullname' => $coursename));
        $eventname1 = 'assignment 1';
        $eventdate1 = time() + 86400;
        $assignment1 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname1, 'duedate' => $eventdate1));
        $eventname2 = 'assignment 2';
        $eventdate2 = time() + (2 * 86400);
        $assignment2 = $this->getDataGenerator()->create_module('assign',
                array('course' => $course->id, 'name' => $eventname2, 'duedate' => $eventdate2));

        $this->setUser(null);

        // Create and enrol user as student, remove capability.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'student'), 'id');
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);
        $this->unassignUserCapability('mod/assign:view', 1, $role->id);

        $limit = 5;
        set_config('calendar_maxevents', $limit);
        $lookahead = 21;
        set_config('calendar_lookahead', $lookahead);

        $actual = $getduedates->invokeArgs(null, array('token' => 'valid'));

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Sorry, you have no upcoming events in the next 3 weeks.';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'I apologize, but there are no upcoming events on your calendar for the next 3 weeks.';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_preferred_course_name.
     */
    public function test_get_preferred_course_name() {
        $this->resetAfterTest();
        $getpreferredcoursename = self::getMethod('get_preferred_course_name');

        $coursename = LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEXMATCH;
        $actual = $getpreferredcoursename->invokeArgs(null, array('coursefullname' => $coursename));

        preg_match(LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEX, $coursename, $matches);
        $expected = strtolower($matches[1]);

        $this->assertEquals($expected, $actual);

        $coursename = LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEXNOMATCH;
        $actual = $getpreferredcoursename->invokeArgs(null, array('coursefullname' => $coursename));

        $expected = strtolower($coursename);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test get_help.
     */
    public function test_get_help() {
        $this->resetAfterTest();
        $gethelp = self::getMethod('get_help');

        $actual = $gethelp->invokeArgs(null, array());

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['ssml'] = "<speak>I didn't quite catch that. Which would you like?</speak>";
        $this->responsejson['response']['shouldEndSession'] = false;

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = '<speak>You can get site announcements, <break time = "350ms"/>course announcements, <break time = "350ms"/>'
                . 'grades, <break time = "350ms"/>or due dates. Which would you like?</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = '<speak>I can get you site announcements, <break time = "350ms"/>course announcements, <break time = "350ms"/>'
                . 'grades, <break time = "350ms"/>or due dates. Which would you like?</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test get_help, fallback intent.
     */
    public function test_get_help_fallback() {
        $this->resetAfterTest();
        $gethelp = self::getMethod('get_help');

        $actual = $gethelp->invokeArgs(null, array(true));

        $this->responsejson['response']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['type'] = 'SSML';
        $this->responsejson['response']['reprompt']['outputSpeech']['ssml'] = "<speak>I didn't quite catch that. Which would you like?</speak>";
        $this->responsejson['response']['shouldEndSession'] = false;

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['ssml'] = "<speak>I'm sorry, I can't help you with that yet. You can get site announcements, "
                . '<break time = "350ms"/>course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. Which would you like?</speak>';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['ssml'] = "<speak>I'm sorry, I can't help you with that yet. I can get you site announcements, "
                . '<break time = "350ms"/>course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. Which would you like?</speak>';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual);
    }

    /**
     * Test say_good_bye.
     */
    public function test_say_good_bye() {
        $this->resetAfterTest();
        $saygoodbye = self::getMethod('say_good_bye');

        $actual = $saygoodbye->invokeArgs(null, array());

        $expected1 = $this->responsejson;
        $expected1['response']['outputSpeech']['text'] = 'Okay, have a nice day!';

        $expected2 = $this->responsejson;
        $expected2['response']['outputSpeech']['text'] = 'Great. Take care!';

        $expected3 = $this->responsejson;
        $expected3['response']['outputSpeech']['text'] = 'Thanks. Good bye!';

        $expected4 = $this->responsejson;
        $expected4['response']['outputSpeech']['text'] = 'Sure. Until next time!';

        $this->assertTrue($expected1 == $actual || $expected2 == $actual
                || $expected3 == $actual || $expected4 == $actual);
    }

    /**
     * Test session_ended_request.
     */
    public function test_session_ended_request() {
        $this->resetAfterTest();
        $sessionendedrequest = self::getMethod('session_ended_request');

        $actual = $sessionendedrequest->invokeArgs(null, array());
        $this->assertDebuggingCalledCount(3);
    }
}