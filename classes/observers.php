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
 * assign_submission_mahara events subscription
 *
 * @package    assignsubmission_maharaws
 * @copyright  2020 Catalyst IT
 * @copyright  2015 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_maharaws\helper;
use assignsubmission_maharaws\webservice;
use core\event\base;
use core\event\group_deleted;
use mod_assign\event\submission_graded;
use mod_assign\event\workflow_state_updated;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/assign/submission/maharaws/lib.php');

/**
 * Event handler for assign_submission_mahara plugin.
 */
class assignsubmission_maharaws_observers {
    /**
     * Process assignment grading function called by event trigger (see db/events.php).
     * It unlocks pages on Mahara when submission has been graded.
     *
     * @param submission_graded $event Event data object passed over by mod_assign
     * @return void
     */
    public static function submission_graded(submission_graded $event) {
        global $DB;
        $eventdata = $event->get_data();
        $grade = $event->get_record_snapshot('assign_grades', $eventdata['objectid']);
        $assign = $event->get_assign();
        $maharasubmissionplugin = $assign->get_submission_plugin_by_type('maharaws');

        // See if need to unlock anything at all.
        if ((int)$maharasubmissionplugin->get_config('lock') !== ASSIGNSUBMISSION_MAHARAWS_SETTING_UNLOCK) {
            return;
        }

        // Get submission if it exists.
        if (!$submission = $assign->get_user_submission($grade->userid, false)) {
            return;
        }

        // Get submission.
        $submission = $assign->get_group_submission($grade->userid, 0, false);
        if (!$submission) {
            $submission = $assign->get_user_submission($grade->userid, false);
        }
        if (!$submission) {
            return;
        }

        // Get Mahara submission.
        $maharasubmission = $DB->get_record('assignsubmission_maharaws', ['submission' => $submission->id]);

        // Process further only if we are dealing with mahara submission that is locked.
        if ($maharasubmission && $maharasubmission->viewstatus == assign_submission_maharaws::STATUS_SUBMITTED) {
            // Check if marking workflow is in place, page unlocking will be handled in
            // assignsubmission_maharaws_observers::workflow_state_updated unless .
            if ($assign->get_instance()->markingworkflow) {
                return;
            }

            // If submission has been "graded" but no grade is selected do not unlock page.
            if ($grade->grade == -1 || $grade->grade === null) {
                return;
            }

            self::release_submitted_view($maharasubmissionplugin, $maharasubmission);
        }
    }

    /**
     * Process workflow state update called by event trigger (see db/events.php).
     * It unlocks pages on Mahara when grades are released to students.
     *
     * @param mod_assign\event\workflow_state_updated $event Event data object passed over by mod_assign
     * @return void
     */
    public static function workflow_state_updated(workflow_state_updated $event) {
        global $DB;
        $eventdata = $event->get_data();
        $assign = $event->get_assign();
        $maharasubmissionplugin = $assign->get_submission_plugin_by_type('maharaws');

        // See if need to unlock anything at all.
        if ((int)$maharasubmissionplugin->get_config('lock') !== ASSIGNSUBMISSION_MAHARAWS_SETTING_UNLOCK) {
            return;
        }

        // Get submission.
        $submission = $assign->get_group_submission($eventdata['relateduserid'], 0, false);
        if (!$submission) {
            $submission = $assign->get_user_submission($eventdata['relateduserid'], false);
        }
        if (!$submission) {
            return;
        }

        // Get Mahara submission.
        $maharasubmission = $DB->get_record('assignsubmission_maharaws', ['submission' => $submission->id]);

        // Process further only if we are dealing with mahara submission that is locked.
        if ($maharasubmission && $maharasubmission->viewstatus == assign_submission_maharaws::STATUS_SUBMITTED) {
            // Check marking workflow state, only unlock page if marks are released.
            if ($eventdata['other']['newstate'] !== ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
                return;
            }

            self::release_submitted_view($maharasubmissionplugin, $maharasubmission);
        }
    }

    /**
     * Process unlocking Mahara page.
     *
     * @param \assign_submission_maharaws $maharasubmissionplugin
     * @param object $maharasubmission Mahara submission data object.
     * @return void
     */
    protected static function release_submitted_view($maharasubmissionplugin, $maharasubmission): void {
        // Release submitted page, but provide no outcomes.
        $maharasubmissionplugin->release_submitted_view(
            $maharasubmission->viewid,
            [],
            $maharasubmission->iscollection
        );

        if ($maharasubmissionplugin->get_error()) {
            throw new moodle_exception('releasefailed', 'error', $maharasubmissionplugin->get_error());
        } else {
            $maharasubmissionplugin->set_mahara_submission_status(
                $maharasubmission->submission,
                assign_submission_maharaws::STATUS_RELEASED
            );
        }
    }

