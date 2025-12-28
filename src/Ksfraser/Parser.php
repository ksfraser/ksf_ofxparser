<?php

/******************************************
*	20240708 Incorporate from OKONST Parser...
*
*	20240720 MANU tested working.
*		Does ??not?? handle multiple accounts within the same OFX.
*/

namespace OfxParser;

//use SimpleXMLElement;


/**
 * An OFX parser library
 *
 * Heavily refactored from Guillaume Bailleul's grimfor/ofxparser
 *
 * @author Guillaume BAILLEUL <contact@guillaume-bailleul.fr>
 * @author James Titcumb <hello@jamestitcumb.com>
 * @author Oliver Lowe <mrtriangle@gmail.com>
 */
class Parser
{

    /**
     * Factory to extend support for OFX document structures.
	* OKONST
	* @since 20240708
     * @param SimpleXMLElement $xml
     * @return Ofx
     */
    //protected function createOfx(SimpleXMLElement $xml)
    protected function createOfx($xml)
    {
        return new Ofx($xml);
    }

    /**
     * Load an OFX file into this parser by way of a filename
     *
     * @param string $ofxFile A path that can be loaded with file_get_contents
     * @return Ofx
     * @throws \Exception
     */
    public function loadFromFile($ofxFile)
    {
        if (!file_exists($ofxFile)) {
            throw new \InvalidArgumentException("File '{$ofxFile}' could not be found");
        }

        return $this->loadFromString(file_get_contents($ofxFile));
    }

    /**
     * Load an OFX by directly using the text content
     *
     * @param string $ofxContent
     * @return  Ofx
     * @throws \Exception
     */
    public function loadFromString($ofxContent)
    {
        $ofxContent = utf8_encode($ofxContent);
        $ofxContent = $this->conditionallyAddNewlines($ofxContent);

        $sgmlStart = stripos($ofxContent, '<OFX>');
        $ofxSgml = trim(substr($ofxContent, $sgmlStart));

 	$ofxHeader =  trim(substr($ofxContent, 0, $sgmlStart));
        $header = $this->parseHeader($ofxHeader);

       $ofxSgml = trim(substr($ofxContent, $sgmlStart));
        if (stripos($ofxHeader, '<?xml') === 0) {
            $ofxXml = $ofxSgml;
        } else {
            //$ofxSgml = $this->conditionallyAddNewlines($ofxSgml);
            $ofxXml = $this->convertSgmlToXml($ofxSgml);
        }


        $xml = $this->xmlLoadString($ofxXml);

        if (empty($xml) || is_null($xml)) {
            throw new \InvalidArgumentException('Content is not valid ofx schema, please visit https://www.ofx.net/downloads.html and check valid schemas.');
        }

        $ofx = $this->createOfx($xml);
	//I haven't updated OFX yet so buildHeader isn't there
        //$ofx->buildHeader($header);

        return $ofx;
//        return new Ofx($xml);
    }

    /**
     * Detect if the OFX file is on one line. If it is, add newlines automatically.
     *
     * @param string $ofxContent
     * @return string
     */
    private function conditionallyAddNewlines($ofxContent)
    {
            $ofxContent =  str_replace('<', "\n<", $ofxContent); // add line breaks to allow XML to parse
		//var_dump( $ofxContent );

        return $ofxContent;
    }

