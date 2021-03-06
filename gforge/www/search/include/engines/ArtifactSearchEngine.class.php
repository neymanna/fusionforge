<?php
/**
 * GForge Search Engine
 *
 * Copyright 2004 (c) Guillaume Smet
 *
 * http://gforge.org
 *
 * @version $Id$
 */

require_once $gfwww.'search/include/engines/GroupSearchEngine.class.php';
require_once $gfwww.'tracker/include/ArtifactTypeHtml.class.php';

class ArtifactSearchEngine extends GroupSearchEngine {
	var $ath;
	
	function ArtifactSearchEngine() {
		$this->type = SEARCH__TYPE_IS_ARTIFACT;
		$this->rendererClassName = 'ArtifactHtmlSearchRenderer';
	}
	
	function getLabel($parameters) {
		return $this->ath->getName();
	}
	
	function isAvailable($parameters) {
		if(parent::isAvailable($parameters) && isset($parameters[SEARCH__PARAMETER_ARTIFACT_ID]) && $parameters[SEARCH__PARAMETER_ARTIFACT_ID]) {
			$ath = new ArtifactTypeHtml($this->Group, $parameters[SEARCH__PARAMETER_ARTIFACT_ID]);
			if($ath && is_object($ath) && !$ath->isError()) {
				$this->ath =& $ath;
				return true;
			}
		}
		return false;
	}
	
	function & getSearchRenderer($words, $offset, $exact, $parameters) {
		$this->includeSearchRenderer();
		$rendererClassName = $this->rendererClassName;
		$renderer = new $rendererClassName($words, $offset, $exact, $parameters[SEARCH__PARAMETER_GROUP_ID], $parameters[SEARCH__PARAMETER_ARTIFACT_ID]);
		return $renderer;
	}
}

?>
