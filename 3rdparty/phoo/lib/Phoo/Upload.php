<?php
namespace Phoo;

/**
 * Ooyala Upload Widget wrapper 
 * 
 * @package Phoo
 * @link http://github.com/company52/Phoo
 */
class Upload extends APIWrapper
{
  
    /**
     * Get Params String 
     *
     * @param array $params Params to add to the string
     * @return string
     */
    public function getParamsString($params = false)
    {
	
		// Require parameters 
        $params = $this->toParams((array) $params)
            ->required(array('pcode', 'expires', 'signature'));

		// Return the query string
        return $params->queryString();
    }
}