<?php
/**
 * Cleanup Script - Delete Duplicate Code from Other OFX Parser Repos
 * 
 * Based on DEEP_COMPARISON_REPORT.md analysis
 * Date: January 13, 2026
 */

$baseDir = dirname(__DIR__);
$deletionLog = [];

// Files to completely delete (functionally equivalent, no differences)
$filesToDelete = [
    // Utils.php - 100% similar/identical across jacques & ofx4
    'jacques-ofxparser/lib/OfxParser/Utils.php',
    'ofx4/lib/OfxParser/Utils.php',
    
    // Transaction.php - 100% similar/identical across all repos
    'jacques-ofxparser/lib/OfxParser/Entities/Transaction.php',
    'ofx4/lib/OfxParser/Entities/Transaction.php',
    'ofx2/lib/OfxParser/Entities/Transaction.php',
    'memhetcoban-ofxparser/lib/OfxParser/Entities/Transaction.php',
    
    // Status.php - 100% similar/identical across all repos
    'jacques-ofxparser/lib/OfxParser/Entities/Status.php',
    'ofx4/lib/OfxParser/Entities/Status.php',
    'ofx2/lib/OfxParser/Entities/Status.php',
    'memhetcoban-ofxparser/lib/OfxParser/Entities/Status.php',
    
    // BankAccount.php - No methods, just properties
    'jacques-ofxparser/lib/OfxParser/Entities/BankAccount.php',
    'ofx4/lib/OfxParser/Entities/BankAccount.php',
    'ofx2/lib/OfxParser/Entities/BankAccount.php',
    'memhetcoban-ofxparser/lib/OfxParser/Entities/BankAccount.php',
    
    // Entities/Investment.php - 100% similar across jacques & ofx4
    'jacques-ofxparser/lib/OfxParser/Entities/Investment.php',
    'ofx4/lib/OfxParser/Entities/Investment.php',
];

echo "=== DELETING DUPLICATE FILES ===\n\n";

foreach ($filesToDelete as $file) {
    $fullPath = $baseDir . '/' . $file;
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            echo "✓ Deleted: $file\n";
            $deletionLog[] = "DELETED FILE: $file";
        } else {
            echo "✗ Failed to delete: $file\n";
            $deletionLog[] = "FAILED: $file";
        }
    } else {
        echo "- Not found: $file\n";
        $deletionLog[] = "NOT FOUND: $file";
    }
}

echo "\n=== CLEANING PARSER.PHP FILES ===\n\n";

// Parser.php cleanup - delete similar methods, keep only differences
$parserCleanup = [
    'jacques-ofxparser/lib/OfxParser/Parser.php' => [
        'delete_methods' => ['loadFromFile', 'conditionallyAddNewlines', 'xmlLoadString', 'closeUnclosedXmlTags', 'convertSgmlToXml', 'parseHeader'],
        'keep_methods' => ['createOfx', 'loadFromString'], // Different visibility and missing createTags()
        'comment' => "// CRITICAL DIFFERENCES FROM ksf_ofxparser:\n// 1. loadFromString: Uses deprecated utf8_encode() instead of mb_convert_encoding() (PHP 8.2+ incompatible)\n// 2. loadFromString: MISSING \$ofx->createTags(\$xml) preprocessing step - this is major functionality gap\n// 3. createOfx: private visibility vs protected in KSF\n// RECOMMENDATION: Migrate to ksf_ofxparser for PHP 8.2+ compatibility and createTags feature.\n"
    ],
    'ofx4/lib/OfxParser/Parser.php' => [
        'delete_methods' => ['loadFromFile', 'conditionallyAddNewlines', 'xmlLoadString', 'closeUnclosedXmlTags', 'convertSgmlToXml', 'parseHeader'],
        'keep_methods' => ['createOfx', 'loadFromString'],
        'comment' => "// CRITICAL DIFFERENCES FROM ksf_ofxparser:\n// 1. loadFromString: Uses deprecated utf8_encode() instead of mb_convert_encoding() (PHP 8.2+ incompatible)\n// 2. loadFromString: MISSING \$ofx->createTags(\$xml) preprocessing step - this is major functionality gap\n// 3. Missing type hints throughout\n// RECOMMENDATION: Migrate to ksf_ofxparser for PHP 8.2+ compatibility and createTags feature.\n"
    ],
    'ofx2/lib/OfxParser/Parser.php' => [
        'delete_methods' => ['loadFromFile', 'loadFromString', 'xmlLoadString', 'closeUnclosedXmlTags', 'convertSgmlToXml', 'parseHeader', 'createOfx'],
        'keep_methods' => [],
        'comment' => "// ALL METHODS DELETED - functionally identical to ksf_ofxparser except:\n// - Uses deprecated utf8_encode() (PHP 8.2+ incompatible)\n// - Missing createTags() preprocessing\n// - Missing type hints\n// - Missing advanced error handling\n// RECOMMENDATION: Delete this repo entirely and use ksf_ofxparser.\n"
    ],
    'memhetcoban-ofxparser/lib/OfxParser/Parser.php' => [
        'delete_methods' => ['loadFromFile', 'loadFromString', 'xmlLoadString', 'closeUnclosedXmlTags', 'convertSgmlToXml', 'parseHeader', 'createOfx'],
        'keep_methods' => [],
        'comment' => "// ALL METHODS DELETED - functionally identical to ksf_ofxparser except:\n// - Uses deprecated utf8_encode() (PHP 8.2+ incompatible)\n// - Missing createTags() preprocessing\n// - Missing type hints\n// - Missing advanced error handling\n// RECOMMENDATION: Delete this repo entirely and use ksf_ofxparser.\n"
    ],
];

