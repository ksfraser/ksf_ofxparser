<?php declare(strict_types=1);

namespace Tests;

/**
 * Helper trait for creating valid OFX test payloads with required message sets
 */
trait TestOFXHelper
{
    /**
     * Wraps partial OFX content with required message set structure for testing
     * 
     * @param string $content Partial OFX content (without wrapping message sets)
     * @param string $messageSetType Type of message set: 'bank', 'creditcard', 'investment', 'signon'
     * @return string Complete OFX XML with required message sets
     */
    protected function wrapOFXContent(string $content, string $messageSetType = 'bank'): string
    {
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
        
        $content = "<STMTTRNRS>\n";
        $content .= "<STMTRS>\n";
        $content .= "<CURDEF>USD\n";
        $content .= "<BANKTRANLIST>\n";
        $content .= "<DTSTART>20260101\n";
        $content .= "<DTEND>20260131\n";
        $content .= "<STMTTRN>\n";
        $content .= "<TRNTYPE>{$trntype}\n";
        $content .= "<DTPOSTED>{$dtposted}\n";
        $content .= "<TRNAMT>{$trnamt}\n";
        $content .= "<FITID>{$trnid}\n";
        $content .= "<MEMO>{$memo}\n";
        $content .= "</STMTTRN>\n";
        $content .= "</BANKTRANLIST>\n";
        $content .= "</STMTRS>\n";
        $content .= "</STMTTRNRS>\n";
        
        return $this->wrapOFXContent($content, 'bank');
    }
}
