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
 * Adhoc task that creates groups in Mahara.
 *
 * @package     assignsubmission_maharaws
 * @author      2025 Sarah Cotton <sarah.cotton@catalyst-au.net>
 * @copyright   Catalyst IT, 2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_maharaws\task;

use assignsubmission_maharaws\helper;
use assignsubmission_maharaws\webservice;

/**
 * Adhoc task class.
 */
class create_group extends \core\task\adhoc_task {
    /**
     * Create a new instance of the task.
     *
     * @param int $userid
     * @return create_group
     */
    public static function instance(int $userid): self {
        $task = new self();
        $task->set_component('assignsubmission_maharaws');
        $task->set_userid($userid);
        return $task;
    }

    /**
     * Run the adhoc task and perform the sync.
     */
    public function execute() {
        global $DB;
        $started = time();

        $group = groups_get_group($this->get_custom_data()->groupid);
        if ($group) {
            // Has the selected group already been created/mapped to a Mahara group?
            $maharagroup = helper::get_mahara_group($group->id);
            // If not try and create the group.
            if ($maharagroup && $maharagroup->maharagroup == 0) {
                $course = get_course($group->courseid);
                $assignment = helper::get_assignment_for_ws($course->id, $group->id);
                $config = helper::get_ws_config($assignment, $maharagroup);
                $errormessage = '';

                try {
                    $result = webservice::call(
                        "mahara_group_create_groups",
                        [
                            'groups' =>
                            [
                                [
                                    'name' => get_string(
                                        'groups:groupname',
                                        'assignsubmission_maharaws',
                                        ['courseshortname' => $course->fullname, 'groupname' => $group->name]
                                    ),
                                    'shortname' => strtolower('moodlemanagedgroup' . $group->id),
                                    'description' => get_string('groups:groupdesc', 'assignsubmission_maharaws'),
                                    'institution' => $config->institution,
                                    'grouptype' => 'course',
                                    'category' => get_string('groups:category', 'assignsubmission_maharaws'),
                                    'forcecategory' => 1,
                                    'editroles' => 'all',
                                    'open' => 0,
                                    'controlled' => 1,
                                    'request' => 0,
                                    'submitpages' => 0,
                                    'public' => 0,
                                    'viewnotify' => 1,
                                    'feedbacknotify' => 1,
                                    'usersautoadded' => 0,
                                    'hidden' => 1,
                                    'hidemembers' => 1,
                                    'hidemembersfrommembers' => 0,
                                    'groupparticipationreports' => 0,
                                    'grouparchivereports' => 0,
                                    'members' => [],
                                ],
                            ],
                        ],
                        $config
                    );

                    foreach ($result as $r) {
                        if (!empty($r)) {
                            // Update record with Mahara group id.
                            $obj = new \stdClass();
                            $obj->id = $maharagroup->id;
                            $obj->maharagroup = $r['id'];
                            $DB->update_record('assignsubmission_maharawsgroup', $obj);

                            mtrace('Group ' . $group->name . ' created in Mahara for course ' . $course->id);

                            // Now sync the users.
                            $groupmembers = groups_get_members($group->id);
                            $maharagroup->maharagroup = $obj->maharagroup;
                            foreach ($groupmembers as $member) {
                                $customdata = new \stdClass();
                                $customdata->courseid = $course->id;
                                $customdata->groupid = $group->id;
                                $customdata->action = 'add';
                                $errormessage .= sync_member::member_sync($maharagroup, $member->username, $customdata);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $errormessage .= "Group '" . $group->name . "' not created in Mahara for course " . $course->id .
                        " | Error: " . $e->getMessage();
                    mtrace($errormessage);
                }

                if (!empty($errormessage)) {
                    // Send notification to tutor and admins.
                    helper::send_notification($this->get_userid(), $course, 'create_group', $errormessage);
                }
            }
        }

        $duration = time() - $started;
        mtrace('Creation completed in: ' . $duration . ' seconds');
    }
}
