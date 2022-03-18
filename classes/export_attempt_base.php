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
 * Export an attempt base class.
 *
 * @package    local_assessment_export
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessment_export;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/filelib.php');

/**
 * Export an attempt base class.
 *
 * @copyright  2022 Martin Gauk, innoCampus, TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class export_attempt_base {
    const REASON_ATTEMPT_FINISHED = 1;
    const REASON_ATTEMPT_GRADED = 2;
    const REASON_DOWNLOAD = 3;

    /** @var \zip_archive */
    protected $zipfile;

    /** @var string[] list of errors during the export */
    private $errorlog = [];

    /** @var array already saved moodle files in archive (file content hash => true) */
    private $storedfiles = [];

    /** @var array already saved javascript files in archive (file path => path within archive) */
    private $jsfiles = [];

    /** @var array already saved images in archive (file path within archive => true) */
    private $images = [];

    /**
     * Constructor.
     *
     * @param \stdClass $coursemodule
     * @param \stdClass $user
     * @param string $file a new zip archive will be created at this path
     * @param int $reason why this attempt is being exported (one of the self::REASON_* constonts)
     */
    public function __construct(\stdClass $coursemodule, \stdClass $user, string $file, int $reason = self::REASON_DOWNLOAD) {
        $this->coursemodule = $coursemodule;
        $this->user = $user;
        $this->zipfile = new \zip_archive();
        if (!$this->zipfile->open($file)) { //get_request_storage_directory?
            throw new \moodle_exception('zip_create_error', 'local_assessment_export');
        }
    }

    public function save() {
        $this->write_metadata_file();
        $this->write_error_log();
        $this->zipfile->close();
    }

    abstract public function process_attempt();

    /**
     * Add an error message to the error log.
     *
     * @param $message
     * @return void
     */
    public function add_error($message) {
        $this->errorlog[] = $message;
    }

    /**
     * Save a stored file to this zip archive.
     *
     * The files are always stored once using their content hashes.
     *
     * @param \stored_file $file
     * @return string|bool location of file in archive (false in case of an error)
     */
    public function add_stored_file(\stored_file $file) {
        $hash = $file->get_contenthash();
        $path = 'files/' . $hash; // TODO store with file ending?

        if (isset($this->storedfiles[$hash])) {
            return $path;
        }

        $this->storedfiles[$hash] = true;

        try {
            $file->archive_file($this->zipfile, $path);
        } catch (\moodle_exception $e) {
            $message = get_string('file_error', 'local_assessment_export', [
                'file' => $file->get_filepath() . $file->get_filename(),
                'hash' => $hash,
                'message' => $e->getMessage(),
            ]);
            $this->add_error($message);
            return false;
        }

        return $path;
    }

    /**
     * Add a stored by passing the parameters of the pathname hash.
     *
     * The first arguments are the same as \file_storage::get_pathname_hash expects.
     *
     * @param $contextid
     * @param $component
     * @param $filearea
     * @param $itemid
     * @param $filepath
     * @param $filename
     * @param $url string original pluginfile.php url
     * @return string|bool location of file in archive (false in case of an error)
     */
    public function add_stored_file_by_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename, $url) {
        $fs = get_file_storage();
        $file = $fs->get_file_by_hash($fs->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename));
        if (!$file || $file->is_directory()) {
            $message = get_string('file_not_found', 'local_assessment_export', $url);
            $this->add_error($message);
            return false;
        }
        return $this->add_stored_file($file);
    }

    /**
     * Add error log file to archive file.
     */
    protected function write_error_log() {
        if ($this->errorlog) {
            $errors = implode("\n", $this->errorlog);
            $this->zipfile->add_file_from_string('errors.txt', $errors);
        }
    }

    /**
     * Get metadata that is included in the zip file.
     *
     * A child class may overwrite this method in order to add more metadata.
     *
     * @return string[] key => value strings
     */
    public function get_metadata() : array {
        return ['bla' => 'blub']; // TODO
    }

    /**
     * Get metadata as a json-encoded string.
     *
     * @return string
     */
    public function get_metadata_json() : string {
        return \json_encode($this->get_metadata());
    }

    /**
     * Write metadata file to zip archive.
     */
    public function write_metadata_file() {
        $this->zipfile->add_file_from_string('metadata.json', $this->get_metadata_json());
    }

    /**
     * Write CSS file to zip archive.
     *
     * @return string path to CSS file within the archive
     */
    protected function write_css_file() : string {
        global $PAGE;

        $PAGE->theme->force_svg_use(true);
        if (!$csscontent = $PAGE->theme->get_css_cached_content()) {
            $csscontent = $PAGE->theme->get_css_content();
        }

        // Add FontAwesome that is used by all Moodle themes.
        $fontawesomepath = $PAGE->theme->resolve_font_location('fontawesome-webfont.woff2', 'core');
        if ($fontawesomepath) {
            $this->zipfile->add_file_from_pathname('styles/fontawesome-webfont.woff2', $fontawesomepath);
            $csscontent .= '
@charset "UTF-8";
@font-face {
    font-family: "FontAwesome";
    src: url(fontawesome-webfont.woff2) format("woff2");
    font-weight: 400;
    font-style: normal
}';
        }

        $csscontent .= $this->get_additional_css();
        $path = 'styles/styles.css';
        $this->zipfile->add_file_from_string($path, $csscontent);

        return $path;
    }

    /**
     * Add some custom CSS to the CSS file.
     *
     * This might be used by a child class for simple customization.
     *
     * @return string CSS
     */
    protected function get_additional_css() : string {
        return '';
    }

    /**
     * Add the javascript files referenced in $text to the archive and replace the occurrences.
     *
     * This function does not include AMD modules.
     *
     * @param string $text
     */
    protected function replace_javascript(string &$text) {
        global $CFG;

        // Replace URLs to plain javascript files.
        $urlbase = preg_quote($CFG->httpswwwroot .'/lib/javascript.php/');
        $regex = '@' . $urlbase . '([/0-9A-Za-z\._-]*)@';

        $text = preg_replace_callback(
            $regex,
            function ($matches) use ($CFG) {
                list($rev, $jspath) = explode('/', $matches[1], 2);
                if (substr($jspath, -3) !== '.js') {
                    $jspath .= '.js';
                }

                $file = realpath($CFG->dirroot . DIRECTORY_SEPARATOR . $jspath);
                if ($file === false || strpos($file, $CFG->dirroot . DIRECTORY_SEPARATOR) !== 0 ||
                        substr($file, -3) !== '.js') {
                    $this->add_error(get_string('js_not_found', 'local_assessment_export', $matches[0]));
                    return $matches[0];
                }

                if (isset($this->jsfiles[$jspath])) {
                    return $this->jsfiles[$jspath];
                }

                $archivepath = 'js/fs/' . $jspath;

                // RequireJS will search for jquery within the baseURL directory.
                $this->jsfiles[$jspath] = (strpos($jspath, 'jquery')) ? 'fs/' . substr($jspath, 0, -3) : $archivepath;
                $this->zipfile->add_file_from_pathname($archivepath, $file);
                return $this->jsfiles[$jspath];
            },
            $text
        );

        if (!is_string($text)) {
            throw new \moodle_exception('preg_replace_javascript_error', 'local_assessment_export');
        }

        // Replace baseURL for RequireJS.
        $oldbase = preg_quote($CFG->httpswwwroot .'/lib/requirejs.php/');
        $oldbaseregex = '@' . $oldbase . '[0-9-]*@';
	$text = preg_replace($oldbaseregex, 'js', $text);
        if (!is_string($text)) {
            throw new \moodle_exception('preg_replace_javascript_error', 'local_assessment_export');
        }
    }

    /**
     * Add Javascript AMD modules to archive.
     *
     * @param string $filter regex that determines whether the module will be included
     */
    protected function add_amd_modules(string $filter) {
        // Copied from /lib/requirejs.php mostly.
        $jsfiles = \core_requirejs::find_all_amd_modules(false, true);
        $content = '';
        foreach ($jsfiles as $modulename => $jsfile) {
            if (preg_match($filter, $modulename) !== 1) {
                continue;
            }

            $js = file_get_contents($jsfile);
            if ($js === false) {
                error_log('Failed to load JavaScript file ' . $jsfile);
                $js = "/* Failed to load JavaScript file {$jsfile}. */\n";
                $content = $js . $content;
                continue;
            }
            // Remove source map link.
            $js = preg_replace('~//# sourceMappingURL.*$~s', '', $js);
            $js = rtrim($js);
            $js .= "\n";

            if (preg_match('/define\(\s*(\[|function)/', $js)) {
                // If the JavaScript module has been defined without specifying a name then we'll
                // add the Moodle module name now.
                $replace = 'define(\'' . $modulename . '\', ';
                $search = 'define(';
                // Replace only the first occurrence.
                $js = implode($replace, explode($search, $js, 2));
            }

            $content .= $js;
        }

        $this->zipfile->add_file_from_string('js/core/first.js', $content);
    }

    /**
     * Add the images/icons referenced in $text to the archive and replace the occurrences.
     *
     * @param string $text
     */
    protected function replace_images(string &$text) {
        global $CFG, $PAGE;

        // Replace URLs to images.
        $urlbase = preg_quote($CFG->httpswwwroot .'/theme/image.php/');
        $regex = '@' . $urlbase . '([/0-9A-Za-z\._-]*)@';

        $text = preg_replace_callback(
            $regex,
            function ($matches) use ($CFG, $PAGE) {
                list($themename, $component, $rev, $image) = explode('/', $matches[1], 4);
                $archivepath = 'images/' . $component . '/' . $image;
                if (isset($this->images[$archivepath])) {
                    return $archivepath;
                }

                $imagefile = $PAGE->theme->resolve_image_location($image, $component, true);
                if (empty($imagefile) or !is_readable($imagefile)) {
                    $this->add_error(get_string('image_not_found', 'local_assessment_export', $matches[0]));
                    return $matches[0];
                }

                $this->images[$archivepath] = true;
                $this->zipfile->add_file_from_pathname($archivepath, $imagefile);
                return $archivepath;
            },
            $text
        );

        if (!is_string($text)) {
            throw new \moodle_exception('preg_replace_images_error', 'local_assessment_export');
        }
    }
}
