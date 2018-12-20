/*
 * Pop-up testing connection with database.
 * TODO : Make this test request more network secure.
 * Solution 1 : Remove GET params, fetch parent window form elements
 * values and fire POST form
 * solution 2 : Obfuscate query string into simply crypted bundle
 */
// jshint undef:false, unused:false
function opencnxpopup(wwwroot) {

    // Input data.
    var dbtype = document.getElementById('id_vdbtype').value;
    var dbhost = document.getElementById('id_vdbhost').value;
    var dblogin = document.getElementById('id_vdblogin').value;
    var dbpass = document.getElementById('id_vdbpass').value;
    var dbname = document.getElementById('id_vdbname').value;

    // PHP file linked the pop-up, and name.
    var url = wwwroot + "/local/vmoodle/views/management.testcnx.php" + "?" + "vdbtype="
            + dbtype + "&" + "vdbhost=" + encodeURIComponent(dbhost) + "&" + "vdblogin=" + encodeURIComponent(dblogin)
            + "&" + "vdbpass=" + encodeURIComponent(dbpass) + "&" + "vdbname=" + encodeURIComponent(dbname);

    // Pop-up's options.
    var options = "width=500,height=300,toolbar=no,menubar=no,location=no,scrollbars=no,status=no";

    // Opening the pop-up (title not working in Firefox).
    var windowobj = window.open(url, '', options);
    // Needed to be valid in IE.
    windowobj.document.title = vmoodle_testconnection;
}

/**
 * Pop-up testing connection with database.
 */
function opendatapathpopup(wwwroot) {

    // Input data.
    var datapath = document.getElementById('id_vdatapath').value;

    // PHP file linked the pop-up, and name.
    var url = wwwroot + "/local/vmoodle/views/management.testdatapath.php?dataroot=" + escape(datapath);

    // Pop-up's options.
    var options = "width=500,height=300,toolbar=no,menubar=no,location=no,scrollbars=no,status=no";

    // Opening the pop-up (title not working in Firefox).
    var windowobj = window.open(url, '', options);
    // Needed to be valid in IE.
    windowobj.document.title = vmoodle_testdatapath;
}

/**
 * Activates/desactivates services selection.
 */
function switcherServices(mnetnewsubnetwork) {

    // Retrieve 'select' elements from form.
    var mnetenabled = document.getElementById('id_mnetenabled');
    var multimnet = document.getElementById('id_multimnet');
    var services = document.getElementById('id_services');

    // Default values for services.
    var mnetfreedefault = '0';
    var defaultservices = 'default';
    var subnetworkservices = 'subnetwork';

    // Do the actions.
    if (multimnet.value == mnetfreedefault
            || multimnet.value == mnetnewsubnetwork) {
        services.value = defaultservices;
        services.disabled = true;
    } else {
        services.disabled = false;
        services.value = subnetworkservices;
    }
}

function syncSchema(){

    var originelement = document.getElementById("id_shortname");

    var syncedelement2 = document.getElementById("id_vdbname");
    var syncedelement3 = document.getElementById("id_vdatapath");
    var syncedelement4 = document.getElementById("id_vhostname");

    dbescapedelement = originelement.value.replace('-', '_');
    dbescapedelement = dbescapedelement.replace(' ', '_');
    syncedelement2.value = syncedelement2.value.replace(/<%%INSTANCE%%>/g, dbescapedelement);
    syncedelement3.value = syncedelement3.value.replace(/<%%INSTANCE%%>/g, originelement.value);
    syncedelement4.value = syncedelement4.value.replace(/<%%INSTANCE%%>/g, originelement.value);
}

/*
 * Let the function be executed immediately after loading the page.
 */
$(function(){
    var originelement = document.getElementById("id_shortname");
    originelement.onchange = syncSchema;
});
