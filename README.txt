VMoodle
#############

Implements a packaged virtualization control feature for large "Moodle Arrays" 

Important requirements for VMoodling :

version 2013020801 summary
=============================

- Fixes security issue when used with local/technicalsignals plugin
- Adds new remote plugin and equipement control
- MultiSQL commands fixed
- Several fixes on meta administration
- added config file generator (to help cli migrations)
- impove Command error and success report
- integrates mahara patches for MNET stability.

version 2016052402 summary
=============================

- Essentially shifts to local 

Summary of prerequisites
################################################################

1. Installing vmoodle local component in codebase
2. Installing accessory vmoodle block in codebase
3. Installing the accessory VMoodle report in codebase
4. Installing the master moodle as usual browsing to Administration -> notifications
5. Installing the config.php hook to vconfig.php
6. Configuring the VMoodle common parameters

Post install procedure
----------------------------------------------------------------
5. Having names resolved for all the virtual moodles, through an explicit DNS binding OR a wildcard dns binding
(local resolutions on the webserver could make it possible also).
6. Setting up the master Moodle with relevant startup settigns (including MNET activation).
7. Snapshoting the master Moodle as template for virtual moodling (may be long)
8. Deploying vmoodle instances (may be long).

0 Old patches information (vs. 1.9)

0.1 Patch for automated key rotations and consolidation in VMoodled networks:
#############################################################################

This patch allows a "well known trusted peer" to force asynchronous renewal of his
own key. It is still needed in Moodle 2.x versions

Location : mnet/lib.php
Changes : start of mnet_get_public_key() (including signature)

Patch content :

