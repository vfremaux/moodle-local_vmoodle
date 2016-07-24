VMoodle block
#############

Implements a packaged virtualization control feature for large "Moodle Arrays" 

Important requirements for VMoodling :

Version 2015062000 summary
=============================

This is a key change in architecture. Main part of the VMoodle mechanism is
transfered to the local/vmoodle component, as local component set is naturally
core enabled for subplugins. 

The original bloc is mostly conserved for quick access to VMoodles configurations

Version 2014071301 summary
=============================

Essentially redraws the internal class organization to cope qith 
core Moodle class loading strategy.

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
$CFG->user_mnet_hosts_admin_override = true;
// this fragment will trap the CLI scripts trying to work for a virtual node, and
// needing booting a first elementary configuration based on main config 
if (isset($CLI_VMOODLE_PRECHECK) && $CLI_VMOODLE_PRECHECK == true) {
    $CLI_VMOODLE_PRECHECK = false;
    return;
}
include $CFG->dirroot.'/blocks/vmoodle/vconfig.php';
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