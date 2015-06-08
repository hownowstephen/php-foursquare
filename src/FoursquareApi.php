<?php
/**
 * FoursquareApi
 * A PHP-based Foursquare client library with a focus on simplicity and ease of integration
 * 
 * @package php-foursquare 
 * @author Stephen Young <me@hownowstephen.com>, @hownowstephen
 * @version 1.2.0
 * @license GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

// Set the default version
// @TODO: Warning when the version becomes too out of date
define("DEFAULT_VERSION", "20140201");

// I have no explanation as to why this is necessary
define("HTTP_GET","GET");
define("HTTP_POST","POST");

/**
 * FoursquareApi
 * Provides a wrapper for making both public and authenticated requests to the
 * Foursquare API, as well as the necessary functionality for acquiring an 
 * access token for a user via Foursquare web authentication
 */

class FoursquareApiException extends Exception {}

class FoursquareApi {
	
	/** @var String $BaseUrl The base url for the foursquare API */
	private $BaseUrl = "https://api.foursquare.com/";
	/** @var String $AuthUrl The url for obtaining the auth access code */
	private $AuthUrl = "https://foursquare.com/oauth2/authenticate";
	/** @var String $AuthorizeUrl The url for obtaining an auth token, reprompting even if logged in */
	private $AuthorizeUrl = "https://foursquare.com/oauth2/authorize";
	/** @var String $TokenUrl The url for obtaining an auth token */
	private $TokenUrl = "https://foursquare.com/oauth2/access_token";
	
	// Edited Petr Babicka (babcca@gmail.com) https://developer.foursquare.com/overview/versioning
	/** @var String $Version YYYYMMDD */
	private $Version;

	/** @var String $ClientID */
	private $ClientID;
	/** @var String $ClientSecret */
	private $ClientSecret;
	/** @var String $RedirectUri */
	protected $RedirectUri;
	/** @var String $AuthToken */
	private $AuthToken;
	/** @var String $ClientLanguage */
	private $ClientLanguage;
    /** @var String[] $ResponseHeaders */
    public $ResponseHeaders = array();
    /** @var String last url sent */
    public $LastUrl;

    /**
     * Constructor for the API
     * Prepares the request URL and client api params
     * @param bool|String $client_id
     * @param bool|String $client_secret
     * @param string $redirect_uri
     * @param String $version Defaults to v2, appends into the API url
     * @param string $language
     * @param string $api_version https://developer.foursquare.com/overview/versioning
     */
	public function  __construct($client_id = false,$client_secret = false, $redirect_uri='', $version='v2', $language='en', $api_version=DEFAULT_VERSION){
		$this->BaseUrl = "{$this->BaseUrl}$version/";
		$this->ClientID = $client_id;
		$this->ClientSecret = $client_secret;
		$this->ClientLanguage = $language;
		$this->RedirectUri = $redirect_uri;
        $this->Version = $api_version;
	}
    
	public function setRedirectUri( $uri ) {
		$this->RedirectUri = $uri;
	}
	
	// Request functions
	
	/** 
	 * GetPublic
	 * Performs a request for a public resource
	 * @param String $endpoint A particular endpoint of the Foursquare API
	 * @param Array $params A set of parameters to be appended to the request, defaults to false (none)
	 */
	public function GetPublic($endpoint,$params=false){
		// Build the endpoint URL
		$url = $this->BaseUrl . trim($endpoint,"/");
		// Append the client details
		$params['client_id'] = $this->ClientID;
		$params['client_secret'] = $this->ClientSecret;
		$params['v'] = $this->Version;
		$params['locale'] = $this->ClientLanguage;
		// Return the result;
		return $this->GET($url,$params);
	}
	
