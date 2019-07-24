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
 * Get the local instance list of system filearea identities. this is scirpt is unprotected and
 * should NOT reveal any sensible information as it does not give access to any file content, but
 * just definitions that are resulting on component installs and moodle standard setup.
 *
 * @package vmoodleadminset_generic
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require('../../../../../config.php');

$systemcontext = context_system::instance();

$sql = "
    SELECT DISTINCT
        component,
        filearea,
        itemid
    FROM
        {files}
    WHERE
        contextid = ?
    ORDER BY
        component, filearea, itemid
";

$fileareas = $DB->get_records_sql($sql, array([$systemcontext->id));

if ($fileareas) {
    $return = '<select id="id_fileareaid" name="fileareaid">';
    foreach ($fileareas as $fa) {
        $fakey = $fa->component.'/'.$fa->filearea.'/'.$fa->itemid;
        $falabel = $fa->component.'@'.$fa->filearea.' § '.$fa->itemid;
        $return .= '<option value="'.$fakey.'">'.$falabel.'</option>'."\n";
    }
    $return .= '</select>';
}

echo $return;