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
 * This file contains the moodle hooks for the submission Mahara plugin
 *
 * @package    assignsubmission_maharaws
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require("../../../../config.php");

require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

confirm_sesskey();

$id = required_param('id', PARAM_INT); // Assignment id.
$target = required_param('url', PARAM_URL); // Mahara view launch.

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/assign:view', $context);

$url = new \moodle_url('/mod/assign/view.php', array('id' => $id, 'sesskey' => sesskey()));
$returnurl = $url->out(false);
$urlparts = parse_url($CFG->wwwroot);
$extuserusername = $USER->username;
$assign = new assign($context, $cm, $course);
$maharasubmission = new assign_submission_maharaws($assign, 'assignsubmission_maharaws');

try {
    $remotehost = (object) $maharasubmission->webservice_call("mahara_user_get_extended_context", array());
} catch (Exception $e) {
    debugging("Remote host webservice call failed: " . $e->getCode() . ":" . $e->getMessage());
    throw new moodle_exception('errorwsrequest', 'assignsubmission_maharaws', '', $e->getMessage());
}
$ltitypes = array();
// Check if there are related LTI instances on Mahara side
foreach ($remotehost->functions as $func) {
    if (preg_match('/_lti_/', $func['function'])) {
        $ltitypes[] = $func['function'];
    }
}

if ( get_config('assignsubmission_maharaws', 'legacy_ext_usr_username') ) {
    // Determine the Mahara field and the username value.
    $usernameattribute = $maharasubmission->get_config_default('username_attribute');
    $remoteuser = $maharasubmission->get_config_default('remoteuser');
    $username = (!empty($CFG->mahara_test_user) ? $CFG->mahara_test_user : $USER->{$usernameattribute});
    $field =
        // Now the trump all - we actually want to test against the institutions auth instances remoteuser.
        ($remoteuser ?
        'remoteuser' :
            // Else idnumber maps to studentid.
            ($usernameattribute == 'idnumber' ?
                'studentid' :
                // Else the same attribute name in Mahara.
                $usernameattribute));
    $extuserusername = $field.':'.$username;
}

$ltiversion = LTI_VERSION_1;
$wsfunction = 'module_lti_launch';
if (in_array('module_lti_advantage_launch', $ltitypes)) {
    // Mahara accepts LTI 1.3 access
    $ltiversion = LTI_VERSION_1P3;
    $wsfunction = 'module_lti_advantage_launch';
    // @TODO: Need to login via LTI 1.3 with the credentials saved against this module for LTI 1.3
    // rather than using the mdl_lti table

    // NOT CORRECT WAY - piggybacking on LTI 1.3 connection already existing for course
    // Find the related LTI instances for this Assignment module
    $sql = "SELECT l.*, lt.ltiversion, lt.baseurl
            FROM {lti} l
            JOIN {lti_types} lt
            ON lt.id = l.typeid
            WHERE l.course = :course";

    $ltis = $DB->get_records_sql($sql, array('course' => $course->id));
    // So lets find our LTI 1.3 access
    foreach ($ltis as $lti) {
        if ($lti->ltiversion == $ltiversion) {
            $url = new \moodle_url('/mod/lti/view.php', array('l' => $lti->id));
            $typeid = $lti->typeid;
            if (empty($typeid) && ($tool = lti_get_tool_by_url_match($lti->toolurl))) {
                $typeid = $tool->id;
            }
            if ($typeid) {
                $config = lti_get_type_type_config($typeid);
            }
            // Need to do the login via the LTI module so we need its ID
            $sql = "SELECT cm.id FROM {course_modules} cm
                    JOIN {modules} md ON md.id = cm.module
                    JOIN {lti} m ON m.id = cm.instance
                    WHERE cm.course = :course
                    AND md.name = :name
                    AND cm.instance = :instance";
            $ltimoduleid = $DB->get_field_sql($sql, array('course' => $course->id,
                                                          'name' => 'lti',
                                                          'instance' => $lti->id));
            $target_array = array();
            // Check to see if we are meant to access a portfolio and if so send that path
            if ($target) {
                $target = rtrim($target, '/');
                if ($target !== $lti->baseurl) {
                    $target_array = array('resource_launch' => array('PublicUrl' => $lti->baseurl . $target));
                }
            }
            error_log('trying LTI 1.3 login');
            echo lti_initiate_login_maharaws($cm->course, $ltimoduleid, $lti, $config, 'basic-lti-launch-request', '', '', $target_array);
        }
    }
    // END - NOT CORRECT WAY
}
else if (in_array('module_lti_launch', $ltitypes)) {
    error_log('trying LTI 1.1 login');
    $requestparams = array(
        'resource_link_title' => $cm->name,
        'resource_link_description' => $cm->name,
        'user_id' => $USER->id,
        'lis_person_sourcedid' => $USER->idnumber,
        'roles' => 'Learner',
        'context_id' => $course->id,
        'context_label' => $course->shortname,
        'context_title' => $course->fullname,
        'resource_link_id' => $target,
        'context_type' => 'CourseSection',
        'lis_person_contact_email_primary' => $USER->email,
        'lis_person_name_given' => $USER->firstname,
        'lis_person_name_family' => $USER->lastname,
        'lis_person_name_full' => fullname($USER),
        'ext_user_username' => $extuserusername,
        'launch_presentation_return_url' => $returnurl,
        'launch_presentation_locale' => current_language(),
        'ext_lms' => 'moodle-2',
        'tool_consumer_info_product_family_code' => 'moodle',
        'tool_consumer_info_version' => strval($CFG->version),
        'oauth_callback' => 'about:blank',
        'lti_version' => $ltiversion,
        'lti_message_type' => 'basic-lti-launch-request',
        "tool_consumer_instance_guid" => $urlparts['host'],
        'tool_consumer_instance_name' => get_site()->fullname,
        'wsfunction' => $wsfunction,
    );

    $endpoint = $maharasubmission->get_config_default('url');
    $key = $maharasubmission->get_config_default('key');
    $secret = $maharasubmission->get_config_default('secret');

    $requestparams = array_merge($requestparams, $extra_requestparams);

    $endpoint = $endpoint.(preg_match('/\/$/', $endpoint) ? '' : '/').'webservice/rest/server.php';

    $parms = lti_sign_parameters($requestparams, $endpoint, "POST", $key, $secret);
    $debuglaunch = $maharasubmission->get_config_default('debug');
    if ($debuglaunch) {
        $parms['ext_submit'] = 'Launch';
    }
    $endpointurl = new \moodle_url($endpoint);
    $endpointparams = $endpointurl->params();

    $content = lti_post_launch_html($parms, $endpoint, $debuglaunch);

    echo $content;
}
else {
    throw new moodle_exception('errorwsrequestnolti', 'assignsubmission_maharaws');
}

