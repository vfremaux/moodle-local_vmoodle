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
// eslint 

define(['jquery', 'core/config', 'core/log'], function($, cfg, log) {

    var hostform = {

        init: function() {
            $("#id_shortname").bind('change', this.syncSchema);
            $("#id_testconnection").bind('click', this.opencnxpopup);
            $("#id_testdatapath").bind('click', this.opendatapathpopup);
            $("#id_mnet").bind('click', this.switcherservices);
            log.debug("AMD VMoodle Hostform initialized");
        },

        /*
         * Pop-up testing connection with database.
         * TODO : Make this test request more network secure.
         * Solution 1 : Remove GET params, fetch parent window form elements
         * values and fire POST form
         * solution 2 : Obfuscate query string into simply crypted bundle
         */
        // jshint undef:false, unused:false
        opencnxpopup: function() {

            // Input data.
            var dbtype = $('#id_vdbtype').val();
            var dbhost = $('#id_vdbhost').val();
            var dblogin = $('#id_vdblogin').val();
            var dbpass = $('#id_vdbpass').val();
            var dbname = $('#id_vdbname').val();

            // PHP file linked the pop-up, and name.
            var url = cfg.wwwroot + "/local/vmoodle/views/management.testcnx.php?";
            url += "vdbtype=" + dbtype;
            url += '&vdbhost=' + encodeURIComponent(dbhost);
            url += '&vdblogin=' + encodeURIComponent(dblogin);
            url += '&vdbpass=' + encodeURIComponent(dbpass);
            url += '&vdbname=' + encodeURIComponent(dbname);

            // Pop-up's options.
            var options = "width=500,height=300,toolbar=no,menubar=no,location=no,scrollbars=no,status=no";

            // Opening the pop-up (title not working in Firefox).
            var windowobj = window.open(url, '', options);
            // Needed to be valid in IE.
            windowobj.document.title = "Vmoodle connexion test";
        },

        /**
         * Pop-up testing connection with database.
         */
        opendatapathpopup: function () {

            // Input data.
            var datapath = $('#id_vdatapath').val();

            // PHP file linked the pop-up, and name.
            var url = cfg.wwwroot + "/local/vmoodle/views/management.testdatapath.php?";
            url += 'dataroot=' + escape(datapath);

            // Pop-up's options.
            var options = "width=500,height=300,toolbar=no,menubar=no,location=no,scrollbars=no,status=no";

            // Opening the pop-up (title not working in Firefox).
            var windowobj = window.open(url, '', options);
            // Needed to be valid in IE.
            windowobj.document.title = "VMoodle test data path";
        },

        /**
         * Activates/desactivates services selection.
         */
        switcherservices: function () {

            var that = $(this);
            var mnetnewsubnetwork = that.attr('data-subnet');

            // Retrieve 'select' elements from form.
            var multimnet = $('#id_multimnet');
            var services = $('#id_services');

            // Default values for services.
            var mnetfreedefault = '0';
            var defaultservices = 'default';
            var subnetworkservices = 'subnetwork';

            // Do the actions.
            if (multimnet.val() === mnetfreedefault
                    || multimnet.val() === mnetnewsubnetwork) {
                services.val(defaultservices);
                services.attr('disabled', true);
            } else {
                services.attr('disabled', false);
                services.val(subnetworkservices);
            }
        },

        syncSchema: function() {

            var originelement = $("#id_shortname");

            var syncedelement2 = $("#id_vdbname");
            var syncedelement3 = $("#id_vdatapath");
            var syncedelement4 = $("#id_vhostname");

            originelement.val(originelement.val().replace(/-/g, '_'));
            originelement.val(originelement.val().replace(/ /g, '_'));
            syncedelement2.val(syncedelement2.val().replace(/<%%INSTANCE%%>/g, originelement.val()));
            syncedelement3.val(syncedelement3.val().replace(/<%%INSTANCE%%>/g, originelement.val()));
            syncedelement4.val(syncedelement4.val().replace(/<%%INSTANCE%%>/g, originelement.val()));
        }

    };

    return hostform;
});