	/** 
	 * GetPrivate
	 * Performs a request for a private resource
	 * @param String $endpoint A particular endpoint of the Foursquare API
	 * @param Array $params A set of parameters to be appended to the request, defaults to false (none)
	 * @param bool $POST whether or not to use a POST request
	 */
	public function GetPrivate($endpoint,$params=false,$POST=false){
		$url = $this->BaseUrl . trim($endpoint,"/");
		$params['oauth_token'] = $this->AuthToken;
		$params['v'] = $this->Version;
		$params['locale'] = $this->ClientLanguage;
		if(!$POST) return $this->GET($url,$params);
		else return $this->POST($url,$params);
	}

	/**
	 * GetMulti
	 * Performs a request for up to 5 private or public resources
	 * @param Array $requests An array of arrays containing the endpoint and a set of parameters
	 * to be appended to the request, defaults to false (none)
	 * @param bool $POST whether or not to use a POST request, e.g.  for large request bodies.
	 * It does not allow you to call endpoints that mutate data.
	 */
	public function GetMulti($requests=false,$POST=false){
		$url = $this->BaseUrl . "multi";		
		$params = array();
		$params['oauth_token'] = $this->AuthToken;
		$params['v'] = $this->Version;		
		if (is_array($requests)){
			$request_queries = array();
			foreach($requests as $request) {
				$endpoint = $request['endpoint'];
				unset($request['endpoint']);
				$query = '/' . $endpoint;
					if (!empty($request)) $query .= '?' . http_build_query($request);
				$request_queries[] = $query;
			}
			$params['requests'] = implode(',', $request_queries);
		}
				if(!$POST) return $this->GET($url,$params);
		else return $this->POST($url,$params);
	}
    
	public function getResponseFromJsonString($json) {
		$json = json_decode( $json );
		if ( !isset( $json->response ) ) {
			throw new FoursquareApiException( 'Invalid response' );
		}

		// Better to check status code and fail gracefully, but not worried about it
		// ... REALLY, we should be checking the HTTP status code as well, not 
		// just what the API gives us in it's microformat
		/*
		if ( !isset( $json->meta->code ) || 200 !== $json->meta->code ) {
			throw new FoursquareApiException( 'Invalid response' );
		}
		*/
		return $json->response;
	}
	
