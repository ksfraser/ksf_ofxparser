<?php

/**
 * Example: Using the new SGML Parser
 * 
 * Demonstrates parsing OFX SGML directly without converting to XML
 */

require __DIR__ . '/../vendor/autoload.php';

use OfxParser\Sgml\Parser;
use OfxParser\Sgml\Elements\Element;

// Example OFX SGML with unclosed tags (typical of OFX v1)
$ofxContent = <<<'OFX'
<OFX>
<SIGNONMSGSRSV1>
 <SONRS>
  <STATUS>
   <CODE>0</CODE>
   <SEVERITY>INFO</SEVERITY>
  </STATUS>
  <DTSERVER>20151209
  <LANGUAGE>POR
  <FI>
   <ORG>Banco do Brasil
   <FID>001
  </FI>
 </SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
 <STMTTRNRS>
  <STMTRS>
   <CURDEF>BRL
   <BANKACCTFROM>
    <BANKID>001
    <ACCTID>455000-5
    <ACCTTYPE>CHECKING
   </BANKACCTFROM>
   <BANKTRANLIST>
    <DTSTART>20151030
    <DTEND>20151130
    <STMTTRN>
     <TRNTYPE>DEP
     <DTPOSTED>20151103
     <TRNAMT>239.55
     <FITID>20151103023955
     <MEMO>DOC CRÉDITO EM CONTA
    </STMTTRN>
    <STMTTRN>
     <TRNTYPE>DEBIT
     <DTPOSTED>20151105
     <TRNAMT>-50.00
     <FITID>20151105005000
     <MEMO>Pagamento
    </STMTTRN>
   </BANKTRANLIST>
   <LEDGERBAL>
    <BALAMT>239.35
    <DTASOF>20151209
   </LEDGERBAL>
  </STMTRS>
 </STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

echo "=== SGML Parser Demo ===\n\n";

// Parse the SGML
$parser = new Parser();
$root = $parser->parse($ofxContent);

echo "1. Root element: {$root->getTagName()}\n\n";

// Access elements using object syntax (SimpleXML-like)
echo "2. Sign-on date: " . $root->SIGNONMSGSRSV1->SONRS->DTSERVER . "\n";
echo "   Language: " . $root->SIGNONMSGSRSV1->SONRS->LANGUAGE . "\n";
echo "   Bank: " . $root->SIGNONMSGSRSV1->SONRS->FI->ORG . "\n\n";

// Access account info
echo "3. Account Info:\n";
$acct = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM;
echo "   Bank ID: {$acct->BANKID}\n";
echo "   Account: {$acct->ACCTID}\n";
echo "   Type: {$acct->ACCTTYPE}\n\n";

// Access transactions
echo "4. Transactions:\n";
$tranList = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST;
$transactions = $tranList->getChildrenByTag('STMTTRN');

foreach ($transactions as $i => $txn) {
    echo "   Transaction " . ($i + 1) . ":\n";
    echo "     Type: {$txn->TRNTYPE}\n";
    echo "     Date: {$txn->DTPOSTED}\n";
    echo "     Amount: {$txn->TRNAMT}\n";
    echo "     ID: {$txn->FITID}\n";
    echo "     Memo: {$txn->MEMO}\n\n";
}

// Show balance
echo "5. Balance:\n";
$bal = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS->LEDGERBAL;
echo "   Amount: {$bal->BALAMT}\n";
echo "   As of: {$bal->DTASOF}\n\n";

// Demonstrate validation
echo "6. Validation:\n";
if (isset($transactions[0])) {
    $dtposted = $transactions[0]->getFirstChild('DTPOSTED');
    if ($dtposted) {
        $errors = $dtposted->validate();
        echo "   DTPOSTED validation: " . (empty($errors) ? "✓ Valid" : "✗ " . implode(', ', $errors)) . "\n";
    }
} else {
    echo "   No transactions to validate\n";
}

// Show typed values
echo "\n7. Typed Values:\n";
if (!empty($transactions)) {
    $firstTxn = reset($transactions); // Get first transaction regardless of index
    $amount = $firstTxn->getFirstChild('TRNAMT');
    if ($amount) {
        echo "   Raw amount: {$amount->getTextValue()}\n";
        echo "   Typed amount: " . var_export($amount->getValue(), true) . "\n";
        echo "   Type: " . $amount->getDataType() . "\n\n";
    }
} else {
    echo "   No transactions available\n\n";
}

// Show parsing errors (if any)
if ($parser->hasErrors()) {
    echo "8. Parsing Errors:\n";
    foreach ($parser->getErrors() as $error) {
        echo "   - $error\n";
    }
} else {
    echo "8. No parsing errors!\n";
}

echo "\n=== Benefits ===\n";
echo "✓ Parses SGML directly (no lossy XML conversion)\n";
echo "✓ Handles unclosed tags naturally\n";
echo "✓ Provides typed values (DateTime, float, etc.)\n";
echo "✓ Validates data formats\n";
echo "✓ SimpleXML-like syntax for easy migration\n";
echo "✓ Forward compatible (unknown tags allowed)\n";
