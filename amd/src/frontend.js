/**
 * Javascript for availability_xpstore.
 *
 * @module     availability_xpstore/frontend
 * @package    availability_xpstore
 * @copyright  2026 Yeison Díaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core_availability/form', 'core_availability/base'], function($, form, base) {
    'use strict';

    /**
     * XP Store condition.
     *
     * @class
     * @extends base
     */
    var xpstore = function(condition, pluginType) {
        this.rewards = condition;
        this.pluginType = pluginType;
    };

    xpstore.prototype = new base();

    /**
     * Rewards array.
     * @type {Array}
     */
    xpstore.prototype.rewards = null;

    /**
     * Initializes the plugin.
     *
     * @param {Array} rewards Array of reward objects {id, name}.
     */
    xpstore.init = function(rewards) {
        form.plugins.xpstore = new xpstore(rewards, 'xpstore');
    };

    /**
     * Gets the form node.
     *
     * @return {Y.Node} Form node.
     */
    xpstore.prototype.getNode = function() {
        var str = M.util.get_string;
        var html = '<label><span class="pr-1">' + str('title', 'availability_xpstore') + '</span> ' +
                   '<span class="availability-group">' +
                   '<select name="productid" class="custom-select">';

        var rewards = this.rewards;
        if (rewards && rewards.length > 0) {
            for (var i = 0; i < rewards.length; i++) {
                var reward = rewards[i];
                html += '<option value="' + reward.id + '">' + reward.name + '</option>';
            }
        } else {
            html += '<option value="">' + str('missing', 'availability_xpstore') + '</option>';
        }

        html += '</select></span></label>';

        // Use jQuery to create the node, but Moodle's availability expects a YUI node.
        // Moodle 3.11+ form base handles jQuery and YUI interop. 
        // We return a YUI node using Y.Node.create.
        return Y.Node.create(html);
    };

    /**
     * Fills the value from the form.
     *
     * @param {Object} value Object to fill.
     * @param {Y.Node} node Form node.
     */
    xpstore.prototype.fillValue = function(value, node) {
        value.productid = node.one('select[name=productid]').get('value');
    };

    /**
     * Fills the form from the value.
     *
     * @param {Object} value Object containing data.
     * @param {Y.Node} node Form node.
     */
    xpstore.prototype.fillErrors = function(errors, node) {
        var value = {};
        this.fillValue(value, node);
        if (!value.productid) {
            errors.push('availability_xpstore:missing');
        }
    };

    return xpstore;
});
