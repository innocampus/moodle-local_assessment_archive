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
 * This script schedules archiving tasks with a specified interval between them.
 *
 * @package    local_assessment_archive
 * @subpackage cli
 * @copyright  2022 Lars Bonczek, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_assessment_archive\export;
use local_assessment_archive\helper;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot.'/course/lib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'never-archived' => false,
    'interval' => 60,
    'dry-run' => false,
], [
    'h' => 'help',
    'n' => 'never-archived',
    'i' => 'interval',
    'd' => 'dry-run'
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "This script schedules archiving tasks with a specified interval between them.

        Options:
        -h, --help              Print out this help.
        -n, --never-archived    Only archive activities that have never been archived before.
        -i, --interval          Specifies the interval between archiving tasks in seconds. Default 60.
        -d, --dry-run           Don't schedule the archiving tasks, just display the number.

        Example:
        \$ sudo -u www-data /usr/bin/php local/assessment_archive/cli/schedule_archiving.php --never-archived --interval=60
        ";

    echo $help;
    die;
}

$interval = $options['interval'];
$neverarchived = $options['never-archived'];
$dryrun = $options['dry-run'];

cli_heading("Scheduling archiving tasks with an interval of $interval seconds...");

if ($neverarchived) {
    cli_writeln("Skipping activities that have already been archived.");
}

$cms = helper::get_cmids_to_archive($neverarchived);
$count = sizeof($cms);
if ($dryrun) {
    cli_writeln("Found $count archiving task(s) to schedule. Tasks that are already scheduled will be skipped.");
    cli_writeln("Repeat this command without the --dry-run option to schedule the tasks.");
    die;
}
if (!$cms) {
    cli_writeln("No activities to archive.");
    die;
}

$runtime = time();
$scheduled = 0;
$skipped = 0;
foreach ($cms as $cmid) {
    if (helper::schedule_archiving($cmid, $runtime, export::REASON_ADMIN_SCRIPT)) {
        $scheduled++;
        $runtime += $interval;
    } else {
        $skipped++;
    }
}

cli_writeln("Scheduled $scheduled archiving task(s). Skipped $skipped already scheduled task(s).");
if ($scheduled > 0) {
    $duration = gmdate("H:i:s", $interval * ($scheduled - 1));
    cli_writeln("Final task scheduled to run in $duration.");
}