foreach ($parserCleanup as $file => $config) {
    $fullPath = $baseDir . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "- Not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Delete specified methods
    foreach ($config['delete_methods'] as $method) {
        $content = deleteMethod($content, $method);
    }
    
    // Add impact comment at top
    if (!empty($config['keep_methods']) || $config['comment']) {
        $content = preg_replace(
            '/(namespace\s+[^;]+;.*?)(class\s)/s',
            "$1\n" . $config['comment'] . "\n$2",
            $content
        );
    }
    
    file_put_contents($fullPath, $content);
    echo "✓ Cleaned: $file (deleted " . count($config['delete_methods']) . " methods)\n";
    $deletionLog[] = "CLEANED: $file - deleted " . count($config['delete_methods']) . " methods";
}

echo "\n=== CLEANING OFX.PHP FILES ===\n\n";

// Ofx.php cleanup
$ofxCleanup = [
    'jacques-ofxparser/lib/OfxParser/Ofx.php' => [
        'delete_methods' => ['__construct', 'buildCreditAccounts', 'buildBankAccounts', 'buildCreditAccount', 'buildTransactions', 'buildStatus', 'createDateTimeFromStr', 'createAmountFromStr', 'copyChildren'],
        'keep_methods' => ['buildSignOn', 'buildAccountInfo', 'buildBankAccount'],
        'comment' => "// CRITICAL DIFFERENCES FROM ksf_ofxparser:\n// 1. buildSignOn: MISSING error handling for absent FI section (will fatal error on malformed OFX)\n//    KSF checks isset(\$xml->FI->FID) and has fallback to INTU.BID\n// 2. buildAccountInfo: Uses property_exists() instead of isset() - more defensive for edge cases\n// 3. buildBankAccount: Missing type casting and some error handling\n// 4. MISSING METHODS: buildHeader, buildPayee, buildBankAccountTo, buildCardAccountTo, createTags\n// RECOMMENDATION: Migrate to ksf_ofxparser for production-tested error handling.\n"
    ],
    'ofx4/lib/OfxParser/Ofx.php' => [
        'delete_methods' => ['__construct', 'buildAccountInfo', 'buildCreditAccounts', 'buildBankAccounts', 'buildBankAccount', 'buildCreditAccount', 'buildTransactions', 'buildStatus', 'createDateTimeFromStr', 'createAmountFromStr', 'copyChildren'],
        'keep_methods' => ['buildSignOn'],
        'comment' => "// CRITICAL DIFFERENCES FROM ksf_ofxparser:\n// 1. buildSignOn: MISSING error handling for absent FI section (will fatal error on malformed OFX)\n//    KSF checks isset(\$xml->FI->FID) and has fallback to INTU.BID for Intuit files\n// 2. Missing type hints throughout\n// 3. MISSING METHODS: buildHeader, buildPayee, buildBankAccountTo, buildCardAccountTo, createTags\n// RECOMMENDATION: Migrate to ksf_ofxparser for production-tested error handling.\n"
    ],
    'ofx2/lib/OfxParser/Ofx.php' => [
        'delete_methods' => ['__construct', 'buildCreditAccounts', 'buildBankAccounts', 'buildCreditAccount', 'buildTransactions', 'buildStatus', 'createDateTimeFromStr', 'createAmountFromStr', 'copyChildren'],
        'keep_methods' => ['buildSignOn', 'buildAccountInfo', 'buildBankAccount'],
        'comment' => "// CRITICAL DIFFERENCES FROM ksf_ofxparser:\n// 1. buildSignOn: MISSING error handling for absent FI section (will fatal error - production bug)\n// 2. buildAccountInfo: Missing type hints and string casting\n// 3. buildBankAccount: Missing enhanced error handling\n// 4. MISSING METHODS: buildHeader, buildPayee, buildBankAccountTo, buildCardAccountTo, createTags\n// RECOMMENDATION: Migrate to ksf_ofxparser - has production bug fixes.\n"
    ],
    'memhetcoban-ofxparser/lib/OfxParser/Ofx.php' => [
        'delete_methods' => ['__construct', 'buildCreditAccounts', 'buildBankAccounts', 'buildBankAccount', 'buildCreditAccount', 'buildTransactions', 'buildStatus', 'createDateTimeFromStr', 'createAmountFromStr', 'copyChildren'],
        'keep_methods' => ['buildSignOn', 'buildAccountInfo'],
        'comment' => "// CRITICAL DIFFERENCES FROM ksf_ofxparser:\n// 1. buildSignOn: MISSING error handling for absent FI section (will fatal error - production bug)\n// 2. buildAccountInfo: Missing type hints and string casting\n// 3. MISSING METHODS: buildHeader, buildPayee, buildBankAccountTo, buildCardAccountTo, createTags\n// RECOMMENDATION: Migrate to ksf_ofxparser - has production bug fixes.\n"
    ],
];

