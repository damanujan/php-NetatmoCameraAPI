<?php
/*

https://github.com/KiboOst/php-NetatmoPresenceAPI


*/

class NetatmoPresenceAPI {

	public $_version = "0.1";

	function __construct($Netatmo_user, $Netatmo_pass)
	{
		$this->_Netatmo_user = $Netatmo_user;
		$this->_Netatmo_pass = $Netatmo_pass;
		$this->connect();
		$this->getDatas();
		$this->getCameras();
	}

	//user functions======================================================
	//GET:

	//for alerts: 0: ignore, 1: record, 2: record and notify
	public function setHumanAlert($value)
	{
		$mode = null;
		if ($value == 0) $mode = 'ignore';
		if ($value == 1) $mode = 'record';
		if ($value == 2) $mode = 'record_and_notify';
		if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

		$setting = 'presence_settings[presence_record_humans]';
		$url = $this->_urlHost.'/api/updatehome';
		$post = 'home_id='.$this->_homeID.'&'.$setting.'='.$mode.'&ci_csrf_netatmo='.$this->_csrf;

		$answer = $this->_request('POST', $url, $post);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	public function setAnimalAlert($value)
	{
		$mode = null;
		if ($value == 0) $mode = 'ignore';
		if ($value == 1) $mode = 'record';
		if ($value == 2) $mode = 'record_and_notify';
		if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

		$setting = 'presence_settings[presence_record_animals]';
		$url = $this->_urlHost.'/api/updatehome';
		$post = 'home_id='.$this->_homeID.'&'.$setting.'='.$mode.'&ci_csrf_netatmo='.$this->_csrf;

		$answer = $this->_request('POST', $url, $post);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	public function setVehicleAlert($value)
	{
		$mode = null;
		if ($value == 0) $mode = 'ignore';
		if ($value == 1) $mode = 'record';
		if ($value == 2) $mode = 'record_and_notify';
		if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

		$setting = 'presence_settings[presence_record_vehicles]';
		$url = $this->_urlHost.'/api/updatehome';
		$post = 'home_id='.$this->_homeID.'&'.$setting.'='.$mode.'&ci_csrf_netatmo='.$this->_csrf;

		$answer = $this->_request('POST', $url, $post);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	public function setOtherAlert($value)
	{
		$mode = null;
		if ($value == 0) $mode = 'ignore';
		if ($value == 1) $mode = 'record';
		if ($value == 2) $mode = 'record_and_notify';
		if (!isset($mode)) return array('error'=>'Set 0 for ignore, 1 for record, 2 for record and notify');

		$setting = 'presence_settings[presence_record_movements]';
		$url = $this->_urlHost.'/api/updatehome';
		$post = 'home_id='.$this->_homeID.'&'.$setting.'='.$mode.'&ci_csrf_netatmo='.$this->_csrf;

		$answer = $this->_request('POST', $url, $post);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	public function setAlertFrom($from)
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
		$post = 'home_id='.$this->_homeID.'&'.$setting.'='.$timeString.'&ci_csrf_netatmo='.$this->_csrf;

		$answer = $this->_request('POST', $url, $post);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	public function setAlertTo($to)
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
		$post = 'home_id='.$this->_homeID.'&'.$setting.'='.$timeString.'&ci_csrf_netatmo='.$this->_csrf;

		$answer = $this->_request('POST', $url, $post);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}


	public function getSettings($camera)
	{
		if ( is_string($camera) ) $camera = $this->getCamByName($camera);
		if ( isset($camera['error']) ) return $camera;

		$vpn = $camera['vpn'];
		$command = '/command/getsetting';
		$url = $vpn.$command;

		$answer = $this->_request('GET', $url);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	public function getSmartZones($camera)
	{
		if ( is_string($camera) ) $camera = $this->getCamByName($camera);
		if ( isset($camera['error']) ) return $camera;

		$vpn = $camera['vpn'];
		$command = '/command/smart_zone_get_config';
		$url = $vpn.$command;

		$answer = $this->_request('GET', $url);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	public function getFloodlight($camera)
	{
		if ( is_string($camera) ) $camera = $this->getCamByName($camera);
		if ( isset($camera['error']) ) return $camera;

		$vpn = $camera['vpn'];
		$command = '/command/floodlight_get_config';
		$url = $vpn.$command;

		$answer = $this->_request('GET', $url);
		$answer = json_decode($answer, true);
		return array('result'=>$answer);
	}

	//SET:
	public function setIntensity($camera, $intensity)
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

	public function setFloodlight($camera, $mode) //auto on off
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

	public function setMonitoring($camera, $mode) //on off
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

	public function setRecording($camera, $always, $person, $vehicle, $animal, $movement) // true false
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


			$id = $thisEvent["id"];
			$time = $thisEvent["time"];
			$camId = $thisEvent["camera_id"];
			foreach ($this->_cameras as $cam)
				{
					if ($cam['id'] == $camId)
					{
						$camName = $cam['name'];
						$camVPN = $cam['vpn'];
						break;
					}
				}

			if ( isset($thisEvent["event_list"]) ) $eventList = $thisEvent["event_list"];
			else continue;
			$isAvailable = $thisEvent["video_status"];
			for ($j=0; $j < count($eventList) ;$j++)
			{
				$thisSubEvent = $thisEvent["event_list"][$j];
				$subType = $thisSubEvent["type"];
				$subMsg = $thisSubEvent["message"];
				if ( (strpos($subType, $requestType) !== false) or ($requestType == "All") )
					{
						$subTime = $thisSubEvent["time"];
						$subTime = date("d-m-Y H:i:s", $subTime);

						if (isset($thisSubEvent["snapshot"]["filename"]))  //other vignette of same event!
						{
							$snapshotURL = $camVPN."/".$thisSubEvent["snapshot"]["filename"];
							$vignetteURL = $camVPN."/".$thisSubEvent["vignette"]["filename"];
						}else{
							$snapshotID = $thisSubEvent["snapshot"]["id"];
							$snapshotKEY = $thisSubEvent["snapshot"]["key"];
							$snapshotURL = "https://api.netatmo.com/api/getcamerapicture?image_id=".$snapshotID."&key=".$snapshotKEY;

							$vignetteID = $thisSubEvent["vignette"]["id"];
							$vignetteKEY = $thisSubEvent["vignette"]["key"];
							$vignetteURL = "https://api.netatmo.com/api/getcamerapicture?image_id=".$vignetteID."&key=".$vignetteKEY;
						}

						$returnThis = array();
						$returnThis['title'] = $subMsg . " | ".$subTime." | ".$camName;
						$returnThis['snapshotURL'] = $snapshotURL;
						$returnThis['vignetteURL'] = $vignetteURL;
						array_push($returnEvents, $returnThis);
					}
			}
		}

		return $returnEvents;
	}

	public function getDatas($eventNum=10)
	{
		//get homedata
		$url = $this->_urlHost.'/api/gethomedata'."&size=".$eventNum;
		$answer = $this->_request('POST', $url);

		$jsonDatas = json_decode($answer, true);
		$this->_homeID = $jsonDatas['body']['homes'][0]['id'];
		$this->_home = $jsonDatas['body']['homes'][0]['name'];
		$this->_fullDatas = $jsonDatas;
	}

	public function getCameras()
	{
		if (is_null($this->_fullDatas)) $this->getDatas();

		$allCameras = array();
		foreach ($this->_fullDatas['body']['homes'][0]['cameras'] as $thisCamera)
		{
			$cameraVPN = $thisCamera["vpn_url"];
			if ($thisCamera['is_local'] == false)
			{
				$cameraLive = $cameraVPN."/live/index.m3u8";
			}
			else
			{
				$cameraLive = $cameraVPN."/live/index_local.m3u8";
			}

			$camera = array('name' => $thisCamera["name"],
							'id' => $thisCamera["id"],
							'vpn' => $cameraVPN,
							'snapshot' => $cameraVPN.'/live/snapshot_720.jpg',
							'live' => $cameraLive,
							'status' => $thisCamera["status"],
							'sd_status' => $thisCamera["sd_status"],
							'alim_status' => $thisCamera["alim_status"],
							'light_mode_status' => $thisCamera["light_mode_status"],
							'is_local' => $thisCamera["is_local"]
							);
			array_push($allCameras, $camera);
		}
		$this->_cameras = $allCameras;
		return $allCameras;
	}

	public function getCamByName($name)
	{
		foreach ($this->_cameras as $thisCamera)
		{
			if ($thisCamera['name'] == $name) return $thisCamera;
		}
		return array('result'=>null, 'error' => 'Unfound camera');
	}

	//calling functions===================================================
	protected function _request($method, $url, $post=null)
	{
		if (!isset($this->_curlHdl))
		{
			$this->_curlHdl = curl_init();
			curl_setopt($this->_curlHdl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->_curlHdl, CURLOPT_HEADER, true);
			curl_setopt($this->_curlHdl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->_curlHdl, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:51.0) Gecko/20100101 Firefox/51.0');
			curl_setopt($this->_curlHdl, CURLOPT_ENCODING , '');
		}

		curl_setopt($this->_curlHdl, CURLOPT_URL, $url);
		curl_setopt($this->_curlHdl, CURLOPT_POST, false);

		if ($method == 'POST')
		{
			curl_setopt($this->_curlHdl, CURLOPT_POST, true);
			curl_setopt($this->_curlHdl, CURLOPT_POSTFIELDS, $post);

			if (isset($this->_token))
			{
				curl_setopt($this->_curlHdl, CURLOPT_HEADER, false);
				curl_setopt($this->_curlHdl, CURLOPT_HTTPHEADER, array(
														'Connection: keep-alive',
														'Content-Type: application/x-www-form-urlencoded',
														'Authorization: Bearer '.$this->_token,
														)
													);
			}

		}

		$cookie = "";
		if (isset($this->_csrf)) $cookie = 'netatmocomci_csrf_cookie_na='.$this->_csrf;
		if (isset($this->_token)) $cookie .= '; netatmocomaccess_token='.$this->_token;

		if ( $cookie != "" )
		{
			curl_setopt($this->_curlHdl, CURLOPT_COOKIE, $cookie);
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
	protected function connect()
	{
		//get csrf:
		$url = $this->_urlHost;
		$answer = $this->_request('GET', $url);

		$csrf = explode('netatmocomci_csrf_cookie_na=', $answer);
		if(count($csrf)>1)
		{
			$csrf = explode('; ', $csrf[1]);
			$csrf = $csrf[0];
			$this->_csrf = $csrf;
			//echo "csrf:".$csrf."<br>";
		}
		else
		{
			die("Couldn't find Netatmo CSRF.");
		}

		//get token:
		$url = $this->_urlAuth.'/en-US/access/login';
		$post = "ci_csrf_netatmo=".$csrf."&mail=".$this->_Netatmo_user."&pass=".$this->_Netatmo_pass."&log_submit=Connexion";
		$answer = $this->_request('POST', $url, $post);

		$token = explode('netatmocomaccess_token=', $answer);
		if(count($token)>1)
		{
			$token = $token[count($token)-1];
			$token = explode('; ', $token)[0];
			$token = urldecode($token);
			$this->_token = $token;
			//echo "token:".$token."<br>";
		}
		else
		{
			die("Couldn't find Netatmo token.");
		}

		/*
		//get netatmocommail_cookie to avoid having email connection each time!!
		$commail = explode('netatmocommail_cookie=', $answer);
		if(count($commail)>1)
		{
			$commail = explode('; ', $commail[1]);
			$commail = $commail[0];
			$this->_commail = $commail;
			echo "commail:".$commail."<br>";
		}
		else
		{
			die("Couldn't find Netatmo email reference.");
		}
		*/
	}

	public $_home = null;
	public $_homeID = null;
	public $_fullDatas;
	public $_cameras;

	protected $_urlHost = 'https://my.netatmo.com';
	protected $_urlAuth = 'https://auth.netatmo.com';

	protected $_Netatmo_user;
	protected $_Netatmo_pass;
	protected $_csrf = null;
	protected $_commail = null;
	protected $_token = null;
	protected $_curlHdl = null;

//NetatmoPresenceAPI end
}

?>
