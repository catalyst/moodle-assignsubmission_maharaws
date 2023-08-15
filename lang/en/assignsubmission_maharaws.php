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
 * @copyright 2014 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assign_submission_maharaws_name'] = 'Mahara Assignment submission (web services)';
$string['assign_submission_maharaws_description'] = 'Mahara functions used in Mahara assignment submission plugin.<br />Publishing this service on a Moodle site has no effect. Subscribe to this service if you want to be able to use assignments with {$a}.<br />';
$string['collectionsby'] = 'Collections by {$a}';
$string['debug_help'] = 'Debug option to interupt the OAuth SSO login jump so parameters can be inspected';
$string['debug'] = 'Debug OAuth';
$string['defaultlockpages_help'] = 'Default setting to use for the "{$a}" setting in new Mahara assignments.';
$string['defaultlockpages'] = 'Default "{$a}"';
$string['defaulton_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['defaulton'] = 'Enabled by default';
$string['defaultsite_help'] = 'Default setting to use for the "{$a}" setting in new Mahara assignments.';
$string['defaultsite'] = 'Default "{$a}"';
$string['emptysubmission'] = 'You have not chosen a portfolio to submit.';
$string['enabled_help'] = "If enabled, students are able to submit Mahara pages and collections for assessment on this site in this course. ";
$string['enabled'] = 'Mahara';
$string['errorinvalidhost'] = 'Invalid host ID selected';
$string['errorinvalidstatus'] = 'Developer error: Invalid submission status sent to assign_submission_mahara::set_mahara_submission_status()';
$string['errorinvalidurl'] = 'Error when connecting to Mahara web services. {$a}';
$string['errorrequest'] = 'Attempt to send OAuth request resulted in error: {$a}';
$string['errorvieworcollectionalreadysubmitted'] = 'The selected page or collection could not be submitted. Please choose another.';
$string['errorwsrequest'] = 'Attempt to send Mahara request resulted in error: {$a}';
$string['eventassessableuploaded'] = 'A page or collection has been submitted.';
$string['forceglobalcredentials'] = 'Force global credentials';
$string['forceglobalcredentials_help'] = 'Always use these credentials to connect to Mahara';
$string['forceglobalcredentialswarning'] = 'Mahara url and credentials have been set globally';
$string['gclabel'] = 'Global credentials';
$string['invalidurlhelp'] = " Check that URL and OAuth credentials are correct and that there is a valid SSL certificate if HTTPS is used. Also check that the correct functions are assigned to the OAuth access.";
$string['key_help'] = 'Enter the web services OAuth key from the partner Mahara site.';
$string['key'] = 'Mahara web services OAuth key';
$string['lockpages_help'] = 'If "Yes, keep locked" is selected, submitted Mahara pages and collections will be locked from editing in Mahara and will remain locked after grading. If "Yes, but unlock after grading", the page or collection will be unlocked after submission grading, or, if marking workflow has been used, they will be unlocked when marks are released to student.';
$string['lockpages'] = 'Lock submitted portfolios';
$string['mahara'] = 'Mahara';
$string['maharaws:configure'] = 'Configure Mahara submission';
$string['nomaharahostsfound'] = 'No Mahara hosts found.';
$string['noneselected'] = 'None selected';
$string['noviewscreated'] = 'You have no available pages or collections. Please visit "{$a->name}" and <a href="{$a->jumpurl}" target="_blank" rel="noopener noreferrer">create a new one</a>.';
$string['option_collections'] = 'Collections';
$string['option_views'] = 'Pages';
$string['outputforlog'] = '{$a->remotehostname}: {$a->viewtitle} (view id: {$a->viewid})';
$string['outputforlognew'] = 'New {$a} submission.';
$string['pluginname'] = 'Mahara submissions';
$string['previousattemptsnotvisible'] = 'Previous attempts with the Mahara submission plugin are not visible.';
$string['privacy:metadata:assignment'] = 'The ID of the assignment';
$string['privacy:metadata:assignsubmission_maharaws'] = 'Stores information about Mahara pages and collections submitted to assignments.';
$string['privacy:metadata:iscollection'] = 'Is this submission a page or collection?';
$string['privacy:metadata:submission'] = 'The ID of the submission';
$string['privacy:metadata:viewid'] = 'The ID of the Mahara page or collection';
$string['privacy:metadata:viewstatus'] = 'The status of the Mahara page or collection';
$string['privacy:metadata:viewtitle'] = 'The title of the Mahara page or collection';
$string['privacy:metadata:viewurl'] = 'The URL of the Mahara page or collection';
$string['privacy:path'] = 'Mahara pages and collections';
$string['remoteuser'] = 'Use remoteuser';
$string['secret_help'] = 'Enter the web services OAuth secret from the partner Mahara site.';
$string['secret'] = 'Mahara web services OAuth secret';
$string['selectmaharaview'] = 'Select one of your available portfolio pages or collections from the list below or visit "{$a->name}" and <a href="{$a->jumpurl}" target="_blank" rel="noopener noreferrer">create a new one</a>.';
$string['token_help'] = 'Enter the web services authentication token from the partner Mahara site.';
$string['token'] = 'Mahara web services token';
$string['url_help'] = 'This setting lets you define which Mahara site your students should submit their portfolios from. Enter the URL to the Mahara site, e.g. https://mahara.some.edu/ .';
$string['url'] = 'URL for the Mahara site';
$string['viewsby'] = 'Pages by {$a}';
$string['yeskeeplocked'] = 'Yes, keep locked';
$string['yesunlock'] = 'Yes, but unlock after grading';
$string['archiveonrelease'] = 'Archive when graded';
$string['archiveonrelease_help'] = 'After a grade has been awarded, a snapshot of the portfolio will be taken.';
$string['legacy_ext_username'] = "Use legacy ext_user_username format";
$string['legacy_ext_username_help'] = "Enabling this option makes the ext_usr_username field format follow the following setup \"Fieldname:value\" It is not reccomended you enable this setting unless you have a specific reason to.";
$string['privacy:metadata:assignmentsubmission_maharaws:userid'] = "The userid is sent from Moodle to allow you to access your data on the remote system.";
$string['privacy:metadata:assignmentsubmission_maharaws:username'] = "Your username is sent from moodle to allow you to access your data on the remote system";
$string['privacy:metadata:assignmentsubmission_maharaws:fullname'] = "Your full name is sent to the remote system to allow a better user experience.";
$string['privacy:metadata:assignmentsubmission_maharaws:firstname'] = "Your first name is sent to the remote system to allow a better user experience.";
$string['privacy:metadata:assignmentsubmission_maharaws:lastname'] = "Your last name is sent to the remote system to allow a better user experience.";
$string['privacy:metadata:assignmentsubmission_maharaws:email'] = "Your email is sent to the remote system to allow a better user experience and for account management";
$string['privacy:metadata:assignmentsubmission_maharaws:idnumber'] = "Your idnumber is sent from Moodle to allow you to access your data on the remote system.";
$string['privacy:metadata:assignmentsubmission_maharaws:courseid'] = "The courseid is sent from moodle to allow the remote system to submit your portfolio to the correct course";
$string['privacy:metadata:assignmentsubmission_maharaws:courseshortname'] = "The course short name is sent  to the remote system to allow a better user experience.";
$string['privacy:metadata:assignmentsubmission_maharaws:coursefullname'] = "The course full name is sent to allow the remote system to allow a better user experience.";
$string['errorinvalidapistring'] = "The mahara instance has returned an api string with an unexpected format.";
