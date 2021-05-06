<?php

function vmoodle_get_jump_link_url($vmoodleid, $wantsurl = '') {
    global $DB, $CFG;
    
    $vmoodle = $DB->get_record('local_vmoodle', array('id' => $vmoodleid));
    if (($vmoodle->mnet > -1) && ($vmoodle->vhostname != $CFG->wwwroot)) {
        $url = new moodle_url('/auth/mnet/jump.php', array('hostwwwroot' => $vmoodle->vhostname));
        if (!empty($wantsurl)) {
            $url->param('wantsurl', $wantsurl);
        }
    } else {
        // If not mnet.
        $url = $vmoodle->vhostname;
        if (!empty($wantsurl)) {
            $url.= '?wantsurl='.$wantsurl;
        }
    }
    return $url;
}

function vmoodle_is_enabled($wwwroot) {
    global $DB;

    return $DB->get_field('local_vmoodle', 'enabled', array('vhostname' => $wwwroot));
}