    /**
     * Load an XML string without PHP errors - throws exception instead
     *
     * @param string $xmlString
     * @throws \Exception
     * @return \SimpleXMLElement
     */
    private function xmlLoadString($xmlString)
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);

        if ($errors = libxml_get_errors()) {
            throw new \RuntimeException('Failed to parse OFX: ' . var_export($errors, true));
        }

        return $xml;
    }

    /**
     * Detect any unclosed XML tags - if they exist, close them
     *
     * @param string $line
     * @return string
     */
    private function closeUnclosedXmlTags_preg_match($line)
    {
        // Matches: <SOMETHING>blah
        // Does not match: <SOMETHING>
        // Does not match: <SOMETHING>blah</SOMETHING>
        if (preg_match(
            "/<([A-Za-z0-9.]+)>([\wà-úÀ-Ú0-9\.\-\_\+\, ;:\[\]\'\&\/\\\*\(\)\+\{\|\}\!\£\$\?=@€£#%±§~`\"]+)$/",
            trim($line),
            $matches
        )) {
            return "<{$matches[1]}>{$matches[2]}</{$matches[1]}>";
        }
        return $line;
    }

   /**
     * Detect any unclosed XML tags - if they exist, close them
     *
	*OKONST
	* Tested working 20240708
     * @param string $line
     * @return string
     */
    private function closeUnclosedXmlTags($line)
    {
        // Special case discovered where empty content tag wasn't closed
        $line = trim($line);
	//Close empty MEMO tag
        if (preg_match('/<MEMO>$/', $line) === 1) {
            return '<MEMO></MEMO>';
        }

        // Matches: <SOMETHING>blah
        // Does not match: <SOMETHING>
        // Does not match: <SOMETHING>blah</SOMETHING>
        if (preg_match(
            "/<([A-Za-z0-9.]+)>([\wÃ -ÃºÃ€-Ãš0-9\.\-\_\+\, ;:\[\]\'\&\/\\\*\(\)\+\{\|\}\!\Â£\$\?=@â‚¬Â£#%Â±Â§~`\"]+)$/",
            $line,
            $matches
        )) {
            $line = "<{$matches[1]}>{$matches[2]}</{$matches[1]}>";
        }
        return $line;
    }


	/**//**
	 * Extract the tag
	 * 
	 * Find the tag in the line, and extract it.
	 *
	 * This code was in another function but was being
	 * duplicated into a 2nd so refactored.
	 *
	 * @since 20240708
	 *
	 * @param string
	 * @return string tag
	 *************************/
	function extract_tag( $line )
	{
/* Logging * /
		var_dump( __LINE__ . " Extract_Tag" );
		var_dump( $line );
/* */

        	$tag = ltrim(
			substr(	$line, 
				1, 
				strpos($line, '>') - 1
			), 
			'/' );
/* Logging * /
		var_dump( __LINE__ . " END Extract_Tag::" . $tag . "::" );
		//var_dump( $tag );
/* */
		return $tag;
	}
    /**
     * Convert an SGML to an XML string
     *
	*
	* OKONST
	* @since 20240708
	*
	*	ASSUMPTION:  Each tag starts a new line
	*	  conditionallyAddNewlines adds a \n in front of each <
	*	  Therefore each <TAG> and </TAG> should start a line.
	*	  <TAG>DATA</TAG> becomes \n<TAG>DATA\n</TAG>
	*
	*
     * @param string $sgml
     * @return string
     */
    private function convertSgmlToXml($sgml)
    {
        $sgml = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $sgml);

        $lines = explode("\n", $sgml);
        $tags = [];		//The depth of embeddedness.  i.e. <tag><tag><tag>...
        $matches = [];
	$depth = 0;

        foreach ($lines as $linenumber => &$line) 
	{
       		// Matches tags like <SOMETHING> or </SOMETHING>
                //var_dump( __LINE__ . " If ! preg_match  ^<(\/?[A-Za-z0-9.]+)>$ ::" . $line );
			//pattern, subject, matches
			//If matches is provided, then it is filled with the results of search. 
			//	$matches[0] will contain the text that matched the full pattern, 
			//	$matches[1] will have the text that matched the first captured parenthesized subpattern, and so on.
		$tag = $this->extract_tag( $line );
		if( strlen( $tag ) <= 1 )
		{
			//var_dump( __LINE__ . " Tag EMPTY.  Line Length: " . strlen( $line ) . " AND Line: " . $line );
			if( strlen( $line ) < 3 )
			{
				//<> takes 2
				continue;
			}
		}
        	if ( ! preg_match("/^<(\/?[A-Za-z0-9.]+)>$/", trim($line), $matches)) 
		{
			//Didn't match an OPEN or CLOSE tag by itself.  Therefore tag has data.		<TAG>XXX
			//Matches will always be an empty 1 element array on a non match
            		$line = trim($this->closeUnclosedXmlTags($line)) . "\n";
			$depth++;
			//Push the tag onto the list
			//var_dump( __LINE__ . " Pushing OPENING Tag onto stack::" . $tag . "::" ); 
			$tags[] = [ $linenumber, $tag, "CLOSED" ];
			//var_dump( $tags );
                	continue;
        	}
		else
		{
			//This is either an OPEN tag or a CLOSE tag.
		}
	            	if ($matches[1][0] == '/') 
			{
				//This is a CLOSING tag.
		
				$popcount = 0;
				while (($last = array_pop($tags)) && $last[1] != $tag) {
					$popcount++;
/* Logging * /
					var_dump( __FILE__ . "::" . __LINE__ . " while pop(tags) [1] != CLOSE tag:: " . $last[1] . " :: " . $tag . " ::" ); 
	                		var_dump(  $tags );
					//var_dump( __LINE__ . " Display LINES" ); 
	                		//var_dump(  $lines );
					var_dump( __FILE__ . "::" . __LINE__ . " tags compared against last (below) and tag (above)" ); 
	                		var_dump(  $last );
/* */
					//IF we are here, and the last[2] <> CLOSED, that tag needs to be closed.  How do we find it in LINES?
				//This is replacing things like <OFX>\n with <OFX/>\n
	                    		//$lines[$last[0]] = "<{$last[1]}/>";
					//var_dump( __FILE__ . "::" . __LINE__ . " REPLACE after POPPING lines" ); 
	                		//var_dump(  $lines );
	                	}
					//$last[1] == $tag.  But so what??
/* Logging * /
				var_dump( __FILE__ . "::" . __LINE__ . " Pop Count::" . $popcount ); 
				var_dump( __FILE__ . "::" . __LINE__ . " last == tag::" . $last[1] . "::" . $tag ); 
/* */
				if( isset(  $last[2] ) AND $last[2] == "CLOSED" )
				{
					//We closed this tag so this found CLOSE tag is redundant
					$line = "\n";
				}
/* Logging * /
				//var_dump( $last ); 	//Array of Line#, TAG
				//var_dump(  $matches ); //Array of </TAG> amd /TAG
/* */

				$previousline = $linenumber - 1;
				if( $depth == 1 )
				{
/* Logging * /
						var_dump( __FILE__ . "::" . __LINE__ . " Depth == 1.  Previous line should be DATA to this TAG and closed!" );
							var_dump( __FILE__ . "::" . __LINE__ . "::" . $previousline );
							var_dump( $lines[ $previousline] );
							var_dump( $line );
/* */
						if( $this->extract_tag( $line ) == $this->extract_tag( $lines[ $previousline ] ) )
						{
							//var_dump( __FILE__ . "::" . __LINE__ . " Cleearing close tag, resetting depth"  );
							$line = "\n";
							$depth = 0;
						}
						else
						{
							//var_dump( __FILE__ . "::" . __LINE__ . " ERROR ERROR ERROR depth is only 1 but tags don't match!!!" );
						}
				}
				else
				{
						//var_dump( __FILE__ . "::" . __LINE__ . " Depth !== 1.  Previous line is NOT DATA to this TAG!:" . $depth  );	
						//So if we popped off of LAST, we should go back 1 level.
				}
	            	} else {
				//line, tag	OPENING TAG
				//var_dump( __FILE__ . "::" . __LINE__ . " Pushing OPENING Tag onto stack::" . $tag . "::" ); 
	                	$tags[] = [$linenumber, $matches[1]];
	            	}
	}//foreach

        $mapped =  array_map('trim', $lines);
        $imploded = implode("\n", $mapped);
        $imploded = str_replace(["\r\n", "\r", "\n\n"], "\n", $imploded);
        $imploded = str_replace(["\n\n"], "\n", $imploded);
