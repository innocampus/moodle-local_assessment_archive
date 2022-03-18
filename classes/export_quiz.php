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
 * Export a quiz attempt class.
 *
 * @package    local_assessment_export
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessment_export;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Export a quiz attempt class.
 *
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_quiz extends export_attempt_base {
    /** @var \quiz_attempt */
    private $attemptobj;

    public function __construct(\quiz_attempt $attemptobj, string $file, int $reason = self::REASON_DOWNLOAD) {
        $this->attemptobj = $attemptobj;
        parent::__construct($attemptobj->get_cm(), \core_user::get_user($attemptobj->get_userid()), $file, $reason);
    }

    public function process_attempt() {
        global $DB, $PAGE;

        $PAGE->set_context(\context_module::instance($this->coursemodule->id));

        // Copied from /mod/quiz/review.php mostly.
        $attemptobj = $this->attemptobj;
        $attemptobj->preload_all_attempt_step_users();
        $page = $attemptobj->force_page_number_into_range(0);
        $showall = true;

        $options = \mod_quiz_display_options::make_from_quiz($attemptobj->get_quiz(), \mod_quiz_display_options::AFTER_CLOSE);

        // Copied from function quiz_get_review_options.
        $options->readonly = true;
        $options->flags = \question_display_options::VISIBLE;
        $options->attempt = \question_display_options::VISIBLE;
        $options->correctness = \question_display_options::VISIBLE;
        $options->marks = \question_display_options::MARK_AND_MAX;
        $options->feedback = \question_display_options::VISIBLE;
        $options->numpartscorrect = \question_display_options::VISIBLE;
        $options->manualcomment = \question_display_options::VISIBLE;
        $options->manualcommentlink = null; // Seems to have no effect (link is still displayed).
        $options->generalfeedback = \question_display_options::VISIBLE;
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->overallfeedback = \question_display_options::VISIBLE;
        $options->history = \question_display_options::VISIBLE;
        $options->userinfoinhistory = 0; // Always set a link to user's profile.

        $headtags = $attemptobj->get_html_head_contributions($page, true);
        $slots = $attemptobj->get_slots();

        $renderer = $PAGE->get_renderer('mod_quiz');
        $corerenderer = $PAGE->get_renderer('core');

        $quizcontent = $renderer->review_form($page, $showall, $options,
            $renderer->questions($attemptobj, true, $slots, $page, $showall, $options),
            $attemptobj);

        $this->replace_files($quizcontent);

        $data = new \stdClass();
        $data->cssfile = $this->write_css_file();
        $data->javascripthead = $PAGE->requires->get_head_code($PAGE, $corerenderer);
        $data->javascriptbodystart = $PAGE->requires->get_top_of_body_code($corerenderer);
        $data->javascriptbodyend = $PAGE->requires->get_end_code();
        $data->quizcontent = $quizcontent;

        $html = $corerenderer->render_from_template('local_assessment_export/quiz_page', $data);
        $this->replace_images($html);
        $this->replace_javascript($html);
        $this->add_amd_modules('@(^core)|quiz|qtype|qbehaviour@');
        $this->zipfile->add_file_from_string('attempt.html', $html);
    }

    /**
     * Add some custom CSS to the CSS file.
     *
     * Hide the link to the comment edit page as it makes no sense within the exported attempt.
     *
     * @return string CSS
     */
    protected function get_additional_css() : string {
        return <<<EOL
.commentlink {
	display: none;
}
EOL;
    }

    /**
     * Add the Moodle stored files referenced in $text to the archive and replace the occurrences.
     *
     * Only files that are included in the questions are handled.
     *
     * @param string $text
     */
    private function replace_files(string &$text) {
        global $CFG;

        $urlbase = preg_quote($CFG->httpswwwroot .'/pluginfile.php/');
        $regex = '@' . $urlbase . '([/0-9A-Za-z-\.\@:%_\+~#=]*)@';

        $text = preg_replace_callback(
            $regex,
            function ($matches) {
                $args = explode('/', $matches[1]);
                $contextid = (int) array_shift($args);
                $component = array_shift($args);

                if ($component !== 'question' && substr($component, 0, 6) !== 'qtype_') {
                    // We cannot handle other files here (it is too complicated to deal with other file areas as there is no
                    // standard way in Moodle how the path is structured and in order to check file access).
                    $this->add_error(get_string('unsupported_file_area', 'local_assessment_export', $matches[0]));
                    return $matches[0];
                }

                $filearea = array_shift($args);
                $attemptusageid = (int) array_shift($args); // TODO check if file really belongs to this attempt
                $slot = (int) array_shift($args);
                $relativepath = implode('/', $args);

                $location = $this->add_stored_file_by_pathname_hash($contextid, $component, $filearea, $relativepath,
                    '', '', $matches[0]);
                if (!$location) {
                    return $matches[0];
                }
                return $location;
            },
            $text
        );

        if (!is_string($text)) {
            throw new \moodle_exception('preg_replace_files_error', 'local_assessment_export');
        }
    }
}
