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

// jshint unused: true, undef:true

define(['jquery', 'core/log'], function($, log) {

    var vmoodle = {

        vmoodlestrings: '',

        init: function() {
            $('.vmoodle-hide-show-image').bind('click', this.switch_panel);

            // File area remote reader ajax binding.
            $('#mform12 select[name="platform"]').bind('change', this.remote_filearealist);

            log.debug("AMD Vmoodle initialisation ");
        },

        switch_panel: function() {

            var that = $(this);

            var handleid = that.attr('id');
            var panelid = that.attr('id').replace('id-showctl-', '');
            var handlesrc = that.attr('src');

            // Make them all collapsed.
            handlesrc = handlesrc.replace('collapsed', 'expanded');
            $('.vmoodle-hide-show-image').attr('src', handlesrc);

            // Reopen the clicked item.
            handlesrc = handlesrc.replace('collapsed', 'expanded');
            $('#' + handleid).attr('src', handlesrc);

            // Close them all.
            $('.vmoodlecmd-panel').addClass('vmoodle-hidden');

            // Reopen the clicked item.
            $('#id-vmoodlecmd-panel-' + panelid).removeClass('vmoodle-hidden');
        },

        filtercapabilitytable: function(filterinput) {
            $('.capabilityrow').css('display', 'table-row');
            if (filterinput.value !== '') {
                $('.capabilityrow').css('display', 'none');
                $('.capabilityrow[id*=\'' + filterinput.value + '\']').css('display', 'table-row');
            }
        },

        remote_filearealist: function() {
            var that = $(this);
            var targeturl = that.val();
            var targetdiv = $('#fitem_id_fileareaid .felement.fselect');

            var url = targeturl + '/local/vmoodle/plugins/generic/ajax/get_system_fileareas.php';

            // Reset the filearea choice list with target's local set.
            $.get(url, function(data) {
                targetdiv.html(data);
            }, 'html');
        }
    };

    return vmoodle;

});