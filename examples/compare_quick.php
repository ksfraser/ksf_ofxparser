<?php
/**
 * Quick comparison - just test critical files
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OfxParser\Parser as OldParser;
use OfxParser\Sgml\Parser as SgmlParser;
use OfxParser\Sgml\DateFormatter;

echo "\n=== Quick Parser Comparison ===\n\n";

$qfxDir = __DIR__ . '/../../../qfx_files';

// Test specific files
$testFiles = [
    '20260112_1518404_transactions.qfx',  // Small QFX
    '20260112_1518404_transactions.qbo',  // QBO (should be same as QFX)
    '2019-20260112 cibc VISA.ofx',        // Credit card
    '20260112 cibc SAV.ofx',               // Small bank account
];

$results = [];

foreach ($testFiles as $filename) {
    $file = $qfxDir . '/' . $filename;
    
    if (!file_exists($file)) {
        echo str_pad($filename, 45) . " SKIP | File not found\n";
        continue;
    }
    
    echo str_pad($filename, 45);
    
    // Old parser
    $oldCount = 0;
    $oldTime = 0;
    $oldOk = false;
    try {
        $start = microtime(true);
        $parser = new OldParser();
        $ofx = $parser->loadFromFile($file);
        $oldTime = microtime(true) - $start;
        
        foreach ($ofx->bankAccounts as $account) {
            if ($statement = $account->statement) {
                $oldCount += count($statement->transactions);
            }
        }
        foreach (($ofx->creditCardAccounts ?? []) as $account) {
            if ($statement = $account->statement) {
                $oldCount += count($statement->transactions);
            }
        }
        $oldOk = true;
    } catch (Exception $e) {
        // Failed
    }
    
    // SGML parser
    $sgmlCount = 0;
    $sgmlTime = 0;
    $sgmlOk = false;
    try {
        $start = microtime(true);
        $content = file_get_contents($file);
        
        if (preg_match('/<OFX>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $sgmlBody = substr($content, $matches[0][1]);
            
            $parser = new SgmlParser();
            $root = $parser->parse($sgmlBody);
            $sgmlTime = microtime(true) - $start;
            
            // Bank accounts
            $stmtrs = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS ?? null;
            if ($stmtrs) {
                $tranList = $stmtrs->BANKTRANLIST ?? null;
                if ($tranList) {
                    $sgmlCount += count($tranList->getChildrenByTag('STMTTRN'));
                }
            }
            
            // Credit cards
            $ccstmtrs = $root->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS ?? null;
            if ($ccstmtrs) {
                $tranList = $ccstmtrs->BANKTRANLIST ?? null;
                if ($tranList) {
                    $sgmlCount += count($tranList->getChildrenByTag('STMTTRN'));
                }
            }
            
            $sgmlOk = true;
        }
    } catch (Exception $e) {
        // Failed
    }
    
    // Print result
    if ($oldOk && $sgmlOk) {
        $match = $oldCount === $sgmlCount ? 'OK  ' : 'DIFF';
        echo sprintf(" %s | Old:%3d(%.2fs) SGML:%3d(%.2fs)", $match, $oldCount, $oldTime, $sgmlCount, $sgmlTime);
        if ($oldCount !== $sgmlCount) {
            echo sprintf(" ← MISMATCH!");
        }
    } elseif (!$oldOk && $sgmlOk) {
        echo sprintf(" FIX  | Old:FAIL SGML:%3d(%.2fs)", $sgmlCount, $sgmlTime);
    } elseif ($oldOk && !$sgmlOk) {
        echo sprintf(" REGR | Old:%3d(%.2fs) SGML:FAIL", $oldCount, $oldTime);
    } else {
        echo " FAIL | Both failed";
    }
    echo "\n";
    
    $results[] = [
        'file' => $filename,
        'old_ok' => $oldOk,
        'sgml_ok' => $sgmlOk,
        'match' => $oldOk && $sgmlOk && $oldCount === $sgmlCount,
    ];
}

echo "\n";
$matches = count(array_filter($results, fn($r) => $r['match']));
$total = count($results);
echo "Identical results: $matches / $total\n";

if ($matches === $total) {
    echo "✓ All tested files match!\n";
}

echo "\n";
