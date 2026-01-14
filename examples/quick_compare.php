<?php
/**
 * Quick comparison test with single file
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OfxParser\Parser as OldParser;
use OfxParser\Sgml\Parser as SgmlParser;
use OfxParser\Sgml\DateFormatter;

$testFile = __DIR__ . '/../../../qfx_files/20260112_1518404_transactions.qfx';

echo "Testing: " . basename($testFile) . "\n\n";

// Test old parser
echo "Old Parser:\n";
$start = microtime(true);
try {
    $parser = new OldParser();
    $ofx = $parser->loadFromFile($testFile);
    $oldTime = microtime(true) - $start;
    
    $txnCount = 0;
    foreach ($ofx->bankAccounts as $account) {
        if ($statement = $account->statement) {
            $txnCount += count($statement->transactions);
        }
    }
    
    echo "  ✓ Success in " . number_format($oldTime, 3) . "s\n";
    echo "  Transactions: $txnCount\n";
    
    if (!empty($ofx->bankAccounts)) {
        $acct = $ofx->bankAccounts[0];
        echo "  Account: " . ($acct->accountNumber ?? 'N/A') . "\n";
        if ($statement = $acct->statement) {
            if (!empty($statement->transactions)) {
                $txn = $statement->transactions[0];
                echo "  First txn: " . ($txn->type ?? '') . " " . ($txn->amount ?? '') . " on " . ($txn->date ? $txn->date->format('Y-m-d') : 'N/A') . "\n";
            }
            if ($bal = $statement->ledgerBalance) {
                echo "  Balance: " . ($bal->amount ?? 'N/A') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "  ✗ Failed: " . $e->getMessage() . "\n";
}

echo "\nSGML Parser:\n";
$start = microtime(true);
try {
    $content = file_get_contents($testFile);
    
    if (preg_match('/<OFX>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
        $sgmlBody = substr($content, $matches[0][1]);
    } else {
        throw new Exception("Could not find <OFX> tag");
    }
    
    $parser = new SgmlParser();
    $root = $parser->parse($sgmlBody);
    $sgmlTime = microtime(true) - $start;
    
    echo "  ✓ Success in " . number_format($sgmlTime, 3) . "s\n";
    
    // Count transactions
    $txnCount = 0;
    $stmtrs = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS ?? null;
    if ($stmtrs) {
        $tranList = $stmtrs->BANKTRANLIST ?? null;
        if ($tranList) {
            $transactions = $tranList->getChildrenByTag('STMTTRN');
            $txnCount = count($transactions);
            
            echo "  Transactions: $txnCount\n";
            
            $acct = $stmtrs->BANKACCTFROM ?? null;
            if ($acct) {
                echo "  Account: " . ($acct->ACCTID ?? 'N/A') . "\n";
            }
            
            if (!empty($transactions)) {
                $txn = reset($transactions);
                $dtposted = $txn->DTPOSTED ?? null;
                
                echo "  First txn: " . ($txn->TRNTYPE ?? '') . " " . ($txn->TRNAMT ?? '');
                if ($dtposted) {
                    echo " on " . DateFormatter::getYMD((string)$dtposted);
                    echo "\n    Date formats:";
                    echo "\n      Raw: " . DateFormatter::getRaw((string)$dtposted);
                    echo "\n      Normalized: " . DateFormatter::getNormalized((string)$dtposted);
                    echo "\n      YMD: " . DateFormatter::getYMD((string)$dtposted);
                } else {
                    echo " on N/A";
                }
                echo "\n";
            }
            
            $bal = $stmtrs->LEDGERBAL ?? null;
            if ($bal) {
                echo "  Balance: " . ($bal->BALAMT ?? 'N/A') . "\n";
            }
        }
    }
    
    if ($parser->hasErrors()) {
        echo "  Warnings: " . count($parser->getErrors()) . "\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Failed: " . $e->getMessage() . "\n";
}

echo "\n";
