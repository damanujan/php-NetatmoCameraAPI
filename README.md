<img align="right" src="/readmeAssets/PresenceAPI.jpg" width="150">

# php Netatmo Presence API

## This API allow full control of your Netatmo Presence camera

This API allow you to fully control Netatmo Presence camera settings, monitoring, floodlight (mode, intensity, records), and alerts.

**The following operations are currently supported:**

- Get and Set Camera Monitoring (on, off).
- Get and Set Camera floodlight intensity.
- Get and Set Camera floodlight mode (auto, on, off).
- Get and Set Camera floodlight auto mode (always, people, cars, animals, other).
- Get and Set Camera Smart-Zones.
- Get and Set alerts and recording (ignore, record, record and notify) for humans, vehicle, animal, other.
- Get and Set alerts hours (from, to).
- Get events (filtered by type or not).
- Get camera(s) status.


**This isn't an official API | USE AT YOUR OWN RISK!**

*This API is reverse-engineered, provided for research and development for interoperability.*

Feel free to submit an issue or pull request to add more.

<img align="right" src="/readmeAssets/requirements.jpg" width="48">

## Warning

This API will connect directly to your Netatmo account, like you do with a web browser. Regarding your account option, each connection (script execution) may send you an email alert telling that someone connected to your account!

To avoid this:

- On the web interface, go to user settings (top right).
- Go to e-mail settings.
- Turn off e-mail on new connection.

Or:

- Create a new account on Netatmo website.
- Disable connection notification for this new account.
- From your original account, invite the new account.
- Use this second account for the API!

<img align="right" src="/readmeAssets/howto.jpg" width="48">

## How-to

- Download the class/NetatmoPresenceAPI.php on your server.
- Include it in your script.
- Start it with your Netatmo account login and password.

#### Connection

```php
require($_SERVER['DOCUMENT_ROOT']."/path/to/NetatmoPresenceAPI.php");
$_Presence = new NetatmoPresenceAPI($Netatmo_user, $Netatmo_pass);
if (isset($_Presence->error)) die($_Presence->error);
```

<img align="right" src="/readmeAssets/read.jpg" width="48">

#### Get settings:
*Change camera name by yours!*

```php
//list your cameras (name, status, etc.):
$cameras = $_Presence->getCameras();
echo "<pre>cameras:<br>".json_encode($cameras, JSON_PRETTY_PRINT)."</pre><br>";

//get home settings, with alert settings:
$home = $_Presence->getHome();
echo "<pre>home:<br>".json_encode($home, JSON_PRETTY_PRINT)."</pre><br>";

//get camera / floodlight settings:
$settings = $_Presence->getCamera("myCamera");
echo "<pre>settings:<br>".json_encode($settings, JSON_PRETTY_PRINT)."</pre><br>";
echo $camera['light_mode_status'].'<br>';
echo $camera['light']['intensity'].'<br>';

//get camera smart zones:
$smartZones = $_Presence->getSmartZones("myCamera");
echo "<pre>smartZones:<br>".json_encode($smartZones, JSON_PRETTY_PRINT)."</pre><br>";

//get last 10 events of all type.
//You can request All, or only human, animal, vehicle, movement
$answer = $_Presence->getEvents("All", $num=10);
echo "<pre>answer:<br>".json_encode($answer, JSON_PRETTY_PRINT)."</pre><br>";
```

<img align="right" src="/readmeAssets/set.jpg" width="48">

#### Change your camera settings:
*Change camera name by yours!*

```php
//SET monitoring on/off
$monitoring = $_Presence->setMonitoring("myCamera", "on");
echo "<pre>monitoring:<br>".json_encode($monitoring, JSON_PRETTY_PRINT)."</pre><br>";

//SET floodlight mode (auto, on, off):
$floodlight = $_Presence->setLightMode("myCamera", "auto");
echo "<pre>floodlight:<br>".json_encode($floodlight, JSON_PRETTY_PRINT)."</pre><br>";

//SET floodlight intensity:
$_Presence->setLightIntensity("myCamera", 100);

//SET when floodlight should turn on in auto mode:
//in order: always, person, vehicle, animal, movement
$lightAutoMode= $_Presence->setLightAutoMode("myCamera", false, true, false, false, true);
echo "<pre>lightAutoMode:<br>".json_encode($lightAutoMode, JSON_PRETTY_PRINT)."</pre><br>";

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

<img align="right" src="/readmeAssets/IF.jpg" width="48">

## IFTTT

You can create an endpoint url for triggering changes from IFTTT.

Basically, you create a php script that will get url parameters and trigger actions regarding these parameters. So in IFTTT, you can trigger same script with different parameters.

See IFTTTactions.php as an example.

<img align="right" src="/readmeAssets/changes.jpg" width="48">

## Changes

#### v0.5 (2017-04-02)

- Code breaking! Some functions names where confusing, read how-to!
- New: getHome() return home alerts
- New: getCamera('my camera') return camera and light settings
- Changes setLightMode(), setLightIntensity(), setLightAutoMode()

#### v0.2 (2017-03-16)
- New setSmartZones

#### v0.1 (2017-03-15)
- First public version.

<img align="right" src="/readmeAssets/mit.jpg" width="48">

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

