<?php namespace Davispeixoto\Laravel4Solr;

use Illuminate\Config\Repository;

class Solr {
	protected $baseurl;
	protected $core;
	
	protected $output_format;
	protected $indent;
	
	protected $start;
	protected $limit;
	protected $qop;
	
	protected $Q;
	protected $FQ;
	protected $FL;
	protected $DF;
	protected $sort;
	
	protected $facetQuery;
	protected $facetField;
	protected $facetPrefix;
	protected $facetLimit;
	
	protected $kraken;
	
	const KRAKEN_TIMEOUT_SECONDS = 600;
	
	public function __construct(Repository $configExternal)
	{
		try {
			$this->baseurl = "http://" . $configExternal->get('laravel-4-solr::endpoint');
			if ($configExternal->get('laravel-4-solr::port') != '80') {
				$this->baseurl .= ':'. $configExternal->get('laravel-4-solr::port');
			}
			
			$this->baseurl .= $configExternal->get('laravel-4-solr::path');
			
			$this->output_format = $configExternal->get('laravel-4-solr::output_format');
			$this->indent = $configExternal->get('laravel-4-solr::indent');
			
			$this->Q = array();
			$this->FQ = array();
			$this->FL = array();
			$this->DF = array();
			$this->sort = array();
			$this->qop = NULL;
			$this->start = NULL;
			$this->limit = NULL;
			$this->facetQuery = array();
			$this->facetField = array();
			$this->facetPrefix = array();
			$this->facetLimit = NULL;
			$this->kraken = FALSE;
			
			return $this;
		} catch (Exception $e) {
			Log::error($e->getMessage());
			throw new Exception('Exception no Construtor' . $e->getMessage() . "\n\n" . $e->getTraceAsString());
		}
	}
	
	public function setCore($core)
	{
		$this->core = $core;
		return $this;
	}
	
	/**
	 * Funny option for serious stuff
	 * 
	 * Sets a long timeout for curl connection.
	 * Sometimes you have a huge result set to fetch (MBs order)
	 * and may end up with a failure in script due a curl timeout
	 * due the long time to download the result set
	 * 
	 * This option is handy to avoid that sort of failure and 
	 * ensure you download all the data you need
	 * 
	 * Take care using it, and ensure you won't face any other 
	 * performance problems, like running out of memory.
	 * 
	 * Also, you can work around paginating docs or facets
	 *
	 * @param integer $timeout
	 * @return \Davispeixoto\Laravel4Solr\Solr
	 */
	public function unleash($timeout = NULL)
	{
		$this->kraken = self::KRAKEN_TIMEOUT_SECONDS;
		if (!empty($timeout) && is_numeric($timeout)) {
			$this->kraken = intval($timeout);
		}
		
		return $this;
	}
	
	public function setQop($qop)
	{
		if (in_array(strtoupper($qop), array('AND','OR'))) {
			$this->qop = strtoupper($qop);
		}
		
		return $this;
	}
	
	public function parseQop()
	{
		$out = '';
		if (!empty($this->qop)) {
			$out = 'q.op=' . $this->qop;
		}
		
		return $out;
	}
	
