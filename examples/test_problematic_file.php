<?php

require_once __DIR__ . '/../vendor/autoload.php';

use OfxParser\Sgml\Tokenizer;
use OfxParser\Sgml\Parser;
use OfxParser\Sgml\ElementFactory;

// Test with the problematic ofxdata-bb.ofx file
$filePath = __DIR__ . '/../tests/fixtures/ofxdata-bb.ofx';

if (!file_exists($filePath)) {
    die("Error: File not found: $filePath\n");
}

echo "=== Testing SGML Parser with ofxdata-bb.ofx ===\n\n";

$ofxContent = file_get_contents($filePath);

// Extract just the SGML body (after headers)
if (preg_match('/<OFX>/i', $ofxContent, $matches, PREG_OFFSET_CAPTURE)) {
    $sgmlBody = substr($ofxContent, $matches[0][1]);
    echo "Found OFX body at position: " . $matches[0][1] . "\n";
    echo "Body length: " . strlen($sgmlBody) . " bytes\n\n";
} else {
    die("Error: Could not find <OFX> tag in file\n");
}

try {
    // Parse with SGML parser
    $factory = new ElementFactory();
    $parser = new Parser($factory);
    
    echo "Parsing...\n";
    $root = $parser->parse($sgmlBody);
    
    if ($parser->hasErrors()) {
        echo "\nParser warnings:\n";
        foreach ($parser->getErrors() as $error) {
            echo "  - $error\n";
        }
    }
    
    echo "\n✓ Successfully parsed!\n\n";
    
    // Show some basic info
    echo "Root element: " . $root->getTagName() . "\n";
    
    // Try to access bank statement info
    $stmtrs = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS ?? null;
    if ($stmtrs) {
        echo "\nBank Statement Info:\n";
        
        $acct = $stmtrs->BANKACCTFROM;
        if ($acct) {
            echo "  Bank ID: " . ($acct->BANKID ?? 'N/A') . "\n";
            echo "  Account: " . ($acct->ACCTID ?? 'N/A') . "\n";
        }
        
        $tranList = $stmtrs->BANKTRANLIST;
        if ($tranList) {
            $transactions = $tranList->getChildrenByTag('STMTTRN');
            echo "  Transactions found: " . count($transactions) . "\n";
            
            if (!empty($transactions)) {
                $firstTxn = reset($transactions);
                echo "\n  First transaction:\n";
                echo "    Type: " . ($firstTxn->TRNTYPE ?? 'N/A') . "\n";
                echo "    Date: " . ($firstTxn->DTPOSTED ?? 'N/A') . "\n";
                echo "    Amount: " . ($firstTxn->TRNAMT ?? 'N/A') . "\n";
                echo "    Memo: " . ($firstTxn->MEMO ?? 'N/A') . "\n";
            }
        }
        
        $bal = $stmtrs->LEDGERBAL;
        if ($bal) {
            echo "\n  Balance:\n";
            echo "    Amount: " . ($bal->BALAMT ?? 'N/A') . "\n";
            echo "    As of: " . ($bal->DTASOF ?? 'N/A') . "\n";
        }
    }
    
    echo "\n✓ SGML parser successfully handled this file!\n";
    echo "  (The old XML-based parser fails on this file)\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
