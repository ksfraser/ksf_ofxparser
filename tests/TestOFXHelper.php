<?php declare(strict_types=1);

namespace Tests;

/**
 * Helper trait for creating valid OFX test payloads with required message sets
 */
trait TestOFXHelper
{
    /**
     * Wraps partial OFX content with required message set structure for testing
     * Supports multiple OFX format variations including complete documents, STMTFRS structures, and raw XML
     * Ensures all required transaction fields are present with defaults if missing
     * 
     * @param string $content OFX content (may be partial, complete, or alternative format)
     * @param string $messageSetType Type of message set: 'bank', 'creditcard', 'investment', 'signon'
     * @param array $options Configuration options for wrapping behavior
     * @return string Complete OFX XML with required message sets
     */
    protected function wrapOFXContent(string $content, string $messageSetType = 'bank', array $options = []): string
    {
        // Edge case 1: Content is already a complete XML document (starts with <?xml)
        if (preg_match('/^\s*<\?xml/i', $content)) {
            // Check if it already has SIGNONMSGSRSV1 wrapped
            if (preg_match('/<SIGNONMSGSRSV1>/i', $content)) {
                // Already complete with signon, pass through as-is
                return $content;
            }
            
            // Complete XML but missing SIGNONMSGSRSV1 - need to add it
            // Extract the OFX root content (everything between <OFX> and </OFX>)
            if (preg_match('/<OFX>(.*)<\/OFX>/is', $content, $matches)) {
                $ofxContent = $matches[1];
                // Add SIGNONMSGSRSV1 wrapper
                $wrapped = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<OFX>\n";
                $wrapped .= "<SIGNONMSGSRSV1>\n";
                $wrapped .= "<SONRS>\n";
                $wrapped .= "<STATUS>\n";
                $wrapped .= "<CODE>0</CODE>\n";
                $wrapped .= "<SEVERITY>INFO</SEVERITY>\n";
                $wrapped .= "</STATUS>\n";
                $wrapped .= "<DTSERVER>20260114120000</DTSERVER>\n";
                $wrapped .= "<LANGUAGE>ENG</LANGUAGE>\n";
                $wrapped .= "</SONRS>\n";
                $wrapped .= "</SIGNONMSGSRSV1>\n";
                $wrapped .= $ofxContent;
                $wrapped .= "\n</OFX>";
                return $wrapped;
            }
            
            // Fallback: return as-is if structure doesn't match
            return $content;
        }
        
        // Edge case 2: Content uses STMTFRS structure (Statement Format Response, alternative format)
        $isSTMTFRS = preg_match('/<STMTFRS>/i', $content);
        $skipFieldInjection = isset($options['skipFieldInjection']) ? $options['skipFieldInjection'] : $isSTMTFRS;
        
        // Only inject transaction fields if not using STMTFRS and not explicitly skipped
        if (!$skipFieldInjection) {
            // Ensure content has complete STMTTRN structure with required fields
            if (preg_match('/<STMTTRN>/i', $content)) {
                // Add DTPOSTED if missing
                if (!preg_match('/<DTPOSTED>/i', $content)) {
                    $content = preg_replace('/<STMTTRN>/i', "<STMTTRN>\n<DTPOSTED>20260114</DTPOSTED>", $content);
                }
                // Add FITID if missing (FITID is required like TRNID)
                if (!preg_match('/<FITID>/i', $content) && !preg_match('/<TRNID>/i', $content)) {
                    $content = preg_replace('/<STMTTRN>/i', "<STMTTRN>\n<FITID>FITID000001</FITID>", $content);
                }
                // Add TRNAMT if missing
                if (!preg_match('/<TRNAMT>/i', $content)) {
                    $content = preg_replace('/<STMTTRN>/i', "<STMTTRN>\n<TRNAMT>100.00</TRNAMT>", $content);
                }
            }
            
            // Ensure BANKTRANLIST has DTSTART and DTEND if not present
            if (preg_match('/<BANKTRANLIST>/i', $content)) {
                if (!preg_match('/<DTSTART>/i', $content)) {
                    $content = preg_replace('/<BANKTRANLIST>/i', "<BANKTRANLIST>\n<DTSTART>20260101</DTSTART>", $content);
                }
                if (!preg_match('/<DTEND>/i', $content)) {
                    $content = preg_replace('/<\/BANKTRANLIST>/i', "<DTEND>20260131</DTEND>\n</BANKTRANLIST>", $content);
                }
            }
        }
        
        // For STMTFRS or when no wrapping needed, just add basic OFX wrapper without SIGNONMSGSRSV1
        if ($isSTMTFRS || (isset($options['minimalWrapper']) && $options['minimalWrapper'])) {
            $wrapped = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<OFX>\n";
            
            // Add appropriate message set based on type
            switch ($messageSetType) {
                case 'bank':
                    $wrapped .= "<BANKMSGSRSV1>\n{$content}\n</BANKMSGSRSV1>";
                    break;
                case 'creditcard':
                    $wrapped .= "<CREDITCARDMSGSRSV1>\n{$content}\n</CREDITCARDMSGSRSV1>";
                    break;
                case 'investment':
                    $wrapped .= "<INVSTMTMSGSRSV1>\n{$content}\n</INVSTMTMSGSRSV1>";
                    break;
                default:
                    $wrapped .= "<BANKMSGSRSV1>\n{$content}\n</BANKMSGSRSV1>";
            }
            
            $wrapped .= "\n</OFX>";
            return $wrapped;
        }
        
        // Standard wrapping with SIGNONMSGSRSV1
        $wrapped = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20260114120000</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
XML;
        
        // Add appropriate message set based on type
        switch ($messageSetType) {
            case 'bank':
                $wrapped .= "\n<BANKMSGSRSV1>\n{$content}\n</BANKMSGSRSV1>";
                break;
            case 'creditcard':
                $wrapped .= "\n<CREDITCARDMSGSRSV1>\n{$content}\n</CREDITCARDMSGSRSV1>";
                break;
            case 'investment':
                $wrapped .= "\n<INVSTMTMSGSRSV1>\n{$content}\n</INVSTMTMSGSRSV1>";
                break;
            case 'signon':
                // Already handled above, just add content if provided
                $wrapped .= "\n{$content}";
                break;
            default:
                $wrapped .= "\n<BANKMSGSRSV1>\n{$content}\n</BANKMSGSRSV1>";
        }
        
        $wrapped .= "\n</OFX>";
        
        return $wrapped;
    }

