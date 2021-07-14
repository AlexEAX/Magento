<?php
/**
 * Class: Retargeting REST API Client
 */
class Retargeting_REST_API_Client
{
	/**
	 * Property: the uniform resource identifier for the current web request 
	 * @var string
	 */
	protected $api_uri = "https://retargeting.biz/api";
	
	/**
	 * Property: the API version
	 * @var string
	 */
	protected $api_version = "1.0";
	
	/**
	 * Property: the API response format: json or serial (php serialize)
	 * @var string
	 */
	protected $response_format = "json";
	
	/**
	 * Property: if is true the response from API request will be decoded (for json response format) / converted (for serial response format)
	 * @var boolean
	 */
	protected $decoding = true;
	
	/**
	 * Property: the API KEY
	 * @see Retargeting Administration Panel
	 * @var unknown
	 */
	private $api_key = "";

	/**
	 * Property: the API request path (/api/path)
	 * @var array
	 */
	private $api_path = array();
	
	/**
	 * Property: the API request parameters
	 * @var array
	 */
	private $api_parameters = array();

    /**
     * Method: constructor method for Retargeting REST API Client class
     * @param string $api_key
     * @throws Exception
     */
	public function __construct($api_key) {
		if (is_string($api_key) || is_numeric($api_key)) {
			$this->api_key = $api_key;
		} else {
			$this->_throwException("checkApiKey");
		}
	}

    /**
     * Method: set a new API uri
     * @param string $api_uri
     * @throws Exception
     */
	public function setApiUri($api_uri) {
		if (is_string($api_uri) && !empty($api_uri)) {
			$this->api_uri = $api_uri;
		} else {
			$this->_throwException("apiUriType");
		}
	}

    /**
     * Method: set a new API version
     * @param string $api_version
     * @throws Exception
     */
	public function setApiVersion($api_version) {
		if (is_string($api_version) && !empty($api_version)) {
			$this->api_version = $api_version;
		} else {
			$this->_throwException("apiVersionType");
		}
	}

    /**
     * Method: set a new API response format: json or serial (php serialize)
     * @param string $response_format
     * @throws Exception
     */
	public function setResponseFormat($response_format = "json") {
		if (in_array($response_format, array("json", "serial"))) {
			$this->response_format = $response_format;
		} else {
			$this->_throwException("responseFormat");
		}
	}
	
	public function setDecoding($mode = true) {
		if (is_bool($mode)) {
			$this->decoding = $mode;
		} else {
			$this->_throwException("decodingMode");
		}
	}
	
	/**
	 * Overloading method: is utilized for reading data from inaccessible properties
	 * @param string $name
	 * @return Retargeting_REST_API_Client
	 */
	public function __get($name) {
		$this->api_path[] = $name;
		return $this;
	}

    /**
     * Overloading method:  is triggered when invoking inaccessible methods in an object context
     * @param string $name
     * @param array $arguments
     * @return array
     * @throws Exception
     * @see _processRequest()
     */
	public function __call($name, $arguments) {
		$this->api_path[] = $name;
		$this->api_parameters = $arguments;
		return $this->_processRequest();
	}

    /**
     * Method: use PHP cURL library to connect with Retargeting REST API and send the request
     * @see http://php.net/manual/ro/book.curl.php
     * @return array
     * @throws Exception
     */
	private function _processRequest() {
		if (empty($this->api_path)) {
			$this->_throwException("emptyApiPath");
		}
		
		$api_uri = $this->api_uri."/".$this->api_version."/".implode("/", $this->api_path).".".$this->response_format;
		$this->api_path = array();

		$api_parameters = array(
			"api_key" => $this->api_key
		);
		$api_parameters = http_build_query(array_merge($api_parameters, $this->api_parameters));
		$this->api_parameters = array();
		
		$curl_request = curl_init();
        curl_setopt($curl_request, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl_request, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl_request, CURLOPT_URL, $api_uri);
		curl_setopt($curl_request, CURLOPT_POST, true);
		curl_setopt($curl_request, CURLOPT_POSTFIELDS, $api_parameters);
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
		
		if ($this->decoding) {
			if ($this->response_format == "json") {
				return json_decode(curl_exec($curl_request), true);
			} elseif ($this->response_format == "serial") {
				return unserialize(curl_exec($curl_request));
			}
		}

        $mh = curl_multi_init();
        curl_multi_add_handle($mh,$curl_request);

        //execute the multi handle
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                // Wait a short time for more activity
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        return true;
	}
	
	/**
	 * Method: throw new exception with custom message
	 * @param string $message
	 * @throws Exception
	 */
	private function _throwException($message) {
		$messages = array(
			"checkApiKey" => "You need an API KEY to use Retargeting API. Please go to your Retargeting Administration Panel to set up or check your API KEY.",
			"apiUriType" => "The API uri must be string",
			"apiVersionType" => "The API version must be a string",
			"responseFormat" => "The response format can only be json or serial (php serialize)",
			"decodingMode" => "Decoding must be boolean",
			"emptyApiPath" => "You API request"
		);
		throw new Exception($messages[$message]);
	}
}
