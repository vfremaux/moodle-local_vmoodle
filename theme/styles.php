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

require_once('../../../config.php');
header('Content-type: text/css');
?>
div.bvmc {
    border: 1px solid #B9BABB;
    margin: 10px auto 15px;
    width: 95%;
}
div.bvmc div.header h2 {
    margin: 5px 0;
    padding: 0 0 0 22px;
}
div.bvmc div.header div.title {
    margin: 0px 5px;
}
div.bvmc div.header .hide-show-image {
    float: right;
    height: 16px;
    width: 16px;
    margin-top: 0.1em;
}
div.bvmc div.content {
    background: #FFFFFF;
    padding: 5px 0 10px;
}
div.bvmc.hidden div.content {
    display: none;
}

#platformschoice div.fitemtitle {
    display: none;
}
#id_platformsgroup_aplatforms,
#id_platformsgroup_splatforms {
    width: 50%;
}

#platformschoice .fgroup {
    white-space: nowrap;
}

table.pfilter {
    width: 90%;
} 

table.pfilter td.pfilter_type {
    width: 50%;
    text-align: right;
}

table.pfilter td.pfilter_value {
    width: 30%;
    padding-left: 6%;
    text-align: left;
}

table.pfilter td.pfilter_action {
    width: 20%;
    text-align: center;
}
<?php

// Adding plugin libraries styles.
$manager = core_plugin_manager::instance();
$plugins = $manager->get_plugins_of_type('vmoodleadminset');
foreach ($plugins as $plugin) {
    if (file_exists($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin->name.'/theme/styles.php')) {
        include_once($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin->name.'/theme/styles.php');
    }
}