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

// Determine the Mahara field and the username value.
$usernameattribute = mahara_assignsubmission_get_config($cm->instance, 'username_attribute');
$remoteuser = mahara_assignsubmission_get_config($cm->instance, 'remoteuser');
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
        'lis_person_name_full' => $USER->firstname . ' ' . $USER->lastname,
        'ext_user_username' => $field.':'.$username,
        'launch_presentation_return_url' => $returnurl,
        'launch_presentation_locale' => current_language(),
        'ext_lms' => 'moodle-2',
        'tool_consumer_info_product_family_code' => 'moodle',
        'tool_consumer_info_version' => strval($CFG->version),
        'oauth_callback' => 'about:blank',
        'lti_version' => 'LTI-1p0',
        'lti_message_type' => 'basic-lti-launch-request',
        "tool_consumer_instance_guid" => $urlparts['host'],
        'tool_consumer_instance_name' => get_site()->fullname,
        'wsfunction' => 'module_lti_launch',
        );


$endpoint = mahara_assignsubmission_get_config($cm->instance, 'url');
$endpoint = $endpoint.(preg_match('/\/$/', $endpoint) ? '' : '/').'webservice/rest/server.php';
$key = mahara_assignsubmission_get_config($cm->instance, 'key');
$secret = mahara_assignsubmission_get_config($cm->instance, 'secret');

$parms = lti_sign_parameters($requestparams, $endpoint, "POST", $key, $secret);
$debuglaunch = mahara_assignsubmission_get_config($cm->instance, 'debug');
if ($debuglaunch) {
    $parms['ext_submit'] = 'Launch';
}
$endpointurl = new \moodle_url($endpoint);
$endpointparams = $endpointurl->params();
$content = lti_post_launch_html($parms, $endpoint, $debuglaunch);

echo $content;


function mahara_assignsubmission_get_config ($id, $setting) {
    global $DB;

    $dbparams = array('assignment' => $id,
                      'plugin' => 'maharaws',
                      'subtype' => 'assignsubmission',
                      'name' => $setting);
    $result = $DB->get_record('assign_plugin_config', $dbparams, '*', IGNORE_MISSING);
    if ($result) {
        return $result->value;
    }
    return false;
}
