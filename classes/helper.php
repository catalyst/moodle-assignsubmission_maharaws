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
 * Helper class.
 *
 * @package     assignsubmission_maharaws
 * @author      2025 Sarah Cotton <sarah.cotton@catalyst-au.net>
 * @copyright   Catalyst IT, 2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */

namespace assignsubmission_maharaws;

use assign;
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/mod/assign/locallib.php");

/**
 * Helper class.
 */
class helper {
    /**
     * Get the Mahara group mapping record.
     *
     * @param int $moodlegroup The Moodle group id.
     * @return mixed a fieldset object containing the first matching record or false.
     */
    public static function get_mahara_group(int $moodlegroup): mixed {
        global $DB;
        return $DB->get_record('assignsubmission_maharawsgroup', ['moodlegroup' => $moodlegroup]);
    }

    /**
     * Create Mahara groups against the configured Mahara institution.
     *
     * @param stdClass $data The form data.
     */
    public static function create_mahara_groups(stdClass $data): void {
        global $DB, $USER;

        $groupids = $data->assignsubmission_maharaws_groups;
        if ($groupids) {
            $creategroups = [];
            $creategroupserrors = [];
            foreach ($groupids as $groupid) {
                $group = groups_get_group($groupid);

                // Has the selected group already been created/mapped to a Mahara group?
                $maharagroup = self::get_mahara_group($group->id);
                // If not, schedule an adhoc task to try and create the group.
                if (!$maharagroup) {
                    try {
                        $task = \assignsubmission_maharaws\task\create_group::instance($USER->id);
                        $taskdata = [
                            'groupid' => $group->id,
                        ];
                        $task->set_custom_data($taskdata);
                        \core\task\manager::queue_adhoc_task($task, true);
                        $creategroups[] = $group->name;

                        if (!$DB->get_record('assignsubmission_maharawsgroup', ['moodlegroup' => $group->id])) {
                            // Store Moodle group id and institution.
                            $obj = new stdClass();
                            $obj->moodlegroup = $group->id;
                            $obj->institution = $data->institution;
                            $DB->insert_record('assignsubmission_maharawsgroup', $obj);
                        }
                    } catch (\Exception $e) {
                        $creategroupserrors[] = $e->getMessage();
                    }
                }
            }

            if (!empty($creategroups)) {
                // Let the user know the groups have been queued for creation.
                \core\notification::add(
                    get_string(
                        'groups:createdgroups',
                        'assignsubmission_maharaws',
                        implode(', ', $creategroups)
                    ),
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }

            if (!empty($creategroupserrors)) {
                // Let the user know of any errors.
                \core\notification::add(
                    get_string(
                        'groups:createdgroups:errors',
                        'assignsubmission_maharaws',
                        implode(', ', $creategroupserrors)
                    ),
                    \core\output\notification::NOTIFY_ERROR
                );
            }
        }
    }

    /**
     * Send error notification for sync job failures.
     *
     * @param int $userid User to send the notification to.
     * @param stdClass $course Course the notification is associated with.
     * @param string $taskname Name of the task that failed.
     * @param string $messagebody The message.
     */
    public static function send_notification(int $userid, stdClass $course, string $taskname, string $messagebody) {
        global $DB;

        $message = new \core\message\message();
        $message->component = 'assignsubmission_maharaws';
        $message->name = $taskname;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $userid;
        $message->subject = get_string('messagesubject:' . $taskname, 'assignsubmission_maharaws', $course->fullname);
        $message->fullmessage = $messagebody;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $messagebody;
        $message->smallmessage = $messagebody;
        $message->notification = 1;
        $message->contexturl = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        $message->contexturlname = 'Course ' . $course->fullname;

        // Extra content for specific processor.
        $content = [
            '*' => [
                'footer' => '<p>Link to course: ' . $message->contexturl . '</p>',
            ],
        ];
        $message->set_additional_content('email', $content);
        message_send($message);

        // Additionally send the notification to admins.
        $admins = get_config('assignsubmission_maharaws', 'errornotifications');
        if ($admins !== '') {
            $admins = explode(',', $admins);
            foreach ($admins as $admin) {
                $userid = $DB->get_field('user', 'id', ['username' => trim($admin)]);
                $message->userto = $userid;
                message_send($message);
            }
        }
    }

    /**
     * Helper function to get config values.
     *
     * @param ?assign $assignment
     * @return stdClass
     */
    public static function get_ws_config(assign $assignment): stdClass {
        global $DB;

        // Get the global plugin config first.
        $config = get_config('assignsubmission_maharaws');

        // Ensure expected defaults exist.
        $config->url = isset($config->url) ? trim($config->url) : '';
        $config->key = isset($config->key) ? trim($config->key) : '';
        $config->secret = isset($config->secret) ? trim($config->secret) : '';
        $config->institution = isset($config->institution) ? trim($config->institution) : '';

        // If global settings are forced, always use those.
        if (!empty($config->force_global_credentials)) {
            return $config;
        }

        // If we have an assignment, override the global connection settings
        // with any assignment-level settings that exist.
        if ($assignment && $assignment->has_instance()) {
            $instance = $assignment->get_instance();

            $connectionvars = ['url', 'key', 'secret', 'institution'];
            [$insql, $inparams] = $DB->get_in_or_equal($connectionvars, SQL_PARAMS_NAMED);

            $sql = "SELECT name, value
                  FROM {assign_plugin_config}
                 WHERE assignment = :assignment
                   AND plugin = :plugin
                   AND subtype = :subtype
                   AND name {$insql}";

            $params = [
                    'assignment' => $instance->id,
                    'plugin' => 'maharaws',
                    'subtype' => 'assignsubmission',
                ] + $inparams;

            $records = $DB->get_records_sql($sql, $params);

            foreach ($connectionvars as $name) {
                if (isset($records[$name]) && $records[$name]->value !== null && $records[$name]->value !== '') {
                    $config->{$name} = trim($records[$name]->value);
                }
            }
        }

        return $config;
    }

    /**
     * Resolve an assignment linked to a Moodle group for Mahara WS config lookup.
     *
     * @param int $courseid The course ID.
     * @param int $groupid The group ID.
     * @return \assign|null
     */
    public static function get_assignment_for_ws(int $courseid, int $groupid): ?\assign {
        global $DB;

        if (empty($courseid) || empty($groupid)) {
            return null;
        }

        // Try to resolve an assignment with the relevant WS config.
        $sql = "SELECT assignment, value
              FROM {assign_plugin_config} apc
              JOIN {assign} a ON a.id = apc.assignment
             WHERE (apc.name = :name
               AND apc.plugin = :plugin
               AND apc.subtype = :subtype)
               AND a.course = :courseid";

        $params = [
            'name' => 'groups',
            'plugin' => 'maharaws',
            'subtype' => 'assignsubmission',
            'courseid' => $courseid,
        ];

        $records = $DB->get_records_sql($sql, $params);
        $groupid = (string)(int)$groupid;
        foreach ($records as $record) {
            $groups = array_filter(array_map('trim', explode(',', (string)$record->value)), 'strlen');
            if (in_array($groupid, $groups, true)) {
                $assignid = (int)$record->assignment;
                [$course, $cm] = get_course_and_cm_from_instance($assignid, 'assign');
                $context = \context_module::instance($cm->id);
                $assignment = new \assign($context, $cm, $course);
                return $assignment;
            }
        }

        return null;
    }
}
