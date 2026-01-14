<?php
/**
 * Debug VISA file parsing issue
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OfxParser\Parser as OldParser;
use OfxParser\Sgml\Parser as SgmlParser;
use OfxParser\Sgml\DateFormatter;

$visaFile = __DIR__ . '/../../../qfx_files/2019-20260112 cibc VISA.ofx';

if (!file_exists($visaFile)) {
    die("File not found: $visaFile\n");
}

echo "Testing VISA file: " . basename($visaFile) . "\n";
echo str_repeat("=", 80) . "\n\n";

// Test with old parser
echo "OLD PARSER:\n";
try {
    $parser = new OldParser();
    $ofx = $parser->loadFromFile($visaFile);
    
    echo "  ✓ Parsed successfully\n";
    
    // Check for bank accounts
    $bankAccounts = $ofx->bankAccounts ?? [];
    echo "  Bank accounts: " . count($bankAccounts) . "\n";
    
    foreach ($bankAccounts as $i => $account) {
        echo "  Account #$i:\n";
        echo "    Type: " . ($account->accountType ?? 'N/A') . "\n";
        echo "    Number: " . ($account->accountNumber ?? 'N/A') . "\n";
        if ($statement = $account->statement) {
            echo "    Transactions: " . count($statement->transactions) . "\n";
        }
    }
    
    // Check for credit card accounts
    $creditCards = $ofx->creditCardAccounts ?? [];
    echo "  Credit card accounts: " . count($creditCards) . "\n";
    
    foreach ($creditCards as $i => $card) {
        echo "  Card #$i:\n";
        echo "    Number: " . ($card->accountNumber ?? 'N/A') . "\n";
        if ($statement = $card->statement) {
            echo "    Transactions: " . count($statement->transactions) . "\n";
            if (!empty($statement->transactions)) {
                $txn = $statement->transactions[0];
                echo "    First txn: " . ($txn->type ?? '') . " " . ($txn->amount ?? '') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "  ✗ Failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// Test with SGML parser
echo "SGML PARSER:\n";
try {
    $content = file_get_contents($visaFile);
    
    if (preg_match('/<OFX>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
        $sgmlBody = substr($content, $matches[0][1]);
        
        // Show first 500 chars to see structure
        echo "  First 500 chars of OFX body:\n";
        echo "  " . substr($sgmlBody, 0, 500) . "\n\n";
        
        $parser = new SgmlParser();
        $root = $parser->parse($sgmlBody);
        
        echo "  ✓ Parsed successfully\n";
        
        // Check BANKMSGSRSV1
        $bankMsgs = $root->BANKMSGSRSV1 ?? null;
        if ($bankMsgs) {
            echo "  Found BANKMSGSRSV1\n";
            $stmttrnrs = $bankMsgs->STMTTRNRS ?? null;
            if ($stmttrnrs) {
                echo "    Found STMTTRNRS\n";
                $stmtrs = $stmttrnrs->STMTRS ?? null;
                if ($stmtrs) {
                    echo "      Found STMTRS\n";
                    $tranList = $stmtrs->BANKTRANLIST ?? null;
                    if ($tranList) {
                        $transactions = $tranList->getChildrenByTag('STMTTRN');
                        echo "        Transactions: " . count($transactions) . "\n";
                    } else {
                        echo "      ✗ No BANKTRANLIST found\n";
                    }
                }
            }
        } else {
            echo "  No BANKMSGSRSV1 found\n";
        }
        
        // Check CREDITCARDMSGSRSV1
        $ccMsgs = $root->CREDITCARDMSGSRSV1 ?? null;
        if ($ccMsgs) {
            echo "  Found CREDITCARDMSGSRSV1\n";
            $ccstmttrnrs = $ccMsgs->CCSTMTTRNRS ?? null;
            if ($ccstmttrnrs) {
                echo "    Found CCSTMTTRNRS\n";
                $ccstmtrs = $ccstmttrnrs->CCSTMTRS ?? null;
                if ($ccstmtrs) {
                    echo "      Found CCSTMTRS\n";
                    
                    // Check for account info
                    $acct = $ccstmtrs->CCACCTFROM ?? null;
                    if ($acct) {
                        echo "      Account: " . ($acct->ACCTID ?? 'N/A') . "\n";
                    }
                    
                    // Check for transactions
                    $tranList = $ccstmtrs->BANKTRANLIST ?? null;
                    if ($tranList) {
                        $transactions = $tranList->getChildrenByTag('STMTTRN');
                        echo "      Transactions: " . count($transactions) . "\n";
                        if (!empty($transactions)) {
                            $txn = reset($transactions);
                            echo "      First txn: " . ($txn->TRNTYPE ?? '') . " " . ($txn->TRNAMT ?? '') . "\n";
                        }
                    } else {
                        echo "      ✗ No BANKTRANLIST found\n";
                        
                        // Show what children CCSTMTRS has
                        $children = $ccstmtrs->getChildren();
                        echo "      CCSTMTRS children: ";
                        $childTags = array_map(function($c) { return $c->getTagName(); }, $children);
                        echo implode(', ', array_unique($childTags)) . "\n";
                    }
                }
            }
        } else {
            echo "  No CREDITCARDMSGSRSV1 found\n";
        }
        
        if ($parser->hasErrors()) {
            echo "  Parser warnings: " . count($parser->getErrors()) . "\n";
            foreach (array_slice($parser->getErrors(), 0, 5) as $error) {
                echo "    - $error\n";
            }
        }
        
    } else {
        echo "  ✗ Could not find <OFX> tag\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Failed: " . $e->getMessage() . "\n";
    echo "  At: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";
