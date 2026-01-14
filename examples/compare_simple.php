<?php
/**
 * Compare parsers on all QFX files - simpler version
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OfxParser\Parser as OldParser;
use OfxParser\Sgml\Parser as SgmlParser;
use OfxParser\Sgml\DateFormatter;

echo "\n=== OFX Parser Comparison ===\n\n";

$qfxDir = __DIR__ . '/../../../qfx_files';
$files = glob($qfxDir . '/*.{ofx,qfx,OFX,QFX}', GLOB_BRACE);

if (empty($files)) {
    die("No files found in $qfxDir\n");
}

echo "Found " . count($files) . " files\n\n";

$stats = [
    'total' => 0,
    'both_success' => 0,
    'identical_count' => 0,
    'old_fail_sgml_success' => 0,
    'old_success_sgml_fail' => 0,
    'both_fail' => 0,
];

foreach ($files as $file) {
    $stats['total']++;
    $filename = basename($file);
    
    echo str_pad($filename, 45);
    flush(); // Force output immediately
    
    // Test old parser
    $oldSuccess = false;
    $oldTxnCount = 0;
    $oldTime = 0;
    try {
        $start = microtime(true);
        $parser = new OldParser();
        $ofx = $parser->loadFromFile($file);
        $oldTime = microtime(true) - $start;
        
        foreach ($ofx->bankAccounts as $account) {
            if ($statement = $account->statement) {
                $oldTxnCount += count($statement->transactions);
            }
        }
        $oldSuccess = true;
    } catch (Exception $e) {
        $oldError = substr($e->getMessage(), 0, 30);
    }
    
    // Test SGML parser
    $sgmlSuccess = false;
    $sgmlTxnCount = 0;
    $sgmlTime = 0;
    try {
        $start = microtime(true);
        $content = file_get_contents($file);
        
        if (preg_match('/<OFX>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $sgmlBody = substr($content, $matches[0][1]);
            
            $parser = new SgmlParser();
            $root = $parser->parse($sgmlBody);
            $sgmlTime = microtime(true) - $start;
            
            $stmtrs = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS ?? null;
            if ($stmtrs) {
                $tranList = $stmtrs->BANKTRANLIST ?? null;
                if ($tranList) {
                    $transactions = $tranList->getChildrenByTag('STMTTRN');
                    $sgmlTxnCount = count($transactions);
                }
            }
            $sgmlSuccess = true;
        }
    } catch (Exception $e) {
        $sgmlError = substr($e->getMessage(), 0, 30);
    }
    
    // Print result
    if ($oldSuccess && $sgmlSuccess) {
        $stats['both_success']++;
        if ($oldTxnCount === $sgmlTxnCount) {
            $stats['identical_count']++;
            echo " OK  ";
        } else {
            echo " DIFF";
        }
        echo sprintf(" | Old:%3d(%.2fs) SGML:%3d(%.2fs)", $oldTxnCount, $oldTime, $sgmlTxnCount, $sgmlTime);
    } elseif (!$oldSuccess && $sgmlSuccess) {
        $stats['old_fail_sgml_success']++;
        echo " SGML";
        echo sprintf(" | Old:FAIL SGML:%3d(%.2fs) ← FIXED!", $sgmlTxnCount, $sgmlTime);
    } elseif ($oldSuccess && !$sgmlSuccess) {
        $stats['old_success_sgml_fail']++;
        echo " REGR";
        echo sprintf(" | Old:%3d(%.2fs) SGML:FAIL", $oldTxnCount, $oldTime);
    } else {
        $stats['both_fail']++;
        echo " FAIL";
        echo " | Both failed";
    }
    
    echo "\n";
}

// Summary
echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY:\n";
echo "  Total files:                    " . $stats['total'] . "\n";
echo "  Both succeeded:                 " . $stats['both_success'] . "\n";
echo "    - Identical transaction count: " . $stats['identical_count'] . "\n";
echo "  Old failed, SGML succeeded:     " . $stats['old_fail_sgml_success'] . " ✓\n";
echo "  Old succeeded, SGML failed:     " . $stats['old_success_sgml_fail'] . "\n";
echo "  Both failed:                    " . $stats['both_fail'] . "\n";

if ($stats['identical_count'] === $stats['total']) {
    echo "\n✓✓✓ PERFECT! All parsers produced identical results! ✓✓✓\n";
} elseif ($stats['old_fail_sgml_success'] > 0) {
    echo "\n✓ SGML parser fixed " . $stats['old_fail_sgml_success'] . " file(s)!\n";
}

echo "\n";
