<?php
define('CLI_SCRIPT', true);

// Assume this file is located in moodle/mod/assign/submission/maharaws/classes/cli/
require(__DIR__.'/../../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');
require_once($CFG->dirroot.'/mod/assign/submissionplugin.php');
require_once($CFG->dirroot.'/mod/assign/submission/maharaws/locallib.php');

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
mtrace('end');
