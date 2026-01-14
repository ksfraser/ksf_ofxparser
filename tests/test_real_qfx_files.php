<?php
/**
 * Test real QFX files with defensive parsing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OfxParser\Parser;
use OfxParser\Config\DefensiveParsingConfig;

$qfxFiles = [
    'C:\Users\prote\Documents\ksf_bank_import\includes\ATB.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\ATB2.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\CIBC_SAVINGS.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\CIBC_SAVINGS2.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\CIBC_VISA.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\MANU.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\MANU2.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\MANU_ALL.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\PCF.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\PCMC.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\RBC.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\includes\SIMPLII.qfx',
    'C:\Users\prote\Documents\ksf_bank_import\qfx_files\20260112_1518404_transactions.qfx',
];

$results = [
    'passed' => [],
    'failed' => [],
    'defensive_helped' => [],
];

echo "=== Testing QFX Files WITHOUT Defensive Parsing ===\n\n";

foreach ($qfxFiles as $file) {
    $filename = basename($file);
    echo "Testing: $filename ... ";
    
    try {
        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $accountCount = count($ofx->bankAccounts);
        $transactionCount = 0;
        foreach ($ofx->bankAccounts as $account) {
            if ($account->statement && $account->statement->transactions) {
                $transactionCount += count($account->statement->transactions);
            }
        }
        
        echo "✓ PASSED ($accountCount accounts, $transactionCount transactions)\n";
        $results['passed'][] = ['file' => $filename, 'accounts' => $accountCount, 'transactions' => $transactionCount];
        
    } catch (\Exception $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
        $results['failed'][] = ['file' => $filename, 'error' => $e->getMessage()];
    }
}

echo "\n=== Testing QFX Files WITH Defensive Parsing (Lenient Mode) ===\n\n";

foreach ($qfxFiles as $file) {
    $filename = basename($file);
    echo "Testing: $filename ... ";
    
    try {
        $parser = new Parser();
        $config = DefensiveParsingConfig::createLenient();
        $parser->withDefensiveParsing($config);
        
        $result = $parser->loadFromFile($file);
        
        // Check if we got a ParsingResult
        if ($result instanceof \OfxParser\Metrics\ParsingResult) {
            $ofx = $result->getOfx();
            $metrics = $result->getMetrics();
            
            $accountCount = count($ofx->bankAccounts);
            $transactionCount = 0;
            foreach ($ofx->bankAccounts as $account) {
                if ($account->statement && $account->statement->transactions) {
                    $transactionCount += count($account->statement->transactions);
                }
            }
            
            $successRate = $metrics->getSuccessRate();
            $incomplete = $metrics->getIncompleteTransactions();
            $corrupt = $metrics->getCorruptTransactions();
            
            if ($incomplete > 0 || $corrupt > 0) {
                echo "⚠ RECOVERED ($accountCount accounts, $transactionCount transactions, ";
                echo "Success: $successRate%, Incomplete: $incomplete, Corrupt: $corrupt)\n";
                $results['defensive_helped'][] = [
                    'file' => $filename,
                    'accounts' => $accountCount,
                    'transactions' => $transactionCount,
                    'success_rate' => $successRate,
                    'incomplete' => $incomplete,
                    'corrupt' => $corrupt,
                ];
            } else {
                echo "✓ PASSED ($accountCount accounts, $transactionCount transactions, Success: $successRate%)\n";
            }
        } else {
            // Regular Ofx object returned
            $accountCount = count($result->bankAccounts);
            $transactionCount = 0;
            foreach ($result->bankAccounts as $account) {
                if ($account->statement && $account->statement->transactions) {
                    $transactionCount += count($account->statement->transactions);
                }
            }
            echo "✓ PASSED ($accountCount accounts, $transactionCount transactions)\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Summary ===\n\n";
echo "Total files tested: " . count($qfxFiles) . "\n";
echo "Passed without defensive parsing: " . count($results['passed']) . "\n";
echo "Failed without defensive parsing: " . count($results['failed']) . "\n";
echo "Defensive parsing helped recover: " . count($results['defensive_helped']) . "\n\n";

if (!empty($results['failed'])) {
    echo "Failed files:\n";
    foreach ($results['failed'] as $fail) {
        echo "  - {$fail['file']}: {$fail['error']}\n";
    }
    echo "\n";
}

if (!empty($results['defensive_helped'])) {
    echo "Files where defensive parsing helped:\n";
    foreach ($results['defensive_helped'] as $help) {
        echo "  - {$help['file']}: Success Rate {$help['success_rate']}%, ";
        echo "Incomplete: {$help['incomplete']}, Corrupt: {$help['corrupt']}\n";
    }
}