	/**
	 * Request
	 * Performs a cUrl request with a url generated by MakeUrl. The useragent of the request is hardcoded
	 * as the Google Chrome Browser agent
	 * @param String $url The base url to query
	 * @param Array $params The parameters to pass to the request
	 */
	private function Request($url, $params=false, $type=HTTP_GET){
		
		// Populate data for the GET request
		if($type == HTTP_GET) $url = $this->MakeUrl($url,$params);

        $this->LastUrl = $url;

        // Reset the headers every time we initiate a new request
        $this->ResponseHeaders = array();

		// borrowed from Andy Langton: http://andylangton.co.uk/
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		} else {
			// Handle the useragent like we are Google Chrome
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.X.Y.Z Safari/525.13.');
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		$acceptLanguage[] = "Accept-Language:" . $this->ClientLanguage;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $acceptLanguage);
        // Set the header callback
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'ParseHeaders'));
		// Populate the data for POST
		if($type == HTTP_POST) {
			curl_setopt($ch, CURLOPT_POST, 1); 
			if($params) curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		$result=curl_exec($ch);
		$info=curl_getinfo($ch);
		curl_close($ch);
		
		return $result;
	}

    /**
     * Callback function to handle header strings as they are returned by cUrl in the $this->Request() function
     * Parses header strings into key/value pairs and stores them in $ResponseHeaders array
     *
     * @param $ch
     * @param $header
     * @return int
     */
    private function ParseHeaders($ch, $header) {
        if (strpos($header, ':') !== false) {
            $header_split = explode(':', $header);
            $this->ResponseHeaders[strtolower(trim($header_split[0]))] = trim($header_split[1]);
        }
        return strlen($header);
    }

	/**
	 * GET
	 * Abstraction of the GET request
	 */
	private function GET($url,$params=false){
		return $this->Request($url,$params,HTTP_GET);
	}

	/**
	 * POST
	 * Abstraction of a POST request
	 */
	private function POST($url,$params=false){
		return $this->Request($url,$params,HTTP_POST);
	}

	
	// Helper Functions
	
	/**
	 * GeoLocate
	 * Leverages the google maps api to generate a lat/lng pair for a given address
	 * packaged with FoursquareApi to facilitate locality searches.
	 * @param String $addr An address string accepted by the google maps api
	 * @return array(lat, lng) || NULL
	 */
	public function GeoLocate($addr){
		$geoapi = "http://maps.googleapis.com/maps/api/geocode/json";
		$params = array("address"=>$addr,"sensor"=>"false");
		$response = $this->GET($geoapi,$params);
		$json = json_decode($response);
		if ($json->status === "ZERO_RESULTS") {
			return null;
		} else {
			return array($json->results[0]->geometry->location->lat,$json->results[0]->geometry->location->lng);
		}
	}
	
	/**
	 * MakeUrl
	 * Takes a base url and an array of parameters and sanitizes the data, then creates a complete
	 * url with each parameter as a GET parameter in the URL
	 * @param String $url The base URL to append the query string to (without any query data)
	 * @param Array $params The parameters to pass to the URL
	 */	
	private function MakeUrl($url,$params){
	    return trim($url) . '?' . http_build_query($params); 
	}
	
	// Access token functions
	
	/**
	 * SetAccessToken
	 * Basic setter function, provides an authentication token to GetPrivate requests
	 * @param String $token A Foursquare user auth_token
	 */
	public function SetAccessToken($token){
		$this->AuthToken = $token;
	}

	/**
	 * AuthenticationLink
	 * Returns a link to the Foursquare web authentication page.
	 * @param String $redirect The configured redirect_uri for the provided client credentials
	 */
	public function AuthenticationLink($redirect=''){
		if ( 0 === strlen( $redirect ) ) {
			$redirect = $this->RedirectUri;
		}
		$params = array("client_id"=>$this->ClientID,"response_type"=>"code","redirect_uri"=>$redirect);
		return $this->MakeUrl($this->AuthUrl,$params);
	}
	
  /**
   * AuthorizeLink
   * Returns a link to the Foursquare web authentication page. Using /authorize will ask the user to
   * re-authenticate their identity and reauthorize your app while giving the user the option to
   * login under a different account.
   * @param String $redirect The configured redirect_uri for the provided client credentials
   */
  public function AuthorizeLink($redirect=''){
    if ( 0 === strlen( $redirect ) ) {
      $redirect = $this->RedirectUri;
    }
    $params = array("client_id"=>$this->ClientID,"response_type"=>"code","redirect_uri"=>$redirect);
    return $this->MakeUrl($this->AuthorizeUrl,$params);
  }
  
	/**
	 * GetToken
	 * Performs a request to Foursquare for a user token, and returns the token, while also storing it
	 * locally for use in private requests
	 * @param $code The 'code' parameter provided by the Foursquare webauth callback redirect
	 * @param $redirect The configured redirect_uri for the provided client credentials
	 */
	public function GetToken($code,$redirect=''){
		if ( 0 === strlen( $redirect ) ) {
			// If we have to use the same URI to request a token as we did for 
			// the authorization link, why are we not storing it internally?
			$redirect = $this->RedirectUri;
		}
		$params = array("client_id"=>$this->ClientID,
						"client_secret"=>$this->ClientSecret,
						"grant_type"=>"authorization_code",
						"redirect_uri"=>$redirect,
						"code"=>$code);
		$result = $this->GET($this->TokenUrl,$params);
		$json = json_decode($result);
		
		// Petr Babicka Check if we get token
		if (property_exists($json, 'access_token')) {
			$this->SetAccessToken($json->access_token);
			return $json->access_token;
		}
		else {
			return 0;
		}
	}
}
