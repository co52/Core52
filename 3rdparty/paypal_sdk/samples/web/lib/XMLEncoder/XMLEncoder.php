<?php
require_once PAYPALSDK_BASE_DIR . '/Serializer/Serializer.php';
require_once PAYPALSDK_BASE_DIR . '/Serializer/Unserializer.php';

/*
 * XML Encoder encodes/decodes the object into/from XML string
 * Methods Encode and Decode
 */
class XMLEncoder
{
	
	private static $FaultMessage = "FAULTMESSAGE";

	/*
	 * Encodes the request object into XML String
	 */
	public static function Encode($requestObject)
	{
		$xml = "";
		
		try
		{
			$options = array(
			                    XML_SERIALIZER_OPTION_INDENT      => '    ',
			                    XML_SERIALIZER_OPTION_LINEBREAKS  => "\n",
			                    XML_SERIALIZER_OPTION_DEFAULT_TAG => '',
			                    XML_SERIALIZER_OPTION_TYPEHINTS   => false,
			                    XML_SERIALIZER_OPTION_IGNORE_NULL => true,
			                    XML_SERIALIZER_OPTION_CLASSNAME_AS_TAGNAME => true
			                );
			                
			$serializer = new XML_Serializer($options);
			
			$result = $serializer->serialize($requestObject);
			
			if( $result === true ) {
			    $xml = $serializer->getSerializedData();
				$xml = str_replace('<>','',$xml);
				$xml = str_replace('</>','',$xml);
			}
			
			$xml = str_replace("<?xml version=\"1.0\"?>", "",$xml);
			 
		}
		catch(Exception $ex)
		{
			#throw new Exception("Error occurred while XML encoding");
			throw $ex;
		}
		
		return $xml;
	}
	
	/*
	 * Decodes back to object from given SOAP String response
	 */
	public static function Decode($XMLResponse, &$isFault)
	{
		$responseXML = null ;
		
		try
		{
			if(empty($XMLResponse))
				throw new Exception("Given Response is not a valid SOAP response.");
			
			$xmlDoc = new XMLReader();
			$res = $xmlDoc->XML($XMLResponse);
			
			if($res)
			{
				$xmlDoc->read();
				$responseXML = $xmlDoc->readOuterXml();
							
				$xmlDOM = new DOMDocument();
				$xmlDOM->loadXML($responseXML);
						
				$isFault = (trim(strtoupper($xmlDoc->localName)) == self::$FaultMessage) ;
				
				if($isFault)
				{
					$xmlDOM->loadXML($xmlDoc->readOuterXml());
				}
				
				switch ($xmlDoc->nodeType)
				{
	            	case XMLReader::ELEMENT:
	            		$nodeName = $xmlDoc->localName;
	            		$prefix = $xmlDoc->prefix;
	            		
						if(class_exists($nodeName))
	            		{
	            			$xmlNodes = $xmlDOM->getElementsByTagName($nodeName);
	            			foreach($xmlNodes as $xmlNode)
	            			{
	            				//$xmlNode->prefix = "";
	            				$xmlNode->setAttribute("_class",$nodeName);
								$xmlNode->setAttribute("_type","object");
	            			}
	            		}
	            		break;
	            }
				
				$responseXML = $xmlDOM->saveXML();
									
				$unserializer = new XML_Unserializer();
				
				$unserializer->setOption(XML_UNSERIALIZER_OPTION_COMPLEXTYPE, 'object');
				
				
				$res = $unserializer->unserialize($responseXML, false);
				
				if($res)
				{
					$responseXML = $unserializer->getUnserializedData();
				}
				
				$xmlDoc->close();
			}
			else
			{
				throw new Exception("Given Response is not a valid XML response.");
			}
			
		}
		catch(Exception $ex)
		{
			#throw new Exception("Error occurred while XML decoding");
			throw $ex;
		}
		
		return $responseXML;
	}
}

?>