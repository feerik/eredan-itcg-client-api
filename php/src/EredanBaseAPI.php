<?php
/**
 * Base API allowing access to Eredan iTCG's REST API
 *
 * @author Aymeric VERGNE <aymeric@feerik.com>
 * @author Christophe SAUVEUR <christophe@feerik.com>
 * @license GNU General Public License, version 2
 *
 * @version 1.0.0
 */
class EredanBaseAPI {

	/**
	 * API server URL
	 */
	const server = 'http://api.itcg.eredan.com';
	
	/**
	 * API key holder
	 * @var string
	 */
	private $apiKey;

	/**
	 * API calls return format holder
	 * @var string
	 */
	private $returnFormat;

	/**
	 * Locale for API calls holder
	 * @var string
	 */
	private $locale;

	/**
	 * Connector holder
	 * @var string
	 */
	private $connector;

	/**
	 * Constructor
	 *
	 * @param string $apiKey 			Required API key provided by Feerik SAS
	 * @param string $returnFormat		Any value of 'json', 'xml' or 'array'
	 * @param string $locale 			(Optional) Locale for API replies. If not set, defaults to English.
	 */
	public function __construct($apiKey, $returnFormat, $locale = '') {
		$connectors = array('json', 'xml', 'array');
		
		if (!in_array(strtolower($returnFormat), $connectors))
			throw new Exception ('This connector does not exists, please use XML or JSON');

		$this->apiKey = $apiKey;
		$this->returnFormat = $returnFormat;
		if ($returnFormat == 'array')
			$this->connector = "json.php";
		else
			$this->connector = $returnFormat.".php";
		
		if (!empty($locale))
			$this->locale = $locale;
	}
	
	/**
	 * Raw API call handler
	 *
	 * @param string $connection 		Connection name.
	 * @param array $params 			Request parameters. Internal structure depends on connection name. See documentation for further details.
	 * @param string method 			HTTP method used to contact the REST API. GET is the only supported method at this time.
	 *
	 * @return array|string|boolean		The reply from the API, depending on the return format specified in the constructor, or FALSE in case of a communication error.
	 *
	 * @link http://api.itcg.eredan.com/docs/doku.php?id=clientapi&#api
	 */
	public function api($connection, array $params = array(), $method = 'GET') {
		$params['apiKey'] = $this->apiKey;
		if (isset($this->locale))
			$params['locale'] = $this->locale;
	
		$qstring = '';
		if (!empty($params))
			$qstring = http_build_query($params);
		
		$url = $this->buildURL($connection);
		
		$ch = curl_init();
		if (strtoupper($method) == 'GET')
			curl_setopt($ch, CURLOPT_URL, $url."?".$qstring);
		else if (strtoupper($method) == 'POST') {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $qstring); 
		}
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$retour = curl_exec($ch);
		curl_close($ch); 

		if (!empty($retour) && $this->returnFormat == 'array')
			$retour = json_decode($retour, true);
		return $retour;
	}
	
	/**
	 * URL builder for connections
	 *
	 * @param string $connection 		Connection name.
	 *
	 * @return string URL suitable for API call, including server URL, connector and connection name.
	 */
	private function buildURL($connection){
		$url = sprintf("%s/%s/%s", self::server, $this->connector, $connection);
		return $url;
	}
}
?>