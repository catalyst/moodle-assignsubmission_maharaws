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

namespace assignsubmission_maharaws;

use core\hook\output\after_http_headers;

/**
 * Hook callbacks for assignsubmission_maharaws.
 *
 * @package     assignsubmission_maharaws
 * @author      2025 Sarah Cotton <sarah.cotton@catalyst-au.net>
 * @copyright   Catalyst IT, 2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Add sync task notifications to assignment or participants pages.
     *
     * @param after_http_headers $hook
     */
    public static function after_http_headers(after_http_headers $hook): void {
        global $OUTPUT, $DB;
        if (has_capability('mod/assign:addinstance', $OUTPUT->get_page()->context)) {
            // Check we're on the right page.
            $pagetype = $OUTPUT->get_page()->pagetype;
            if ($pagetype == 'mod-assign-view') {
                $classname = '\assignsubmission_maharaws\task\create_group';
            }
            if ($pagetype == 'course-view-participants') {
                $classname = '\assignsubmission_maharaws\task\sync_member';
            }

            // Get any queued adhoc tasks for this course.
            if (isset($classname)) {
                $courseid = $OUTPUT->get_page()->course->id;
                $likecourseid = $DB->sql_like('customdata', ':customdata');
                $params = [
                    'classname' => $classname,
                    'customdata' => '%"courseid":' . $courseid . '%',
                ];
                $tasks = $DB->get_records_sql(
                    "SELECT *
                           FROM {task_adhoc}
                          WHERE classname = :classname
                                AND $likecourseid",
                    $params
                );

                // Count pending/failed tasks.
                if (count($tasks) > 0) {
                    $pending = 0;
                    $failed = 0;
                    foreach ($tasks as $task) {
                        if ($task->faildelay == 0) {
                            $pending++;
                        } else {
                            $failed++;
                        }
                    }

                    // Output notification to the user.
                    if (isset($tasks)) {
                        $message = get_string(
                            'groups:syncnotification',
                            'assignsubmission_maharaws',
                            ['pending' => $pending, 'failed' => $failed]
                        );
                        $notification = $OUTPUT->render(
                            new \core\output\notification(
                                $message,
                                \core\output\notification::NOTIFY_WARNING
                            )
                        );
                        $hook->add_html($notification);
                    }
                }
            }
        }
    }
}