/**
 * Generate the form for initiating a login request for an LTI 1.3 message
 * This is a copy of the lti_initiate_login() with $extraparams added
 *
 * @param int            $courseid  Course ID
 * @param int            $id        LTI instance ID
 * @param stdClass|null  $instance  LTI instance
 * @param stdClass       $config    Tool type configuration
 * @param string         $messagetype   LTI message type
 * @param string         $title     Title of content item
 * @param string         $text      Description of content item
 * @param array          $extraparams
 * @return string
 */
function lti_initiate_login_maharaws($courseid, $id, $instance, $config, $messagetype = 'basic-lti-launch-request', $title = '', $text = '', $extraparams=array()) {
    global $SESSION, $CFG, $USER, $maharasubmission;

    $params = array();
    $params['iss'] = $CFG->wwwroot;
    $params['target_link_uri'] = $maharasubmission->get_config_default('url');
    $params['login_hint'] = $USER->id;
    $params['lti_message_hint'] = $id;
    $params['client_id'] = $maharasubmission->get_config_default('clientid');
    $params['lti_deployment_id'] = 'maharaws';
    $params = array_merge($params, $extraparams);

    $SESSION->lti_message_hint = "{$courseid},{$config->typeid},{$id}," . base64_encode($title) . ',' . base64_encode($text);
    error_log('Send initial request'):
        $r = "<form action=\"" . $maharasubmission->get_config_default('initiatelogin') .
              "\" name=\"ltiInitiateLoginForm\" id=\"ltiInitiateLoginForm\" method=\"post\" " .
              "encType=\"application/x-www-form-urlencoded\">\n";

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    $key = htmlspecialchars($key) . '[' . $subkey . ']';
                    $value = htmlspecialchars($subvalue);
                    $r .= "  <input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>\n";
                }
            }
            else {
                $key = htmlspecialchars($key);
                $value = htmlspecialchars($value);
                $r .= "  <input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>\n";
            }
        }
        $r .= "</form>\n";
        $r .= "<script type=\"text/javascript\">\n" .
              "//<![CDATA[\n" .
              "document.ltiInitiateLoginForm.submit();\n" .
              "//]]>\n" .
              "</script>\n";

        return $r;
}
