<?php
require_once('EredanBaseAPI.php');

/**
 * Caching boolean flag. If set, client-side caching is enabled.
 */
define('CACHING', true);

/**
 * Caching expiration delay, in seconds.
 */
define('CACHING_EXPIRE', 3600);

/**
 * Caching storage directory. Defaults is current directory.
 */
define('CACHING_DIR', './');

/**
 * Extended client-side API for Eredan iTCG's server-side REST API, including basic client-side caching mechanism.
 *
 * @author Aymeric VERGNE <aymeric@feerik.com>
 * @author Christophe SAUVEUR <christophe@feerik.com>
 * @license GNU General Public License, version 2
 *
 * @version 1.0.0
 */
class EredanExtendedAPI extends EredanBaseAPI {
	
	/**
	 * Constructor
	 *
	 * @param string $apiKey 			Required API key provided by Feerik SAS
	 * @param string $locale 			(Optional) Locale for API replies. If not set, defaults to English.
	 */
	public function __construct($apiKey, $locale = '') {
		parent::__construct($apiKey, 'array', $locale);
	}
	
	/**
	 * Fetches all aggregates data from the REST API.
	 *
	 * @return array An array containing aggregates data. See API documentation for further details.
	 *
	 * @link http://api.itcg.eredan.com/docs/doku.php?id=clientapi&#getaggregatesdata
	 */
	public function getAggregatesData() {
		$cachingFile = CACHING_DIR.'aggregates.cache';
		if (!CACHING || !file_exists($cachingFile) || filemtime($cachingFile) + CACHING_EXPIRE < time()) {
			$res = $this->api('aggregates');
			foreach($res['data'] as $v)
				$aggregates[$v] = $this->getData($v);
			if (CACHING)
				$this->setCache($aggregates,'aggregates');
		}
		else
			$aggregates = $this->getCache('aggregates');

		return $aggregates;
	}

	/**
	 * Fetches custom data for the REST API.
	 *
	 * @param string $connection 		Connection name.
	 * @param array $params 			Request parameters.
	 *
	 * @return array An array containing data from the REST API reply. See API documentation for further details.
	 *
	 * @link http://api.itcg.eredan.com/docs/doku.php?id=clientapi&#getdata
	 */
	public function getData($connection, array $params = array()) {
		$http_method  = (isset($params['http_method']) && $params['http_method'] == 'POST') ? 'POST' : 'GET';
		$query_params = empty($params['query_params']) ? array() : $params['query_params'];
		$filters = empty($params['filters']) ? array() : $params['filters'];

		$connections_str = $this->buildFilters($filters, $connection);
		
		$res = $this->api($connection.$connections_str, $query_params, $http_method);
		$list = array();
		foreach ($res['data'] as $k => $v){
			if (isset($v['visuals'])) {
				$v['visuals']['big'] 	= $res['visual_url_prefix'].$v['visuals']['big'];
				$v['visuals']['medium'] = $res['visual_url_prefix'].$v['visuals']['medium'];
				$v['visuals']['small'] 	= $res['visual_url_prefix'].$v['visuals']['small'];
			}
			$list[$v['id']] = $v;
		}
		
		if (isset($res['next_page_url'])) {
			$tab = explode('?', $res['next_page_url']);
			parse_str($tab[1], $page_params);
			$this->pagination[$connection]['next'] = $page_params['start'];
		}
		if (isset($res['prev_page_url'])) {
			$tab = explode('?', $res['prev_page_url']);
			parse_str($tab[1], $page_params);
			$this->pagination[$connection]['prev'] = $page_params['start'];
		}

		return $list;
	}
	
	/**
	 * Retrieves last pagination indices for the specified connection.
	 *
	 * @param string $connection 		Connection name.
	 *
	 * @return array An associative array containing two keys called 'next' and 'prev' holding respectively the indices to use in order to call the next or the previous page of data.
	 *
	 * @link http://api.itcg.eredan.com/docs/doku.php?id=clientapi&#getpagination
	 */
	public function getPagination($connection) {
		$next = $prev = null;
		if (isset($this->pagination[$connection]['next']))
			$next = $this->pagination[$connection]['next'];
		if (isset($this->pagination[$connection]['prev']))
			$prev = $this->pagination[$connection]['prev'];
		return array('next' => $next,'prev' => $prev);
	}
	
	/**
	 * Builds a filename for a cache element.
	 * 
	 * @param string $cacheName 		Cache element name
	 * 
	 * @return string The filename for the specified cache element name.
	 */
	private static function buildCacheFileName($cacheName) {
		$dir = trim(CACHING_DIR);
		if (!empty($dir) && !preg_match('#/$#', $dir))
			$dir .= '/';
		return sprintf("%s%s.cache", $dir, $cacheName);
	}

	/**
	 * Builds the appropriate path info based on the provided filters for the request.
	 *
	 * @param array $filters 			Filters list
	 * @param string $connection 		Connection name
	 *
	 * @return string Path info string based on the provided filters.
	 */
	private function buildFilters(array $filters, $connection) {
		$connections_str = '';
		
		foreach ($filters as $filter => $values) {
			if (isset($values['operator']) && strtoupper($values['operator']) == 'AND')
				$separator = ';';
			else
				$separator = ',';

			$str = ($filter != $connection) ? '/%1$s/%2$s' : '/%2$s';
			$connections_str .= sprintf($str, $filter, implode($separator, $values['list']));
		}

		return $connections_str;
	}

	/**
	 * Sets the value of a cache element.
	 * 
	 * @param mixed $values 			Values to cache for this element.
	 * @param string $cacheName 		Cache element name
	 */	
	private function setCache($values, $cacheName) {
		$cacheFile = self::buildCacheFileName($cacheName);
		file_put_contents($cacheFile, json_encode($values));
	}
	
	/**
	 * Gets the value of a cache element.
	 *
	 * @param string $cacheName 		Cache element name
	 *
	 * @return mixed Values of this element.
	 */
	private function getCache($cacheName){
		$cacheFile = self::buildCacheFileName($cacheName);
		return json_decode(file_get_contents($cacheFile), true);
	}

}
?>
