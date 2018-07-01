<?php

// Hooks the original search script adding an extra custom class for user selectors
if (file_exists($CFG->dirroot.'/customscripts/admin/roles/user_selector.class.php')) {
    include_once $CFG->dirroot.'/customscripts/admin/roles/lib.php';
    include_once $CFG->dirroot.'/customscripts/admin/roles/user_selector.class.php';
    include_once $CFG->dirroot.'/customscripts/enrol/manual/locallib.php';
    include_once $CFG->dirroot.'/customscripts/cohort/cohort_selector.class.php';
}

if (file_exists($CFG->dirroot.'/customscripts/admin/roles/classes/admins_potential_selector.php')) {
    include_once $CFG->dirroot.'/customscripts/admin/roles/classes/admins_potential_selector.php';
}
