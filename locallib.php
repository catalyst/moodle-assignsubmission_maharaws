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
 * This file contains the definition for the library class for Mahara submission plugin
 *
 * @package    assignsubmission_maharaws
 * @copyright  2020 Catalyst IT
 * @copyright  2012 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_maharaws\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/oauthlib.php');

/**
 * library class for Mahara submission plugin extending submission plugin base class.
 *
 * @package    assignsubmission_maharaws
 * @copyright  2012 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_maharaws extends assign_submission_plugin {
    /**
     * We've selected the page/collection, but we haven't locked it or issued a special access token.
     */
    const STATUS_SELECTED = 'selected';

    /**
     * We've locked the page in Mahara and issued an access token.
     */
    const STATUS_SUBMITTED = 'submitted';

    /**
     * We locked and then unlocked the page in Mahara, which means we probably still have a valid access token for it.
     */
    const STATUS_RELEASED = 'released';

    /**
     * Get the name of the Mahara submission plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('mahara', 'assignsubmission_maharaws');
    }

    /**
     * Get Mahara submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_mahara_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_maharaws', ['submission' => $submissionid]);
    }

    /**
     * Get the settings form for Mahara submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        require_once($CFG->dirroot . '/mod/assign/submission/maharaws/lib.php');

        $config = helper::get_ws_config($this->assignment);
        if (!empty($config->force_global_credentials)) {
            if (empty($config->url) || empty($config->key) || empty($config->secret)) {
                return;
            }
            // Static element doesn't allow hideif so we use a group to do this (MDL-66251).
            $group = [];
            $group[] = $mform->createElement(
                'static',
                'assignsubmission_maharaws_label',
                get_string('gclabel', 'assignsubmission_maharaws'),
                get_string('forceglobalcredentialswarning', 'assignsubmission_maharaws')
            );
            $mform->addGroup($group, 'maharawslabelgroup', '', ' ', false);
            $mform->hideIf('maharawslabelgroup', 'assignsubmission_maharaws_enabled', 'notchecked');
        }

        if (empty($config->force_global_credentials)) {
            $mform->addElement(
                'text',
                'assignsubmission_maharaws_url',
                get_string('url', 'assignsubmission_maharaws'),
                ['maxlength' => 255, 'size' => 50]
            );
            $mform->setType('assignsubmission_maharaws_url', PARAM_URL);
            $mform->setDefault('assignsubmission_maharaws_url', $config->url);
            $mform->addHelpButton('assignsubmission_maharaws_url', 'url', 'assignsubmission_maharaws');
            $mform->hideIf('assignsubmission_maharaws_url', 'assignsubmission_maharaws_enabled', 'notchecked');

            if (!$this->can_configure()) {
                $mform->freeze(['assignsubmission_maharaws_url']);
            }

            $mform->addElement(
                'text',
                'assignsubmission_maharaws_institution',
                get_string('groups:institution', 'assignsubmission_maharaws'),
                ['maxlength' => 255, 'size' => 50]
            );
            $mform->setType('assignsubmission_maharaws_institution', PARAM_TEXT);
            $mform->setDefault('assignsubmission_maharaws_institution', $config->institution);
            $mform->addHelpButton(
                'assignsubmission_maharaws_institution',
                'groups:institution',
                'assignsubmission_maharaws'
            );
            $mform->freeze(['assignsubmission_maharaws_institution']);

            if ($this->can_configure()) {
                $mform->addElement(
                    'text',
                    'assignsubmission_maharaws_key',
                    get_string('key', 'assignsubmission_maharaws'),
                    ['maxlength' => 255, 'size' => 50]
                );
                $mform->setType('assignsubmission_maharaws_key', PARAM_ALPHANUM);
                $mform->setDefault('assignsubmission_maharaws_key', $config->key);
                $mform->addHelpButton('assignsubmission_maharaws_key', 'key', 'assignsubmission_maharaws');
                $mform->hideIf('assignsubmission_maharaws_key', 'assignsubmission_maharaws_enabled', 'notchecked');

                $mform->addElement(
                    'password',
                    'assignsubmission_maharaws_secret',
                    get_string('secret', 'assignsubmission_maharaws'),
                    ['maxlength' => 255, 'size' => 50]
                );
                $mform->setType('assignsubmission_maharaws_secret', PARAM_ALPHANUM);
                $mform->setDefault('assignsubmission_maharaws_secret', $config->secret);
                $mform->addHelpButton('assignsubmission_maharaws_secret', 'secret', 'assignsubmission_maharaws');
                $mform->hideIf('assignsubmission_maharaws_secret', 'assignsubmission_maharaws_enabled', 'notchecked');
            }

            // If groups have been mapped we don't want users changing the web service connection details.
            if ($this->get_config('groups')) {
                if ($mform->elementExists('assignsubmission_maharaws_url')) {
                    $mform->freeze('assignsubmission_maharaws_url');
                }
                if ($mform->elementExists('assignsubmission_maharaws_key')) {
                    $mform->freeze('assignsubmission_maharaws_key');
                }
                if ($mform->elementExists('assignsubmission_maharaws_secret')) {
                    $mform->freeze('assignsubmission_maharaws_secret');
                }
            }
        }

        $locked = $this->get_config('lock');
        if ($locked === false) {
            // No setting for this instance, so use the sitewide default.
            $locked = get_config('assignsubmission_maharaws', 'lock');
        }

        // Menu to select whether to lock Mahara pages or not.
        $locksettings = [
            ASSIGNSUBMISSION_MAHARAWS_SETTING_DONTLOCK => new lang_string('no'),
            ASSIGNSUBMISSION_MAHARAWS_SETTING_KEEPLOCKED => new lang_string('yeskeeplocked', 'assignsubmission_maharaws'),
            ASSIGNSUBMISSION_MAHARAWS_SETTING_UNLOCK => new lang_string('yesunlock', 'assignsubmission_maharaws'),
        ];
        if ($this->can_configure()) {
            $mform->addElement(
                'select',
                'assignsubmission_maharaws_lockpages',
                get_string('lockpages', 'assignsubmission_maharaws'),
                $locksettings
            );
            $mform->setDefault('assignsubmission_maharaws_lockpages', $locked);
            $mform->addHelpButton('assignsubmission_maharaws_lockpages', 'lockpages', 'assignsubmission_maharaws');
            $mform->hideIf('assignsubmission_maharaws_lockpages', 'assignsubmission_maharaws_enabled', 'notchecked');
        }
        $mform->addElement(
            'selectyesno',
            'assignsubmission_maharaws_archiveonrelease',
            get_string('archiveonrelease', 'assignsubmission_maharaws')
        );

        $mform->hideIf('assignsubmission_maharaws_archiveonrelease', 'assignsubmission_maharaws_lockpages', 'eq', 0);

        if (!empty($this->get_config('archiveonrelease'))) {
            $mform->setDefault('assignsubmission_maharaws_archiveonrelease', $this->get_config('archiveonrelease'));
        } else {
            $mform->setDefault('assignsubmission_maharaws_archiveonrelease', 0);
        }
        $mform->hideIf('assignsubmission_maharaws_archiveonrelease', 'assignsubmission_maharaws_enabled', 'notchecked');
        $mform->addHelpButton('assignsubmission_maharaws_archiveonrelease', 'archiveonrelease', 'assignsubmission_maharaws');
        $mform->disabledIf(
            'assignsubmission_maharaws_archiveonrelease',
            'assignsubmissioqn_maharaws_lockpages',
            'eq',
            ASSIGNSUBMISSION_MAHARAWS_SETTING_DONTLOCK
        );
        // If team submissions are enabled, archiving is disabled.
        $mform->disabledIf(
            'assignsubmission_maharaws_archiveonrelease',
            'teamsubmission',
            'eq',
            1
        );

        if (get_config('assignsubmission_maharaws', 'enablegroupsubmissions')) {
            // Groups selector (only visible if group submissions are enabled and the assignment exists or global
            // credentials are forced).
            // Options include groups that are: Visible, Not already linked to a Mahara group or already
            // linked to the currently configured institution.
            $instance = $this->assignment->has_instance();
            if ($instance || !empty($config->force_global_credentials)) {
                $groups = groups_get_all_groups(
                    $COURSE->id,
                    0,
                    0,
                    'id,
                    name',
                    true,
                    true
                );
                $groups = $this->validate_group_options($groups);

                if (!empty($groups)) {
                    $options = [];
                    foreach ($groups as $group) {
                        $options[$group->id] = $group->name;
                    }
                    $select = $mform->addElement(
                        'select',
                        'assignsubmission_maharaws_groups',
                        get_string('groups:groupselector', 'assignsubmission_maharaws'),
                        $options
                    );
                    $select->setMultiple(true);
                    $select->setSelected($this->get_config('groups'));
                    // Hide the group selector if group submissions aren't enabled.
                    $mform->hideIf('assignsubmission_maharaws_groups', 'teamsubmission', 'neq', 1);
                    $mform->addHelpButton(
                        'assignsubmission_maharaws_groups',
                        'groups:groupselector',
                        'assignsubmission_maharaws'
                    );
                } else {
                    $mform->addElement(
                        'static',
                        'assignsubmission_maharaws_nogroupsavailable',
                        get_string('groups:groupselector', 'assignsubmission_maharaws'),
                        get_string('groups:groupselector:nogroups', 'assignsubmission_maharaws')
                    );
                    $mform->addHelpButton(
                        'assignsubmission_maharaws_nogroupsavailable',
                        'groups:groupselector:nogroups',
                        'assignsubmission_maharaws'
                    );
                }

                $mform->hideIf('assignsubmission_maharaws_groups', 'assignsubmission_maharaws_enabled', 'notchecked');
                $mform->hideIf('assignsubmission_maharaws_nogroupsavailable', 'assignsubmission_maharaws_enabled', 'notchecked');
            }
        }
    }

    /**
     * Save the settings for Mahara plugin
     *
     * @param stdClass $formdata
     * @return bool
     */
    public function save_settings(stdClass $formdata) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/submission/maharaws/lib.php');

        $config = helper::get_ws_config($this->assignment);

        if ($this->can_configure()) {
            if ($formdata->assignsubmission_maharaws_lockpages == ASSIGNSUBMISSION_MAHARAWS_SETTING_DONTLOCK) {
                $formdata->assignsubmission_maharaws_archiveonrelease = 0;
            }
            if (empty($config->force_global_credentials)) {
                $this->set_config('url', $formdata->assignsubmission_maharaws_url);
                $this->set_config('key', $formdata->assignsubmission_maharaws_key);
                $this->set_config('secret', $formdata->assignsubmission_maharaws_secret);
            }
            if ($formdata->teamsubmission == 1) {
                $formdata->assignsubmission_maharaws_archiveonrelease = 0;
            }

            $this->set_config('lock', $formdata->assignsubmission_maharaws_lockpages);
        } else {
            // Set to existing config or default value for users that cannot see lock element.
            $locked = $this->get_config('lock');
            if ($locked === false) {
                $locked = get_config('assignsubmission_maharaws', 'lock');
            }
            $this->set_config('lock', $locked);
        }

        $this->set_config('debug', false);
        $this->set_config('remoteuser', false);
        $this->set_config('username_attribute', 'email');
        $this->set_config('archiveonrelease', $formdata->assignsubmission_maharaws_archiveonrelease);

        // Test Mahara connection.
        try {
            // Skip webservice call if running unit tests.
            if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
                return true;
            }

            // Reload config after saving any updated settings so the webservice call
            // uses the latest URL/key/secret from this request.
            $config = helper::get_ws_config($this->assignment);

            $data = \assignsubmission_maharaws\webservice::call("mahara_user_get_extended_context", [], $config);
            $funcs = [];

            if (empty($data) || empty($data['functions']) || !is_array($data['functions'])) {
                $this->set_error(
                    get_string('errorinvalidurl', 'assignsubmission_maharaws', 'Invalid response from Mahara')
                    . "\n" .
                    get_string('invalidurlhelp', 'assignsubmission_maharaws')
                );
                return false;
            }

            // If global config is not set then we store the institution this web service is linked to as we need it for
            // managing groups.
            if (empty(get_config('assignsubmission_maharaws', 'force_global_credentials')) && !empty($data['institution'])) {
                $this->set_config('institution', $data['institution']);
                $formdata->institution = $data['institution'];
            }

            $required = [
                "mahara_user_get_extended_context",
                "mahara_submission_get_views_for_user",
                "mahara_submission_submit_view_for_assessment",
                "mahara_submission_release_submitted_view",
                "module_lti_launch",
                "mahara_group_create_groups",
                "mahara_user_get_users_by_id",
                "mahara_user_create_users",
            ];

            foreach ($data['functions'] as $v) {
                $funcs[] = $v['function'];
            }
            foreach ($required as $f) {
                if (!in_array($f, $funcs)) {
                    $this->set_error(
                        get_string('errorinvalidurl', 'assignsubmission_maharaws', 'missing functions: ' . implode(", ", $required))
                        . "\n\n" .
                        get_string('invalidurlhelp', 'assignsubmission_maharaws')
                    );
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->set_error(
                get_string('errorinvalidurl', 'assignsubmission_maharaws', $e->getMessage())
                . "\n" .
                get_string('invalidurlhelp', 'assignsubmission_maharaws')
            );
            return false;
        }

        // Create the groups in Mahara.
        if (isset($formdata->assignsubmission_maharaws_groups)) {
            $formdata->institution = $data['institution'];
            helper::create_mahara_groups($formdata);
        }

        // Link the group(s) to the assignment.
        if (isset($formdata->assignsubmission_maharaws_groups)) {
            $this->set_config('groups', implode(',', $formdata->assignsubmission_maharaws_groups));
        }

        // If this is a new groups assignment we need to ask the user to go back in and map the groups.
        // Until the settings are saved it's difficult to know which groups should be available, but once the assignment
        // has been created, and we know which institution it belongs to, we can safely present a list of available groups
        // for selection.
        if (!isset($formdata->assignsubmission_maharaws_groups) && $this->assignment->get_instance()->teamsubmission == 1) {
            \core\notification::add(
                get_string('groups:groupconfig', 'assignsubmission_maharaws'),
                \core\output\notification::NOTIFY_WARNING
            );
        }

        return true;
    }

    /**
     * Check if the current user can configure the plugin in the provided context.
     *
     * @return bool
     */
    public function can_configure() {
        global $PAGE;

        return has_capability('assignsubmission/maharaws:configure', $PAGE->context);
    }

    /**
     * Add elements to user submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param int $userid
     * @return bool
     */
    public function get_form_elements_for_user($submission, MoodleQuickForm $mform, stdClass $data, $userid) {
        global $DB, $PAGE, $CFG, $COURSE, $USER;

        $PAGE->requires->js('/mod/assign/submission/maharaws/js/popup.js');
        // Getting submission.
        if ($submission) {
            $maharasubmission = $this->get_mahara_submission($submission->id);
        }

        $config = helper::get_ws_config($this->assignment);

        $maharagroup = null;
        if ($this->assignment->get_instance()->teamsubmission) {
            // Because this is a group submission we don't want a student to accidentally submit a portfolio from a group in
            // Mahara that the other group members in Moodle are not in. If that were to happen a link would be created in
            // Moodle that gives the other Moodle group members access to a Mahara portfolio they shouldn't have access to.
            // Get the groups this submission is linked to.
            $moodlegroups = $this->get_config('groups');
            $moodlegroups = explode(',', $moodlegroups);
            // Get the students groups.
            $usergroups = groups_get_user_groups($COURSE->id);
            $count = [];
            // Check if the student has membership in any of the linked groups.
            foreach ($moodlegroups as $group) {
                $membership = array_search($group, $usergroups[0]);
                if (is_int($membership)) {
                    $count[$membership] = $group;
                }
            }
            // If the user is in more than one group we won't show them any portfolios until their teacher
            // adjusts their group membership in Moodle so that they're only in one Moodle/Mahara group.
            if (count($count) > 1) {
                \core\notification::add(
                    get_string('groups:usergroups', 'assignsubmission_maharaws'),
                    \core\output\notification::NOTIFY_WARNING
                );
                return false;
            } else if (empty($count)) {
                // The user is not in any of the group that have been linked to this assignment.
                \core\notification::add(
                    get_string('groups:notingroup', 'assignsubmission_maharaws'),
                    \core\output\notification::NOTIFY_WARNING
                );
                return false;
            } else {
                $maharagroup = helper::get_mahara_group($count[0]);
            }
        }

        if (!empty($maharasubmission)) {
            $selectedid = $maharasubmission->viewid;
            $selectediscollection = $maharasubmission->iscollection;
            if ($submission->userid != $USER->id && empty($maharagroup)) {
                // The submission does not belong to this user - display a message and prevent editing.
                $mform->addElement('static', '', '', get_string('notyoursubmission', 'assignsubmission_maharaws'));
                $mform->addElement('hidden', 'viewid', 'none');
                $mform->setType('viewid', PARAM_ALPHANUM);
                $mform->disabledIf('submitbutton', 'viewid', 'eq', 'none');
                return true;
            }
        } else {
            $selectedid = 0;
            $selectediscollection = null;
        }

        // Getting views (pages) user have in linked site.
        $views = false;
        if ($USER->id == $userid) {
            // Only get the list of views the user has access to if we are looking at this users own submission.
            try {
                $views = $this->get_views('', $maharagroup);
            } catch (moodle_exception $e) {
                $error = $e->getMessage();
            }
        }

        if (!$views) {
            $views = [
                'data' => [],
                'collections' => ['data' => []],
                'ids' => [],
            ];
        }
        $viewids = $views['ids'];
        [$insql, $inparams] = $DB->get_in_or_equal($viewids, SQL_PARAMS_NAMED, 'param', true, true);
        $sql = "SELECT mws.id, us.* from (
              select value as url,
                      assignment
              FROM {assign_plugin_config}
              WHERE
                plugin = 'maharaws'
                AND subtype = 'assignsubmission'
                AND name = 'url'
          ) AS us JOIN {assignsubmission_maharaws} as mws on us.assignment = mws.assignment where url = :url
        AND viewstatus = 'submitted' AND viewid {$insql}";
        $params = [
            'url' => $config->url,
        ];
        $params += $inparams;
        $alreadyselected = $DB->get_records_sql($sql, $params);
        if (is_array($alreadyselected)) {
            $alreadyselected = array_column($alreadyselected, 'viewid', 'viewid');
        } else {
            $alreadyselected = [];
        }
        // Filter out collection views, special views, and already-submitted views (except the current one).
        foreach ($views['data'] as $i => $view) {
            if (
                $view['collid']
                || $view['type'] != 'portfolio'
                || (
                    (
                        array_key_exists($view['id'], $alreadyselected)
                        || $view['submittedtime']
                    )
                    && !($view['id'] == $selectedid
                    && $selectediscollection == false)
                )
            ) {
                unset($views['ids'][$i]);
                unset($views['data'][$i]);
                $views['count']--;
            }
        }

        // Filter out empty or submitted collections.

        foreach ($views['collections']['data'] as $i => $coll) {
            if (
                (
                    array_key_exists('numviews', $coll)
                    && $coll['numviews'] == 0
                ) || (
                    ($coll['submittedtime']
                        ||
                        array_key_exists($coll['id'], $alreadyselected)
                    )
                    && !($coll['id'] == $selectedid && $selectediscollection == true)
                )
            ) {
                unset($views['collections']['data'][$i]);
                $views['collections']['count']--;
            }
        }
        $viewids = $views['ids'];

        // Prepare the header.
        try {
            $remotehost = \assignsubmission_maharaws\webservice::call("mahara_user_get_extended_context", [], $config);
        } catch (Exception $e) {
            debugging("Remote host webservice call failed: " . $e->getCode() . ":" . $e->getMessage());
            throw new moodle_exception('errorwsrequest', 'assignsubmission_maharaws', '', $e->getMessage());
        }


        $remotehost->jumpurl = new moodle_url(
            '/mod/assign/submission/maharaws/launch.php',
            ['id' => $PAGE->cm->id, 'url' => urlencode($remotehost->siteurl)],
        );
        $remotehost->name = $remotehost->sitename;

        // See if any of views are already in use, we will remove them from select.
        if (!empty($viewids) || !empty($views['collections']['data'])) {
            $mform->addElement(
                'static',
                '',
                $remotehost->name,
                get_string('selectmaharaview', 'assignsubmission_maharaws', $remotehost)
            );

            // Add "none selected" option.
            $mform->addElement('radio', 'viewid', '', get_string('noneselected', 'assignsubmission_maharaws'), 'none');
            $mform->setType('viewid', PARAM_ALPHANUM);
            $mform->setDefault('viewid', 'none');

            $owner = $views['displayname'];
            if ($this->assignment->get_instance()->teamsubmission) {
                $owner = $views['groupname'];
            }

            if (!empty($views['data'])) {
                $mform->addElement(
                    'static',
                    '',
                    get_string('viewsby', 'assignsubmission_maharaws', $owner)
                );

                foreach ($views['data'] as $view) {
                    // If the view (page) hasn't already been submitted, add it to the options for selection.
                    if ($view['submissionoriginal'] == 0) {
                        $viewurl = "/view/view.php?id=" . $view['id'];
                        $anchor = $this->get_preview_url($view['title'], $viewurl, strip_tags($view['description']));
                        $mform->addElement('radio', 'viewid', '', $anchor, 'v' . $view['id']);
                    }
                    if ($maharasubmission && $view['id'] == $maharasubmission->viewid) {
                        $currentpagesubmitted = $mform->createElement(
                            'static',
                            'currentsubmission',
                            get_string('currentsubmitted', 'assignsubmission_maharaws', 'page'),
                            $view['displaytitle']
                        );
                    }
                }

                if (!empty($currentpagesubmitted)) {
                    $mform->addElement($currentpagesubmitted);
                }
            }
            if (!empty($views['collections']['data'])) {
                $mform->addElement(
                    'static',
                    'collection_by',
                    get_string('collectionsby', 'assignsubmission_maharaws', $owner)
                );
                foreach ($views['collections']['data'] as $coll) {
                    // If the collection hasn't already been submitted, add it to the options for selection.
                    if ($coll['submissionoriginal'] == 0) {
                        $anchor = $this->get_preview_url($coll['name'], $coll['url'], strip_tags($coll['description']));
                        $mform->addElement('radio', 'viewid', '', $anchor, 'c' . $coll['id']);
                    }

                    if ($maharasubmission && $coll['id'] == $maharasubmission->viewid) {
                        $currentcollsubmitted = $mform->createElement(
                            'static',
                            'currentsubmission',
                            get_string('currentsubmitted', 'assignsubmission_maharaws', 'collection'),
                            $coll['name']
                        );
                    }
                }
                if (!empty($currentcollsubmitted)) {
                    $mform->addElement($currentcollsubmitted);
                }
            }
            if (!empty($maharasubmission)) {
                if ($maharasubmission->iscollection) {
                    $prefix = 'c';
                } else {
                    $prefix = 'v';
                }
                $mform->setDefault('viewid', $prefix . $maharasubmission->viewid);
            }

            return true;
        } else {
            $mform->addElement(
                'static',
                '',
                $remotehost->name,
                get_string('noviewscreated', 'assignsubmission_maharaws', $remotehost)
            );
            $mform->addElement('hidden', 'viewid', 'none');
            $mform->setType('viewid', PARAM_ALPHANUM);
            return true;
        }
    }

    /**
     * Retrieve user views from Mahara portfolio.
     *
     * @param string $query Search query
     * @param stdClass|null $group Mahara group
     * @return mixed
     */
    public function get_views(string $query = '', ?stdClass $group = null): mixed {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/mod/assign/submission/maharaws/lib.php');

        $username = (!empty($CFG->mahara_test_user) ? $CFG->mahara_test_user : $USER->{$this->get_config('username_attribute')});
        $field = $this->get_mahara_idfield();
        if ($group) {
            $group = $group->maharagroup;
        }

        try {
            $config = helper::get_ws_config($this->assignment);
            $result = \assignsubmission_maharaws\webservice::call(
                "mahara_submission_get_views_for_user",
                ['users' => [ [$field => $username,
                'query' => $query,
                'group' => $group]]],
                $config
            );
            if (!empty($result)) {
                $result = array_pop($result);
                $result['views']['ids'] = array_map('intval', explode(',', $result['views']['ids']));

                // Overwrite url with full URL.
                foreach ($result['views']['data'] as $key => $value) {
                    $result['views']['data'][$key]['url'] = $result['views']['data'][$key]['fullurl'];
                }
                foreach ($result['views']['collections']['data'] as $key => $value) {
                    $result['views']['collections']['data'][$key]['url'] = $result['views']['collections']['data'][$key]['fullurl'];
                }
            } else {
                $result['views'] = null;
            }
        } catch (Exception $e) {
            throw new moodle_exception('errorwsrequest', 'assignsubmission_maharaws', '', $e->getMessage());
        }
        return $result['views'];
    }

    /**
     * Get view by id.
     *
     * @param int $viewid The view id.
     * @param bool $iscollection Is collection.
     * @param stdClass|null $group Mahara group
     * @return false|array
     */
    private function get_view(int $viewid, bool $iscollection, ?stdClass $group = null) {
        if (!$views = $this->get_views('', $group)) {
            // Wrap recorded error in language string and return false.
            $this->set_error(get_string('errorrequest', 'assignsubmission_maharaws', $this->get_error()));
            return false;
        }
        if ($iscollection) {
            $foundcoll = false;
            if (!is_array($views['collections']['data'])) {
                return false;
            }
            foreach ($views['collections']['data'] as $coll) {
                if ($coll['id'] == $viewid) {
                    $foundcoll = true;
                    $viewdata = $coll;
                    $viewdata['title'] = $coll['name'];
                    break;
                }
            }
            // The submitted collection id isn't one of the allowed options for this user.
            if (!$foundcoll) {
                return false;
            }
        } else {
            $keys = array_flip($views['ids']);
            // The submitted view id isn't one of the allowed options for this user.
            if (!array_key_exists($viewid, $keys)) {
                return false;
            }
            $viewdata = $views['data'][$keys[$viewid]];
        }
        return $viewdata;
    }

    /**
     * Submit view or collection for assessment in Mahara. This marks the view/collection
     * as "submitted", creates an access token, and locks the view/collection from editing
     * or further submissions in Mahara.
     *
     * @param stdClass $submission The submission record (used for verification)
     * @param int $viewid Id of the view or collection to submit
     * @param boolean $iscollection True if it's a collection, False if not
     * @param ?int $viewownermoodleid Id of the view ower's Moodle user record
     *
     * @return mixed|null
     */
    public function submit_view($submission, $viewid, $iscollection, $viewownermoodleid = null) {
        global $USER, $DB, $CFG;
        $config = helper::get_ws_config($this->assignment);
        // Verify that it's not already submitted to another Mahara assignment in this Moodle site.
        // We can't do this on the Mahara side, because Mahara only knows the remote site's wwwroot.
        $sql = "SELECT mws.id,
                us.*
         FROM   (SELECT value AS url,
                        assignment
                 FROM   {assign_plugin_config}
                 WHERE  plugin = 'maharaws'
                        AND subtype = 'assignsubmission'
                        AND NAME = 'url') us
                JOIN {assignsubmission_maharaws} mws
                  ON us.assignment = mws.assignment
         WHERE  url = :url
                AND viewstatus = 'submitted'
                AND viewid = :viewid
                AND 'iscollection' = :iscollection  ";
        $params = [
            'url' => $config->url,
            'viewid' => $viewid,
            'iscollection' => $iscollection,
        ];
        $alreadyselected = $DB->get_records_sql($sql, $params);
        if (!empty($alreadyselected)) {
            throw new moodle_exception('errorvieworcollectionalreadysubmitted', 'assignsubmission_maharaws');
        }

        if (!$viewownermoodleid) {
            $username = $USER->{$this->get_config('username_attribute')};
        } else {
            $username = $DB->get_field('user', $this->get_config('username_attribute'), ['id' => $viewownermoodleid]);
        }

        require_once($CFG->dirroot . '/mod/assign/submission/maharaws/lib.php');
        $username = (!empty($CFG->mahara_test_user) ? $CFG->mahara_test_user : $username);
        $field = $this->get_mahara_idfield();

        try {
            $maharagroup = null;
            if ($this->assignment->get_instance()->teamsubmission) {
                $maharagroup = helper::get_mahara_group(
                    $this->assignment->get_submission_group($USER->id)->id
                );
                $maharagroup = $maharagroup->maharagroup;
            }

            $result  = \assignsubmission_maharaws\webservice::call(
                'mahara_submission_submit_view_for_assessment',
                ['views' =>
                    [
                        [$field => $username,
                            'viewid' => $viewid,
                            'iscollection' => $iscollection,
                            'lock' => true,
                            'apilevel' => 'moodle-assignsubmission-mahara:3',
                            'wwwroot' => $CFG->wwwroot,
                            'group' => $maharagroup,
                        ],
                    ],
                ],
                $config
            );
            $result = array_pop($result);
        } catch (Exception $e) {
            debugging("Submit view for assessment webservice call failed: " . $e->getCode() . ":" . $e->getMessage());
            throw new moodle_exception('errorwsrequest', 'assignsubmission_maharaws', '', $e->getMessage());
        }
        return $result;
    }

    /**
     * Release submitted view for assessment.
     *
     * @param int $viewid View or Collection ID
     * @param array $viewoutcomes Outcomes data
     * @param boolean $iscollection Whether the $viewid is a view or a collection
     * @return mixed
     */
    public function release_submitted_view($viewid, $viewoutcomes, $iscollection = false) {
        global $USER, $CFG, $DB;
        $config = helper::get_ws_config($this->assignment);
        try {
            $username = (!empty($CFG->mahara_test_user) ?
                $CFG->mahara_test_user :
                $USER->{$this->get_config('username_attribute')});

            $field = $this->get_mahara_idfield();
            // Get Mahara group for this submission.
            $maharagroup = $DB->get_field_sql(
                'SELECT maharagroup
                       FROM {assignsubmission_maharaws} am
                       JOIN {assignsubmission_maharawsgroup} amg ON am.groupid = amg.moodlegroup
                      WHERE am.viewid = ?',
                [$viewid]
            );

            $maharagroup = $maharagroup ?: null;
            $result = \assignsubmission_maharaws\webservice::call(
                'mahara_submission_release_submitted_view',
                [
                    'views' => [
                        [
                            $field => $username,
                            'viewid' => $viewid,
                            'iscollection' => $iscollection,
                            'viewoutcomes' => implode(',', $viewoutcomes),
                            'archiveonrelease' => $this->get_config('archiveonrelease'),
                            'group' => $maharagroup,
                            'externalid' => $this->assignment->get_course_module()->id,
                        ],
                    ],
                ],
                $config
            );
        } catch (Exception $e) {
            debugging("Release submitted view webservice call failed: " . $e->getCode() . ":" . $e->getMessage());
            throw new moodle_exception('errorwsrequest', 'assignsubmission_maharaws', '', $e->getMessage());
        }
        return $result;
    }


    /**
     * Save submission data to the database
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $group = null;
        if ($this->assignment->get_instance()->teamsubmission) {
            $group = $this->assignment->get_submission_group($USER->id);
            $group = helper::get_mahara_group($group->id);
        }

        // Because the drop-down menu contains collections & views, we make the id
        // start with "v" or "c" to indicate the type, e.g. v30, c100.
        if ($data->viewid == 'none') {
            $iscollection = false;
            $data->viewid = null;
        } else {
            $iscollection = ($data->viewid[0] == 'c');
            $data->viewid = substr($data->viewid, 1);

            if ($viewdata = $this->get_view($data->viewid, $iscollection, $group)) {
                $url = $viewdata['url'];
                $title = clean_text($viewdata['title']);
            }
        }

        $maharasubmission = $this->get_mahara_submission($submission->id);
        if ($submission->status === ASSIGN_SUBMISSION_STATUS_DRAFT) {
            // Draft. All we need to do is just save or update submitted view data.
            if ($data->viewid === null) {
                // They selected "(nothing selected)", so remove their Mahara selection.
                return $DB->delete_records(
                    'assignsubmission_maharaws',
                    ['submission' => $submission->id]
                );
            }

            if ($maharasubmission) {
                $maharasubmission->viewid = $data->viewid;
                $maharasubmission->viewurl = $url;
                $maharasubmission->viewtitle = $title;
                $maharasubmission->iscollection = (int) $iscollection;
                $maharasubmission->viewstatus = self::STATUS_SELECTED;
                if ($group) {
                    $maharasubmission->groupid = $group->id;
                }
                return $DB->update_record('assignsubmission_maharaws', $maharasubmission);
            } else {
                $maharasubmission = new stdClass();
                $maharasubmission->viewid = $data->viewid;
                $maharasubmission->viewurl = $url;
                $maharasubmission->viewtitle = $title;
                $maharasubmission->iscollection = (int) $iscollection;
                $maharasubmission->viewstatus = self::STATUS_SELECTED;
                if ($group) {
                    $maharasubmission->groupid = $group->id;
                }
                $maharasubmission->submission = $submission->id;
                $maharasubmission->assignment = $this->assignment->get_instance()->id;
                return $DB->insert_record('assignsubmission_maharaws', $maharasubmission) > 0;
            }
        } else {
            // This is not the draft, but the actual submission. Process it properly.

            // If viewid is null, it means they selected no page.
            if ($data->viewid === null) {
                if ($maharasubmission) {
                    // Unlock the previously selected page.
                    if ($maharasubmission->viewstatus == self::STATUS_SUBMITTED) {
                        $response = $this->release_submitted_view(
                            $maharasubmission->viewid,
                            [],
                            $maharasubmission->iscollection
                        );
                        if ($response === false) {
                            throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
                        }
                    }
                    // Delete the record of the previously selected page from our submission, and exit.
                    return $DB->delete_records('assignsubmission_maharaws', ['submission' => $submission->id]);
                } else {
                    // No previously selected page to clear.
                    return true;
                }
            }

            // If we're not locking user pages, we are not submitting the view, not creating copy.
            // This will leave it unlocked, but leave the access code in place.
            // TODO: Replace this hack with something more robust. It's an oversight and a security hole, that the
            // access code remains in place in Mahara when you release the page via XML-RPC.
            if ($this->get_config('lock')) {
                if (!$response = $this->submit_view($submission, $data->viewid, $iscollection, $submission->userid)) {
                    throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
                }
                $status = self::STATUS_SUBMITTED;
            } else {
                $status = self::STATUS_RELEASED;
            }

            $params = [
                'context' => context_module::instance($this->assignment->get_course_module()->id),
                'courseid' => $this->assignment->get_course()->id,
                'objectid' => $submission->id,
                'other' => [
                    'pathnamehashes' => [],
                    'content' => '',
                ],
            ];
            if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
                $params['relateduserid'] = $submission->userid;
            }
            $event = \assignsubmission_maharaws\event\assessable_uploaded::create($params);
            $event->trigger();

            $groupname = null;
            $groupid = 0;
            // Get the group name as other fields are not transcribed in the logs and this information is important.
            if (empty($submission->userid) && !empty($submission->groupid)) {
                $groupname = $DB->get_field('groups', 'name', ['id' => $submission->groupid], MUST_EXIST);
                $groupid = $submission->groupid;
            } else {
                $params['relateduserid'] = $submission->userid;
            }

            // Unset the objectid and other field from params for use in submission events.
            unset($params['objectid']);
            unset($params['other']);
            $params['other'] = [
                'submissionid' => $submission->id,
                'submissionattempt' => $submission->attemptnumber,
                'submissionstatus' => $submission->status,
                'groupid' => $groupid,
                'groupname' => $groupname,
            ];

            if ($maharasubmission) {
                // If we are updating previous submission, release previous submission first (if it's locked).
                if ($maharasubmission->viewid != $data->viewid && $maharasubmission->viewstatus == self::STATUS_SUBMITTED) {
                    $result = $this->release_submitted_view($maharasubmission->viewid, [], $maharasubmission->iscollection);
                    if ($result === false) {
                        throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
                    }
                }

                // Update submission data if its locked.
                if ($this->get_config('lock')) {
                    $maharasubmission = $this->update_submission_data($response, $iscollection, $maharasubmission);
                } else {
                    $maharasubmission->viewid = $data->viewid;
                    $maharasubmission->viewurl = $viewdata['url'];
                    $maharasubmission->viewtitle = clean_text($viewdata['title']);
                }

                $maharasubmission->viewstatus = $status;
                $maharasubmission->iscollection = (int) $iscollection;
                if ($group) {
                    $maharasubmission->groupid = $group->id;
                }
                $params['objectid'] = $maharasubmission->id;
                $updatestatus = $DB->update_record('assignsubmission_maharaws', $maharasubmission);
                $event = \assignsubmission_maharaws\event\submission_updated::create($params);
                $event->set_assign($this->assignment);
                $event->trigger();
                return $updatestatus;
            } else {
                if ($data->viewid === null) {
                    return $DB->delete_records('assignsubmission_maharaws', ['submission' => $submission->id]);
                } else {
                    // We are dealing with the new submission.
                    $maharasubmission = new stdClass();
                    if ($this->get_config('lock')) {
                        $maharasubmission = $this->update_submission_data($response, $iscollection, $maharasubmission);
                    } else {
                        $maharasubmission->viewid = $data->viewid;
                        $maharasubmission->viewurl = $viewdata['url'];
                        $maharasubmission->viewtitle = clean_text($viewdata['title']);
                    }

                    $maharasubmission->viewstatus = $status;
                    if ($group) {
                        $maharasubmission->groupid = $group->id;
                    }
                    $maharasubmission->iscollection = (int) $iscollection;

                    $maharasubmission->submission = $submission->id;
                    $maharasubmission->assignment = $this->assignment->get_instance()->id;
                    $maharasubmission->id = $DB->insert_record('assignsubmission_maharaws', $maharasubmission);
                    $params['objectid'] = $maharasubmission->id;
                    $event = \assignsubmission_maharaws\event\submission_created::create($params);
                    $event->set_assign($this->assignment);
                    $event->trigger();
                    return $maharasubmission->id > 0;
                }
            }
        }
    }

    /**
     * Update submission data.
     *
     * @param mixed $response The response from Mahara API.
     * @param bool $iscollection The iscollection bool value.
     * @param mixed $maharasubmission The Mahara submission object.
     * @return mixed
     */
    private function update_submission_data($response, $iscollection, $maharasubmission) {
        global $USER;
        $apilevel = $this->process_apilevel($response['apilevel']);
        if ($apilevel >= 3) {
            $group = null;
            if ($this->assignment->get_instance()->teamsubmission) {
                $group = $this->assignment->get_submission_group($USER->id);
                $group = helper::get_mahara_group($group->id);
            }
            if ($viewdata = $this->get_view($response['copyid'], $iscollection, $group)) {
                $url = $viewdata['url'];
                $title = clean_text($viewdata['title']);
            } else {
                $url = $response['url'];
                $title = clean_text($response['title']);
            }
            $maharasubmission->viewid = $response['copyid'];
            $maharasubmission->viewurl = $url;
            $maharasubmission->viewtitle = $title;
        } else {
            $maharasubmission->viewid = $response['viewid'];
        }
        return $maharasubmission;
    }

    /**
     * Check if the submission plugin has all the required data to allow the work
     * to be submitted for grading
     * @param stdClass $submission the assign_submission record being submitted.
     * @return bool|string 'true' if OK to proceed with submission, otherwise a
     *                        a message to display to the user
     */
    public function precheck_submission($submission) {
        $maharasubmission = $this->get_mahara_submission($submission->id);
        if (!$maharasubmission) {
            return get_string('emptysubmission', 'assignsubmission_maharaws');
        }
        return true;
    }

     /**
      * Process submission for grading
      *
      * @param stdClass $submission
      * @return void
      */
    public function submit_for_grading($submission) {
        global $DB, $USER;

        // If the submission has been locked in the gradebook, then it has already been submitted on the Mahara side.
        $flags = $this->assignment->get_user_flags($submission->userid, false);
        if ($flags && $flags->locked == 1) {
            return;
        }

        $maharasubmission = $this->get_mahara_submission($submission->id);
        // Lock view on Mahara side as it has been submitted for assessment.
        if ($this->get_config('lock')) {
            if (
                !$response = $this->submit_view(
                    $submission,
                    $maharasubmission->viewid,
                    $maharasubmission->iscollection,
                    $submission->userid
                )
            ) {
                throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
            }
            // Update submission data by replacing the original view url and title with the copy url and title.
            $maharasubmission = $this->update_submission_data($response, $maharasubmission->iscollection, $maharasubmission);
            $maharasubmission->viewstatus = self::STATUS_SUBMITTED;
        } else {
            $maharasubmission->viewstatus = self::STATUS_RELEASED;
        }

        $DB->update_record('assignsubmission_maharaws', $maharasubmission);
    }

    /**
     * Process locking
     *
     * @param false|\stdClass $submission
     * @param \stdClass|null $flags
     */
    public function lock($submission, ?stdClass $flags = null) {
        global $DB;

        $maharasubmission = $this->get_mahara_submission($submission->id);

        // If we're not using page locking, then don't need to do anything special here.
        // If no page is selected, then we don't need to do anything special here.
        // If it's in submitted status, then it has already been locked and we don't need to do anything.
        if (
                !$this->get_config('lock')
                || !$maharasubmission
                || !$maharasubmission->viewid
                || $maharasubmission->viewstatus == self::STATUS_SUBMITTED
        ) {
            return;
        }

        // Lock view on Mahara side.
        $response = $this->submit_view(
            $submission,
            $maharasubmission->viewid,
            $maharasubmission->iscollection,
            $submission->userid
        );

        if (!$response) {
            throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
        }
        $maharasubmission->viewurl = $response['url'];
        $maharasubmission->viewstatus = self::STATUS_SUBMITTED;
        $DB->update_record('assignsubmission_maharaws', $maharasubmission);
    }

    /**
     * Process unlocking
     *
     * @param false|\stdClass $submission
     * @param \stdClass|null $flags
     */
    public function unlock($submission, ?stdClass $flags = null) {
        global $DB;

        // If it has been submitted, and we're using page locking, it needs to remain locked.
        if ($submission->status === ASSIGN_SUBMISSION_STATUS_SUBMITTED && $this->get_config('lock')) {
            return;
        }

        $maharasubmission = $this->get_mahara_submission($submission->id);

        // If no page is selected, then we don't need to do anything special here.
        // If the page isn't locked, we don't need to do anything special here.
        if (!$maharasubmission || !$maharasubmission->viewid || $maharasubmission->viewstatus !== self::STATUS_SUBMITTED) {
            return;
        }

        // Unlock view on Mahara side as it has been unlocked.
        if ($this->release_submitted_view($maharasubmission->viewid, [], $maharasubmission->iscollection) === false) {
            throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
        }
        $this->set_mahara_submission_status($maharasubmission->submission, self::STATUS_RELEASED);
    }

    /**
     * Process reverting to draft
     *
     * @param stdClass $submission
     * @return void
     */
    public function revert_to_draft(stdClass $submission) {
        global $DB;

        // If the submission has been locked in the gradebook, then we don't want to release it on the Mahara side
        // ... unless we've disabled page locking, in which case we might as well unlock it.
        $flags = $this->assignment->get_user_flags($submission->userid, false);
        if ($this->get_config('lock') && $flags && $flags->locked == 1) {
            return;
        }

        $maharasubmission = $this->get_mahara_submission($submission->id);
        if ($maharasubmission->viewstatus === self::STATUS_SUBMITTED) {
            // Unlock view on Mahara side as it has been reverted to draft.
            if ($this->release_submitted_view($maharasubmission->viewid, [], $maharasubmission->iscollection) === false) {
                throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
            }
            $this->set_mahara_submission_status($submission->id, self::STATUS_RELEASED);
        }
    }

    /**
     * Check if submission has been made
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $maharasubmission = $this->get_mahara_submission($submission->id);
        return empty($maharasubmission);
    }


    /**
     * Gets the preview (popup) and link out for the portfolio
     *
     * @param string $name
     * @param string|moodle_url $url
     * @param string $title (Optional)
     * @return string
     */
    public function get_preview_url($name, $url, $title = null) {
        global $OUTPUT, $PAGE;

        $cm = $PAGE->cm;

        $icon = $OUTPUT->pix_icon('t/preview', $name);
        $params = [
            'title' => $title ?: $name,
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ];

        $url = new moodle_url(
            '/mod/assign/submission/maharaws/launch.php',
            ['id' => $cm->id, 'url' => $url]
        );

        $popupicon = html_writer::link($url->out(false), $icon, $params + [
          'class' => 'portfolio',
        ]);

        $link = html_writer::link($url, $name, $params);

        return "$popupicon $link";
    }

    /**
     * Display the view of submission.
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $PAGE, $OUTPUT, $DB, $USER;

        $PAGE->requires->js('/mod/assign/submission/maharaws/js/popup.js');

        $result = '';
        $maharasubmission = $this->get_mahara_submission($submission->id);
        if ($maharasubmission) {
            $fields = ['assignment' => $submission->assignment];
            if (!empty($submission->groupid)) {
                $fields['groupid'] = $submission->groupid;
            }
            if (!empty($submission->userid)) {
                $fields['userid'] = $submission->userid;
            }
            $lastattempt = $DB->get_field('assign_submission', 'max(attemptnumber)', $fields);
            if ($submission->attemptnumber < $lastattempt) {
                $result .= get_string('previousattemptsnotvisible', 'assignsubmission_maharaws');
            } else {
                // Either the page is viewed by the author or access code has been issued.
                return $this->get_preview_url($maharasubmission->viewtitle, $maharasubmission->viewurl);
            }
        }
        return $result;
    }

    /**
     * View summary.
     *
     * @param stdClass $submission
     * @param bool $showviewlink (Mutable)
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        return $this->view($submission);
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'maharaws' && $version >= 2011070110) {
            return true;
        }
        return false;
    }

    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string $log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, &$log) {
        return true;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, &$log) {
        global $DB;

        $maharadata = unserialize($oldsubmission->data2);

        $maharasubmission = new stdClass();
        $maharasubmission->viewid = $maharadata['id'];
        $maharasubmission->viewurl = $maharadata['url'];
        $maharasubmission->viewtitle = $maharadata['title'];

        $url = new moodle_url($maharadata['url']);
        if ($url->get_param('mt')) {
            $maharasubmission->viewstatus = self::STATUS_SUBMITTED;
        }

        $maharasubmission->submission = $submission->id;
        $maharasubmission->assignment = $this->assignment->get_instance()->id;

        if (!$DB->insert_record('assignsubmission_maharaws', $maharasubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }
        return true;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // First of all release all pages on remote site.
        $records = $DB->get_records(
            'assignsubmission_maharaws',
            [
                'assignment' => $this->assignment->get_instance()->id,
                'viewstatus' => self::STATUS_SUBMITTED,
            ]
        );
        foreach ($records as $record) {
            if ($this->release_submitted_view($record->viewid, [], $record->iscollection) === false) {
                // A problem on the Mahara side should not prevent the assignment from being deleted.
                // But it's worth printing a message to the error logs.
                debugging(get_string('errorrequest', 'assignsubmission_maharaws', $this->get_error()));
            }
        }
        // Now delete records.
        $DB->delete_records('assignsubmission_maharaws', ['assignment' => $this->assignment->get_instance()->id]);

        return true;
    }

    /**
     *
     * Remove submission, happens when submisions are removed/revoked.
     * Additionally releases the submitted mahara view.
     * @param stdClass $submission The submission to be removed.
     */
    public function remove(stdClass $submission) {
        global $DB;
        $maharasubmission = $this->get_mahara_submission($submission->id);
        if ($maharasubmission && $maharasubmission->viewstatus == self::STATUS_SUBMITTED) {
            if ($this->release_submitted_view($maharasubmission->viewid, [], $maharasubmission->iscollection) === false) {
                throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
            }
            $this->set_mahara_submission_status($maharasubmission->submission, self::STATUS_RELEASED);
        }
        if ($submission->id || $submission->id == 0) {
            $DB->delete_records('assignsubmission_maharaws', ['submission' => $submission->id]);
        }
        return true;
    }

    /**
     * Carry out any extra processing required when a student is given a new attempt
     * (i.e. when the submission is "reopened"
     * @param stdClass $oldsubmission The previous attempt
     * @param stdClass $newsubmission The new attempt
     */
    public function add_attempt(stdClass $oldsubmission, stdClass $newsubmission) {
        global $DB;
        // Unlock the previous submission's page if the assignment is reopened. That way
        // the student can make improvements and then resubmit.
        $maharasubmission = $this->get_mahara_submission($oldsubmission->id);
        if ($maharasubmission && $maharasubmission->viewstatus == self::STATUS_SUBMITTED) {
            if ($this->release_submitted_view($maharasubmission->viewid, [], $maharasubmission->iscollection) === false) {
                throw new moodle_exception('errorrequest', 'assignsubmission_maharaws', '', $this->get_error());
            }
            $this->set_mahara_submission_status($maharasubmission->submission, self::STATUS_RELEASED);
        }
    }

    /**
     * Helper method to set the status of the an assignsubmission_maharaws record
     *
     * @param int $submissionid
     * @param string $status
     * @throws moodle_exception
     * @return boolean
     */
    public function set_mahara_submission_status($submissionid, $status) {
        global $DB;
        if (!($status === self::STATUS_SELECTED || $status === self::STATUS_SUBMITTED || $status === self::STATUS_RELEASED)) {
            throw new moodle_exception('errorinvalidstatus', 'assignsubmission_maharaws');
        }
        return $DB->set_field('assignsubmission_maharaws', 'viewstatus', $status, ['submission' => $submissionid]);
    }

    /**
     * Helper method to process API level strings into a useful value
     *
     * @param string $apilevelstring Api string from upstream mahara instance.
     * @throws moodle_exception Invalid api string exception
     * @return int
     */
    private function process_apilevel($apilevelstring) {
        $apinumber = explode(':', $apilevelstring)[1];
        if (!is_numeric($apinumber)) {
            throw new moodle_exception('errorinvalidapistring', 'assignsubmission_maharaws');
        }
        return $apinumber;
    }

    /**
     * Return id field name.
     *
     * @return string
     */
    private function get_mahara_idfield() {

        return
        // Now the trump all - we actually want to test against the institutions auth instances remoteuser.
            ($this->get_config('remoteuser') ?
             'remoteuser' :
        // Else idnumber maps to studentid.
             ($this->get_config('username_attribute') == 'idnumber' ?
             'studentid' :
        // Else the same attribute name in Mahara.
             $this->get_config('username_attribute')));
    }

    /**
     * Helper function to get url to use in site.
     *
     * @param string $config
     * @return string
     */
    public function get_config_default($config) {
        global $DB;
        // Some vars can be set at the site level.
        $sitelevelvars = ['url', 'key', 'secret', 'institution'];
        if (in_array($config, $sitelevelvars) && !empty(get_config('assignsubmission_maharaws', 'force_global_credentials'))) {
            return trim(get_config('assignsubmission_maharaws', $config));
        } else {
            // Check if this is set at activity level.
            // We can't use $this->get_config beacuase the settings aren't stored quite in a standard way.
            if ($this->assignment->has_instance()) {
                $assignment = $this->assignment->get_instance();
                $dbparams = ['assignment' => $assignment->id,
                          'plugin' => 'maharaws',
                          'subtype' => 'assignsubmission',
                          'name' => $config];
                $result = $DB->get_record('assign_plugin_config', $dbparams, '*', IGNORE_MISSING);
                if ($result) {
                    return trim($result->value);
                }
            }
            if (in_array($config, $sitelevelvars)) {
                // Return site default. (if not set at activity level).
                return trim(get_config('assignsubmission_maharaws', $config));
            }
        }
        return false;
    }

    /**
     * Helper function to call webservice function with specified parameters.
     *
     * @param array $data
     * @param array $records
     * @return array
     */
    public function run_get_views_by_id(array $data, array $records): array {
        $items = [];
        foreach ($records as $record) {
            $items[] = [
                'id' => $record->id,
                'viewid' => $record->viewid,
                'iscollection' => $record->iscollection,
            ];
        }
        try {
            $config = helper::get_ws_config($this->assignment);
            $returned = \assignsubmission_maharaws\webservice::call(
                "mahara_submission_get_views_by_id",
                ['items' => $items],
                $config
            );
        } catch (Exception $e) {
            throw new moodle_exception('errorwsrequest', 'assignsubmission_maharaws', '', $e->getMessage());
        }
        $returned['ids'] = array_map('intval', explode(',', $returned['ids']));
        for ($i = 0; $i < count($returned['ids']); $i++) {
            $data[$returned['ids'][$i]] = $returned['data'][$i];
            $data[$returned['ids'][$i]]['endpointurl'] = trim(get_config('assignsubmission_maharaws', 'url'));
        }
        return $data;
    }

    /**
     * Validate selected groups against the configured Mahara institution.
     *
     * @param array $allgroups Selected groups
     * @return array
     */
    public function validate_group_options(array $allgroups): array {
        global $DB, $COURSE;
        $groups = array_column($allgroups, 'id');
        $mappedinstitution = $this->get_config('institution');
        // If we don't have a mapped institution, fall back to the global institution for now.
        if (!$mappedinstitution) {
            $mappedinstitution = get_config('assignsubmission_maharaws', 'institution');
        }
        // Get all groups that belong to this institution or haven't been created in Mahara yet.
        if (!empty($groups)) {
            $sql = "SELECT g.id
                      FROM {groups} g
                 LEFT JOIN {assignsubmission_maharawsgroup} mg ON g.id = mg.moodlegroup
                     WHERE courseid = :courseid
                           AND (mg.institution = :institution
                           OR mg.institution IS NULL)";
            $params = [
                'courseid' => $COURSE->id,
                'institution' => $mappedinstitution,
            ];
            $result = $DB->get_records_sql($sql, $params);

            foreach ($allgroups as $moodlegroup) {
                if (!array_key_exists($moodlegroup->id, $result)) {
                    unset($allgroups[$moodlegroup->id]);
                }
            }
        }
        return $allgroups;
    }
}
