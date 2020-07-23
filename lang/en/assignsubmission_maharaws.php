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
 * Strings for component 'assignsubmission_maharaws', language 'en'
 *
 * @package   assignsubmission_maharaws
 * @copyright 2014 Lancaster University (@link http://www.lancaster.ac.uk/)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assign_submission_maharaws_name'] = 'Assign Submission Mahara services';
$string['assign_submission_maharaws_description'] = 'Mahara functions used in Mahara Assignment Submission plugin.<br />Publishing this service on a Moodle site has no effect. Subscribe to this service if you want to be able to use assignments with {$a}.<br />';
$string['collectionsby'] = 'Collections by {$a}';
$string['debug_help'] = 'Debug option to interupt the OAuth SSO login jump so parameters can be inspected';
$string['debug'] = 'Debug OAuth';
$string['defaultlockpages_help'] = 'Default setting to use for the "{$a}" setting in new Mahara assignments.';
$string['defaultlockpages'] = 'Default "{$a}"';
$string['defaulton_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['defaulton'] = 'Enabled by default';
$string['defaultsite_help'] = 'Default setting to use for the "{$a}" setting in new Mahara assignments.';
$string['defaultsite'] = 'Default "{$a}"';
$string['emptysubmission'] = 'You have not chosen the page to submit.';
$string['enabled_help'] = "If enabled, students are able to submit Mahara pages and collections for assessment on this site in this course. ";
$string['enabled'] = 'Mahara';
$string['errorinvalidhost'] = 'Invalid host id selected';
$string['errorinvalidstatus'] = 'Developer Error: Invalid submission status sent to assign_submission_mahara::set_mahara_submission_status()';
$string['errorinvalidurl'] = 'Error when connecting to Mahara webservice. {$a}';
$string['errorrequest'] = 'Attempt to send OAuth request resulted in error: {$a}';
$string['errorvieworcollectionalreadysubmitted'] = 'The selected Mahara view or collection could not be submitted. Please choose another.';
$string['errorwsrequest'] = 'Attempt to send Mahara request resulted in error: {$a}';
$string['eventassessableuploaded'] = 'A Mahara page or collection has been submitted.';
$string['invalidurlhelp'] = " Check that URL and OAuth credentials are correct, and that there is a valid SSL certificate if HTTPS is used.  Also check that the correct functions are assigned to the OAuth access.";
$string['key_help'] = 'Enter the Web Services OAuth key from the partner Mahara site.';
$string['key'] = 'Mahara Web Services OAuth key';
$string['lockpages_help'] = 'If "Yes, keep locked" is selected, submitted Mahara pages and collections will be locked from editing in Mahara and will remain locked after grading. If "Yes, but unlock after grading", the page or collection will be unlocked after submission grading, or, if marking workflow has been used, they will be unlocked when marks are released to student.';
$string['lockpages'] = 'Lock submitted pages';
$string['mahara'] = 'Mahara';
$string['maharaws:configure'] = 'Configure mahara submission';
$string['nomaharahostsfound'] = 'No mahara hosts found.';
$string['noneselected'] = 'None selected';
$string['noviewscreated'] = 'You have no available Mahara pages or collections. Please <a href="{$a->jumpurl}" target="_blank" rel="noopener noreferrer">click here</a> to visit "{$a->name}" and create a new one.';
$string['option_collections'] = 'Collections';
$string['option_views'] = 'Views';
$string['outputforlog'] = '{$a->remotehostname}: {$a->viewtitle} (view id: {$a->viewid})';
$string['outputforlognew'] = 'New {$a} submission.';
$string['pluginname'] = 'Mahara Submissions';
$string['previousattemptsnotvisible'] = 'Previous attempts with the Mahara submission plugin are not visible.';
$string['privacy:metadata:assignment'] = 'The id of the Assignment';
$string['privacy:metadata:assignsubmission_maharaws'] = 'Stores information about Mahara pages and collections submitted to assignments.';
$string['privacy:metadata:iscollection'] = 'Is this submission a page or collection';
$string['privacy:metadata:submission'] = 'The id of the submission';
$string['privacy:metadata:viewid'] = 'The id of the Mahara page or collection';
$string['privacy:metadata:viewstatus'] = 'The status of the Mahara page or collection';
$string['privacy:metadata:viewtitle'] = 'The title of the Mahara page or collection';
$string['privacy:metadata:viewurl'] = 'The url of the Mahara page or collection';
$string['privacy:path'] = 'Mahara pages and collections';
$string['remoteuser_help'] = 'Map the selected Moodle username attribute to the Mahara users remoteuser id within the institution authentication source.';
$string['remoteuser'] = 'Use remoteuser';
$string['secret_help'] = 'Enter the Web Services OAuth secret from the partner Mahara site.';
$string['secret'] = 'Mahara Web Services OAuth secret';
$string['selectmaharaview'] = 'Select one of your available portfolio pages or collections from the list below, or <a href="{$a->jumpurl}" target="_blank" rel="noopener noreferrer">click here</a> to visit "{$a->name}" and create a new one.';
$string['sharewith'] = 'Ensure that your {$a->site} pages are shared for grading with: {$a->names}';
$string['token_help'] = 'Enter the Web Services authentication token from the partner Mahara site.';
$string['token'] = 'Mahara Web Services token';
$string['url_help'] = 'This setting lets you define which Mahara site your students should submit their pages from.  Enter the fully qualified URL to the Mahara site home page eg: https://mahara.some.edu/mahara/';
$string['url'] = 'URL for Mahara site';
$string['username_attribute_help'] = 'The Moodle user attribute to use as the Mahara remote username to map to Mahara users. In Mahara, email maps to email, username to username and idnumber to studentid.';
$string['username_attribute'] = 'Username Attribute';
$string['viewsby'] = 'Pages by {$a}';
$string['yeskeeplocked'] = 'Yes, keep locked';
$string['yesunlock'] = 'Yes, but unlock after grading';