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
 * @package    local_assessment_archive
 * @author     Martin Gauk <gauk@math.tu-berlin.de>
 * @copyright  2022 innoCampus, Technische Universität Berlin <info@isis.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/backup/moodle2/restore_local_plugin.class.php');

class restore_local_assessment_archive_plugin extends restore_local_plugin {
    /**
     * Returns the paths to be handled by the plugin at module level.
     */
    protected function define_module_plugin_structure() {
        $paths = [];

        // Because of using get_recommended_name() it is able to find the
        // correct path just by using the part inside the element name (which
        // only has a /ou element).
        $elepath = $this->get_pathfor('/archive');

        $paths[] = new restore_path_element('archive', $elepath);

        return $paths;
    }

    /**
     * Process the 'archive' element.
     */
    public function process_archive($backupdata) {
        global $DB;

        $data = new stdClass();
        $data->archive = $backupdata['archive'];
        $data->cmid = $this->task->get_moduleid();

        $existingid = $DB->get_field('local_assessment_archive', 'id', ['cmid' => $data->cmid]);
        if ($existingid) {
            $data->id = $existingid;
            $DB->update_record('local_assessment_archive', $data);
        } else {
            $DB->insert_record('local_assessment_archive', $data);
        }

        // No need to record the old/new id as nothing ever refers to
        // the id of this table.
    }
}
