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
 * Event handler definition.
 *
 * @package    local_assessment_archive
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [

    [
        'eventname' => '\mod_assign\event\submission_created',
        'callback'  => '\local_assessment_archive\observer::assign_submission_created_updated',
    ],
    [
        'eventname' => '\mod_assign\event\submission_updated',
        'callback'  => '\local_assessment_archive\observer::assign_submission_created_updated',
    ],
    [
        'eventname' => '\mod_assign\event\submission_graded',
        'callback'  => '\local_assessment_archive\observer::assign_submission_graded',
    ],

    [
        'eventname' => '\mod_quiz\event\attempt_abandoned',
        'callback'  => '\local_assessment_archive\observer::quiz_attempt_submitted',
    ],
    [
        'eventname' => '\mod_quiz\event\attempt_regraded',
        'callback'  => '\local_assessment_archive\observer::quiz_attempt_graded',
    ],
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback'  => '\local_assessment_archive\observer::quiz_attempt_submitted',
    ],
    [
        'eventname' => '\mod_quiz\event\question_manually_graded',
        'callback'  => '\local_assessment_archive\observer::quiz_attempt_graded',
    ],

    [
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => '\local_assessment_archive\observer::course_module_deleted',
    ],

];