// PATCH : VMoodle Mnet automated key renewal -- adding force mode
function mnet_get_public_key($uri, $application=null, $force=0) {
    global $CFG, $DB;

    $mnet = get_mnet_environment();
    // The key may be cached in the mnet_set_public_key function...
    // check this first
    // cache location of key must be bypassed when we need an automated renew.
    if (!$force){
        $key = mnet_set_public_key($uri);
        if ($key != false) {
            return $key;
        }
    }
// /PATCH

    if (empty($application)) {
        $application = $DB->get_record('mnet_application', array('name'=>'moodle'));
    }

//! PATCH : Mnet automated key renewal
    $rq = xmlrpc_encode_request('system/keyswap', array($CFG->wwwroot, $mnet->public_key, $application->name, $force), array("encoding" => "utf-8"));
// /PATCH

1. Master configuration changes : Installing the config.php hook to vconfig.php
###############################################################################

Main config.php file must be changed in order to plug virtualization hooking.

config must have an include call to vconfig.php virtualization configuration router.
You can obtain this file from the vconfig-dist.php template, making your own 
vconfig.php file in blocks/vmoodle and then install the hook point in the standard 
config.php of Moodle.

/// VMOODLE Hack
$CFG->mainhostprefix = 'http://someprefixthatmatchs';

// this fragment will trap the CLI scripts trying to work for a virtual node, and
// needing booting a first elementary configuration based on main config 
if (isset($CLI_VMOODLE_PRECHECK) && $CLI_VMOODLE_PRECHECK == true) {
    $CLI_VMOODLE_PRECHECK = false;
    return;
}
include $CFG->dirroot.'/local/vmoodle/vconfig.php';
/// /VMOODLE Hack

must be located BEFORE the call to lib/setup.php include and AFTER the static configuration. 

Setting up Apache configuration for virtual Moodling
####################################################

Moodle virtualization assumes correct routing of each instance to the same RootDirectory inthe server.

A way to do this is using VirtualDirectory. Here comes a sample of Apache configuration that allows
this kind of generic binding.

<VirtualHost 127.0.0.1>
    ServerAdmin webmaster@eisti.fr
    ServerName %mastermoodlehost%
    ServerAlias *.%mastermoodledomain%
    VirtualDocumentRoot "D:/wwwroot/%moodledir%"
    ErrorLog logs/vmoodle_common-error_log
    CustomLog logs/vmoodle_common-access_log common
</VirtualHost>

For example :

the master moodle site could be :

%mastermoodlehost% = moodle.mydomain.edu
%moodledomain% = mydomain.edu

Using a wildcard DNS
####################

Defining wildcard DNS record say, to resolve *.mydomain.edu
avoids having to bind each virtual moodle within the DNS server
before using it. 

Any undefined moodle will respond with an error message.

Additional useful settings
###############################

Those additional keys have been added to the $CFG global variable to drive alternative features
from the physical config file.

/*
 * A way to force protocol to https even if the system context variable HTTP_X_FORWARDED_PROTO
 * is not set. This may e useful in some load balancing or proxied installation. Use the now standard
 * config variable.
 */
// $CFG-&gt;overridetossl = true; // Default false.

/*
 * This allows vmoodle instances to be depoyed on a subdiretory basis of a single domaine, e.g:
 * http://mymoodle.domain.org/moodle1
 * http://mymoodle.domain.org/moodle2
 * http://mymoodle.domain.org/moodle3
 * ...
 *
 * In that case you will need having symlinks to the root moodle installation (link to self) named
 * as per directory:
 * 
 * lxxxxxxxxx moodle1 =&gt; .
 * lxxxxxxxxx moodle2 =&gt; .
 * etc.
 */
// $CFG-&gt;vmoodleusesubpaths = true; // Default false.

/*
 * Will load an additional config file for master installation named as
 * /local/defaults_maindefaultname.php
 * in which plugin settings defaults can be added
 */
// $CFG-&gt;vmoodlehardmasterdefaults = 'maindefaultname'; // Default empty.

/*
 * Will load an additional config file for master installation named as
 * /local/defaults_childsdefaultname.php
 * in which plugin settings defaults can be added
 */
// $CFG-&gt;vmoodlehardchildsdefaults = 'childsdefaultname'; // Default empty.

/*
 * Used to avoid master moodle is actually usable.
 */
// $CFG-&gt;vmoodlenodefault = true; // Default false.

/*
 * You may hard define the login/pass of any child moodle in the config file, 
 * so no need to expose logins and password in your local_vmoodle DB tables.
 * Note this is NOT usable if your instances use multiple distinct credentials for
 * accessing the DB.
 */
// $CFG-&gt;vchildsdblogin = 'xxxxx'; // Default empty.
// $CFG-&gt;vchildsdbpass = 'xxxxx'; // Default empty.

Cron handling by virtualization
###############################

VMoodle provides from now an automated handling of cron execution 
for virtualized hosts. It will be not any more needed to manually 
edit the cron tab for each new VMoodle instance. A single cron call 
to vcron.php in the VMoodle block implementation will schedule and 
rotate all VMoodle crons. The VMoodle cron should be run at sufficiant 
frequence so each VMoodle has the expected turnover.  

The ROUND_ROBIN mode runs a VMoodle per turn scannnig the VMoodle 
definitions in order.

The LOWEST_POSSIBLE_GAP mode guarantees a nominal period for each 
VMoodle by dispatching defined VMoodle on available cron ticks 
(still in table order).  

The VMoodle interface adds three additional attributes in the display 
for VMoodles : 

•amount of passed crons
•date of last cron
•time elapsed (in secs) since the last cron was run

On large implementations running heavy moodle cron jobs or a lot 
of VMoodles, it can be a good idea to make cron run on a spare little 
server that can run the codebase and has access to the database pool.

Using admin scripts in a VMoodle Configuration
##############################################

As standard scripts do not know anything of virtualisation, and command line scripts
cannot rely on SERVER_NAME or other HTTP environment variables, most of the admin/cli 
scripts have been adapted for a VMoodle environment and reside in blocks/vmoodle/cli
directory.

Use them adding an additional --host options with the current wwwroot of the instance
you want to address.

VMoodle boot output variables
################################################

when booting a virtual moodle, $CFG is added severa variables during the boot process :

$CFG->vmoodleroot The computed effective wwwroot of the vmoodle instance. May comme from Web context or from
                  CLI forced value. Includes subdir if deploying as subdirectories.

$CFG->vmoodlename The vmoodle server name (without protocol). Includes subdir if deploying as subdirectories.

$CFG->vhost the host name (first token of the domain name).

2022060700 (X.X.0009)
##############################################

Add registration to report_zabbix senders.

2023030200 (X.X.0010)
##############################################

Add new course mnetadmin function mnetadmin_check_course