    /**
     * Parse OFX content, automatically wrapping if needed
     * This method intelligently handles raw XML, partial content, and complete documents
     * 
     * @param string $content Raw or partial OFX content
     * @param string $messageSetType Type of message set for auto-wrapping
     * @return mixed Parsed OFX object
     */
    protected function parseOFX(string $content, string $messageSetType = 'bank')
    {
        $wrappedContent = $this->wrapOFXContent($content, $messageSetType);
        return $this->parser->loadFromString($wrappedContent);
    }

    /**
     * Creates a simple valid OFX statement with basic transaction
     * 
     * @param array $transactionData Optional transaction data
     * @return string Complete OFX XML
     */
    protected function createSimpleOFXStatement(array $transactionData = []): string
    {
        $trnid = $transactionData['id'] ?? '12345';
        $trntype = $transactionData['type'] ?? 'DEBIT';
        $trnamt = $transactionData['amount'] ?? '100.00';
        $dtposted = $transactionData['date'] ?? '20260114';
        $memo = $transactionData['memo'] ?? 'Test Transaction';
        
        // Escape XML special characters
        $trntype = htmlspecialchars($trntype, ENT_XML1, 'UTF-8');
        $trnamt = htmlspecialchars((string)$trnamt, ENT_XML1, 'UTF-8');
        $memo = htmlspecialchars($memo, ENT_XML1, 'UTF-8');
        $trnid = htmlspecialchars($trnid, ENT_XML1, 'UTF-8');
        
        $content = "<STMTTRNRS>\n";
        $content .= "<STMTRS>\n";
        $content .= "<CURDEF>USD</CURDEF>\n";
        $content .= "<BANKTRANLIST>\n";
        $content .= "<DTSTART>20260101</DTSTART>\n";
        $content .= "<DTEND>20260131</DTEND>\n";
        $content .= "<STMTTRN>\n";
        $content .= "<TRNTYPE>{$trntype}</TRNTYPE>\n";
        $content .= "<DTPOSTED>{$dtposted}</DTPOSTED>\n";
        $content .= "<TRNAMT>{$trnamt}</TRNAMT>\n";
        $content .= "<FITID>{$trnid}</FITID>\n";
        $content .= "<MEMO>{$memo}</MEMO>\n";
        $content .= "</STMTTRN>\n";
        $content .= "</BANKTRANLIST>\n";
        $content .= "<LEDGERBAL>\n";
        $content .= "<BALAMT>5000.00</BALAMT>\n";
        $content .= "<DTASOF>20260131</DTASOF>\n";
        $content .= "</LEDGERBAL>\n";
        $content .= "<BANKACCTFROM>\n";
        $content .= "<BANKID>123456</BANKID>\n";
        $content .= "<ACCTID>9876543</ACCTID>\n";
        $content .= "<ACCTTYPE>CHECKING</ACCTTYPE>\n";
        $content .= "</BANKACCTFROM>\n";
        $content .= "</STMTRS>\n";
        $content .= "</STMTTRNRS>\n";
        
        return $this->wrapOFXContent($content, 'bank');
    }
}
