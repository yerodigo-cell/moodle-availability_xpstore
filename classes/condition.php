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
 * Condition (core logic).
 *
 * @package    availability_xpstore
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var string The target product identifier, e.g. "U123" or "G456" */
    protected $productid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON.
     */
    public function __construct($structure) {
        if (isset($structure->productid) && is_string($structure->productid)) {
            $this->productid = $structure->productid;
        } else {
            $this->productid = '';
        }
    }

    /**
     * Saves the condition data.
     *
     * @return \stdClass Structure of data.
     */
    public function save() {
        return (object)['type' => 'xpstore', 'productid' => $this->productid];
    }

    /**
     * Determines whether this condition is met for the given user.
     *
     * @param \core_availability\info $info Information about the item.
     * @param bool $not True if the condition is inverted.
     * @param int $userid User ID.
     * @return bool True if available.
     */
    public function is_available($not, \core_availability\info $info, $notnecessary, $userid) {
        global $DB;

        if (!$this->productid) {
            return false;
        }

        // The product id is composed of type (1 char) and cmid/itemid.
        $type = substr($this->productid, 0, 1);
        $itemid = (int)substr($this->productid, 1);

        // Check if the user has purchased this exact item.
        $haspurchased = $DB->record_exists('local_xpstore_gastos', [
            'userid' => $userid,
            'itemtype' => $type,
            'itemid' => $itemid,
        ]);

        if ($not) {
            return !$haspurchased;
        } else {
            return $haspurchased;
        }
    }

    /**
     * Obtains a string describing this condition.
     *
     * @param bool $full True for full description, false for compact.
     * @param bool $not True if inverted.
     * @param \core_availability\info $info Item info.
     * @return string Description.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        $rewardname = $this->get_reward_name($info->get_course()->id);
        
        if ($not) {
            return get_string('requires_not_reward', 'availability_xpstore', $rewardname);
        } else {
            return get_string('requires_reward', 'availability_xpstore', $rewardname);
        }
    }

    /**
     * Gets the custom string describing this condition for the current standalone setting.
     *
     * @return string
     */
    protected function get_debug_string() {
        return $this->productid;
    }

    /**
     * Helper to get the product name from local_xpstore configuration.
     *
     * @param int $courseid
     * @return string
     */
    protected function get_reward_name($courseid) {
        $configraw = get_config('local_xpstore', 'catalog_course_' . $courseid) ?: '';
        $items = array_filter(explode(',', $configraw));

        foreach ($items as $item) {
            $parts = explode(':', trim($item));
            if (isset($parts[0]) && $parts[0] === $this->productid) {
                if (!empty($parts[2])) {
                    return $parts[2];
                }
            }
        }
        return get_string('missing', 'availability_xpstore');
    }
}
