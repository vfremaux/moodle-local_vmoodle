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
 * Define form for adding or editing a vmoodle host.
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace local_vmoodle;

defined('MOODLE_INTERNAL') || die();

use StdClass;

require_once($CFG->libdir.'/formslib.php');

class Host_Form extends \moodleform {

    /**
     * Action to call from controller.
     */
    private $mode;

    /**
     * Data array for the form.
     */
    private $platformform;

    /**
     * Constructor.
     * @param string $mode The action to call from controler.
     * @param array $platformform Data to input in fields.
     */
    public function __construct($mode, $platformform = null) {
        // Settings mode and data.
        $this->mode = $mode;
        $this->platformform = $platformform;

        // Calling parent's constructor.
        $params = array('view' => 'management', 'what' => 'do'.$this->mode, 'page' => $this->mode);
        parent::__construct(new \moodle_url('/local/vmoodle/view.php', $params));
    }

    /**
     * Describes the form (each elements' name  corresponds to its name in database).
     */
    public function definition() {
        global $CFG, $DB;

        // Settings variables.
        $mform = &$this->_form;
        $sizeinputtext = 'size="30"';
        $sizeinputtextbig = 'size="60"';

        /*
         * Host's id.
         */
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        /*
         * Features fieldset.
         */
        $mform->addElement('header', 'featuresform', get_string('addformfeaturesgroup', 'local_vmoodle'));
        // Name.
        $mform->addElement('text', 'name', get_string('addformname', 'local_vmoodle'), $sizeinputtext);
        $mform->addHelpButton('name', 'name', 'local_vmoodle');
        $mform->setType('name', PARAM_TEXT);
        if ($this->is_in_add_mode()) {
            // Shortname.
            $elmname = get_string('addformshortname', 'local_vmoodle');
            $mform->addElement('text', 'shortname', $elmname, ($this->mode == 'edit' ? 'disabled="disabled" ' : ''));
            $mform->addHelpButton('shortname', 'shortname', 'local_vmoodle');
            $mform->setType('shortname', PARAM_TEXT);
        }

        // Description.
        $elmname = get_string('addformdescription', 'local_vmoodle');
        $mform->addElement('textarea', 'description', $elmname, 'rows="15" cols="40"');
        $mform->addHelpButton('description', 'description', 'local_vmoodle');
        $mform->setType('description', PARAM_TEXT);

        if ($this->is_in_add_mode()) {
            // Host's name.
            $elmname = get_string('vhostname', 'local_vmoodle');
            $disabled = ($this->mode == 'edit') ? 'disabled="disabled" ' : '';
            $mform->addElement('text', 'vhostname', $elmname, $disabled.$sizeinputtext);
            $mform->addHelpButton('vhostname', 'vhostname', 'local_vmoodle');
            $mform->addElement('checkbox', 'forcedns', get_string('forcedns', 'local_vmoodle'));
            $mform->setType('vhostname', PARAM_URL);
        }

        $mform->closeHeaderBefore('dbform');

        /*
         * Database fieldset.
         */
        $mform->addElement('header', 'dbform', get_string('addformdbgroup', 'local_vmoodle'));

        // Database type.
        $dbtypearray = array('mariadb' => 'MariaDB', 'mysqli' => 'MySQL', 'postgres' => 'PostgreSQL');
        $mform->addElement('select', 'vdbtype', get_string('vdbtype', 'local_vmoodle'), $dbtypearray);
        $mform->addHelpButton('vdbtype', 'vdbtype', 'local_vmoodle');
        $mform->setType('vdbtype', PARAM_TEXT);

        // Database host.
        $mform->addElement('text', 'vdbhost', get_string('vdbhost', 'local_vmoodle'));
        $mform->setType('vdbhost', PARAM_TEXT);

        // Database login.
        $mform->addElement('text', 'vdblogin', get_string('vdblogin', 'local_vmoodle'));
        $mform->setType('vdblogin', PARAM_TEXT);

        // Database password.
        $mform->addElement('passwordunmask', 'vdbpass', get_string('vdbpass', 'local_vmoodle'));
        $mform->setType('vdbpass', PARAM_RAW);

        // Button for testing database connection.
        $label = get_string('testconnection', 'local_vmoodle');
        $mform->addElement('button', 'testconnection', $label);

        // Database name.
        $mform->addElement('text', 'vdbname', get_string('vdbname', 'local_vmoodle'));
        $mform->addHelpButton('vdbname', 'vdbname', 'local_vmoodle');
        $mform->setType('vdbname', PARAM_TEXT);

        // Table's prefix.
        $mform->addElement('text', 'vdbprefix', get_string('vdbprefix', 'local_vmoodle'));
        $mform->setType('vdbprefix', PARAM_TEXT);

        // Connection persistance.
        $noyesarray = array('0' => get_string('no'), '1' => get_string('yes'));
        $mform->addElement('select', 'vdbpersist', get_string('vdbpersist', 'local_vmoodle'), $noyesarray);
        $mform->addHelpButton('vdbpersist', 'vdbpersist', 'local_vmoodle');
        $mform->setType('vdbpersist', PARAM_BOOL);
        $mform->closeHeaderBefore('nfform');

        /*
         * Network and data fieldset.
         */
        $mform->addElement('header', 'nfform', get_string('addformnfgroup', 'local_vmoodle'));

        // Path for "moodledata".
        $mform->addElement('text', 'vdatapath', get_string('vdatapath', 'local_vmoodle'), $sizeinputtextbig);
        $mform->addHelpButton('vdatapath', 'vdatapath', 'local_vmoodle');
        $mform->setType('vdatapath', PARAM_TEXT);

        // Button for testing datapath.
        $elmname = get_string('testdatapath', 'local_vmoodle');
        $mform->addElement('button', 'testdatapath', $elmname);

        // MultiMNET.
        $subnetworks = array('-1' => get_string('nomnet', 'local_vmoodle'));
        $subnetworks['0'] = get_string('mnetfree', 'local_vmoodle');
        $sql = "
            SELECT
                *
            FROM
                {local_vmoodle}
            WHERE
                mnet > 0
            ORDER BY
                mnet
        ";
        $subnetworksrecords = $DB->get_records_sql($sql);

        $newsubnetwork = 1;
        if (!empty($subnetworksrecords)) {
            $maxmnet = 0;
            foreach ($subnetworksrecords as $subnetworksrecord) {
                $subnetworks[$subnetworksrecord->mnet] = $subnetworksrecord->mnet;
                $maxmnet = max($maxmnet, $subnetworksrecord->mnet);
            }
            $newsubnetwork = $maxmnet + 1;
        }
        $subnetworks[$newsubnetwork] = $newsubnetwork.' ('.get_string('mnetnew', 'local_vmoodle').')';
        $label = get_string('multimnet', 'local_vmoodle');
        $attrs = ['data-subnet' => $newsubnetwork];
        $mform->addElement('select', 'mnet', $label, $subnetworks, $attrs);
        $mform->addHelpButton('mnet', 'mnet', 'local_vmoodle');
        $mform->setType('mnet', PARAM_TEXT);

        // Services strategy.
        $servicesstrategies = array(
            'default' => get_string('servicesstrategydefault', 'local_vmoodle'),
            'subnetwork' => get_string('servicesstrategysubnetwork', 'local_vmoodle')
        );
        $mform->addElement('select', 'services', get_string('servicesstrategy', 'local_vmoodle'), $servicesstrategies);
        $mform->addHelpButton('services', 'services', 'local_vmoodle');
        $mform->setType('services', PARAM_TEXT);

        if ($this->is_in_add_mode()) {
            // Template.
            $templatesarray = vmoodle_get_available_templates();
            $mform->addElement('select', 'vtemplate', get_string('vtemplate', 'local_vmoodle'), $templatesarray);
            $mform->addHelpButton('vtemplate', 'vtemplate', 'local_vmoodle');
            $mform->setType('vtemplate', PARAM_TEXT);
        }
        $mform->closeHeaderBefore('submitbutton');

        // Control buttons.
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string(($this->mode == 'edit' ? 'edit' : 'create')));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'controlbuttons', '', array(' '), false);

        // Rules for the add mode.
        if ($this->is_in_add_mode()) {
            $mform->addRule('name', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
            $mform->addRule('shortname', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
            $mform->addRule('vhostname', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
            $mform->addRule('vdbhost', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
            $mform->addRule('vdblogin', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
            $mform->addRule('vdbname', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
            $mform->addRule('vdbprefix', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
            $mform->addRule('vdatapath', get_string('addforminputtexterror', 'local_vmoodle'), 'required', null, 'client');
        }
    }

    /**
     * Test connection validation.
     * @see lib/moodleform#validation($data, $files)
     */
    public function validation($data, $files = null) {
        global $CFG, $DB;

        // Empty array.
        $errors = parent::validation($data, null);

        // Checks database connection again, after Javascript test.
        $vmaster = new StdClass();
        $vmaster->vdbtype = $CFG->vmasterdbtype;
        $vmaster->vdbhost = $CFG->vmasterdbhost;
        $vmaster->vdblogin = $CFG->vmasterdblogin;
        $vmaster->vdbpass = $CFG->vmasterdbpass;
        $vmaster->vdbname = $CFG->vmasterdbname;

        $config = get_config('local_vmoodle');
        if (!preg_match('/'.$config->vmoodleinstancepattern.'/', $data['name'])) {
            $errors['name'] = get_string('errorinvalidnameform', 'local_vmoodle', $config->vmoodleinstancepattern);
        }

        if (!vmoodle_make_connection($vmaster, false)) {
            $errors['vdbhost'] = get_string('badconnection', 'local_vmoodle');
            $errors['vdblogin'] = get_string('badconnection', 'local_vmoodle');
            $errors['vdbpass'] = get_string('badconnection', 'local_vmoodle');
        }

        // Checks if database's name doesn't finish with '_'.
        if ($data['vdbname'][strlen($data['vdbname']) - 1] == '_') {
            $errors['vdbname'] = get_string('baddatabasenamecoherence', 'local_vmoodle');
        }

        // Checks if database's name has hyphens '-'.
        if (strstr($data['vdbname'], '-') !== false) {
            $errors['vdbname'] = get_string('badnohyphensindbname', 'local_vmoodle');
        }

        // Checks if table's prefix doesn't begin with restricted values (which can evolve).
        $restrictedvalues = array(
            'vmoodle_'
        );
        foreach ($restrictedvalues as $restrictedvalue) {
            if ($data['vdbprefix'] == $restrictedvalue) {
                $errors['vdbprefix'] = get_string('baddatabaseprefixvalue', 'local_vmoodle');
            }
        }

        // ATTENTION Checks if user has entered a datapath with only one backslash between each folder and/or file.
        if (isset($CFG->ostype)
                && ($CFG->ostype == 'WINDOWS')
                    && (preg_match('#\\\{3,}#', $data['vdatapath']) > 0)) {
            $errors['vdatapath'] = get_string('badmoodledatapathbackslash', 'local_vmoodle');
        }

        // Test of values which have to be well-formed and can not be modified after.
        if ($this->is_in_add_mode()) {

            // Checks 'shortname', which must have no spaces.
            $shortname = $data['shortname'];
            if (strstr($shortname, ' ')) {
                $errors['shortname'] = get_string('badshortname', 'local_vmoodle');
            }

            // Check vhostname has no unresolved %%INSTANCE%% placeholder. Catched by vhostname PARAM_TYPE
            // Check vhost name not empty.
            if (empty($data['vhostname'])) {
                $errors['vhostname'] = get_string('emptyormalformedvhost', 'local_vmoodle');
            }

            // Checks 'vhostname', if not already used.
            if ($this->is_equal_to_another_vhostname($data['vhostname'])) {
                // Check if the vhostname is deleted.
                $sql = "
                    SELECT
                        m.deleted
                    FROM
                        {local_vmoodle} b,
                        {mnet_host} m
                    WHERE
                        b.vhostname = ?
                    AND
                        b.vhostname = m.wwwroot
                ";
                $resultsqlrequest = $DB->get_record_sql($sql, array($data['vhostname']));
                if (!empty($resultsqlrequest)) {
                    if ($resultsqlrequest->deleted == 0) {
                        $errors['vhostname'] = get_string('badhostnamealreadyused', 'local_vmoodle');
                    } else {
                        // Id the plateforme is deleted and the user want to reactivate the vhostname.
                        if ($data['vtemplate'] == 0) {
                            $sql = "
                                SELECT
                                    id,
                                    vdatapath,
                                    vdbname
                                FROM
                                    {local_vmoodle}
                                WHERE
                                    vhostname = ?
                            ";
                            $resultsqlrequest = $DB->get_record_sql($sql, array($data['vhostname']));

                            // Checks if datapath and vdbname of vhostname are the same on the form.
                            if ($resultsqlrequest->vdatapath != stripslashes($data['vdatapath'])
                                    && $resultsqlrequest->vdbname != $data['vdbname']) {
                                $errors['vdatapath'] = get_string('errorreactivetemplate', 'local_vmoodle');
                                $errors['vdbname'] = get_string('errorreactivetemplate', 'local_vmoodle');
                            }
                        }
                    }
                }
            }

            // Checks 'vhostname' consistency, with a regular expression.
            $vhostname = $data['vhostname'];
            if (!preg_match('/^http(s)?:\/\//', $vhostname)) {
                $errors['vhostname'] = get_string('badvhostname', 'local_vmoodle');
            }

            // Checks 'vdatapath', if not already used.
            if ($this->is_equal_to_another_dataroot($data['vhostname'], $data['vdatapath'])) {
                if (!empty($data['vtemplate'])) {
                    $errors['vdatapath'] = get_string('badmoodledatapathalreadyused', 'local_vmoodle');
                }
            }

            // Checks 'vdbname', if not already used.
            if ($this->is_equal_to_another_database_name($data['vdbname'])) {
                if (!empty($data['vtemplate'])) {
                    $errors['vdbname'] = get_string('baddatabasenamealreadyused', 'local_vmoodle');
                }
            }
        }

        return $errors;
    }

    /**
     * Test if form is in add mode.
     * @return bool If true, form is in add mode, else false.
     */
    protected function is_in_add_mode() {
        return ($this->mode == 'add');
    }

    /**
     * Test if form is in edit mode.
     * @return bool If true, form is in edit mode, else false.
     */
    protected function is_in_edit_mode() {
        return ($this->mode == 'edit');
    }

    /**
     * Checks if the new virtual host's selected hostname is already used.
     * @param string $vhostname The hostname to check.
     * @return bool If TRUE, the chosen hostname is already used, else FALSE.
     */
    private function is_equal_to_another_vhostname($vhostname) {
        global $DB;

        $sql = "
            SELECT
                *
            FROM
                {local_vmoodle}
            WHERE
                vhostname LIKE ?
        ";
        $localvmoodles = $DB->get_records_sql($sql, array('%'.$vhostname));
        return (empty($localvmoodles) ? false : true);
    }

    /**
     * Checks if the new virtual host's datapath is already used by an enabled vmoodle.
     * @param  string $vdatapath The datapath to check.
     * @return bool If TRUE, the chosen datapath is already used, else FALSE.
     */
    private function is_equal_to_another_dataroot($vhostname, $vdatapath) {
        global $DB;

        $vmoodles = $DB->get_records('local_vmoodle', array('enabled' => 1));
        if (!empty($vmoodles)) {
            // Retrieves all the vmoodles datapaths.
            $vdatapaths = array();
            foreach ($vmoodles as $vmoodle) {
                if ($vmoodle->vhostname == $vhostname) {
                    // an existing datapath for ourself is legitimous.
                    continue;
                }
                $vdatapaths[] = $vmoodle->vdatapath;
            }

            return in_array(stripslashes($vdatapath), $vdatapaths);
        }
        return false;
    }

    /**
     * Checks if the new virtual host's selected database name is already used.
     * @param string $vdbname The database name to check.
     * @return bool If TRUE, the chosen database name is already used, else FALSE.
     */
    private function is_equal_to_another_database_name($vdbname) {
        global $DB;

        $vdbs = $DB->get_records_sql('SHOW DATABASES');
        if (!empty($vdbs)) {
            // Retrieves all the databases names.
            $vdbnames = array();
            foreach ($vdbs as $vdb) {
                $vdbnames[] = $vdb->database;
            }
            return in_array($vdbname, $vdbnames);
        }
        return false;
    }
}