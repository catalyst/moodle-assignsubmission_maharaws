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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file defines the admin settings for this plugin
 *
 * @package assignsubmission_file
 * @copyright 2020 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/submission/maharaws/lib.php');

$settings->add(
        new admin_setting_configcheckbox('assignsubmission_maharaws/default',
                new lang_string('defaulton', 'assignsubmission_maharaws'),
                new lang_string('defaulton_help', 'assignsubmission_maharaws'),
                0
        )
);

$settings->add(
    new admin_setting_configselect(
        'assignsubmission_maharaws/lock',
        new lang_string(
                'defaultlockpages',
                'assignsubmission_maharaws',
                new lang_string('lockpages', 'assignsubmission_maharaws')
        ),
        new lang_string(
                'defaultlockpages_help',
                'assignsubmission_maharaws',
                new lang_string('lockpages', 'assignsubmission_maharaws')
        ),
        ASSIGNSUBMISSION_MAHARAWS_SETTING_UNLOCK,
        array(ASSIGNSUBMISSION_MAHARAWS_SETTING_DONTLOCK => new lang_string('no'),
                ASSIGNSUBMISSION_MAHARAWS_SETTING_KEEPLOCKED => new lang_string('yeskeeplocked', 'assignsubmission_maharaws'),
                ASSIGNSUBMISSION_MAHARAWS_SETTING_UNLOCK => new lang_string('yesunlock', 'assignsubmission_maharaws'))
    )
);

$settings->add(
    new admin_setting_configcheckbox('assignsubmission_maharaws/force_global_credentials',
        new lang_string('forceglobalcredentials', 'assignsubmission_maharaws'),
        new lang_string('forceglobalcredentials_help', 'assignsubmission_maharaws'),
        0
    )
);

$settings->add(
    new admin_setting_configtext('assignsubmission_maharaws/url',
        new lang_string('url', 'assignsubmission_maharaws'),
        new lang_string('url_help', 'assignsubmission_maharaws'),
        "",
        PARAM_URL
    )
);

$settings->add(
    new admin_setting_configtext('assignsubmission_maharaws/key',
        new lang_string('key', 'assignsubmission_maharaws'),
        new lang_string('key_help', 'assignsubmission_maharaws'),
        "",
        PARAM_ALPHANUM
    )
);

$settings->add(
        new admin_setting_configtext('assignsubmission_maharaws/secret',
        new lang_string('secret', 'assignsubmission_maharaws'),
        new lang_string('secret_help', 'assignsubmission_maharaws'),
        "",
        PARAM_ALPHANUM
        )
);
