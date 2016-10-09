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

/**
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
require_once($CFG->dirroot.'/config.php');
?>

<script language="Javascript">
    initfields();
    modifyfield();

    function initfields() {
        var b = false;
        var dirroot = "<?php echo(urlencode($CFG->dirroot));?>";
        var element  = document.getElementById("id_vdbprefix");
        var element1 = document.getElementById("id_vdbname");
        var element2 = document.getElementById("id_vdatapath");

        dirroot = unescape(dirroot);

        while (b == false) {
            b = true;

            for (i = 0; i < dirroot.length; i++) {
                if(dirroot[i] == "+") b = false;
            }

            if (b == false) {
                dirroot  = dirroot.replace("+"," ");
            } else {
                b = true;
            }
        }

        var tab1 = dirroot.split("www");
        if (tab1.length >= 1) {
                element2.value = tab1[0]+"moodledata_";
        }

        element.value = "mdl_";
        element1.value = "vmoodle_";
    }

    function modifyfield() {
        var element  = document.getElementById("id_vhostname");
        element.onkeyup = changeHostName; 
        element.onclick = changeHostName;
    }

    function changeHostName(e) {
       if ((window.event) ? event.keyCode : e.keyCode) {
            var dirroothostname = document.getElementById("id_vhostname").value;
            var element  = document.getElementById("id_vdbprefix");
            var element1 = document.getElementById("id_vdbname");
            var element2 = document.getElementById("id_vdatapath");
            var tab = null;

            tab = dirroothostname.split("://");

            if (tab != null && tab.length >= 2) {
                var b = false;
                var dirroot = "<?php echo(urlencode($CFG->dirroot));?>";
                var tab1 = tab[1].split(".");

                if (tab1 != null && tab1.length >= 1) {
                    element.value = "mdl_"+tab1[0];
                    element1.value = "vmoodle_"+tab1[0];
                    element1.value.replace('-', '_');
                    element1.value.replace(' ', '_');
                }

                dirroot = unescape(dirroot);

                while (b == false) {
                    b = true;
                    for (i = 0; i < dirroot.length; i++) {
                        if (dirroot[i] == "+") b = false;
                    }
                    if (b == false) {
                        dirroot  = dirroot.replace("+"," ");
                    } else {
                        b = true;
                    }
                }

                var tab2 = dirroot.split("www");
                if (tab != null && tab1 != null && tab2.length >= 2 && tab1.length >= 1) {
                    element2.value = tab2[0]+"moodledata_" + tab1[0];
                }
            }
        }
    }

</script>