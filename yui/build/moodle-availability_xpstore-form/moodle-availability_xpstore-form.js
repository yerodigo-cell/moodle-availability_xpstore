YUI.add('moodle-availability_xpstore-form', function (Y, NAME) {

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
 * JavaScript for availability_xpstore.
 *
 * @module moodle-availability_xpstore-form
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* eslint-disable camelcase */


    /**
     * Provides the form interface for the availability_xpstore plugin.
     *
     * @class M.availability_xpstore.form
     * @extends M.core_availability.plugin
     */
    M.availability_xpstore = M.availability_xpstore || {};

    M.availability_xpstore.form = Y.Object(M.core_availability.plugin);

    /**
     * Rewards array.
     *
     * @property rewards
     * @type Array
     */
    M.availability_xpstore.form.rewards = null;

    /**
     * Initializes this plugin.
     *
     * @method initInner
     * @param {Array} rewards Array of reward objects.
     */
    M.availability_xpstore.form.initInner = function(rewards) {
        this.rewards = rewards;
    };

    /**
     * Gets the form node.
     *
     * @method getNode
     * @return {Y.Node} Form node.
     */
    M.availability_xpstore.form.getNode = function(json) {
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

        var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

        // Set initial value if specified.
        if (json.productid !== undefined &&
                node.one('select[name=productid] > option[value=' + json.productid + ']')) {
            node.one('select[name=productid]').set('value', '' + json.productid);
        }

        if (!M.availability_xpstore.form.addedEvents) {
            M.availability_xpstore.form.addedEvents = true;
            var root = Y.one('.availability-field');
            if (root) {
                root.delegate('change', function() {
                    M.core_availability.form.update();
                }, '.availability_xpstore select');
            }
        }

        return node;
    };

    /**
     * Fills the value from the form.
     *
     * @method fillValue
     * @param {Object} value Object to fill.
     * @param {Y.Node} node Form node.
     */
    M.availability_xpstore.form.fillValue = function(value, node) {
        value.productid = node.one('select[name=productid]').get('value');
    };

    /**
     * Fills the form from the value.
     *
     * @method fillErrors
     * @param {Array} errors Array of errors.
     * @param {Y.Node} node Form node.
     */
    M.availability_xpstore.form.fillErrors = function(errors, node) {
        var value = {};
        this.fillValue(value, node);
        if (!value.productid) {
            errors.push('availability_xpstore:missing');
        }
    };


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
