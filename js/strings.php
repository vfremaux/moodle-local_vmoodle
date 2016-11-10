<?php
header('Content-Type: application/x-javascript');
require_once('../../../config.php');
echo 'var vmoodle_badregexp = "'.get_string('badregexp', 'local_vmoodle').'"; ';
echo 'var vmoodle_contains = "'.get_string('contains', 'local_vmoodle').'"; ';
echo 'var vmoodle_delete = "'.get_string('delete', 'local_vmoodle').'"; ';
echo 'var vmoodle_none = "'.get_string('none', 'local_vmoodle').'"; ';
echo 'var vmoodle_notcontains = "'.get_string('notcontains', 'local_vmoodle').'"; ';
echo 'var vmoodle_regexp = "'.get_string('regexp', 'local_vmoodle').'"; ';

echo 'var vmoodle_testconnection = "'.get_string('testconnection', 'local_vmoodle').'"; ';
echo 'var vmoodle_testdatapath = "'.get_string('testdatapath', 'local_vmoodle').'"; ';
echo 'var mnetactivationrequired = "'.get_string('mnetactivationrequired', 'local_vmoodle').'"; ';