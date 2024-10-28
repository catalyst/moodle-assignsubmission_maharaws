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
 * Tests for mod/assign/submission/ltisubmissions/locallib.php
 *
 * @package    assignsubmission_maharaws
 * @copyright  2024 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_maharaws;

use mod_assign_test_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Unit tests for mod/assign/submission/maharaws/locallib.php
 *
 * @package    assignsubmission_maharaws
 * @copyright  2024 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locallib_test extends \advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    /**
     * Test save_settings.
     *
     * Ensure that all required config is set if force_global_credentials is enabled
     * when assign is created with Mahara submissions by user with and without
     * assignsubmission/maharaws:configure permission.
     *
     * @covers ::save_settings
     */
    public function test_save_settings() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin configuration, enable global credentials.
        $pluginname = 'assignsubmission_maharaws';
        set_config('lock', '0', $pluginname);
        set_config('force_global_credentials', '1', $pluginname);
        set_config('url', 'https://url.com', $pluginname);
        set_config('key', 'key', $pluginname);
        set_config('secret', 'secret', $pluginname);

        // Course setup.
        $course = $this->getDataGenerator()->create_course();
        $teacherwithpermission = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $teacherwithoutpermission = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Assign configure role to editingteacher role.
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('assignsubmission/maharaws:configure', CAP_ALLOW, $roleid, SYSCONTEXTID, true);

        $this->setUser($teacherwithpermission);
        $assignwithpermission = $this->create_instance($course, [
                'assignsubmission_maharaws_enabled' => 1,
                // Must pass same data expected during config form submission by user with permissions.
                'assignsubmission_maharaws_lockpages' => 0,
                'assignsubmission_maharaws_archiveonrelease' => 0,
            ]);
        $this->setUser($teacherwithoutpermission);
        $assignwithoutpermission = $this->create_instance($course, [
                'assignsubmission_maharaws_enabled' => 1,
                // Don't set lockpages as this cannot be changed by user without permissions.
                'assignsubmission_maharaws_archiveonrelease' => 0,
        ]);

        // Assert that username_attribute has been set.
        $plugin = $assignwithpermission->get_submission_plugin_by_type('maharaws');
        $this->assertEquals('email', $plugin->get_config('username_attribute'));
        $plugin = $assignwithoutpermission->get_submission_plugin_by_type('maharaws');
        $this->assertEquals('email', $plugin->get_config('username_attribute'));

        // Assert that all submission config for assign has been set.
        // Assumes that global credentials are forced.
        $dbparams = array('assignment' => $assignwithpermission->get_instance()->id,
                          'subtype' => 'assignsubmission',
                          'plugin' => 'maharaws');
        $this->assertCount(6, $DB->get_records('assign_plugin_config', $dbparams));
        $dbparams = array('assignment' => $assignwithoutpermission->get_instance()->id,
                          'subtype' => 'assignsubmission',
                          'plugin' => 'maharaws');
        $this->assertCount(6, $DB->get_records('assign_plugin_config', $dbparams));
    }
}
