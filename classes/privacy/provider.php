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
 * Privacy Subsystem implementation for assignsubmission_maharaws.
 *
 * @package    assignsubmission_maharaws
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_maharaws\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\writer;
use \mod_assign\privacy\assign_plugin_request_data;

/**
 * Privacy Subsystem for assignsubmission_maharaws.
 *
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, \mod_assign\privacy\assignsubmission_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table('assignsubmission_maharaws', [
            'assignment' => 'privacy:metadata:assignment',
            'submission' => 'privacy:metadata:submission',
            'viewid' => 'privacy:metadata:viewid',
            'viewurl' => 'privacy:metadata:viewurl',
            'viewtitle' => 'privacy:metadata:viewtitle',
            'iscollection' => 'privacy:metadata:iscollection',
            'viewstatus' => 'privacy:metadata:viewstatus'
        ], 'privacy:metadata:assignsubmission_maharaws');

        return $collection;
    }

    /**
     * This is covered by mod_assign provider and the query on assign_submissions.
     *
     * @param  int $userid The user ID that we are finding contexts for.
     * @param  contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
        // This is already fetched from mod_assign.
    }

    /**
     * This is covered by the mod_assign provider and it's queries.
     *
     * @param  \mod_assign\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function get_student_user_ids(\mod_assign\privacy\useridlist $useridlist) {
        // No need.
    }

    /**
     * Export all user data for this plugin.
     *
     * @param  assign_plugin_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        global $DB;
        // We currently don't show submissions to teachers when exporting their data.
        if ($exportdata->get_user() != null) {
            return null;
        }
        $context = $exportdata->get_context();
        $currentpath = $exportdata->get_subcontext();
        $currentpath[] = get_string('privacy:path', 'assignsubmission_maharaws');
        $submission = $exportdata->get_pluginobject();
        $maharasubmission = $DB->get_record('assignsubmission_maharaws', array('submission' => $submission->id));
        if (!empty($maharasubmission)) {
            writer::with_context($context)
                // Add the text to the exporter.
                ->export_data($currentpath, $maharasubmission);
        }
    }

    /**
     * Delete all the submission records made for this context.
     *
     * @param  assign_plugin_request_data $requestdata Data to fulfill the deletion request.
     */
    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        global $DB;
        $DB->delete_records('assignsubmission_maharaws', ['assignment' => $requestdata->get_assign()->get_instance()->id]);
    }

    /**
     * A call to this method should delete user data (where practical) using the userid and submission.
     *
     * @param  assign_plugin_request_data $deletedata Details about the user and context to focus the deletion.
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata) {
        global $DB;

        $submissionid = $deletedata->get_pluginobject()->id;

        // Delete the records in the table.
        $DB->delete_records('assignsubmission_maharaws', ['assignment' => $deletedata->get_assign()->get_instance()->id,
            'submission' => $submissionid]);

    }
}
