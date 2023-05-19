<?php

namespace vadgab\Yii2GoogleSheets; 

use \Exception;
class GoogleSheets
{
    private $serviceAccountEmail;
    private $privateKey;
    private $scopes;
    private $accessToken;

	public $calendarId = "";
	public $eventId = "";
	private $url = "";

	const URL_SETTING_TIMEZONE = "https://www.googleapis.com/calendar/v3/users/me/settings/timezone";
	const URL_OAUTH2_TOKEN = "https://www.googleapis.com/oauth2/v4/token";
	
	/**
	 * __construct
	 *
	 * @param  mixed $serviceAccountEmail
	 * @param  mixed $privateKeyFile
	 * @param  mixed $scopes
	 * @return void
	 */
	public function __construct($serviceAccountEmail, $privateKeyFile, $scopes)
    {
        $this->serviceAccountEmail = $serviceAccountEmail;
        $this->privateKey = file_get_contents($privateKeyFile);
        $this->scopes = $scopes;
        $this->accessToken = $this->getAccessToken();
    }

	private function getAccessToken()
    {


        $jwt = $this->generateJwtToken();
        $params = array(
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion" => $jwt
        );
		$response = self::connectCurlToken($params);
        $json = json_decode($response, true);
        return $json["access_token"];
    }

	private function generateJwtToken()
    {
        $header = array(
            "alg" => "RS256",
            "typ" => "JWT"
        );

        $now = time();
        $exp = $now + 3600;
        $payload = array(
            "iss" => $this->serviceAccountEmail,
            "scope" => implode(" ", $this->scopes),
            "aud" => self::URL_OAUTH2_TOKEN,
            "exp" => $exp,
            "iat" => $now
        );

		$keyJson = json_decode($this->privateKey, true);
		
		// Lekérjük a privát kulcsot és dekódoljuk
		$privateKey = openssl_pkey_get_private($keyJson['private_key']);

        $jwt = $this->jwtEncode($header, $payload, $privateKey, 'RS256');
        return $jwt;
    }
	
	/**
	 * jwtEncode
	 *
	 * @param  mixed $header
	 * @param  mixed $payload
	 * @param  mixed $privateKey
	 * @param  mixed $alg
	 * @return void
	 */
	private function jwtEncode($header, $payload, $privateKey, $alg = "RS256")
	{
		$segments = array();
		$segments[] = base64_encode(json_encode($header));
		$segments[] = base64_encode(json_encode($payload));
		$signing_input = implode(".", $segments);

		$signature = "";
		switch ($alg) {
			case "RS256":
				openssl_sign($signing_input, $signature, $privateKey, OPENSSL_ALGO_SHA256);
				break;
			case "RS384":
				openssl_sign($signing_input, $signature, $privateKey, OPENSSL_ALGO_SHA384);
				break;
			case "RS512":
				openssl_sign($signing_input, $signature, $privateKey, OPENSSL_ALGO_SHA512);
				break;
			default:
				throw new Exception("Unsupported signing algorithm: " . $alg);
		}

		$segments[] = base64_encode($signature);
		return implode(".", $segments);
	}

	
	/**
	 * connectCurlToken
	 *
	 * @param  mixed $params
	 * @return void
	 */
	private function connectCurlToken($params){
        
		$ch = curl_init(self::URL_OAUTH2_TOKEN);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $response = curl_exec($ch);
        curl_close($ch);
		
		return $response;
	}	
	/**
	 * connectCurl
	 *
	 * @param  mixed $headers
	 * @return void
	 */
	private function connectCurl($headers){

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
		return $response; 

	}

	private function connectCurlwithPost($headers,$payload){

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));		
        $response = curl_exec($ch);
        curl_close($ch);
		return $response; 		

	}

	private function connectCurlwithPut($headers,$payload){

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));		
        $response = curl_exec($ch);
        curl_close($ch);
		return $response; 		

	}
	
	private function connectCurlwithDelete($headers){

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $response = curl_exec($ch);
        curl_close($ch);
		return $response; 		

	}	

	
	
	/**
	 * getEvents
	 *
	 * @param  array $params
	 * @return void
	 */
	public function getEvents($params = [])
    {
        $this->url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events";
		if($this->eventId)
			$this->url = $this->url."/".$this->eventId;
		if(!empty($params)){
			$this->url = $this->url."?";
			$count = 0;			
			foreach($params as $key => $item){
				if($count == 0)$separator = "";
				else $separator = "&";
				$this->url = $this->url.$separator.$key."=".$item;			
			}
		}

        $headers = array(
            "Authorization: Bearer {$this->accessToken}",
            "Content-type: application/json"
        );
        $response = self::connectCurl($headers);
        return json_decode($response, true);
    }	
	
	/**
	 * getEvents
	 *
	 * @param  mixed $payload
	 * @return void
	 */
	public function insertEvent($payload)
    {
        $this->url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events";
        $headers = array(
            "Authorization: Bearer {$this->accessToken}",
            "Content-type: application/json"
        );
        $response = self::connectCurlwithPost($headers,$payload);
        return json_decode($response, true);
    }	
	

	public function updateEvent($payload)
    {
        $this->url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events/".$this->eventId;

        $headers = array(
            "Authorization: Bearer {$this->accessToken}",
            "Content-type: application/json"
        );
        $response = self::connectCurlwithPut($headers,$payload);
        return json_decode($response, true);
    }	
	
	public function deleteEvent()
    {
        $this->url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events/".$this->eventId;

        $headers = array(
            "Authorization: Bearer {$this->accessToken}",
            "Content-type: application/json"
        );
        $response = self::connectCurlwithDelete($headers);
        return json_decode($response, true);
    }		


	
}

?>