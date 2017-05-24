<?php
/*

https://github.com/KiboOst/php-NetatmoCameraAPI

*/

class NetatmoCameraAPI {

    public $_version = "1.02";

    //user functions======================================================
    //GET:
    public function getHome() //refresh home datas
    {
        $this->getDatas();
        return $this->_home;
    }

    public function getCameraSettings($camera) //Presence - Welcome
    {
        foreach ($this->_cameras as $thisCamera)
        {
            if ($thisCamera['name'] == $camera) return $this->getCameraConfig($camera);
        }
        return array('result'=>null, 'error' => 'Unfound camera');
    }

    //Presence:
    public function getSmartZones($camera) //Presence
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        if ($camera['type'] != 'Presence')
        {
            return array('result'=>null, 'error' => 'Unsupported camera for getSmartZones()');
        }

        $vpn = $camera['vpn'];
        $command = '/command/smart_zone_get_config';
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return $answer;
    }

    public function getOutdoorEvents($requestType="All", $num=5) //Presence
    {
        //human, animal, vehicle, All
        //will return the last event of defined type as array of [title, snapshotURL, vignetteURL]
        if (is_null($this->_fullDatas)) $this->getDatas();
        if (is_null($this->_cameras)) $this->getCamerasDatas();
        if($requestType="all") $requestType="All";

        $cameraEvents = $this->_outdoorEvents;
        $returnEvents = array();
        for ($i=0; $i <= $num ;$i++)
        {
            //avoid iterating more than there is!
            if (isset($cameraEvents[$i])) $thisEvent = $cameraEvents[$i];
            else break;

            $id = $thisEvent['id'];
            $time = $thisEvent['time'];
            $camId = $thisEvent['camera_id'];
            foreach ($this->_cameras as $cam)
                {
                    if ($cam['id'] == $camId)
                    {
                        $camName = $cam['name'];
                        $camVPN = $cam['vpn'];
                        break;
                    }
                }

            if ( isset($thisEvent['event_list']) ) $eventList = $thisEvent['event_list'];
            else continue;
            $isAvailable = $thisEvent['video_status'];
            for ($j=0; $j < count($eventList) ;$j++)
            {
                $eventType = 'MainEvent';
                if ($j > 0) $eventType = 'SubEvent';
                $thisSubEvent = $thisEvent['event_list'][$j];
                $subType = $thisSubEvent['type'];
                $subMsg = $thisSubEvent['message'];
                if ( (strpos($subType, $requestType) !== false) or ($requestType == 'All') )
                    {
                        $subTime = $thisSubEvent['time'];
                        $subTime = date('d-m-Y H:i:s', $subTime);

                        if (isset($thisSubEvent['snapshot']['filename']))  //other vignette of same event!
                        {
                            $snapshotURL = $camVPN.'/'.$thisSubEvent['snapshot']['filename'];
                            $vignetteURL = $camVPN.'/'.$thisSubEvent['vignette']['filename'];
                        }else{
                            $snapshotID = $thisSubEvent['snapshot']['id'];
                            $snapshotKEY = $thisSubEvent['snapshot']['key'];
                            $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;

                            $vignetteID = $thisSubEvent['vignette']['id'];
                            $vignetteKEY = $thisSubEvent['vignette']['key'];
                            $vignetteURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$vignetteID.'&key='.$vignetteKEY;
                        }

                        $returnThis = array();
                        $returnThis['title'] = $subMsg . ' | '.$subTime.' | '.$camName;
                        $returnThis['type'] = $eventType;
                        $returnThis['snapshotURL'] = $snapshotURL;
                        $returnThis['vignetteURL'] = $vignetteURL;

                        $returnThis['camera_id'] = $camId;
                        $returnThis['event_id'] = $id;
                        array_push($returnEvents, $returnThis);
                    }
            }
        }

        return $returnEvents;
    }

    public function getTimeLapse($camera, $folderPath=__DIR__) //Presence
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        if ($camera['type'] != 'Presence')
        {
            return array('result'=>null, 'error' => 'Unsupported camera for getTimeLapse()');
        }

        date_default_timezone_set($this->_timeZone);
        $now = date('d_m_Y_H_i');
        $filename = 'presence_timelapse_'.$now.'.mp4';
        $filePath = $folderPath.'/'.$filename;

        //write it to file:
        if (is_writable($folderPath))
        {
            $vpn = $camera['vpn'];
            $command = '/command/dl/timelapse&filename='.$filename;
            $url = $vpn.$command;

            $answer = $this->_request('GET', $url);

            $put = file_put_contents($filePath, $answer);
            if ($put) return array('result'=>$filePath);
        }
        return array('result'=>$datasArray, 'error'=>'Unable to write file!');

        return array('result'=>null, 'error' => 'Can\'t write in script folder');
    }

    //Welcome:
    public function getPerson($name) //Welcome
    {
        if ( is_string($name) ) $person = $this->getPersonByName($name);
        return $person;
    }

    public function getPersonsAtHome() //Welcome
    {
        $atHome = array();
        foreach ($this->_persons as $thisPerson)
        {
            if ($thisPerson['out_of_sight'] == false) array_push($atHome, $thisPerson);
        }
        return array('result'=>$atHome);
    }

    public function isHomeEmpty() //Welcome
    {
        $atHome = $this->getPersonsAtHome();
        if (count($atHome)==0) return true;
        return false;
    }

    public function getIndoorEvents($num=5) //Welcome
    {
        if (is_null($this->_fullDatas)) $this->getDatas();
        if (is_null($this->_cameras)) $this->getCameras();

        $cameraEvents = $this->_indoorEvents;
        $returnEvents = array();
        for ($i=0; $i <= $num ;$i++)
        {
            //avoid iterating more than there is!
            if (isset($cameraEvents[$i])) $thisEvent = $cameraEvents[$i];
            else break;

            $id = $thisEvent['id'];
            $type = $thisEvent['type'];
            $time = $thisEvent['time'];
            $date = date('d-m-Y H:i:s', $time);
            $camId = $thisEvent['camera_id'];
            $message = $thisEvent['message'];
            foreach ($this->_cameras as $cam)
                {
                    if ($cam['id'] == $camId)
                    {
                        $camName = $cam['name'];
                        $camVPN = $cam['vpn'];
                        break;
                    }
                }

            $returnThis = array();
            $returnThis['title'] = $message . ' | '.$date.' | '.$camName;
            $returnThis['type'] = $type;
            $returnThis['time'] = $thisEvent['time'];
            $returnThis['date'] = $date;


            if (isset($thisEvent['person_id'])) $returnThis['person_id'] = $thisEvent['person_id'];

            if (isset($thisEvent['snapshot']))
            {
                $snapshot = $thisEvent['snapshot'];
                $snapshotID = $snapshot['id'];
                $snapshotKEY = $snapshot['key'];
                $snapshotURL = 'https://api.netatmo.com/api/getcamerapicture?image_id='.$snapshotID.'&key='.$snapshotKEY;
                $returnThis['snapshotURL'] = $snapshotURL;
            }

            if (isset($thisEvent['is_arrival'])) $returnThis['is_arrival'] = $thisEvent['is_arrival'];
            $returnThis['camera_id'] = $camId;
            $returnThis['event_id'] = $id;

            array_push($returnEvents, $returnThis);
        }
        return $returnEvents;
    }

    public function getFtpConfig($camera) //Presence - Welcome
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        $vpn = $camera['vpn'];
        $command = '/command/ftp_get_config';
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return $answer;
    }

    //return fulldatas:
    public function getNetatmoDatas()
    {
        return $this->_fullDatas;
    }


    //SET:
    public function setMonitoring($camera, $mode='on') //Presence - Welcome
    {
        //on off
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        $vpn = $camera['vpn'];
        $command = '/command/changestatus?status='.$mode;
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setFTPenable($camera, $state=true) //Presence - Welcome
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        $mode = null;
        if ($state == true) $mode = 1;
        if ($state == false) $mode = 0;
        if (!isset($mode)) return array('error'=>'Use true or false for FTP state.');


        $vpn = $camera['vpn'];
        $config = '{"on_off":'.$mode.'}';
        $command = '/command/ftp_set_config?config=';
        $url = $vpn.$command.urlencode($config);

        echo 'url:', $url, "<br>";

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    //___Presence:
    public function setHumanOutAlert($value=1) //Presence
    {
        $mode = null;
        if ($value == 0) $mode = 'ignore';
        if ($value == 1) $mode = 'record';
        if ($value == 2) $mode = 'record_and_notify';
        if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

        $setting = 'presence_settings[presence_record_humans]';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }
    public function setAnimalOutAlert($value=1) //Presence
    {
        $mode = null;
        if ($value == 0) $mode = 'ignore';
        if ($value == 1) $mode = 'record';
        if ($value == 2) $mode = 'record_and_notify';
        if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

        $setting = 'presence_settings[presence_record_animals]';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }
    public function setVehicleOutAlert($value=1) //Presence
    {
        $mode = null;
        if ($value == 0) $mode = 'ignore';
        if ($value == 1) $mode = 'record';
        if ($value == 2) $mode = 'record_and_notify';
        if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

        $setting = 'presence_settings[presence_record_vehicles]';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }
    public function setOtherOutAlert($value=1) //Presence
    {
        $mode = null;
        if ($value == 0) $mode = 'ignore';
        if ($value == 1) $mode = 'record';
        if ($value == 2) $mode = 'record_and_notify';
        if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

        $setting = 'presence_settings[presence_record_movements]';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }
    public function setOutAlertFrom($from='00:00') //Presence
    {
        $var = explode(':', $from);
        if (count($var)==2)
        {
            $h = $var[0];
            $m = $var[1];
            $timeString = $h*3600 + $m*60;
        }
        else
        {
            return array('error'=>'Use time as string "10:30"');
        }

        $setting = 'presence_settings[presence_notify_from]';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$timeString;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }
    public function setOutAlertTo($to='23:59') //Presence
    {
        $var = explode(':', $from);
        if (count($var)==2)
        {
            $h = $var[0];
            $m = $var[1];
            $timeString = $h*3600 + $m*60;
        }
        else
        {
            return array('error'=>'Use time as string "10:30"');
        }

        $setting = 'presence_settings[presence_notify_to]';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$timeString;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setSmartZones($camera, $zone1=null, $zone2=null, $zone3=null, $zone4=null) //Presence
    {
        //zone as array(x, y, width, height)
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        if ($camera['type'] != 'Presence')
        {
            return array('result'=>null, 'error' => 'Unsupported camera for setSmartZones()');
        }

        $zones = func_get_args();
        array_shift($zones); //remove $camera

        //Netatmo server won't check overlapping zones, so do it before setting them:
        for ($i=0; $i<=count($zones)-1; $i++)
        {
            $z1x = $zones[$i][0];
            $z1x2 = $z1x + $zones[$i][2];
            $z1y = $zones[$i][1];
            $z1y2 = $z1y + $zones[$i][3];

            for ($j=0; $j<=count($zones)-1; $j++)
            {
                if ($j == $i) continue;
                $z2x = $zones[$j][0];
                $z2x2 = $z2x + $zones[$j][2];
                $z2y = $zones[$j][1];
                $z2y2 = $z2y + $zones[$j][3];

                //so ??
                if ( ($z1x < $z2x2) and ($z1x2 > $z2x) and ($z1y < $z2y2) and ($z1y2 > $z2y) )
                {
                    $j++;
                    $i++;
                    return array('result'=>null, 'error'=>"Can't set overlapping zones (zone".$j."|zone".$i."). Well, I can, but this won't work!");
                }
            }
        }

        return array('result'=>null);

        $config = '{"version":0,"max_number_of_zones":4,"zones_count":'.count($zones).',"ref_size":[1920,1080],"zones":[';
        foreach($zones as $zone)
        {
            $conf = '{"x":'.$zone[0].',"y":'.$zone[1].',"width":'.$zone[2].',"height":'.$zone[3].'}';
            $config .= $conf.',';

        }
        $config = rtrim($config,","); //remove last ','
        $config  .= ']}';

        $vpn = $camera['vpn'];
        $command = '/command/smart_zone_set_config?config=';
        $url = $vpn.$command.urlencode($config);

        $answer = $this->_request('POST', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setLightMode($camera, $mode='auto') //Presence
    {
        //auto on off
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        if ($camera['type'] != 'Presence')
        {
            return array('result'=>null, 'error' => 'Unsupported camera for setLightMode()');
        }

        $vpn = $camera['vpn'];
        $config = '{"mode":"'.$mode.'"}';
        $command = '/command/floodlight_set_config?config=';
        $url = $vpn.$command.urlencode($config);

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setLightIntensity($camera, $intensity=100) //Presence
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        if ($camera['type'] != 'Presence')
        {
            return array('result'=>null, 'error' => 'Unsupported camera for setLightIntensity()');
        }

        $vpn = $camera['vpn'];
        $config = '{"intensity":"'.$intensity.'"}';
        $command = '/command/floodlight_set_config?config=';
        $url = $vpn.$command.urlencode($config);

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setLightAutoMode($camera, $always=true, $person=true, $vehicle=true, $animal=true, $movement=true) //Presence
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        if ($camera['type'] != 'Presence')
        {
            return array('result'=>null, 'error' => 'Unsupported camera for setLightAutoMode()');
        }

        $config = '{"night":{
                            "always":'.var_export($always,true).',
                            "person":'.var_export($person,true).',
                            "vehicle":'.var_export($vehicle,true).',
                            "animal":'.var_export($animal,true).',
                            "movement":'.var_export($movement,true).'
                        }}';

        $vpn = $camera['vpn'];
        $command = '/command/floodlight_set_config?config=';
        $url = $vpn.$command.urlencode($config);

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    //___Welcome:
    public function setPersonAway($person) //Welcome
    {
        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];

        $url = $this->_urlHost.'/api/setpersonsaway';
        $post = 'home_id='.$this->_home['id'].'&'.'person_id='.$personID;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setPersonAtHome($person) //Welcome
    {
        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];

        $url = $this->_urlHost.'/api/setpersonshome';
        $post = 'home_id='.$this->_home['id'].'&'.'person_ids=["'.$personID.'"]';

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setHomeEmpty() //Welcome
    {
        $url = $this->_urlHost.'/api/setpersonsaway';
        $post = 'home_id='.$this->_home['id'];

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setUnknownFacesInAlert($value='always') //Welcome
    {
        $mode = null;
        if ($value == 'nobody') $mode = 'empty';
        if ($value == 'always') $mode = 'always';
        if (!isset($mode)) return array('error'=>'Use "nobody", "always" as parameter.');

        $setting = 'notify_unknowns';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setAwayHomeAfter($minutes=240) //Welcome
    {
        $seconds = $minutes*60;
        $setting = 'gone_after';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$seconds;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setMotionInRecord($value='always') //Welcome
    {
        $mode = null;
        if ($value == 'never') $mode = 'never';
        if ($value == 'nobody') $mode = 'empty';
        if ($value == 'always') $mode = 'always';
        if (!isset($mode)) return array('error'=>'Use "never", "nobody", "always" as parameter.');

        $setting = 'record_movements';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setMotionInAlert($value='always') //Welcome
    {
        $mode = null;
        if ($value == 'never') $mode = 'never';
        if ($value == 'nobody') $mode = 'empty';
        if ($value == 'always') $mode = 'always';
        if (!isset($mode)) return array('error'=>'Use "never", "nobody", "always" as parameter.');

        $setting = 'notify_movements';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setPersonInRecord($person, $value='arrive') //Welcome
    {
        $mode = null;
        if ($value == 'never') $mode = 'never';
        if ($value == 'arrive') $mode = 'on_arrival';
        if ($value == 'always') $mode = 'always';
        if (!isset($mode)) return array('error'=>'Use "never", "arrive", "always" as parameter.');

        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];

        $url = $this->_urlHost.'/api/updateperson';
        $post = 'home_id='.$this->_home['id'].'&person_id='.$personID.'&pseudo='.$person['pseudo'].'&record_rule='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setPersonArriveAlert($person, $alert=1) //Welcome
    {
        $mode = null;
        if ($alert == 0) $mode = 'false';
        if ($alert == 1) $mode = 'true';
        if (!isset($mode)) return array('error'=>'Use 0 (disable) or 1 (enable) as parameter.');

        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];

        $url = $this->_urlHost.'/api/updateperson';
        $post = 'home_id='.$this->_home['id'].'&person_id='.$personID.'&pseudo='.$person['pseudo'].'&notify_on_arrival='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setPersonInAlertFromTo($person, $from='00:00', $to='23:59') //Welcome
    {
        if ( is_string($person) ) $person = $this->getPersonByName($person);
        if ( isset($person['error']) ) return $person;
        $personID = $person['id'];

        $var = explode(':', $from);
        if (count($var)==2)
        {
            $h = $var[0];
            $m = $var[1];
            $from = $h*3600 + $m*60;
        }
        else
        {
            return array('result'=>null, 'error'=>'Use time as string "10:30"');
        }

        $var = explode(':', $to);
        if (count($var)==2)
        {
            $h = $var[0];
            $m = $var[1];
            $to = $h*3600 + $m*60;
        }
        else
        {
            return array('result'=>null, 'error'=>'Use time as string "10:30"');
        }

        $url = $this->_urlHost.'/api/updateperson';
        $post = 'home_id='.$this->_home['id'].'&person_id='.$personID.'&pseudo='.$person['pseudo'].'&notification_begin='.$from.'&notification_end='.$to;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setAlarmInDetected($value='never') //Welcome
    {
        $mode = null;
        if ($value == 'never') $mode = 'never';
        if ($value == 'nobody') $mode = 'empty';
        if ($value == 'always') $mode = 'always';
        if (!isset($mode)) return array('error'=>'Use "never", "nobody", "always" as parameter.');

        $setting = 'record_alarms';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function deleteEvent($eventId)
    {
        $events = $this->_fullDatas['body']['homes'][$this->_homeID]['events'];
        foreach ($events as $event)
        {
            if ($event['id'] == $eventId)
            {
                $camId = $event['camera_id'];
                $url = $this->_urlHost.'/api/deleteevent';
                $post = 'home_id='.$this->_home['id'].'&camera_id='.$camId.'&event_id='.$eventId;

                $answer = $this->_request('POST', $url, $post);
                $answer = json_decode($answer, true);
                return array('result'=>$answer);
            }
        }
        return array('result'=>null, 'error' => 'Can\'t find this event ID');
    }

    //not yet implemented by Netatmo!
    public function setAnimalInAlert($value='never') //Welcome
    {
        $mode = null;
        if ($value == 'never') $mode = 'never';
        if ($value == 'nobody') $mode = 'empty';
        if ($value == 'always') $mode = 'always';
        if (!isset($mode)) return array('error'=>'Use "never", "nobody", "always" as parameter.');

        $setting = 'notify_animals';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    public function setAnimalInRecord($value='never') //Welcome
    {
        $mode = null;
        if ($value == 'never') $mode = 'never';
        if ($value == 'nobody') $mode = 'empty';
        if ($value == 'always') $mode = 'always';
        if (!isset($mode)) return array('error'=>'Use "never", "nobody", "always" as parameter.');

        $setting = 'record_animals';
        $url = $this->_urlHost.'/api/updatehome';
        $post = 'home_id='.$this->_home['id'].'&'.$setting.'='.$mode;

        $answer = $this->_request('POST', $url, $post);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }


    //internal functions==================================================
    protected function getDatas($eventNum=100) //request home datas
    {
        //get homedata
        $url = $this->_urlHost.'/api/gethomedata'."&size=".$eventNum;
        $answer = $this->_request('POST', $url);

        $jsonDatas = json_decode($answer, true);
        $this->_fullDatas = $jsonDatas;

        if ($this->_homeID == -1)
        {
            $var = $this->getHomeByName();
            if (!$var == true) return $var;
        }

        $homedata = $this->_fullDatas['body']['homes'][$this->_homeID];
        $data = array(
                'id' => $homedata['id'],
                'name' => $homedata['name'],
                'share_info' => $homedata['share_info'],
                'gone_after' => $homedata['gone_after'],
                'smart_notifs' => $homedata['smart_notifs'],
                'presence_record_humans' => $homedata['presence_record_humans'], //Presence
                'presence_record_vehicles' => $homedata['presence_record_vehicles'], //Presence
                'presence_record_animals' => $homedata['presence_record_animals'], //Presence
                'presence_record_alarms' => $homedata['presence_record_alarms'], //Presence
                'presence_record_movements' => $homedata['presence_record_movements'], //Presence
                'presence_notify_from' => gmdate('H:i', $homedata['presence_notify_from']), //Presence
                'presence_notify_to' => gmdate('H:i', $homedata['presence_notify_to']), //Presence
                'presence_enable_notify_from_to' => $homedata['presence_enable_notify_from_to'], //Presence
                'notify_movements' => $homedata['notify_movements'], //welcome
                'record_movements' => $homedata['record_movements'], //welcome
                'notify_unknowns' => $homedata['notify_unknowns'], //welcome
                'record_alarms' => $homedata['record_alarms'], //welcome
                'record_animals' => $homedata['record_animals'], //welcome
                'notify_animals' => $homedata['notify_animals'], //welcome
                'events_ttl' => $homedata['events_ttl'], //welcome
                'place' => $homedata['place']
                );
        $this->_home = $data;
        $this->_homeName = $homedata['name'];
        $this->_timeZone = $homedata['place']['timezone'];

        //get Persons:
        $this->getPersons();

        return true;
    }

    protected function getPersons() //Welcome
    {
        if (is_null($this->_fullDatas)) $this->getDatas();
        $homeDatas = $this->_fullDatas;

        $personsArray = array();
        if ( isset($homeDatas['body']['homes'][$this->_homeID]['persons']) )
        {
            $persons = $homeDatas['body']['homes'][$this->_homeID]['persons'];
            foreach ($persons as $person)
            {
                $thisPerson = array();
                $pseudo = 'Unknown';
                if ( isset($person['pseudo']) ) $pseudo = $person['pseudo'];
                $thisPerson['pseudo'] = $pseudo;
                $thisPerson['id'] = $person['id'];
                $lastseen = $person['last_seen'];
                if ($lastseen == 0) $thisPerson['last_seen'] = 'Been long';
                else $thisPerson['last_seen'] = date("d-m-Y H:i:s", $person['last_seen']);
                $thisPerson['out_of_sight'] = $person['out_of_sight'];
                $thisPerson['record'] = $person['record'];
                $thisPerson['notify_on_arrival'] = $person['notify_on_arrival'];
                $thisPerson['notification_begin'] = $person['notification_begin'];
                $thisPerson['notification_end'] = $person['notification_end'];
                array_push($personsArray, $thisPerson);
            }

            $this->_persons = $personsArray;
            return $personsArray;
        }
        else return array('None');
    }

    protected function getPersonByName($name) //Welcome
    {
        if (empty($this->_persons)) return array('result'=>null, 'error' => 'No person defined in this home.');

        foreach ($this->_persons as $thisPerson)
        {
            if ($thisPerson['pseudo'] == $name) return $thisPerson;
        }
        return array('result'=>null, 'error' => 'Unfound person');
    }

    protected function getHomeByName()
    {
        $fullData = $this->_fullDatas['body']['homes'];
        $idx = 0;
        foreach ($fullData as $home)
        {
            if ($home['name'] == $this->_homeName)
            {
                $this->_homeID = $idx;
                return true;
            }
            $idx ++;
        }
        $this->error = "Can't find home named ".$this->_homeName;
    }

    protected function getCamByName($name) //Presence - Welcome
    {
        foreach ($this->_cameras as $thisCamera)
        {
            if ($thisCamera['name'] == $name) return $thisCamera;
        }
        return array('result'=>null, 'error' => 'Unfound camera');
    }

    protected function getCamerasDatas() //Presence - Welcome
    {
        if (is_null($this->_fullDatas)) $this->getDatas();

        $allCameras = array();
        foreach ($this->_fullDatas['body']['homes'][$this->_homeID]['cameras'] as $thisCamera)
        {
            //live and snapshots:
            $cameraVPN = (isset($thisCamera['vpn_url']) ? $thisCamera['vpn_url'] : null);
            $isLocal = (isset($thisCamera['is_local']) ? $thisCamera['is_local'] : false);

            $cameraSnapshot = null;
            $cameraLive = null;

            if ($cameraVPN != null)
            {
                $cameraLive = ($isLocal == false ? $cameraVPN.'/live/index.m3u8' : $cameraVPN.'/live/index_local.m3u8');
                $cameraSnapshot = $cameraVPN.'/live/snapshot_720.jpg';
            }

            //which camera model:
            if ($thisCamera['type'] == 'NOC') //Presence
            {
                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'firmware' => $thisCamera['firmware'],
                                'vpn' => $cameraVPN,
                                'snapshot' => $cameraSnapshot,
                                'live' => $cameraLive,
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'light_mode_status' => $thisCamera['light_mode_status'],
                                'is_local' => $isLocal,
                                'timelapse_available' => $thisCamera['timelapse_available'],
                                'type' => 'Presence'
                                );

                array_push($allCameras, $camera);
            }
            elseif ($thisCamera['type'] == 'NACamera') //Welcome:
            {
                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'vpn' => $cameraVPN,
                                'snapshot' => $cameraSnapshot,
                                'live' => $cameraLive,
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'is_local' => $isLocal,
                                'type' => 'Welcome'
                                );

                array_push($allCameras, $camera);
            }
        }
        $this->_cameras = $allCameras;

        //sort events:
        $outdoorCams = array();
        $indoorCams = array();
        foreach ($this->_cameras as $cam)
        {
            if ($cam['type'] == 'Presence') array_push($outdoorCams, $cam['id']);
            if ($cam['type'] == 'Welcome') array_push($indoorCams, $cam['id']);
        }
        $cameraEvents = $this->_fullDatas['body']['homes'][$this->_homeID]['events'];
        foreach ($cameraEvents as $event)
        {
            if (in_array($event['camera_id'], $outdoorCams)) array_push($this->_outdoorEvents, $event);
            if (in_array($event['camera_id'], $indoorCams)) array_push($this->_indoorEvents, $event);
        }

        return $allCameras;
    }

    protected function getCameraConfig($camera) //Presence - Welcome
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        //get camera conf:
        $vpn = $camera['vpn'];
        $command = '/command/getsetting';
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);

        if ($camera['type'] == 'Presence')
        {
            $camera['error_status'] = $answer['error']['code'].' '.$answer['error']['message'];
            $camera['image_orientation'] = $answer['conf']['image_orientation'];
            $camera['audio'] = $answer['conf']['audio'];

            //get camera light settings:
            if ($camera['error_status'] == '200 OK')
            {
                $command = '/command/floodlight_get_config';
                $url = $vpn.$command;

                $answer = $this->_request('GET', $url);
                $answer = json_decode($answer, true);

                $camera['light'] = $answer;
            }
        }
        elseif ($camera['type'] == 'Welcome')
        {
            $camera['error_status'] = $answer['error']['code'].' '.$answer['error']['message'];
            $camera['mirror'] = $answer['conf']['mirror'];
            $camera['audio'] = $answer['conf']['audio'];
            $camera['irmode'] = $answer['conf']['irmode'];
            $camera['led_on_live'] = $answer['conf']['led_on_live'];
        }

        return $camera;
    }

    //calling functions===================================================
    protected function _request($method, $url, $post=null)
    {
        if (!isset($this->_curlHdl))
        {
            $this->_curlHdl = curl_init();

            curl_setopt($this->_curlHdl, CURLOPT_COOKIEJAR, '');
            curl_setopt($this->_curlHdl, CURLOPT_COOKIEFILE, '');

            curl_setopt($this->_curlHdl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->_curlHdl, CURLOPT_HEADER, true);
            curl_setopt($this->_curlHdl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->_curlHdl, CURLOPT_REFERER, 'http://www.google.com/');
            curl_setopt($this->_curlHdl, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:51.0) Gecko/20100101 Firefox/51.0');
            curl_setopt($this->_curlHdl, CURLOPT_ENCODING , '');
        }

        curl_setopt($this->_curlHdl, CURLOPT_URL, $url);

        if ($method == 'POST')
        {
            curl_setopt($this->_curlHdl, CURLOPT_POST, true);

            //add csrf to post data:
            if ( isset($post)) $post .= '&'.$this->_csrfName.'='.$this->_csrf;
            curl_setopt($this->_curlHdl, CURLOPT_POSTFIELDS, $post);

            //should have token after login:
            if (isset($this->_token))
            {
                curl_setopt($this->_curlHdl, CURLOPT_HEADER, false);
                curl_setopt($this->_curlHdl, CURLOPT_HTTPHEADER, array(
                                                        'Connection: keep-alive',
                                                        'Content-Type: application/x-www-form-urlencoded',
                                                        'Authorization: Bearer '.$this->_token
                                                        )
                                                    );
            }
        }
        else
        {
            curl_setopt($this->_curlHdl, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($this->_curlHdl);

        //$info   = curl_getinfo($this->_curlHdl);
        //echo "<pre>cURL info".json_encode($info, JSON_PRETTY_PRINT)."</pre><br>";

        if ($response === false)
        {
            echo 'cURL error: ' . curl_error($this->_curlHdl);
        }
        else
        {
            return $response;
        }
    }

    //functions authorization=============================================
    public $error = null;
    public $_csrf = null;
    public $_csrfName = null;
    public $_token = null;
    public $_homeID = 0;
    public $_homeName = null;
    public $_timeZone = null;
    public $_home = null;
    public $_cameras = array();
    public $_persons = array();

    protected $_fullDatas;
    protected $_indoorEvents = array();
    protected $_outdoorEvents = array();

    protected $_Netatmo_user;
    protected $_Netatmo_pass;
    protected $_urlHost = 'https://my.netatmo.com';
    protected $_urlAuth = 'https://auth.netatmo.com';
    protected $_curlHdl = null;

    protected function getCSRF($answerString)
    {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $answerString, $matches);
        $cookies = array();
        foreach($matches[1] as $item)
        {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        $cookie = null;
        $cookiename = null;
        foreach ($cookies as $name => $value)
        {
            if (strpos($name, 'csrf') !== false)
            {
                $cookiename = str_replace('netatmocom', '', $name);
                $cookiename = str_replace('_cookie_na', '_netatmo', $cookiename);
                return array($cookiename, $value);
            }
        }
        return false;
    }

    protected function connect()
    {
        //get csrf, required for login and all post requests:
        $url = $this->_urlHost;
        $answer = $this->_request('GET', $url);

        $var = $this->getCSRF($answer);
        if ($var != false)
        {
            $this->_csrfName = $var[0];
            $this->_csrf = $var[1];
        }
        else
        {
            $this->error = "Couldn't find Netatmo CSRF.";
            return false;
        }

        //get token, required for all post requests as bearer auth:
        $url = $this->_urlAuth.'/en-US/access/login';
        $post = "mail=".$this->_Netatmo_user."&pass=".$this->_Netatmo_pass."&log_submit=Connexion";
        $answer = $this->_request('POST', $url, $post);

        $cookies = explode('Set-Cookie: ', $answer);
        foreach($cookies as $var)
        {
            if (strpos($var, 'netatmocomaccess_token') === 0)
            {
                $cookieValue = explode(';', $var)[0];
                $cookieValue = str_replace('netatmocomaccess_token=', '', $cookieValue);
                $token = urldecode($cookieValue);
                if ($token != 'deleted')
                {
                    $this->_token = $token;
                    return true;
                }
            }

        }
        //unfound valid token:
        $this->error = "Couldn't find Netatmo token.";
        return false;
    }

    function __construct($Netatmo_user, $Netatmo_pass, $homeName=0)
    {
        $this->_Netatmo_user = urlencode($Netatmo_user);
        $this->_Netatmo_pass = urlencode($Netatmo_pass);
        if ($homeName !== 0)
        {
            $this->_homeName = $homeName;
            $this->_homeID = -1;
        }

        if ($this->connect() == true)
        {
            if ($this->getDatas() == true) $this->getCamerasDatas();
        }
    }
} //NetatmoCameraAPI end
?>