/*
	if( $debug )
	{
		var_dump( __FILE__ . "::" . __LINE__  );
		var_dump( $imploded );
	}
*/
        return $imploded;
        //return implode("\n", array_map('trim', $lines));
    }
   /**
     * Parse the SGML Header to an Array
     *
     * @param string $ofxHeader
     * @param int $sgmlStart
     * @return array
     */
    private function parseHeader($ofxHeader)
    {
        $header = [];


        $ofxHeader = trim($ofxHeader);
        // Remove empty new lines.
        $ofxHeader = preg_replace('/^\n+/m', '', $ofxHeader);

        // Check if it's an XML file (OFXv2)
        if(preg_match('/^<\?xml/', $ofxHeader) === 1) {
            // Only parse OFX headers and not XML headers.
            $ofxHeader = preg_replace('/<\?xml .*?\?>\n?/', '', $ofxHeader);
            $ofxHeader = preg_replace(['/"/', '/\?>/', '/<\?OFX/i'], '', $ofxHeader);
            $ofxHeaderLine = explode(' ', trim($ofxHeader));

            foreach ($ofxHeaderLine as $value) {
                $tag = explode('=', $value);
                $header[$tag[0]] = $tag[1];
            }

            return $header;
        }

        $ofxHeaderLines = explode("\n", $ofxHeader);
        foreach ($ofxHeaderLines as $value) {
            $tag = explode(':', $value);
            $header[$tag[0]] = $tag[1];
        }

        return $header;
    }
}