foreach ($ofxCleanup as $file => $config) {
    $fullPath = $baseDir . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "- Not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Delete specified methods
    foreach ($config['delete_methods'] as $method) {
        $content = deleteMethod($content, $method);
    }
    
    // Add impact comment
    $content = preg_replace(
        '/(namespace\s+[^;]+;.*?)(class\s)/s',
        "$1\n" . $config['comment'] . "\n$2",
        $content
    );
    
    file_put_contents($fullPath, $content);
    echo "✓ Cleaned: $file (deleted " . count($config['delete_methods']) . " methods)\n";
    $deletionLog[] = "CLEANED: $file - deleted " . count($config['delete_methods']) . " methods";
}

echo "\n=== CLEANING OFX/INVESTMENT.PHP FILES ===\n\n";

// Ofx/Investment.php cleanup
$investmentCleanup = [
    'jacques-ofxparser/lib/OfxParser/Ofx/Investment.php' => [
        'delete_methods' => ['__construct', 'buildAccounts', 'buildAccount', 'buildTransactions'],
        'keep_methods' => [],
        'comment' => "// ALL METHODS FUNCTIONALLY EQUIVALENT TO ksf_ofxparser (only type hints differ)\n// RECOMMENDATION: Delete this file entirely and use ksf_ofxparser.\n"
    ],
    'ofx4/lib/OfxParser/Ofx/Investment.php' => [
        'delete_methods' => ['__construct', 'buildAccounts', 'buildAccount', 'buildTransactions'],
        'keep_methods' => [],
        'comment' => "// ALL METHODS FUNCTIONALLY EQUIVALENT TO ksf_ofxparser (only type hints differ)\n// RECOMMENDATION: Delete this file entirely and use ksf_ofxparser.\n"
    ],
];

foreach ($investmentCleanup as $file => $config) {
    $fullPath = $baseDir . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "- Not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Delete specified methods
    foreach ($config['delete_methods'] as $method) {
        $content = deleteMethod($content, $method);
    }
    
    // Add impact comment
    $content = preg_replace(
        '/(namespace\s+[^;]+;.*?)(class\s)/s',
        "$1\n" . $config['comment'] . "\n$2",
        $content
    );
    
    // Check if class is now empty (only properties, no methods)
    if (shouldDeleteEmptyFile($content)) {
        unlink($fullPath);
        echo "✓ Deleted empty: $file\n";
        $deletionLog[] = "DELETED EMPTY: $file";
    } else {
        file_put_contents($fullPath, $content);
        echo "✓ Cleaned: $file (deleted " . count($config['delete_methods']) . " methods)\n";
        $deletionLog[] = "CLEANED: $file - deleted " . count($config['delete_methods']) . " methods";
    }
}

// Save deletion log
file_put_contents($baseDir . '/ksf_ofxparser/CLEANUP_LOG.txt', implode("\n", $deletionLog));
echo "\n✓ Cleanup log saved to CLEANUP_LOG.txt\n";
echo "\n=== CLEANUP COMPLETE ===\n";

/**
 * Delete a method from PHP code
 */
function deleteMethod($content, $methodName) {
    // Match method definition and its body
    $pattern = '/\n\s*(\/\*\*.*?\*\/\s*)?(public|protected|private)\s+(?:static\s+)?function\s+' . 
               preg_quote($methodName, '/') . 
               '\s*\([^)]*\)(?:\s*:\s*[\w\?\\\\]+)?\s*\{(?:[^{}]*|\{(?:[^{}]*|\{[^{}]*\})*\})*\}/s';
    
    $newContent = preg_replace($pattern, '', $content);
    
    // If no change, method might not have been found
    if ($newContent === $content) {
        echo "  ! Warning: Method $methodName not found or couldn't be deleted\n";
    }
    
    return $newContent;
}

/**
 * Check if file should be deleted (empty class)
 */
function shouldDeleteEmptyFile($content) {
    // Remove comments, namespace, use statements
    $stripped = preg_replace('/\/\*.*?\*\//s', '', $content);
    $stripped = preg_replace('/\/\/.*$/m', '', $stripped);
    $stripped = preg_replace('/namespace\s+[^;]+;/', '', $stripped);
    $stripped = preg_replace('/use\s+[^;]+;/', '', $stripped);
    
    // Check if there are any methods left
    $hasMethod = preg_match('/(public|protected|private)\s+(?:static\s+)?function\s+\w+/', $stripped);
    
    return !$hasMethod;
}
