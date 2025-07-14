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
 * Adhoc task that syncs group members in Mahara.
 *
 * @package     assignsubmission_maharaws
 * @author      2025 Sarah Cotton <sarah.cotton@catalyst-au.net>
 * @copyright   Catalyst IT, 2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_maharaws\task;

use assignsubmission_maharaws\helper;
use assignsubmission_maharaws\webservice;
use Exception;
use stdClass;

/**
 * Adhoc task class.
 */
class sync_member extends \core\task\adhoc_task {
    /**
     * Create a new instance of the task.
     *
     * @param int $groupid Moodle group id
     * @param int $courseid
     * @param int $userid
     * @param int $relateduserid
     * @param string $action
     * @return sync_member
     */
    public static function instance(int $groupid, int $courseid, int $userid, int $relateduserid, string $action): self {
        $task = new self();
        $task->set_component('assignsubmission_maharaws');
        $task->set_userid($userid);
        $task->set_custom_data((object)[
            'groupid' => $groupid,
            'courseid' => $courseid,
            'relateduserid' => $relateduserid,
            'action' => $action,
        ]);
        return $task;
    }

    /**
     * Run the adhoc task and perform the sync.
     */
    public function execute(): void {
        global $DB;

        $maharagroup = helper::get_mahara_group($this->get_custom_data()->groupid);

        if ($maharagroup) {
            $username = $DB->get_field('user', 'username', ['id' => $this->get_custom_data()->relateduserid]);
            $errormessage = self::member_sync($maharagroup, $username, $this->get_custom_data());

            if (!empty($errormessage)) {
                $course = get_course($this->get_custom_data()->courseid);
                helper::send_notification($this->get_userid(), $course, 'sync_member', $errormessage);
            }
        }
    }

    /**
     * Sync the member to Mahara.
     *
     * @param stdClass $maharagroup
     * @param string $username
     * @param stdClass $customdata Must contain courseid|groupid|action
     * @return string
     */
    public static function member_sync(stdClass $maharagroup, string $username, stdClass $customdata): string {
        $assignment = helper::get_assignment_for_ws($customdata->courseid, $customdata->groupid);
        $config = helper::get_ws_config($assignment, $maharagroup);
        $result = self::check_mahara_user_exists($username, $customdata->action, $config);
        $errormessage = '';

        if (is_string($result)) {
            $errormessage .= $result;
        }

        if ($result === true) {
            $params = [
                'groups' => [[
                    'id' => $maharagroup->maharagroup,
                    'institution' => $maharagroup->institution,
                    'members' => [[
                        'username' => $username,
                        'role' => 'member',
                        'action' => $customdata->action,
                    ]],
                ]],
            ];

            $group = groups_get_group($maharagroup->moodlegroup);

            try {
                mtrace('Updating group membership for: ' . $username .
                    ' | Group: ' . $group->name);
                webservice::call('mahara_group_update_group_members', $params, $config);
            } catch (\Exception $e) {
                $errormessage = 'Group membership not updated for  ' . $username .
                    ' | Group: ' . $group->name . ' | Error' . strstr($e->getMessage(), ':');
                mtrace($errormessage);
                return $errormessage;
            }
        }

        return $errormessage;
    }

    /**
     * Check whether a given user exists in Mahara and create an account if required.
     *
     * @param string $username The user being synced
     * @param string $action The action being performed
     * @param stdClass $connection The web service connection details
     * @return string|bool
     */
    private static function check_mahara_user_exists(string $username, string $action, stdClass $connection): string|bool {
        global $DB;
        $member = $DB->get_record('user', ['username' => $username]);
        $params = [
            'users' => [[
                'username' => $member->username,
                'email' => $member->email,
            ]],
        ];
        try {
            webservice::call("mahara_user_get_users_by_id", $params, $connection);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'does not exist')) {
                // Create user in Mahara if we're adding them to a group.
                if ($action === 'add') {
                    self::create_mahara_user($member, $connection);
                } else {
                    mtrace("User doesn't exist in Mahara and group action is 'remove': No sync performed");
                    return false;
                }
            }

            // A user might not exist in the institution for this web service, but they might exist in another.
            if (str_contains($e->getMessage(), 'Not authorised for access to account')) {
                $errormessage = get_string(
                    'groups:updatemember:error',
                    'assignsubmission_maharaws',
                    ['username' => $member->username, 'institution' => $connection->institution]
                );
                mtrace($errormessage);
                return $errormessage;
            }
        }

        return true;
    }

    /**
     * Create a user in Mahara.
     *
     * @param stdClass $member The user being synced
     * @param stdClass $connection The web service connection details.
     */
    private static function create_mahara_user(stdClass $member, stdClass $connection): void {

        $params = [
            'users' => [[
                'username' => $member->username,
                'firstname' => $member->firstname,
                'lastname' => $member->lastname,
                'email' => $member->email,
                'institution' => $connection->institution,
                'remoteuser' => $member->id,
                'password' => generate_password(),
                'auth' => 'webservice',
            ]],
        ];

        try {
            // Create the user.
            mtrace('Creating user in Mahara: ' . $member->username);
            webservice::call("mahara_user_create_users", $params, $connection);
        } catch (\Exception $e) {
            mtrace('Error' . $e->getMessage());
        }
    }
}
