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

defined('MOODLE_INTERNAL') || die();

/**
 * Frontend classes for availability_xpstore.
 *
 * @package    availability_xpstore
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Defines Javascript strings required by the plugin.
     *
     * @return array Array of strings.
     */
    protected function get_javascript_strings() {
        return [
            'title',
            'label_reward',
        ];
    }

    /**
     * Defines Javascript initialization parameters.
     *
     * @param \stdClass $course Course object.
     * @param \cm_info $cm Course module object.
     * @param \section_info $section Section object.
     * @return array Array of init params.
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        $rewards = [];
        
        // Fetch products configured in this course.
        $configraw = get_config('local_xpstore', 'catalog_course_' . $course->id) ?: '';
        $items = array_filter(explode(',', $configraw));

        // Get module info for resolving real activity names.
        $modinfo = get_fast_modinfo($course->id);
        $cms = $modinfo->get_cms();

        foreach ($items as $item) {
            $parts = explode(':', trim($item));
            if (isset($parts[0])) {
                $productid = $parts[0];
                $customname = isset($parts[2]) ? trim($parts[2]) : '';
                
                // If there's no custom name, we could try to resolve the activity name.
                if (empty($customname)) {
                    $tipochar = substr($productid, 0, 1);
                    $cid = (int)substr($productid, 1);
                    if ($tipochar === 'M') {
                        global $DB;
                        $customname = $DB->get_field('grade_items', 'itemname', ['id' => $cid]);
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
