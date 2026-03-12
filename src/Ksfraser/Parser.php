<?php declare(strict_types=1);

/******************************************
*	20240708 Incorporate from OKONST Parser...
*
*	20240720 MANU tested working.
*		Does ??not?? handle multiple accounts within the same OFX.
*/

namespace OfxParser;

use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Metrics\ParsingResult;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Loaders\OfxLoaderInterface;
use OfxParser\Loaders\XmlOfxLoader;
use OfxParser\Loaders\SgmlOfxLoader;

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
     * @var DefensiveParsingConfig|null
     */
    private $config = null;
    
    /**
     * @var RecoveryContext|null
     */
    private $recoveryContext = null;
    
    /**
     * @var ParsingMetrics|null
     */
    protected $metrics = null;
    
    /**
     * @var FieldExtractor|null
     */
    protected $fieldExtractor = null;
    
    /**
     * @var TransactionBuilder|null
     */
    protected $transactionBuilder = null;
    
    /**
     * @var string|null Track which parser path was used
     */
    private $parserPathUsed = null;
    
    /**
     * @var string|null Track the detected OFX version
     */
    private $ofxVersionDetected = null;
    
    /**
     * @var OfxLoaderInterface[] Available loaders
     */
    private $loaders = [];
    
    /**
     * Constructor - optionally inject custom loaders
     * 
     * @param OfxLoaderInterface[] $loaders Custom loaders (empty = use defaults)
     */
    public function __construct(array $loaders = [])
    {
        $this->loaders = $loaders;
    }
    
    /**
     * Enable defensive parsing with optional configuration
     * 
     * @param DefensiveParsingConfig|null $config Configuration (null = default)
     * @return self
     */
    public function withDefensiveParsing(?DefensiveParsingConfig $config = null): self
    {
        $this->config = $config ?? DefensiveParsingConfig::createDefault();
        $this->metrics = new ParsingMetrics();
        $this->recoveryContext = new RecoveryContext($this->config);
        $this->fieldExtractor = new FieldExtractor($this->recoveryContext, $this->metrics);
        $this->transactionBuilder = new TransactionBuilder(
            $this->fieldExtractor,
            $this->recoveryContext,
            $this->metrics
        );
        return $this;
    }
    
    /**
     * Check if defensive parsing is enabled
     * 
     * @return bool
     */
    public function isDefensiveParsingEnabled(): bool
    {
        return $this->config !== null;
    }
    
    /**
     * Check if XML parser path was used
     * 
     * @return bool
     */
    public function usedXmlPath(): bool
    {
        return $this->parserPathUsed === 'xml';
    }
    
    /**
     * Check if SGML parser path was used
     * 
     * @return bool
     */
    public function usedSgmlPath(): bool
    {
        return $this->parserPathUsed === 'sgml';
    }
    
    /**
     * Get information about the parsing path used
     * 
     * @return array
     */
    public function getParsingPathInfo(): array
    {
        return [
            'parser_used' => $this->parserPathUsed,
            'version_detected' => $this->ofxVersionDetected,
        ];
    }

    /**
     * Factory to extend support for OFX document structures.
	* OKONST
	* @since 20240708
     * @param SimpleXMLElement $xml
     * @return Ofx
     */
    /**
     * Create an instance of the Ofx object.
     * 
     * Factory method that subclasses can override to create different Ofx types
     * (e.g., InvestmentParser creates InvestmentOfx).
     *
     * @param \SimpleXMLElement|\OfxParser\Sgml\Elements\Element $element Parsed OFX element
     * @param array $header Parsed OFX header
     * @return Ofx|ParsingResult
     */
    protected function createOfx($element, array $header)
    {
        // Handle SGML Elements - use builder pattern
        if ($element instanceof \OfxParser\Sgml\Elements\Element) {
            $builder = new \OfxParser\Builders\SgmlOfxBuilder();
            $ofx = $builder->buildOfx($element, $header);
        } else {
            // Handle SimpleXMLElement - traditional constructor
            $ofx = new Ofx(
                $element,
                $this->transactionBuilder,
                $this->fieldExtractor,
                $this->metrics
            );
            $ofx->buildHeader($header);
        }
        
        // Return ParsingResult if defensive parsing is enabled
        if ($this->metrics !== null) {
            return new \OfxParser\Metrics\ParsingResult($ofx, $this->metrics);
        }

        return $ofx;
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
     * Auto-detects XML vs SGML and routes to appropriate loader
     *
     * @param string $ofxContent
     * @return  Ofx|ParsingResult
     * @throws \Exception
     */
    public function loadFromString($ofxContent)
    {
		//utf8_encode is depreciated in php8.2 and will be removed in 9
        //$ofxContent = utf8_encode($ofxContent);
		//From DevCapere - php8?
		$ofxContent = mb_convert_encoding($ofxContent, "UTF-8", mb_detect_encoding($ofxContent));
        
        $sgmlStart = stripos($ofxContent, '<OFX>');
        if ($sgmlStart === false) {
            throw new \InvalidArgumentException('OFX tag not found');
        }
        
        $ofxHeader = trim(substr($ofxContent, 0, $sgmlStart));
        $ofxBody = trim(substr($ofxContent, $sgmlStart));
        
        // Get available loaders (use defaults if none injected)
        $loaders = $this->getLoaders();
        
        // Find a loader that can handle this content
        foreach ($loaders as $loader) {
            if ($loader->canHandle($ofxHeader, $ofxBody)) {
                $this->parserPathUsed = $loader->getFormatName();
                $this->ofxVersionDetected = $loader->getVersion();
                
                // Loader returns parsed element, we create the appropriate Ofx type
                $result = $loader->load($ofxHeader, $ofxBody);
                return $this->createOfx($result['element'], $result['header']);
            }
        }
        
        throw new \InvalidArgumentException('No suitable loader found for OFX content. Format not recognized.');
    }
    
    /**
     * Get available loaders (creates defaults if none injected)
     * 
     * @return OfxLoaderInterface[]
     */
    private function getLoaders(): array
    {
        if (empty($this->loaders)) {
            // Create default loaders
            $this->loaders = [
                new XmlOfxLoader(
                    $this->transactionBuilder,
                    $this->fieldExtractor,
                    $this->metrics
                ),
                new SgmlOfxLoader(
                    $this->transactionBuilder,
                    $this->fieldExtractor,
                    $this->metrics
                ),
            ];
        }
        
        return $this->loaders;
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
        	$tag = ltrim(
			substr(	$line, 
				1, 
				strpos($line, '>') - 1
			), 
			'/' );
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
					//IF we are here, and the last[2] <> CLOSED, that tag needs to be closed.  How do we find it in LINES?
				//This is replacing things like <OFX>\n with <OFX/>\n
	                    		//$lines[$last[0]] = "<{$last[1]}/>";
					//var_dump( __FILE__ . "::" . __LINE__ . " REPLACE after POPPING lines" ); 
	                		//var_dump(  $lines );
	                	}
					//$last[1] == $tag.  But so what??
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

        // Check if it's an XML-style header (OFXv2) - starts with <?xml or <?OFX
        if(preg_match('/^<\?(xml|OFX)/i', $ofxHeader) === 1) {
            // Only parse OFX headers and not XML headers.
            $ofxHeader = preg_replace('/<\?xml .*?\?>\n?/', '', $ofxHeader);
            $ofxHeader = preg_replace(['/"/', '/\?>/', '/<\?OFX/i'], '', $ofxHeader);
            $ofxHeaderLine = explode(' ', trim($ofxHeader));

            foreach ($ofxHeaderLine as $value) {
                $tag = explode('=', $value);
                if (isset($tag[1])) {
                    $header[$tag[0]] = $tag[1];
                }
            }

            return $header;
        }

        $ofxHeaderLines = explode("\n", $ofxHeader);
        foreach ($ofxHeaderLines as $value) {
            $tag = explode(':', $value);
            if (isset($tag[1])) {
                $header[$tag[0]] = $tag[1];
            }
        }

        return $header;
    }
}

