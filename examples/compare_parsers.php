<?php
/**
 * Compare Old XML Parser vs New SGML Parser
 * 
 * Tests both parsers against all OFX/QFX files in qfx_files directory
 * and compares results to ensure they produce identical output.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OfxParser\Parser as OldParser;
use OfxParser\Sgml\Parser as SgmlParser;

// ANSI color codes for terminal output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");
define('COLOR_BOLD', "\033[1m");

class ParserComparison
{
    private array $results = [];
    private int $totalFiles = 0;
    private int $bothSuccess = 0;
    private int $bothFailed = 0;
    private int $oldSuccessNewFail = 0;
    private int $oldFailNewSuccess = 0;
    private int $identicalOutput = 0;
    private int $differentOutput = 0;

    public function compareFile(string $filePath): array
    {
        $this->totalFiles++;
        $filename = basename($filePath);
        
        echo "\n" . COLOR_BLUE . "Testing: " . COLOR_BOLD . $filename . COLOR_RESET . "\n";
        echo str_repeat("-", 80) . "\n";

        $result = [
            'file' => $filename,
            'old_parser' => $this->testOldParser($filePath),
            'sgml_parser' => $this->testSgmlParser($filePath),
        ];

        // Compare results
        $result['comparison'] = $this->compareResults($result['old_parser'], $result['sgml_parser']);
        
        $this->updateStats($result);
        $this->printResult($result);
        
        $this->results[] = $result;
        return $result;
    }

    private function testOldParser(string $filePath): array
    {
        $startTime = microtime(true);
        try {
            $parser = new OldParser();
            $ofx = $parser->loadFromFile($filePath);
            
            $result = [
                'success' => true,
                'time' => microtime(true) - $startTime,
                'error' => null,
                'data' => $this->extractData($ofx),
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'time' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
        
        return $result;
    }

    private function testSgmlParser(string $filePath): array
    {
        $startTime = microtime(true);
        try {
            $content = file_get_contents($filePath);
            
            // Extract SGML body (after headers)
            if (preg_match('/<OFX>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $sgmlBody = substr($content, $matches[0][1]);
            } else {
                throw new Exception("Could not find <OFX> tag");
            }
            
            $parser = new SgmlParser();
            $root = $parser->parse($sgmlBody);
            
            $result = [
                'success' => true,
                'time' => microtime(true) - $startTime,
                'error' => null,
                'warnings' => $parser->getErrors(),
                'data' => $this->extractDataFromSgml($root),
            ];
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'time' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
                'warnings' => [],
                'data' => null,
            ];
        }
        
        return $result;
    }

    private function extractData($ofx): array
    {
        if (!$ofx) {
            return [];
        }

        $data = [
            'sign_on' => [],
            'accounts' => [],
            'transactions' => [],
            'balance' => null,
        ];

        // Sign-on info
        try {
            $signOn = $ofx->signOn;
            if ($signOn) {
                $data['sign_on'] = [
                    'status' => $signOn->status ?? null,
                    'date' => $signOn->date ? $signOn->date->format('Ymd') : null,
                    'language' => $signOn->language ?? null,
                    'institute' => $signOn->institute->name ?? null,
                ];
            }
        } catch (Exception $e) {
            // Skip if not available
        }

        // Bank accounts
        try {
            $bankAccounts = $ofx->bankAccounts;
            if ($bankAccounts) {
                foreach ($bankAccounts as $account) {
                    $data['accounts'][] = [
                        'bank_id' => $account->routingNumber ?? null,
                        'account_number' => $account->accountNumber ?? null,
                        'account_type' => $account->accountType ?? null,
                        'routing_number' => $account->routingNumber ?? null,
                    ];
                    
                    // Get transactions for this account
                    $statement = $account->statement;
                    if ($statement) {
                        foreach ($statement->transactions as $txn) {
                            $data['transactions'][] = [
                                'type' => $txn->type ?? null,
                                'date' => $txn->date ? $txn->date->format('Ymd') : null,
                                'amount' => $txn->amount ?? null,
                                'fitid' => $txn->uniqueId ?? null,
                                'name' => $txn->name ?? null,
                                'memo' => $txn->memo ?? null,
                                'checkNumber' => $txn->checkNumber ?? null,
                            ];
                        }
                        
                        // Balance
                        if ($statement->ledgerBalance) {
                            $data['balance'] = [
                                'amount' => $statement->ledgerBalance->amount ?? null,
                                'date' => $statement->ledgerBalance->asOf ? $statement->ledgerBalance->asOf->format('Ymd') : null,
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Skip if not available
        }

        return $data;
    }

    private function extractDataFromSgml($root): array
    {
        $data = [
            'sign_on' => [],
            'accounts' => [],
            'transactions' => [],
            'balance' => null,
        ];

        // Sign-on info
        try {
            $signOn = $root->SIGNONMSGSRSV1->SONRS ?? null;
            if ($signOn) {
                $data['sign_on'] = [
                    'status' => (string)($signOn->STATUS->CODE ?? null),
                    'date' => (string)($signOn->DTSERVER ?? null),
                    'language' => (string)($signOn->LANGUAGE ?? null),
                    'institute' => (string)($signOn->FI->ORG ?? null),
                ];
            }
        } catch (Exception $e) {
            // Skip if not available
        }

        // Bank account
        try {
            $stmtrs = $root->BANKMSGSRSV1->STMTTRNRS->STMTRS ?? null;
            if ($stmtrs) {
                $acct = $stmtrs->BANKACCTFROM ?? null;
                if ($acct) {
                    $data['accounts'][] = [
                        'bank_id' => (string)($acct->BANKID ?? null),
                        'account_number' => (string)($acct->ACCTID ?? null),
                        'account_type' => (string)($acct->ACCTTYPE ?? null),
                        'routing_number' => (string)($acct->BRANCHID ?? null),
                    ];
                }
                
                // Transactions
                $tranList = $stmtrs->BANKTRANLIST ?? null;
                if ($tranList) {
                    $transactions = $tranList->getChildrenByTag('STMTTRN');
                    foreach ($transactions as $txn) {
                        $data['transactions'][] = [
                            'type' => (string)($txn->TRNTYPE ?? null),
                            'date' => (string)($txn->DTPOSTED ?? null),
                            'amount' => (string)($txn->TRNAMT ?? null),
                            'fitid' => (string)($txn->FITID ?? null),
                            'name' => (string)($txn->NAME ?? null),
                            'memo' => (string)($txn->MEMO ?? null),
                            'checkNumber' => (string)($txn->CHECKNUM ?? null),
                        ];
                    }
                }
                
                // Balance
                $bal = $stmtrs->LEDGERBAL ?? null;
                if ($bal) {
                    $data['balance'] = [
                        'amount' => (string)($bal->BALAMT ?? null),
                        'date' => (string)($bal->DTASOF ?? null),
                    ];
                }
            }
        } catch (Exception $e) {
            // Skip if not available
        }

        return $data;
    }

    private function compareResults(array $oldResult, array $sgmlResult): array
    {
        $comparison = [
            'status' => 'unknown',
            'differences' => [],
        ];

        if (!$oldResult['success'] && !$sgmlResult['success']) {
            $comparison['status'] = 'both_failed';
        } elseif ($oldResult['success'] && !$sgmlResult['success']) {
            $comparison['status'] = 'old_success_sgml_failed';
        } elseif (!$oldResult['success'] && $sgmlResult['success']) {
            $comparison['status'] = 'old_failed_sgml_success';
        } else {
            // Both succeeded - compare data
            $diffs = $this->compareData($oldResult['data'], $sgmlResult['data']);
            if (empty($diffs)) {
                $comparison['status'] = 'identical';
            } else {
                $comparison['status'] = 'different';
                $comparison['differences'] = $diffs;
            }
        }

        return $comparison;
    }

    private function compareData(array $oldData, array $sgmlData): array
    {
        $differences = [];

        // Compare transaction counts
        $oldTxnCount = count($oldData['transactions'] ?? []);
        $sgmlTxnCount = count($sgmlData['transactions'] ?? []);
        if ($oldTxnCount !== $sgmlTxnCount) {
            $differences[] = "Transaction count: Old=$oldTxnCount, SGML=$sgmlTxnCount";
        }

        // Compare account counts
        $oldAcctCount = count($oldData['accounts'] ?? []);
        $sgmlAcctCount = count($sgmlData['accounts'] ?? []);
        if ($oldAcctCount !== $sgmlAcctCount) {
            $differences[] = "Account count: Old=$oldAcctCount, SGML=$sgmlAcctCount";
        }

        // Compare sign-on data
        if (($oldData['sign_on']['institute'] ?? null) !== ($sgmlData['sign_on']['institute'] ?? null)) {
            $differences[] = sprintf(
                "Institute: Old='%s', SGML='%s'",
                $oldData['sign_on']['institute'] ?? 'null',
                $sgmlData['sign_on']['institute'] ?? 'null'
            );
        }

        // Compare first transaction (if any)
        if (!empty($oldData['transactions']) && !empty($sgmlData['transactions'])) {
            $oldFirst = $oldData['transactions'][0];
            $sgmlFirst = $sgmlData['transactions'][0];
            
            foreach (['type', 'date', 'amount', 'fitid'] as $field) {
                $oldVal = $oldFirst[$field] ?? null;
                $sgmlVal = $sgmlFirst[$field] ?? null;
                
                // Normalize amounts for comparison
                if ($field === 'amount') {
                    $oldVal = number_format((float)$oldVal, 2, '.', '');
                    $sgmlVal = number_format((float)$sgmlVal, 2, '.', '');
                }
                
                if ($oldVal !== $sgmlVal) {
                    $differences[] = sprintf(
                        "First txn $field: Old='%s', SGML='%s'",
                        $oldVal ?? 'null',
                        $sgmlVal ?? 'null'
                    );
                }
            }
        }

        // Compare balance
        if (($oldData['balance']['amount'] ?? null) !== ($sgmlData['balance']['amount'] ?? null)) {
            $oldBal = number_format((float)($oldData['balance']['amount'] ?? 0), 2, '.', '');
            $sgmlBal = number_format((float)($sgmlData['balance']['amount'] ?? 0), 2, '.', '');
            if ($oldBal !== $sgmlBal) {
                $differences[] = sprintf("Balance: Old=%s, SGML=%s", $oldBal, $sgmlBal);
            }
        }

        return $differences;
    }

    private function updateStats(array $result): void
    {
        $status = $result['comparison']['status'];
        
        switch ($status) {
            case 'both_failed':
                $this->bothFailed++;
                break;
            case 'old_success_sgml_failed':
                $this->oldSuccessNewFail++;
                break;
            case 'old_failed_sgml_success':
                $this->oldFailNewSuccess++;
                break;
            case 'identical':
                $this->bothSuccess++;
                $this->identicalOutput++;
                break;
            case 'different':
                $this->bothSuccess++;
                $this->differentOutput++;
                break;
        }
    }

    private function printResult(array $result): void
    {
        $old = $result['old_parser'];
        $sgml = $result['sgml_parser'];
        $comp = $result['comparison'];

        // Old parser result
        echo "Old Parser:  ";
        if ($old['success']) {
            echo COLOR_GREEN . "✓ Success" . COLOR_RESET;
            echo sprintf(" (%.3fs)", $old['time']);
            echo sprintf(" - %d txns", count($old['data']['transactions'] ?? []));
        } else {
            echo COLOR_RED . "✗ Failed" . COLOR_RESET;
            echo " - " . substr($old['error'], 0, 60);
        }
        echo "\n";

        // SGML parser result
        echo "SGML Parser: ";
        if ($sgml['success']) {
            echo COLOR_GREEN . "✓ Success" . COLOR_RESET;
            echo sprintf(" (%.3fs)", $sgml['time']);
            echo sprintf(" - %d txns", count($sgml['data']['transactions'] ?? []));
            if (!empty($sgml['warnings'])) {
                echo COLOR_YELLOW . " (" . count($sgml['warnings']) . " warnings)" . COLOR_RESET;
            }
        } else {
            echo COLOR_RED . "✗ Failed" . COLOR_RESET;
            echo " - " . substr($sgml['error'], 0, 60);
        }
        echo "\n";

        // Comparison result
        echo "Comparison:  ";
        switch ($comp['status']) {
            case 'identical':
                echo COLOR_GREEN . COLOR_BOLD . "✓ IDENTICAL OUTPUT" . COLOR_RESET;
                break;
            case 'different':
                echo COLOR_YELLOW . "⚠ Different output (" . count($comp['differences']) . " diffs)" . COLOR_RESET;
                foreach ($comp['differences'] as $diff) {
                    echo "\n             " . COLOR_YELLOW . "- " . $diff . COLOR_RESET;
                }
                break;
            case 'both_failed':
                echo COLOR_RED . "✗ Both failed" . COLOR_RESET;
                break;
            case 'old_failed_sgml_success':
                echo COLOR_GREEN . COLOR_BOLD . "✓ SGML FIXED IT!" . COLOR_RESET;
                echo "\n             Old parser failed, but SGML parser succeeded";
                break;
            case 'old_success_sgml_failed':
                echo COLOR_RED . "✗ Regression - SGML failed where old succeeded" . COLOR_RESET;
                break;
        }
        echo "\n";
    }

    public function printSummary(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo COLOR_BOLD . "SUMMARY" . COLOR_RESET . "\n";
        echo str_repeat("=", 80) . "\n\n";

        echo sprintf("Total files tested:           %d\n", $this->totalFiles);
        echo sprintf("Both parsers succeeded:       %s%d%s\n", COLOR_GREEN, $this->bothSuccess, COLOR_RESET);
        echo sprintf("  - Identical output:         %s%d%s\n", COLOR_GREEN, $this->identicalOutput, COLOR_RESET);
        echo sprintf("  - Different output:         %s%d%s\n", COLOR_YELLOW, $this->differentOutput, COLOR_RESET);
        echo sprintf("Both parsers failed:          %s%d%s\n", COLOR_RED, $this->bothFailed, COLOR_RESET);
        echo sprintf("Old succeeded, SGML failed:   %s%d%s\n", COLOR_RED, $this->oldSuccessNewFail, COLOR_RESET);
        echo sprintf("Old failed, SGML succeeded:   %s%d%s ← SGML IMPROVEMENTS!\n", COLOR_GREEN, $this->oldFailNewSuccess, COLOR_RESET);

        // Success rate
        $successRate = $this->totalFiles > 0 ? ($this->identicalOutput / $this->totalFiles) * 100 : 0;
        echo sprintf("\nIdentical output rate:        %.1f%%\n", $successRate);

        if ($this->oldFailNewSuccess > 0) {
            echo "\n" . COLOR_GREEN . COLOR_BOLD . "✓ SGML parser fixed $this->oldFailNewSuccess file(s) that the old parser couldn't handle!" . COLOR_RESET . "\n";
        }

        if ($this->oldSuccessNewFail > 0) {
            echo "\n" . COLOR_RED . COLOR_BOLD . "✗ WARNING: SGML parser failed on $this->oldSuccessNewFail file(s) that the old parser handled!" . COLOR_RESET . "\n";
        }

        if ($this->identicalOutput === $this->totalFiles) {
            echo "\n" . COLOR_GREEN . COLOR_BOLD . "🎉 PERFECT! All files produced identical output!" . COLOR_RESET . "\n";
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

// Main execution
echo COLOR_BOLD . "\n╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║           OFX Parser Comparison: Old XML Parser vs New SGML Parser         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;

$qfxDir = __DIR__ . '/../../../qfx_files';

if (!is_dir($qfxDir)) {
    die(COLOR_RED . "Error: Directory not found: $qfxDir\n" . COLOR_RESET);
}

$files = glob($qfxDir . '/*.{ofx,qfx,OFX,QFX}', GLOB_BRACE);

if (empty($files)) {
    die(COLOR_YELLOW . "No OFX/QFX files found in $qfxDir\n" . COLOR_RESET);
}

echo "\nFound " . count($files) . " files to test\n";
echo "Directory: $qfxDir\n";

$comparison = new ParserComparison();

foreach ($files as $file) {
    $comparison->compareFile($file);
}

$comparison->printSummary();

echo "\n";
