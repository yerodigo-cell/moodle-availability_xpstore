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
 * Condition (core logic).
 *
 * @package    availability_xpstore
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var string The target product identifier, e.g. "U123" or "G456" */
    protected $productid;

    /** @var array Request-scoped cache of purchased product keys indexed by userid. */
    protected static $userpurchasescache = [];

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
     * Preloads all purchases for the user once per request so courses with multiple
     * restricted activities execute only 1 SQL query instead of N queries on every page load.
     *
     * @param bool $not True if the condition is inverted.
     * @param \core_availability\info $info Information about the item.
     * @param bool $notnecessary True if this condition is not necessary.
     * @param int $userid User ID.
     * @return bool True if available.
     */
    public function is_available($not, \core_availability\info $info, $notnecessary, $userid) {
        global $DB;

        if (!$this->productid) {
            return false;
        }

        $userid = (int)$userid;
        if ($userid <= 0) {
            return false;
        }

        // Preload all purchases for this user in 1 query using pure PHP concatenation (100% DB-agnostic).
        if (!array_key_exists($userid, self::$userpurchasescache) || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            self::$userpurchasescache[$userid] = [];
            $records = $DB->get_records('local_xpstore_gastos', ['userid' => $userid], '', 'id, itemtype, itemid');
            foreach ($records as $rec) {
                $k = strtoupper((string)$rec->itemtype) . (int)$rec->itemid;
                self::$userpurchasescache[$userid][$k] = true;
            }
        }

        $type = strtoupper(substr($this->productid, 0, 1));
        $itemid = (int)substr($this->productid, 1);
        $haspurchased = !empty(self::$userpurchasescache[$userid][$type . $itemid]);

        return $not ? !$haspurchased : $haspurchased;
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
     * Updates this condition after restore, returning true if anything changed.
     *
     * @param string $restoreid Restore ID
     * @param int $courseid Course ID
     * @param \base_logger $logger Logger
     * @param string $name Name of item being restored
     * @return bool True if updated
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        global $DB;

        $res = false;
        if (!$this->productid) {
            return $res;
        }

        $type = substr($this->productid, 0, 1);
        $itemid = (int)substr($this->productid, 1);

        if ($itemid <= 0) {
            return $res;
        }

        // Determine if it's a grade item or course module.
        $mappingname = ($type === 'M') ? 'grade_item' : 'course_module';

        $rec = \restore_dbops::get_backup_ids_record($restoreid, $mappingname, $itemid);
        if ($rec && $rec->newitemid) {
            $newproductid = $type . $rec->newitemid;
            if ($this->productid !== $newproductid) {
                $this->productid = $newproductid;
                $res = true;
            }
        } else {
            // Check if the item already exists in the current course (e.g., duplicated in the same course).
            if ($mappingname === 'course_module') {
                if ($DB->record_exists('course_modules', ['id' => $itemid, 'course' => $courseid])) {
                    return $res;
                }
            } else {
                if ($DB->record_exists('grade_items', ['id' => $itemid, 'courseid' => $courseid])) {
                    return $res;
                }
            }
            // Could not map it.
            $logger->process(
                'Restored item (' . $name . ') has xpstore condition on ' . $mappingname . ' that was not restored',
                \backup::LOG_WARNING
            );
        }

        return $res;
    }

    /**
     * Updates the dependency ID when a module or grade item is modified/deleted.
     *
     * @param string $table Table name
     * @param int $oldid Old ID
     * @param int $newid New ID
     * @return bool True if changed
     */
    public function update_dependency_id($table, $oldid, $newid) {
        if (!$this->productid) {
            return false;
        }
        $type = substr($this->productid, 0, 1);
        $itemid = (int)substr($this->productid, 1);

        if ($table === 'course_modules' && $type !== 'M' && $itemid === (int)$oldid) {
            $this->productid = $type . $newid;
            return true;
        }
        if ($table === 'grade_items' && $type === 'M' && $itemid === (int)$oldid) {
            $this->productid = $type . $newid;
            return true;
        }
        return false;
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
                    return str_replace(['[[XPCOLON]]', '[[XPCOMMA]]'], [':', ','], trim($parts[2]));
                }
            }
        }

        return get_string('missing', 'availability_xpstore');
    }
}
