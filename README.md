Important requirements for VMoodling :

Summary of prerequisites
################################################################

1. Installing vmoodle block and local plugin in codebase
2. Installing the master moodle as usual browsing to Administration -&gt; notifications
3. Make a vconfig.php in /local/vmoodle on base of /local/vmoodle/vconfig-dist.php
4. Installing the config.php hook to /local/vmoodle/vconfig.php
5. Configuring the VMoodle common parameters

Post install procedure
----------------------------------------------------------------
6. Having names resolved for all the virtual moodles, through an explicit DNS binding OR a wildcard dns binding
(local resolutions on the webserver could make it possible also).
7. Setting up the master Moodle with relevant startup settings (including MNET activation).
8. Snapshoting the master Moodle as template for virtual moodling (may be long)
9. Deploying vmoodle instances (may be long).

1. Master configuration changes : Installing the config.php hook to vconfig.php
###############################################################################

Main config.php file must be changed in order to plug virtualization hooking.

config must have an include call to vconfig.php virtualization configuration router.
You can obtain this file from the vconfig-dist.php template, making your own 
vconfig.php file in local/vmoodle and then install the hook point in the standard 
config.php of Moodle.

/// VMOODLE CLI Activation
/*
 * this fragment will trap the CLI scripts trying to work for a virtual node, and
 * needing booting a first elementary configuration based on main config 
 */
if (isset($CLI_VMOODLE_PRECHECK) && $CLI_VMOODLE_PRECHECK == true) {
    $CLI_VMOODLE_PRECHECK = false;
    return;
}
/// /VMOODLE CLI Activation

/// VMOODLE Hack
include $CFG-&gt;dirroot.'/local/vmoodle/vconfig.php';
/// /VMOODLE Hack

must be located BEFORE the call to lib/setup.php include and AFTER the static configuration. 

Setting up Apache configuration for virtual Moodling
####################################################

Moodle virtualization assumes correct routing of each instance to the same RootDirectory inthe server.

A way to do this is using VirtualDirectory. Here comes a sample of Apache configuration that allows
this kind of generic binding.


    ServerAdmin webmaster@mydomain.fr
    ServerName %mastermoodlehost%
    ServerAlias *.%mastermoodledomain%
    VirtualDocumentRoot "D:/wwwroot/%moodledir%"
    ErrorLog logs/vmoodle_common-error_log
    CustomLog logs/vmoodle_common-access_log common


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
 * is not set. This may e useful in some load balancing or proxied installation 
 */
// $CFG->vmoodle_force_https_proto = true; // Default false.

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
 * lxxxxxxxxx moodle1 => .
 * lxxxxxxxxx moodle2 => .
 * etc.
 */
// $CFG->vmoodleusesubpaths = true; // Default false.

/*
 * Will load an additional config file for master installation named as
 * /local/defaults_maindefaultname.php
 * in which plugin settings defaults can be added
 */
// $CFG->vmoodlehardmasterdefaults = 'maindefaultname'; // Default empty.

/*
 * Will load an additional config file for master installation named as
 * /local/defaults_childsdefaultname.php
 * in which plugin settings defaults can be added
 */
// $CFG->vmoodlehardchildsdefaults = 'childsdefaultname'; // Default empty.

/*
 * Used to avoid master moodle is actually usable.
 */
// $CFG->vmoodlenodefault = true; // Default false.

/*
 * You may hard define the login/pass of any child moodle in the config file, 
 * so no need to expose logins and password in your local_vmoodle DB tables.
 * Note this is NOT usable if your instances use multiple distinct credentials for
 * accessing the DB.
 */
// $CFG->vchildsdblogin = 'xxxxx'; // Default empty.
// $CFG->vchildsdbpass = 'xxxxx'; // Default empty.

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

�amount of passed crons
�date of last cron
�time elapsed (in secs) since the last cron was run

On large implementations running heavy moodle cron jobs or a lot 
of VMoodles, it can be a good idea to make cron run on a spare little 
server that can run the codebase and has access to the database pool.

