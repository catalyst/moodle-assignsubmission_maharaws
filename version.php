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
 * This file contains the version information for Mahara submission plugin
 *
 * @package    assignsubmission_maharaws
 * @copyright  2020 Catalyst IT
 * @copyright  2012 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025032100;
$plugin->release   = 2025032100;
$plugin->requires  = 2022090200; // Requires 4.1 or higher
$plugin->component = 'assignsubmission_maharaws';
$plugin->supported = [401, 405];
$plugin->maturity  = MATURITY_STABLE;
