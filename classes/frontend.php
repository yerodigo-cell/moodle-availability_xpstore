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

namespace availability_xpstore;

/**
 * Frontend classes for availability_xpstore.
 *
 * @package    availability_xpstore
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Decides whether this plugin should be available in a given course.
     * Avoids loading JS init params when the course has no configured store catalog.
     *
     * @param \stdClass $course Course object.
     * @param \cm_info|null $cm Course module object.
     * @param \section_info|null $section Section object.
     * @return bool True if there is at least one catalog item in the course.
     */
    protected function allow_add($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        $configraw = get_config('local_xpstore', 'catalog_course_' . $course->id);
        return !empty($configraw) && trim((string)$configraw) !== '';
    }

    /**
     * Defines Javascript strings required by the plugin.
     *
     * @return array Array of strings.
     */
    protected function get_javascript_strings() {
        return [
            'title',
            'label_reward',
            'missing',
        ];
    }

    /**
     * Defines Javascript initialization parameters.
     *
     * @param \stdClass $course Course object.
     * @param \cm_info|null $cm Course module object.
     * @param \section_info|null $section Section object.
     * @return array Array of init params.
     */
    protected function get_javascript_init_params($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        global $DB;

        $rewards = [];

        // Fetch products configured in this course (cached in RAM by Moodle).
        $configraw = get_config('local_xpstore', 'catalog_course_' . $course->id) ?: '';
        if (trim($configraw) === '') {
            return [$rewards];
        }

        $items = array_filter(explode(',', $configraw));

        // Pass $course object directly to avoid an extra DB query to {course}.
        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();
        $manualgrades = null;

        foreach ($items as $item) {
            $parts = explode(':', trim($item));
            if (!empty($parts[0])) {
                $productid = trim($parts[0]);
                $customname = isset($parts[2])
                    ? str_replace(['[[XPCOLON]]', '[[XPCOMMA]]'], [':', ','], trim($parts[2]))
                    : '';

                // If there is no custom name, resolve the activity or grade item name from memory.
                if ($customname === '') {
                    $tipochar = strtoupper(substr($productid, 0, 1));
                    $cid = (int)substr($productid, 1);

                    if ($tipochar === 'M' || $tipochar === 'C') {
                        if ($manualgrades === null) {
                            $manualgrades = $DB->get_records_menu(
                                'grade_items',
                                ['courseid' => $course->id],
                                '',
                                'id, itemname'
                            );
                        }
                        $customname = !empty($manualgrades[$cid])
                            ? $manualgrades[$cid]
                            : (($tipochar === 'C') ? get_string('coursetotal', 'grades') : '');
                    } else if (isset($cms[$cid])) {
                        $customname = $cms[$cid]->name;
                    }

                    if (empty($customname)) {
                        $customname = $productid;
                    }
                }

                $rewards[] = (object)[
                    'id' => $productid,
                    'name' => $customname,
                ];
            }
        }

        return [$rewards];
    }
}
