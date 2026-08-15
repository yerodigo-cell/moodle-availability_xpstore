/**
 * JavaScript for availability_xpstore.
 *
 * @module moodle-availability_xpstore-form
 */
YUI.add('moodle-availability_xpstore-form', function(Y, NAME) {

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
                var selected = '';
                if (json.productid !== undefined && json.productid == reward.id) {
                    selected = ' selected="selected"';
                }
                html += '<option value="' + reward.id + '"' + selected + '>' + reward.name + '</option>';
            }
        } else {
            html += '<option value="">' + str('missing', 'availability_xpstore') + '</option>';
        }

        html += '</select></span></label>';

        return Y.Node.create(html);
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

}, '@VERSION@', {
    requires: ['base', 'node', 'event', 'moodle-core_availability-form']
});
