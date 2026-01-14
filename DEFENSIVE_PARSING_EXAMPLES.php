<?php declare(strict_types=1);

/**
 * Defensive Parsing Usage Examples
 * 
 * This file demonstrates how to use the defensive parsing features
 * in the KSF OFX Parser library.
 */

require_once __DIR__ . '/vendor/autoload.php';

use OfxParser\Parser;
use OfxParser\Config\DefensiveParsingConfig;

// ============================================================================
// Example 1: DEFAULT BEHAVIOR (Backward Compatible)
// ============================================================================
// Without calling withDefensiveParsing(), the parser behaves exactly as before
// Returns Ofx object directly

$parser = new Parser();
try {
    $ofx = $parser->loadFromFile('bank_statement.ofx');
    echo "Parsed {$ofx->bankAccount->statement->balance} balance\n";
    echo "Found " . count($ofx->bankAccount->statement->transactions) . " transactions\n";
} catch (\Exception $e) {
    echo "Parsing failed: {$e->getMessage()}\n";
}

// ============================================================================
// Example 2: DEFENSIVE PARSING - Default Configuration
// ============================================================================
// Balanced approach: recovers from optional field issues, skips corrupt transactions

$parser = new Parser();
$parser->withDefensiveParsing(); // Uses default config

$result = $parser->loadFromFile('bank_statement.ofx');

// Check if parsing had errors
if ($result->hasErrors()) {
    echo "Parsing completed with errors:\n";
    echo "  Success rate: {$result->getSuccessRate()}%\n";
    echo "  Successful: {$result->getSuccessfulTransactions()}\n";
    echo "  Corrupt: {$result->getCorruptTransactions()}\n";
    echo "  Incomplete: {$result->getIncompleteTransactions()}\n";
}

// Get the OFX data (works same as before)
$ofx = $result->getOfx();
$transactions = $ofx->bankAccount->statement->transactions;

echo "Successfully parsed " . count($transactions) . " transactions\n";

// Export detailed metrics
$metrics = $result->getMetrics()->toArray();
print_r($metrics);

// ============================================================================
// Example 3: STRICT MODE - Validation
// ============================================================================
// Strict mode throws exceptions on ANY error - useful for validation

$parser = new Parser();
$parser->withDefensiveParsing(DefensiveParsingConfig::createStrict());

try {
    $result = $parser->loadFromFile('bank_statement.ofx');
    
    if ($result->isComplete()) {
        echo "File is 100% valid!\n";
    }
} catch (\Exception $e) {
    echo "Validation failed: {$e->getMessage()}\n";
    echo "This file does not meet OFX 2.2 specification\n";
}

// ============================================================================
// Example 4: LENIENT MODE - Production Import
// ============================================================================
// Lenient mode aggressively recovers from errors - parse as much as possible

$parser = new Parser();
$parser->withDefensiveParsing(DefensiveParsingConfig::createLenient());

$result = $parser->loadFromFile('messy_bank_statement.ofx');

echo "Lenient parsing results:\n";
echo "  Total transactions attempted: {$result->getTotalTransactions()}\n";
echo "  Successfully parsed: {$result->getSuccessfulTransactions()}\n";
echo "  Partially parsed: {$result->getIncompleteTransactions()}\n";
echo "  Skipped (corrupt): {$result->getCorruptTransactions()}\n";

// Get usable transactions (even from messy file)
$ofx = $result->getOfx();
$transactions = $ofx->bankAccount->statement->transactions;

foreach ($transactions as $transaction) {
    echo "Transaction: {$transaction->uniqueId} - {$transaction->amount}\n";
}

// ============================================================================
// Example 5: CUSTOM CONFIGURATION
// ============================================================================
// Customize recovery strategies for specific needs

use OfxParser\Recovery\FieldRecovery\ZeroAmountStrategy;
use OfxParser\Recovery\FieldRecovery\CurrentDateStrategy;
use OfxParser\Recovery\TransactionRecovery\LogAndContinueStrategy;

$config = DefensiveParsingConfig::createDefault();

// Override specific recovery strategies
$config->setFieldStrategy('OptionalFieldMissingException', new ZeroAmountStrategy());
$config->setFieldStrategy('InvalidFieldFormatException', new CurrentDateStrategy());
$config->setTransactionStrategy('CorruptTransactionException', new LogAndContinueStrategy());

$parser = new Parser();
$parser->withDefensiveParsing($config);

$result = $parser->loadFromFile('bank_statement.ofx');

// Get detailed logs of what went wrong
$corruptLogs = $result->getMetrics()->getCorruptTransactionLogs();
foreach ($corruptLogs as $log) {
    echo "Corrupt transaction #{$log['transaction_number']}: {$log['reason']}\n";
}

// ============================================================================
// Example 6: BATCH PROCESSING WITH METRICS
// ============================================================================
// Process multiple files and collect aggregate metrics

$files = glob('statements/*.ofx');
$totalSuccessful = 0;
$totalCorrupt = 0;
$totalIncomplete = 0;

$parser = new Parser();
$parser->withDefensiveParsing(); // Default config

foreach ($files as $file) {
    try {
        $result = $parser->loadFromFile($file);
        
        $totalSuccessful += $result->getSuccessfulTransactions();
        $totalCorrupt += $result->getCorruptTransactions();
        $totalIncomplete += $result->getIncompleteTransactions();
        
        echo "Processed {$file}: {$result->getSuccessRate()}% success rate\n";
        
    } catch (\Exception $e) {
        echo "Failed to process {$file}: {$e->getMessage()}\n";
    }
}

