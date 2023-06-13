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

    var vmoodle_hostlist = {

        init: function() {
            $('.mnet-key-query').bind('hover', this.query_key_state);

            log.debug("AMD Vmoodle Hostlist initialised ");
        },

        query_key_state: function(e) {
            var that  = $(this);

            var mnetid = that.attr('data-mnetid').val();

            var url = cfg.wwwroot + "/local/vmoodle/ajax/check_mnet_key.php?";
            url += 'mnetid='.mnetid;

            var icon = $('img', this);

            $.get(url, function(data) {
                icon.src = data.iconsrc;
            }, 'json');
        }

    };

    return vmoodle_hostlist;

});