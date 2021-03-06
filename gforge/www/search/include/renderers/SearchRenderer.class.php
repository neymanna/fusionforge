<?php

class SearchRenderer extends Error {
	
	/**
	 * This is not the SQL query but elements from the HTTP query
	 *
	 * @var array $query
	 */
	var $query = array();

	/**
	 * This is the searchQuery. It's a SearchQuery instance.
	 *
	 * @var object $searchQuery
	 */
	var $searchQuery;

	/**
	 * Constructor
	 *
	 * @param string $typeOfSearch type of search
	 * @param string $words words we are searching for
	 * @param int $offset offset
	 * @param boolean $isExact if we want to search for all the words or if only one matching the query is sufficient
	 */
	function SearchRenderer($typeOfSearch, $words, $isExact, $searchQuery) {
		$this->query['typeOfSearch'] = $typeOfSearch;
		$this->query['isExact'] = $isExact;
		$this->query['words'] = $words;
		
		$this->searchQuery = $searchQuery;
	}

	/**
	 * flush - flush the output
	 * This is an abstract method. It _MUST_ be implemented in children classes.
	 */
	function flush() {}
	
}

?>
