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
 * Upgrade code for install
 *
 * @package    assignsubmission_maharaws
 * @copyright  2020 Catalyst IT
 * @copyright  2012 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignsubmission_maharaws_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013062401) {

        // If you're migrating from the Portland U version of the plugin, we can skip this part because
        // the table won't exist at all.
        if ($dbman->table_exists('assignsubmission_maharaws')) {
            // Define field iscollection to be added to assignsubmission_maharaws.
            $table = new xmldb_table('assignsubmission_maharaws');
            $field = new xmldb_field('iscollection', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'viewaccesskey');

            // Conditionally launch add field iscollection.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

        }

        // Mahara savepoint reached.
        upgrade_plugin_savepoint(true, 2013062401, 'assignsubmission', 'maharaws');
    }

    if ($oldversion < 2014071000) {

        // If you're upgrading from the Portland U version of the plugin, this table won't exist yet, so you don't need to add the
        // viewstatus column.
        if ($dbman->table_exists('assignsubmission_maharaws')) {
            require_once($CFG->dirroot.'/mod/assign/submissionplugin.php');
            require_once($CFG->dirroot.'/mod/assign/submission/maharaws/locallib.php');

            // Define field viewstatus to be added to assignsubmission_maharaws.
            $table = new xmldb_table('assignsubmission_maharaws');
            $field = new xmldb_field('viewstatus', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'iscollection');

            // Conditionally launch add field viewstatus.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $DB->execute("update {assignsubmission_maharaws}
                             set viewstatus='".assign_submission_mahara::STATUS_SELECTED."'
                           where viewaccesskey is null");

            $DB->execute("update {assignsubmission_maharaws}
                             set viewstatus='".assign_submission_mahara::STATUS_SUBMITTED."'
                           where viewaccesskey is not null");

            // Define field viewaccesskey to be dropped from assignsubmission_maharaws.
            $table = new xmldb_table('assignsubmission_maharaws');
            $field = new xmldb_field('viewaccesskey');

            // Conditionally launch drop field viewaccesskey.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Mahara savepoint reached.
        upgrade_plugin_savepoint(true, 2014071000, 'assignsubmission', 'maharaws');
    }

    if ($oldversion < 2014082000) {

        // Migrate from the Portland U version of the plugin.
        if (
                !$dbman->table_exists('assignsubmission_maharaws')
                && $dbman->table_exists('assign_mahara_submit_views')
                && $dbman->table_exists('mahara_portfolio')
        ) {
            require_once($CFG->dirroot.'/mod/assign/submissionplugin.php');
            require_once($CFG->dirroot.'/mod/assign/submission/maharaws/locallib.php');

            // Change config name.
            $DB->set_field(
                    'assign_plugin_config',
                    'name',
                    'mnethostid',
                    array(
                            'plugin' => 'maharaws',
                            'subtype' => 'assignsubmission',
                            'name' => 'mahara_host'
                    )
            );

            // Define table assignsubmission_maharaws to be created.
            $table = new xmldb_table('assignsubmission_maharaws');

            // Adding fields to table assignsubmission_maharaws.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('assignment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('submission', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('viewid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('viewurl', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('viewtitle', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('iscollection', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('viewstatus', XMLDB_TYPE_CHAR, '20', null, null, null, null);

            // Adding keys to table assignsubmission_maharaws.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('assignment', XMLDB_KEY_FOREIGN, array('assignment'), 'assign', array('id'));
            $table->add_key('submission', XMLDB_KEY_FOREIGN, array('submission'), 'assign_submission', array('id'));
            $dbman->create_table($table);

            // Migrate data from assign_mahara_submit_views && mahara_portfolio tables.
            $rs = $DB->get_recordset('assign_mahara_submit_views', null, 'id');
            foreach ($rs as $submissiondata) {
                $page = $DB->get_record('mahara_portfolio', array('id' => $submissiondata->portfolio));
                $todb = new stdClass();
                $todb->assignment = $submissiondata->assignment;
                $todb->submission = $submissiondata->submission;
                $todb->viewid = $page->page;
                $todb->viewurl = $page->url;
                $todb->viewtitle = $page->title;
                $todb->iscollection = 0;
                $status = $submissiondata->status;
                if ($status == assign_submission_mahara::STATUS_RELEASED ||
                    $status == assign_submission_mahara::STATUS_SELECTED ||
                    $status == assign_submission_mahara::STATUS_SUBMITTED) {
                    $todb->status = $status;
                }
                $todb->status = $submissiondata->status;
                $DB->insert_record('assignsubmission_maharaws', $todb);
            }
            $dbman->drop_table(new xmldb_table('assign_mahara_submit_views'));
            $dbman->drop_table(new xmldb_table('mahara_portfolio'));

        }
        // Mahara savepoint reached.
        upgrade_plugin_savepoint(true, 2014082000, 'assignsubmission', 'maharaws');
    }

    if ($oldversion < 2015021002) {
        // First of all, fetch assignments that have assignfeedback enabled.
        $sql = 'SELECT assignment FROM {assign_plugin_config} WHERE plugin = ? AND subtype = ? AND name = ? AND value = ?';
        $records = $DB->get_recordset_sql($sql, array(
                $DB->sql_compare_text('maharaws'), $DB->sql_compare_text('assignfeedback'),
                $DB->sql_compare_text('enabled'), $DB->sql_compare_text('1')));
        // Now update assignment settings, making unlocking enabled in assignment lock
        // setting for those where assignfeedback_mahara was enabled.
        foreach ($records as $record) {
            $sql = "UPDATE {assign_plugin_config} SET value = '2'
                     WHERE plugin = 'maharaws' AND
                           subtype = 'assignsubmission' AND
                           name = 'lock' AND
                           value = '1' AND
                           assignment = ?";
            $DB->execute($sql, array($record->assignment));
        }
        upgrade_plugin_savepoint(true, 2015021002, 'assignsubmission', 'maharaws');
    }

    if ($oldversion < 2015021003) {
        $result = true;
        $feedbackplugins = core_component::get_plugin_list('assignfeedback');
        if (!empty($feedbackplugins['maharaws'])) {
            $pluginman = core_plugin_manager::instance();
            $uninstallurl = $pluginman->get_uninstall_url('assignfeedback_mahara', 'overview');
            $uninstall = html_writer::link($uninstallurl, 'uninstall');
            echo html_writer::div("It seems you are using assignfeedback_mahara plugin. "
                    . "This plugin is no longer required for Mahara pages unlocking and conflicting "
                    . "with this upgrade. Please " . $uninstall . " assignfeedback_mahara "
                    . "plugin first, remove its installation directory, and then proceed "
                    . "with upgrading by navigating to \"Site adminstration\" > \"Notifications\".",
                    'alert alert-error');
            $result = false;
        }

        // Mahara savepoint reached.
        upgrade_plugin_savepoint(true, 2015021003, 'assignsubmission', 'maharaws');
    }

    if ($oldversion < 2021081800) {
        // We don't want to break existing sites, but we don't want new ones
        // to do this either as it doesn't match how the lti login behaves.
        set_config('legacy_ext_usr_username', true, 'assignsubmission_maharaws');
        upgrade_plugin_savepoint(true, 2021081800, 'assignsubmission', 'maharaws');
    }

    // Uncomment this upgrade step to convert records from assignsubmission_mahara to assignsubmission_maharaws
    /*
    if ($oldversion < 2024081601) {
        $assign = new assign(null, null, null);
        $wsplugin = $assign->get_submission_plugin_by_type('maharaws');
        $data = [];
        $records = $DB->get_records('assignsubmission_mahara');
        if (!empty(get_config('assignsubmission_maharaws', 'force_global_credentials'))) {
            // if force globals, proceed with whole table.
            $data = $wsplugin->run_get_views_by_id($data, $records);
        } else {
            set_config('force_global_credentials', '1', 'assignsubmission_maharaws');
            // if globals available, save in a variable
            $globals = [];
            foreach (['url','key','secret'] as $config) {
                if (isset($globals)) {
                    if ($result = get_config('assignsubmission_maharaws', $config)) {
                        $globals[$config] = trim($result);
                    } else {
                        unset($globals);
                    }
                }
            }
            // arrange table records in assignment clusters
            $assignments = [];
            foreach ($records as $record) {
                $assignments[$record->assignment][] = (object)[
                    'id'           => $record->id,
                    'viewid'       => $record->viewid,
                    'iscollection' => $record->iscollection
                ];
            }
            foreach ($assignments as $assid => $assignment) {
                // get maharaws config for this assignment
                $locals = true;
                $dbparams = [
                    'assignment' => $assid,
                    'plugin' => 'maharaws',
                    'subtype' => 'assignsubmission'
                ];
                if ($result = $DB->get_records('assign_plugin_config', $dbparams)) {
                    $resultarray = [];
                    foreach($result as $resultitem) {
                        $resultarray[$resultitem->name] = trim($resultitem->value);
                    }
                    if (empty($resultarray['enabled'])) {
                        mtrace("assignsubmission_maharaws disabled for assignment {$assid}: skipping");
                        $records = array_filter($records, function($a) use($assid) {
                            return $a->assignment != $assid;
                        });
                        continue;
                    }
                    foreach (['url','key','secret'] as $config) {
                        if (empty($resultarray[$config])) {
                            unset($locals);
                        }
                    }
                } else {
                    unset($locals);
                }
                if (isset($locals)) {
                    foreach (['url','key','secret'] as $config) {
                        set_config($config, $resultarray[$config], 'assignsubmission_maharaws');
                    }
                    $data = $wsplugin->run_get_views_by_id($data, $assignment);
                    foreach (['url','key','secret'] as $config) {
                        if (!empty($globals)) {
                            set_config($config, $globals[$config], 'assignsubmission_maharaws');
                        } else {
                            unset_config($config, 'assignsubmission_maharaws');
                        }
                    }
                } elseif (isset($globals)) {
                    $data = $wsplugin->run_get_views_by_id($data, $assignment);
                } else {
                    mtrace("no maharaws endpoint configured for assignment {$assid}: skipping");
                    $records = array_filter($records, function($a) use($assid) {
                        return $a->assignment != $assid;
                    });
                    continue;
                }
            }
            set_config('force_global_credentials', '0', 'assignsubmission_maharaws');
        }
        foreach ($records as $record) {
            $dataitem = $data[$record->id];
            $todb = new \stdClass();
            $todb->assignment   = $record->assignment;
            $todb->submission   = $record->submission;
            $todb->viewid       = $record->viewid;
            $todb->viewurl      = '';
            $todb->viewtitle    = $record->viewtitle;
            $todb->iscollection = $record->iscollection;
            $status = $record->viewstatus;
            if ($status == assign_submission_mahara::STATUS_RELEASED ||
                $status == assign_submission_mahara::STATUS_SELECTED ||
                $status == assign_submission_mahara::STATUS_SUBMITTED) {
                $todb->viewstatus = $status;
            }
            if (!$todb->iscollection) {
                if ($todb->viewstatus == assign_submission_mahara::STATUS_SELECTED) {
                    $urlstring = '/user/' . $dataitem['owner'] .'/'. $dataitem['urlid'];
                    $todb->viewurl = $dataitem['endpointurl'] . $urlstring;
                } else {
                    $todb->viewurl = '/view/view.php?id=' . $todb->viewid;
                }
            } else {
                switch ($dataitem['complexity']) {
                    case 0:
                        //simple collection
                        if ($todb->viewstatus == assign_submission_mahara::STATUS_SELECTED) {
                            $urlstring = '/view/view.php?id=' . $dataitem['viewid'];
                            $todb->viewurl = $dataitem['endpointurl'] . $urlstring;
                        } else {
                            $todb->viewurl = '/view/view.php?id=' . $dataitem['viewid'];
                        }
                        break;
                    case 1:
                        //progresscompletion
                        if ($todb->viewstatus == assign_submission_mahara::STATUS_SELECTED) {
                            $urlstring = '/collection/progresscompletion.php?id=' . $todb->viewid;
                            $todb->viewurl = $dataitem['endpointurl'] . $urlstring;
                        } else {
                            $todb->viewurl = '/collection/progresscompletion.php?id=' . $todb->viewid;
                        }
                        break;
                    case 2:
                        //smartevidence
                        if ($todb->viewstatus == assign_submission_mahara::STATUS_SELECTED) {
                            $urlstring = '/module/framework/matrix.php?id=' . $todb->viewid;
                            $todb->viewurl = $dataitem['endpointurl'] . $urlstring;
                        } else {
                            $todb->viewurl = '/module/framework/matrix.php?id=' . $todb->viewid;
                        }
                        break;
                }
            }
            $currid = $DB->insert_record('assignsubmission_maharaws', $todb);
            mtrace($currid);
        }
        // Mahara savepoint reached.
        upgrade_plugin_savepoint(true, 2024081601, 'assignsubmission', 'maharaws');
    }
    */

    return true;
}
