<?php

class rex_effect_abstract
{

	var $params = array(); // lokale parameter
	var $img = array(); // img mit parametern
	
	function setParams(&$img,$params)
	{
		$this->img = &$img;
		$this->params = $params;
	}	
	
	function execute()
	{
		
	}
	
	function getParams()
	{

	}
	
}