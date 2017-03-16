# php Netatmo Presence API

## This API allow full control of your Netatmo Presence cameras
(C) 2017, KiboOst

This API allow you to fully control Netatmo Presence cameras settings, monitoring, floodlight (mode, intensity, records), and alerts settings.

- No need for official Netatmo SDK or any other ressources!
- No need for Netatmo connect application.

The following operations are currently supported:

- Get and Set alerts (ignore, record, record and notify) for humans, vehicle, animal, other.
- Get and Set alerts hours.
- Get and Set recording or not for each events.
- Set floodlight intensity, mode (auto, on, off).
- Get events (filtered by type or not).
- Get camera(s) status.
- Get and Set SmartZones.

As it doesn't rely on official Netatmo API (which doesn't provide editing camera settings), it can't set/drop webhooks.

**This isn't an official API | USE AT YOUR OWN RISK!**

Feel free to submit an issue or pull request to add more.

## Warning

This API will connect directly to your Netatmo account, like you do with a web browser. Regarding your account option, each connection (script execution) may send you an email alert telling that someone connected to your account!

To avoid this:

- On the web interface, go to user settings (top right)
- Go to e-mail settings
- Turn off e-mail on new connection

## How-to

- Download the class/NetatmoPresenceAPI.php on your server.
- Include it in your script.
- Start it with your Netatmo account login and password.

```php
require($_SERVER['DOCUMENT_ROOT']."/path/to/NetatmoPresenceAPI.php");
$_Presence = new NetatmoPresenceAPI($Netatmo_user, $Netatmo_pass);
```

Here are functions to get actual settings:
*Change camera name by yours!*

```php
//list your cameras (name, status, etc.):
$cameras = $_Presence->getCameras();
echo "<pre>cameras:<br>".json_encode($cameras, JSON_PRETTY_PRINT)."</pre><br>";

//get last 10 events of all type.
//You can request All, or only human, animal, vehicle, movement
$answer = $_Presence->getEvents("All", $num=10);
echo "<pre>answer:<br>".json_encode($answer, JSON_PRETTY_PRINT)."</pre><br>";

//get camera settings:
$settings = $_Presence->getSettings("myCamera");
echo "<pre>settings:<br>".json_encode($settings, JSON_PRETTY_PRINT)."</pre><br>";

//get floodlight settings: will return intensity, mode, alerts
$floodlight = $_Presence->getFloodlight("myCamera");
echo "<pre>floodlight:<br>".json_encode($floodlight, JSON_PRETTY_PRINT)."</pre><br>";

//get smart zones settings:
$smartZones = $_Presence->getSmartZones("myCamera");
echo "<pre>smartZones:<br>".json_encode($smartZones, JSON_PRETTY_PRINT)."</pre><br>";
```

Here are function to CHANGE your camera settings:
*Change camera name by yours!*

```php
//SET events recording (here, only for person and other movements):
//in order: always, person, vehicle, animal, movement
$alerts = $_Presence->setRecording("myCamera", false, true, false, false, true);
echo "<pre>alerts:<br>".json_encode($alerts, JSON_PRETTY_PRINT)."</pre><br>";

//SET floodlight intensity:
$_Presence->setIntensity("myCamera", 100);

//SET floodlight mode (auto, on, off):
$floodlight = $_Presence->setFloodlight("myCamera", "auto");
echo "<pre>floodlight:<br>".json_encode($floodlight, JSON_PRETTY_PRINT)."</pre><br>";

//SET monitoring on/off
$monitoring = $_Presence->setMonitoring("myCamera", "on");
echo "<pre>monitoring:<br>".json_encode($monitoring, JSON_PRETTY_PRINT)."</pre><br>";

//SET alert level for human detection:
//0: ignore, 1: record, 2: record and notify
$alert = $_Presence->setHumanAlert(1);
echo "<pre>alert:<br>".json_encode($alert, JSON_PRETTY_PRINT)."</pre><br>";
//and:
$alert = $_Presence->setAnimalAlert(1);
$alert = $_Presence->setVehicleAlert(1);
$alert = $_Presence->setOtherAlert(1);

//SET alert time from 10h15 (always use hh:mm)
$alert = $_Presence->setAlertFrom('10:15');
echo "<pre>alert:<br>".json_encode($alert, JSON_PRETTY_PRINT)."</pre><br>";

//SET alert time to 22h00
$alert = $_Presence->setAlertTo('22:00');

/*SET SmartZones:
- Define zones as array(x, y, width, height)
- Send at least one zone
- The API check for overlapping zones and don't send them to camera if so.
*/
$zone1 = array(0, 140, 455, 938);
$zone2 = array(457, 99, 880, 979);
$zone3 = array(1339, 569, 545, 509);
$zone4 = array(1339, 158, 580, 409);
$smartZones = $_Presence->setSmartZones("myCamera", $zone1, $zone2, $zone3, $zone4);
echo "<pre>smartZones:<br>".json_encode($smartZones, JSON_PRETTY_PRINT)."</pre><br>";
```
## IFTTT

You can create an endpoint url for triggering changes from IFTTT. See IFTTTactions.php

## Changes

#### v0.2 (2017-03-16)
- New setSmartZones

#### v0.1 (2017-03-15)
- First public version.

## License

The MIT License (MIT)

Copyright (c) 2017 KiboOst

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
