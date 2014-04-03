<?php namespace Davispeixoto\Laravel4Solr;

use Illuminate\Config\Repository;

class Solr {
	protected $baseurl;
	protected $core;
	protected $output_format;
	protected $indent;
	protected $Q;
	protected $FQ;
	protected $FL;
	protected $DF;
	protected $sort;
	protected $start;
	protected $limit;
	protected $facetQuery;
	protected $facetField;
	protected $facetPrefix;
	
	public function __construct(Repository $configExternal)
	{
		try {
			$this->baseurl = "http://" . $configExternal->get('laravel-4-solr::endpoint');
			if ($configExternal->get('laravel-4-solr::port') != '80') {
				$this->baseurl .= ':'. $configExternal->get('laravel-4-solr::port');
			}
			
			$this->baseurl .= '/solr/';
			
			$this->output_format = $configExternal->get('laravel-4-solr::output_format');
			$this->indent = $configExternal->get('laravel-4-solr::indent');
			
			$this->Q = array();
			$this->FQ = array();
			$this->FL = array();
			$this->DF = array();
			$this->sort = array();
			$this->start = NULL;
			$this->limit = NULL;
			$this->facetQuery = array();
			$this->facetField = array();
			$this->facetPrefix = array();
			
			return $this;
		} catch (Exception $e) {
			Log::error($e->getMessage());
			throw new Exception('Exception no Construtor' . $e->getMessage() . "\n\n" . $e->getTraceAsString());
		}
	}
	
	public function setCore($core)
	{
		$this->core = $core;
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
		$result = curl_exec($ch);
		
		if ($result === FALSE) {
			curl_close($ch);
			
			$errno = curl_errno($ch);
			$error = curl_error($ch);
			throw new \Exception($error , $errno);
		}
		
		curl_close($ch);
		return $result;
	}
	
	public function addQ()
	{
		
	}
	
	public function addFQ($field , $value)
	{
		$this->FQ[] = array('field' => $field , 'value' => $value);
	}
	
	public function addSort($field , $order)
	{
		$this->sort[] = array('field' => $field , 'order' => $order);
	}
	
	public function setStart()
	{
	
	}
	
	public function setLimit()
	{
		
	}
	
	public function setFL()
	{
	
	}
	
	public function setDF()
	{
	
	}
	
	public function setFacetQuery()
	{
	
	}
	
	public function setFacetField()
	{
	
	}
	
	public function setFacetPrefix()
	{
	
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
		
		foreach($inputVector as $key => $value){
			$aux[] = $value['field'] . $glue[0] . $value['value'];
		}
		
		return urlencode(implode($glue[1] , $aux));
	}
}