    /**
     * Update the Mahara group name.
     *
     * @param base $event
     * @return void
     */
    public static function mahara_group_update(base $event): void {
        global $COURSE;
        $data = $event->get_data();
        $maharagroup = helper::get_mahara_group($data['objectid']);

        if ($maharagroup) {
            if ($data['action'] == 'updated') {
                $moodlegroup = groups_get_group($data['objectid']);
                $groupname = $moodlegroup->name;
            }
            if ($data['action'] == 'deleted') {
                $groupname = get_string('groups:groupdeleted:name', 'assignsubmission_maharaws', $data['objectid']);
            }

            $assignment = helper::get_assignment_for_ws($data['courseid'], $data['objectid']);

            if (!$assignment) {
                // If the assignment has already been deleted we can't retrieve the connection
                // settings needed to update Mahara. The user will need to manage the Mahara
                // group manually in this case.
                \core\notification::add(
                    get_string(
                        'groups:noassignmentfound',
                        'assignsubmission_maharaws',
                        preg_replace(
                            '/[^a-z0-9]/',
                            '',
                            strtolower(
                                get_string('groups:groupdesc', 'assignsubmission_maharaws') . $data['objectid']
                            )
                        )
                    ),
                    \core\output\notification::NOTIFY_WARNING
                );
                return;
            }

            $config = helper::get_ws_config($assignment);
            $params = [
                'groups' => [[
                    'id' => $maharagroup->maharagroup,
                    'name' => get_string(
                        'groups:groupname',
                        'assignsubmission_maharaws',
                        ['courseshortname' => $COURSE->fullname, 'groupname' => $groupname]
                    ),
                    'description' => get_string('groups:groupdesc', 'assignsubmission_maharaws'),
                ]],
            ];

            try {
                webservice::call('mahara_group_update_groups_details', $params, $config);
            } catch (Exception $e) {
                \core\notification::add(
                    get_string(
                        'errorwsrequest',
                        'assignsubmission_maharaws',
                        $e->getMessage()
                    ),
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            \core\notification::add(
                get_string(
                    'groups:updategroup',
                    'assignsubmission_maharaws'
                ),
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }

    /**
     * Unlink the Mahara group from Moodle.
     * We do not delete the Mahara group itself as it could have student work in it.
     *
     * @param group_deleted $event
     * @return void
     */
    public static function mahara_group_delete(group_deleted $event): void {
        global $DB;
        $data = $event->get_data();
        // Get Mahara group - we now have the institution.
        $maharagroup = helper::get_mahara_group($data['objectid']);

        // Rename the Mahara group to append (Deleted from Moodle).
        self::mahara_group_update($event);

        if ($maharagroup) {
            // Unlink the Mahara group from any Moodle assignments.
            $assignmentconfig = $DB->get_records_sql(
                "SELECT ac.id, a.teamsubmission, value
                       FROM {assign} a
                       JOIN {assign_plugin_config} ac ON ac.assignment = a.id
                      WHERE course = ?
                            AND plugin = 'maharaws'
                            AND ac.name = 'groups'",
                ['course' => $data['courseid']]
            );

            foreach ($assignmentconfig as $config) {
                if (!empty($config->value)) {
                    $groups = explode(',', $config->value);
                    foreach ($groups as $group) {
                        if ($group == $maharagroup->moodlegroup) {
                            $key = array_search($group, $groups);
                            unset($groups[$key]);
                        }
                    }

                    if (!empty($groups)) {
                        $groups = implode(',', $groups);
                    }
                    if ($groups != $config->value) {
                        $params = new stdClass();
                        $params->id = $config->id;
                        $params->value = $groups;
                        $DB->update_record('assign_plugin_config', $params);
                    }
                }
            }

            // Unlink the Mahara group from its Moodle group.
            $result = $DB->delete_records('assignsubmission_maharawsgroup', ['id' => $maharagroup->id]);

            if ($result) {
                \core\notification::add(
                    get_string(
                        'groups:groupdeleted',
                        'assignsubmission_maharaws'
                    ),
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
        }
    }

    /**
     * Sync a users Moodle group membership to the linked Mahara group.
     *
     * @param base $event
     * @return void
     */
    public static function mahara_group_update_member(base $event): void {
        global $USER;
        $data = $event->get_data();
        $action = 'add';
        if ($data['action'] == 'removed') {
            $action = 'remove';
        }

        // Get Mahara group - we now have the institution.
        $maharagroup = helper::get_mahara_group($data['objectid']);
        if ($maharagroup) {
            $user = core_user::get_user($data['relateduserid'], '*', MUST_EXIST);
            $user->moodlegroup = $maharagroup->moodlegroup;
            $user->action = $action;

            // Create adhoc task to sync the user.
            $task = \assignsubmission_maharaws\task\sync_member::instance(
                $data['objectid'],
                $data['courseid'],
                $USER->id,
                $user->id,
                $action
            );
            \core\task\manager::queue_adhoc_task($task, true);

            \core\notification::add(
                get_string(
                    'groups:updatemember',
                    'assignsubmission_maharaws',
                    $user->username
                ),
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }
}
