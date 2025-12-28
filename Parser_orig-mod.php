<?php

namespace OfxParser;

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

        $ofxXml = $this->convertSgmlToXml($ofxSgml);

	//print_r( $ofxXml, true );
//	var_dump( $ofxXml );
        $xml = $this->xmlLoadString($ofxXml);
//	var_dump( $xml );
	//print_r( $xml );
        return new Ofx($xml);
    }

    /**
     * Detect if the OFX file is on one line. If it is, add newlines automatically.
     *
     * @param string $ofxContent
     * @return string
     */
    private function conditionallyAddNewlines($ofxContent)
    {
        if (preg_match('/<OFX>.*<\/OFX>/', $ofxContent) === 1) {
            $ofxContent =  str_replace('<', "\n<", $ofxContent); // add line breaks to allow XML to parse
		var_dump( $ofxContent );
            //return str_replace('<', "\n<", $ofxContent); // add line breaks to allow XML to parse
        }

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
    private function closeUnclosedXmlTags($line)
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
     * Convert an SGML to an XML string
     *
	*
	*	20240707 this is the function that was here originally
     * @param string $sgml
     * @return string
     */
    private function convertSgmlToXml_orig($sgml)
    {
	//20240227 New Lines were not being inserted so matching in cluseUnclosed wasn't working
		//20240707 Adding the following line seems to fix CIBC at least as far as test.php goes...
		//Either way Manulife is broken
		//Manu is 1 giant line.
		//ATB works when the following line ISN"T uncommented.
        $sgml = str_replace("<", "\n<", $sgml);
	//--20240227
        $sgml = str_replace(["\r\n", "\r", "\n\n"], "\n", $sgml);

        $lines = explode("\n", $sgml);

        $xml = '';
        foreach ($lines as $line) {
		//	var_dump( $line );
		//ATB is ending up with extra blank lines
		if( strlen( $line ) > 1 )
		{
			//var_dump( $line );
            		$res = trim( $this->closeUnclosedXmlTags($line) ) . "\n";
			//var_dump( $res );
            		$xml .= $res;
		}
        }

        return trim($xml);
    }
    /**
     * Convert an SGML to an XML string
     *
	*
	*	20240707 This is what is on the archived repository.  Not sure why this didn't pull by composer...
     * @param string $sgml
     * @return string
     */
    private function convertSgmlToXml($sgml)
    {
        $sgml = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $sgml);

        $lines = explode("\n", $sgml);
        $tags = [];

        foreach ($lines as $i => &$line) {
		var_dump( $line );
            $line = trim($this->closeUnclosedXmlTags($line)) . "\n";

            // Matches tags like <SOMETHING> or </SOMETHING>
            if (!preg_match("/^<(\/?[A-Za-z0-9.]+)>$/", trim($line), $matches)) {
                continue;
            }

            // If matches </SOMETHING>, looks back and replaces all tags like
            // <OTHERTHING> to <OTHERTHING/> until finds the opening tag <SOMETHING>
            if ($matches[1][0] == '/') {
                $tag = substr($matches[1], 1);

                while (($last = array_pop($tags)) && $last[1] != $tag) {
                    $lines[$last[0]] = "<{$last[1]}/>";
                }
            } else {
                $tags[] = [$i, $matches[1]];
            }
        }

        return implode("\n", array_map('trim', $lines));
    }
}
