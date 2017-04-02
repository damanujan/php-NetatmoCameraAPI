<?php
/*

https://github.com/KiboOst/php-NetatmoPresenceAPI

*/

class NetatmoPresenceAPI {

    public $_version = "0.5";

    //user functions======================================================
    //GET:
    public function getHome()
    {
        $this->getDatas();
        return $this->_home;
    }

    public function getCameras()
    {
        if(!isset($this->_cameras[0]['light'])) $this->getCamerasDatas(true);
        return $this->_cameras;
    }

    public function getCamera($camera)
    {
        if(!isset($this->_cameras[0]['light'])) $this->getCamerasDatas(true);
        foreach ($this->_cameras as $thisCamera)
        {
            if ($thisCamera['name'] == $camera) return $thisCamera;
        }
        return array('result'=>null, 'error' => 'Unfound camera');
    }

    public function getSmartZones($camera)
    {
        if ( is_string($camera) ) $camera = $this->getCamera($camera);
        if ( isset($camera['error']) ) return $camera;

        $vpn = $camera['vpn'];
        $command = '/command/smart_zone_get_config';
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return $answer;
    }

    public function getEvents($requestType="All", $num=1) //human, animal, vehicle, All
    {
        //will return the last event of defined type as array of [title, snapshotURL, vignetteURL]
        if (is_null($this->_fullDatas)) $this->getDatas();
        if (is_null($this->_cameras)) $this->getCameras();

        $cameraEvents = $this->_fullDatas['body']['homes'][0]['events'];
        $numEvents = count($cameraEvents);
        $counts = $num;
        if ($numEvents < $counts) $counts == $numEvents;

        $returnEvents = array();
        for ($i=0; $i < $counts ;$i++)
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
                        $returnThis['snapshotURL'] = $snapshotURL;
                        $returnThis['vignetteURL'] = $vignetteURL;
                        array_push($returnEvents, $returnThis);
                    }
            }
        }

        return $returnEvents;
    }

    //SET:
    //for alerts: 0=ignore, 1=record, 2=record and notify
    public function setHumanAlert($value=1)
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

    public function setAnimalAlert($value=1)
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

    public function setVehicleAlert($value=1)
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

    public function setOtherAlert($value=1)
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

    public function setAlertFrom($from='00:00')
    {
        $var = explode(':', $from);
        if(count($var)==2)
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

    public function setAlertTo($to='23:59')
    {
        $var = explode(':', $from);
        if(count($var)==2)
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

    public function setSmartZones($camera, $zone1=null, $zone2=null, $zone3=null, $zone4=null) //zone as array(x, y, width, height)
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

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

    //monitoring:
    public function setMonitoring($camera, $mode='on') //on off
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        $vpn = $camera['vpn'];
        $command = '/command/changestatus?status='.$mode;
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    //floodlight settings:
    public function setLightMode($camera, $mode='auto') //auto on off
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        $vpn = $camera['vpn'];
        $config = '{"mode":"'.$mode.'"}';
        $command = '/command/floodlight_set_config?config=';
        $url = $vpn.$command.urlencode($config);

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    //floodlight intensity
    public function setLightIntensity($camera, $intensity=100) //100
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

        $vpn = $camera['vpn'];
        $config = '{"intensity":"'.$intensity.'"}';
        $command = '/command/floodlight_set_config?config=';
        $url = $vpn.$command.urlencode($config);

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);
        return array('result'=>$answer);
    }

    //floodlight turning on in auto mode for:
    public function setLightAutoMode($camera, $always=true, $person=true, $vehicle=true, $animal=true, $movement=true) // true false
    {
        if ( is_string($camera) ) $camera = $this->getCamByName($camera);
        if ( isset($camera['error']) ) return $camera;

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


    //internal functions==================================================
    public function getDatas($eventNum=10) //request home datas
    {
        //get homedata
        $url = $this->_urlHost.'/api/gethomedata'."&size=".$eventNum;
        $answer = $this->_request('POST', $url);

        $jsonDatas = json_decode($answer, true);
        $this->_fullDatas = $jsonDatas;

        $homedata = $this->_fullDatas['body']['homes'][0];
        $data = array(
                'id' => $homedata['id'],
                'name' => $homedata['name'],
                'smart_notifs' => $homedata['smart_notifs'],
                'presence_record_humans' => $homedata['presence_record_humans'],
                'presence_record_vehicles' => $homedata['presence_record_vehicles'],
                'presence_record_animals' => $homedata['presence_record_animals'],
                'presence_record_alarms' => $homedata['presence_record_alarms'],
                'presence_record_movements' => $homedata['presence_record_movements'],
                'presence_notify_from' => gmdate('H:i', $homedata['presence_notify_from']),
                'presence_notify_to' => gmdate('H:i', $homedata['presence_notify_to']),
                'presence_enable_notify_from_to' => $homedata['presence_enable_notify_from_to'],
                'place' => $homedata['place']
                );
        $this->_home = $data;
    }

    protected function getCamByName($name)
    {
        foreach ($this->_cameras as $thisCamera)
        {
            if ($thisCamera['name'] == $name) return $thisCamera;
        }
        return array('result'=>null, 'error' => 'Unfound camera');

    }

    protected function getCamerasDatas($getSettings=false)
    {
        if (is_null($this->_fullDatas)) $this->getDatas();

        $allCameras = array();
        foreach ($this->_fullDatas['body']['homes'][0]['cameras'] as $thisCamera)
        {
            if( $thisCamera['type'] == 'NOC' ) //Presence
            {
                $cameraVPN = $thisCamera['vpn_url'];
                if ($thisCamera['is_local'] == false)
                {
                    $cameraLive = $cameraVPN.'/live/index.m3u8';
                }
                else
                {
                    $cameraLive = $cameraVPN.'/live/index_local.m3u8';
                }

                $camera = array('name' => $thisCamera['name'],
                                'id' => $thisCamera['id'],
                                'firmware' => $thisCamera['firmware'],
                                'vpn' => $cameraVPN,
                                'snapshot' => $cameraVPN.'/live/snapshot_720.jpg',
                                'live' => $cameraLive,
                                'status' => $thisCamera['status'],
                                'sd_status' => $thisCamera['sd_status'],
                                'alim_status' => $thisCamera['alim_status'],
                                'light_mode_status' => $thisCamera['light_mode_status'],
                                'is_local' => $thisCamera['is_local'],
                                'type' => 'Presence'
                                );
                if ($getSettings==true) $camera = $this->getCameraSettings($camera);
                array_push($allCameras, $camera);
            }
        }
        $this->_cameras = $allCameras;
        return $allCameras;
    }

    protected function getCameraSettings($camera)
    {
        if ( is_string($camera) ) $camera = $this->getCamera($camera);
        if ( isset($camera['error']) ) return $camera;

        //get camera conf:
        $vpn = $camera['vpn'];
        $command = '/command/getsetting';
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);

        $camera['error_status'] = $answer['error']['code'].' '.$answer['error']['message'];
        $camera['image_orientation'] = $answer['conf']['image_orientation'];
        $camera['audio'] = $answer['conf']['audio'];

        //get camera light settings:
        $command = '/command/floodlight_get_config';
        $url = $vpn.$command;

        $answer = $this->_request('GET', $url);
        $answer = json_decode($answer, true);

        $camera['light'] = $answer;

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

        if($response === false)
        {
            echo 'cURL error: ' . curl_error($this->_curlHdl);
        }
        else
        {
            return $response;
        }
    }

    //functions authorization=============================================
    public $_home = null;
    public $error = null;

    public $_cameras;
    public $_fullDatas;


    protected $_urlHost = 'https://my.netatmo.com';
    protected $_urlAuth = 'https://auth.netatmo.com';

    protected $_Netatmo_user;
    protected $_Netatmo_pass;
    protected $_csrf = null;
    protected $_csrfName = null;
    protected $_token = null;
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

        $token = explode('netatmocomaccess_token=', $answer);
        if(count($token)>1)
        {
            $token = $token[count($token)-1];
            $token = explode(';', $token)[0];
            $token = urldecode($token);
            $this->_token = $token;
        }
        else
        {
            $this->error = "Couldn't find Netatmo token.";
            return false;
        }
        return true;
    }

    function __construct($Netatmo_user, $Netatmo_pass)
    {
        $this->_Netatmo_user = $Netatmo_user;
        $this->_Netatmo_pass = $Netatmo_pass;

        if ($this->connect() == true)
        {
            $this->getDatas();
            $this->getCamerasDatas();
        }
    }

//NetatmoPresenceAPI end
}

?>