	/**
	 * Here is where the magic happens
	 */
	public function getResults()
	{
		$url = $this->buildSolrUrl();
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		// Return from curl_exec rather than echoing
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);		// Follow redirects
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);		// Always ensure the connection is fresh
		
		if ($this->kraken) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);		// Wait for connection for long periods
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->kraken); // Increase timeout for looooooooong results, ensure you have enough memory
		}
		
		$result = curl_exec($ch);
		
		if ($result === FALSE) {
			$errno = curl_errno($ch);
			$error = curl_error($ch);
			curl_close($ch);
			
			throw new \Exception($error , $errno);
		}
		
		curl_close($ch);
		return $result;
	}
	
	public function addQ($field , $value , $exclude = FALSE)
	{
		if ($exclude) {
			$field = '-' . $field;
		} else {
			$field = '+' . $field;
		}
		
		$this->Q[] = array('field' => $field , 'value' => $value);
		return $this;
	}
	
	public function parseQ()
	{
		$out = '';
		if (!empty($this->Q)) {
			$out = 'q=' . $this->imploder($this->Q , array(':' , ' '));
		}
		
		return $out;
	}
	
	public function addFQ($field , $value , $exclude = FALSE)
	{
		if ($exclude) {
			$field = '-' . $field;
		} else {
			$field = '+' . $field;
		}
		
		$this->FQ[] = array('field' => $field , 'value' => $value);
		return $this;
	}
	
	public function parseFQ()
	{
		$out = '';
		if (!empty($this->FQ)) {
			$out = 'fq=' . $this->imploder($this->FQ , array(':' , ' '));
		}
		
		return $out;
	}
	
	public function addSort($field , $order = 'desc')
	{
		$this->sort[] = array('field' => $field , 'order' => $order);
		return $this;
	}
	
	public function parseSort()
	{
		$out = '';
		
		if (!empty($this->sort)) {
			$out = 'sort=' . $this->imploder($this->FQ , array(' ' , ','));
		}
	
		return $out;
	}
	
	public function setStart($start)
	{
		$this->start = $start;
		return $this;
	}
	
	public function parseStart()
	{
		$out = '';
		
		if (!empty($this->start)) {
			$out = 'start=' . $this->start;
		}
		
		return $out;
	}
	
	public function setLimit($rowsAmount)
	{
		$this->limit = $rowsAmount;
		return $this;
	}
	
	public function parseLimit()
	{
		$out = '';
	
		if (!empty($this->limit)) {
			$out = 'rows=' . $this->limit;
		}
	
		return $out;
	}
	
	public function addFL($field)
	{
		$this->FL[] = $field;
		return $this;
	}
	
	public function parseFL()
	{
		$out = '';
		
		if (!empty($this->FL)) {
			$out = 'fl=' . implode(',' , $this->FL);
		}
		
		return $out;
	} 
	
	/**
	 * 
	 * Set the Default Search Field
	 * 
	 * This option is used to override the DefaultSearch Field from Solr Schema
	 * Due the future deprecation of this Schema option, this is the preferred
	 * method for setting the field.
	 * 
	 * This option is used for matching when no query is specified, thus no field
	 *
	 * @return \Davispeixoto\Laravel4Solr\Solr
	 */
	public function setDF($field)
	{
		$this->DF = $field;
		return $this;
	}
	
	public function parseDF()
	{
		$out = '';
		
		if (!empty($this->DF)) {
			$out = 'df=' . $this->DF;
		}
		
		return $out;
	}
	
	public function setFacetQuery($field , $value)
	{
		$this->facetQuery[] = array('field' => $field , 'value' => $value);
		return $this;
	}
	
	public function parseFacetQuery()
	{
		$aux = array();
		$out = '';
		
		if (!empty($this->facetQuery)) {
			foreach($this->facetQuery as $key => $value) {
				$aux[] = urlencode('facet.query=' . $value['field'] . ':' . $value['value']);
			}
			
			$out = implode('&', $aux);
		}
		
		return $out;
	}
	
	public function setFacetField($field)
	{
		$this->facetField[] = $field;
		return $this;
	}
	
	public function parseFacetField()
	{
		$aux = array();
		$out = '';
		
		if (!empty($this->facetField)) {
			foreach($this->facetField as $key => $value) {
				$aux[] = urlencode('facet.field=' . $value);
			}
			
			$out = implode('&', $aux);
		}
		
		return $out;
	} 
	
	public function setFacetPrefix($prefix)
	{
		$this->facetPrefix[] = $prefix;
		return $this;
	}
	
	public function parseFacetPrefix()
	{
		$aux = array();
		$out = '';
		
		if (!empty($this->facetPrefix)) {
			foreach($this->facetPrefix as $key => $value) {
				$aux[] = urlencode('facet.prefix=' . $value);
			}
				
			$out = implode('&', $aux);
		}
		
		return $out;
	}
	
	public function setFacetLimit($facetLimit)
	{
		$this->facetLimit = $facetLimit;
		return $this;
	}
	
	public function parseFacetLimit()
	{
		$out = '';
		
		if (!empty($this->facetLimit)) {
			$out = 'facet.limit=' . $this->facetLimit;
		}
		
		return $out;
	}
	
	private function buildSolrUrl()
	{
		if (empty($this->core)) {
			throw new \Exception('You must set the core before triggering a query');
		}
		
		$url = $this->baseurl . $this->core . '/select?';
		
		if(!empty($this->Q)) {
			$url .= $this->imploder($this->Q, array('' , ''));
		}
		
		if(!empty($this->FQ)) {
			$url .= $this->imploder($this->FQ, array(':' , ' && '));
		}
		
		if(!empty($this->FL)) {
			$url .= $this->imploder($this->FL, array('' , ''));
		}
		
		if(!empty($this->DF)) {
			$url .= $this->imploder($this->DF, array('' , ''));
		}
		
		if(!empty($this->sort)) {
			$url .= $this->imploder($this->sort, array('' , ''));
		}
		
		if(!empty($this->start)) {
			$url .= $this->imploder($this->start, array('' , ''));
		}
		
		if(!empty($this->limit)) {
			$url .= $this->imploder($this->limit, array('' , ''));
		}
		
		if(!empty($this->facetQuery)) {
			$url .= $this->imploder($this->facetQuery, array('' , ''));
		}
		
		if(!empty($this->facetField)) {
			$url .= $this->imploder($this->facetField, array('' , ''));
		}
		
		if(!empty($this->facetPrefix)) {
			$url .= $this->imploder($this->facetPrefix, array('' , ''));
		}
		
		return $url;
	}
	
	private function imploder($inputVector, $glue)
	{
		$aux = array();
		
		foreach($inputVector as $key => $value) {
			if (is_array($value['value'])) {
				$aux[] = $value['field'] . $glue[0] . '(' . implode(',' , $value['value']) . ')';
			} else {
				$aux[] = $value['field'] . $glue[0] . $value['value'];
			}
		}
		
		return urlencode(implode($glue[1] , $aux));
	}
}