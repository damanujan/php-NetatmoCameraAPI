<?php

/*

Action endpoint for IFTTT buttons or applets

call:
http://www.mydomain.com/path/to/action.php?action=turnlighton

you can also pass other params with action.php?action=turnlighton&camera=camera1

*/

//get url variable:
if(isset($_GET['action']))
{
	require($_SERVER['DOCUMENT_ROOT']."/path/to/NetatmoCameraAPI.php");
	$_Presence = new NetatmoPresenceAPI($Netatmo_user, $Netatmo_pass);
    $action = $_GET['action'];
    $cam = 'my camera';
    $param = 0;
    if(isset($_GET['camera'])) $cam = $_GET['camera'];
    if(isset($_GET['param'])) $param = $_GET['param'];

    if ($action == "turnlighton") $_Presence->setFloodlight($cam, "on");
    if ($action == "turnlightoff") $_Presence->setFloodlight($cam, "off");
    if ($action == "alerthumanon") $_Presence->setHumanOutAlert(2);
    if ($action == "monitoringoff") $_Presence->setMonitoring($cam, "off");
    if ($action == "monitoringon") $_Presence->setMonitoring($cam, "on");
    //http://www.mydomain.com/path/to/action.php?action=setintensity&camera=cam2&param=100
    if ($action == "setintensity") $_Presence->setLightIntensity($cam, $param);
    //etc.

}

?>