echo "\nBatch Processing Summary:\n";
echo "  Total successful transactions: {$totalSuccessful}\n";
echo "  Total corrupt transactions: {$totalCorrupt}\n";
echo "  Total incomplete transactions: {$totalIncomplete}\n";

// ============================================================================
// Example 7: PRODUCTION ERROR HANDLING
// ============================================================================
// Recommended pattern for production use

function importBankStatement(string $filePath): array
{
    $parser = new Parser();
    $parser->withDefensiveParsing(DefensiveParsingConfig::createLenient());
    
    try {
        $result = $parser->loadFromFile($filePath);
        
        // Log metrics for monitoring
        $metrics = $result->getMetrics();
        error_log("OFX Import: {$result->getSuccessRate()}% success rate");
        
        if ($result->getCorruptTransactions() > 0) {
            // Log corrupt transaction details for investigation
            foreach ($metrics->getCorruptTransactionLogs() as $log) {
                error_log("Corrupt transaction #{$log['transaction_number']}: {$log['reason']}");
            }
        }
        
        // Return transactions even if some failed
        $ofx = $result->getOfx();
        return [
            'success' => true,
            'transactions' => $ofx->bankAccount->statement->transactions,
            'metrics' => $metrics->toArray(),
            'has_errors' => $result->hasErrors(),
            'is_complete' => $result->isComplete()
        ];
        
    } catch (\Exception $e) {
        error_log("OFX Import failed completely: {$e->getMessage()}");
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'transactions' => []
        ];
    }
}

// Usage
$import = importBankStatement('bank_statement.ofx');

if ($import['success']) {
    echo "Imported {$import['metrics']['summary']['successful']} transactions\n";
    
    if ($import['has_errors']) {
        echo "Warning: Import had errors. Check logs.\n";
    }
    
    if ($import['is_complete']) {
        echo "100% complete import!\n";
    }
}

// ============================================================================
// Example 8: METRICS REPORTING
// ============================================================================
// Generate detailed report of parsing issues

$parser = new Parser();
$parser->withDefensiveParsing();

$result = $parser->loadFromFile('bank_statement.ofx');
$metrics = $result->getMetrics();

echo "\n=== OFX Parsing Report ===\n";
echo "Total Transactions: {$metrics->getTotalTransactions()}\n";
echo "Success Rate: {$metrics->getSuccessRate()}%\n";
echo "\nBreakdown:\n";
echo "  ✓ Successful: {$metrics->getSuccessfulTransactions()}\n";
echo "  ⚠ Incomplete: {$metrics->getIncompleteTransactions()}\n";
echo "  ✗ Corrupt: {$metrics->getCorruptTransactions()}\n";
echo "  ⚠ Unexpected Errors: {$metrics->getUnexpectedErrors()}\n";

echo "\nField Issues:\n";
$missingRequired = $metrics->getMissingRequiredFields();
if (!empty($missingRequired)) {
    echo "  Missing Required Fields:\n";
    foreach ($missingRequired as $field => $count) {
        echo "    - {$field}: {$count} occurrences\n";
    }
}

$missingOptional = $metrics->getMissingOptionalFields();
if (!empty($missingOptional)) {
    echo "  Missing Optional Fields:\n";
    foreach ($missingOptional as $field => $count) {
        echo "    - {$field}: {$count} occurrences\n";
    }
}

$recoveries = $metrics->getFieldRecoveries();
if (!empty($recoveries)) {
    echo "  Recovered Fields:\n";
    foreach ($recoveries as $field => $count) {
        echo "    - {$field}: {$count} recoveries\n";
    }
}

// ============================================================================
// Example 9: REUSING PARSER INSTANCE
// ============================================================================
// Parser can be reused for multiple files (metrics are per-file)

$parser = new Parser();
$parser->withDefensiveParsing();

$files = ['statement1.ofx', 'statement2.ofx', 'statement3.ofx'];

foreach ($files as $file) {
    // Each call gets fresh metrics
    $result = $parser->loadFromFile($file);
    
    echo "{$file}: {$result->getSuccessRate()}% success\n";
}

// ============================================================================
// Example 10: BACKWARD COMPATIBILITY CHECK
// ============================================================================
// Verify code works with or without defensive parsing

function processOfxFile(string $filePath, bool $useDefensiveParsing = false)
{
    $parser = new Parser();
    
    if ($useDefensiveParsing) {
        $parser->withDefensiveParsing();
    }
    
    $result = $parser->loadFromFile($filePath);
    
    // Handle both Ofx and ParsingResult
    if ($result instanceof \OfxParser\Metrics\ParsingResult) {
        echo "Using defensive parsing\n";
        $ofx = $result->getOfx();
        echo "Success rate: {$result->getSuccessRate()}%\n";
    } else {
        echo "Using standard parsing\n";
        $ofx = $result;
    }
    
    // Same API for getting transactions
    $transactions = $ofx->bankAccount->statement->transactions;
    echo "Transactions: " . count($transactions) . "\n";
}

// Both work
processOfxFile('statement.ofx', false); // Standard
processOfxFile('statement.ofx', true);  // Defensive

?>
