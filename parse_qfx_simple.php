<?php
/**
 * Parse QFX files with immediate output for each file
 */

require_once __DIR__ . '/vendor/autoload.php';

use OfxParser\Parser;

$qfxDir = __DIR__ . '/QFX';
$parser = new Parser();

$files = [
    '20170901 CapitalOne.qfx',
    '20260112 Presidents Choice Mastercard.qfx',
    '20260311 CIBC HISA.ofx',
    '20260311 CIBC SAV.ofx',
    '20260311 CIBC VISA.ofx',
    '20260311_1518404_transactions.qbo',
    '20260311_1708272_transactions.qbo',
    'ATB_6030_2025-03-12_to_2025-09-08.qbo',
];

echo sprintf("%-45s | %-20s | Tx | Status\n", "File", "Bank");
echo str_repeat("-", 85) . "\n";

foreach ($files as $fileName) {
    $filePath = $qfxDir . '/' . $fileName;
    
    if (!file_exists($filePath)) {
        echo sprintf("%-45s | %-20s | -- | NOT FOUND\n", $fileName, "N/A");
        continue;
    }
    
    $fileSize = filesize($filePath);
    if ($fileSize === 0) {
        echo sprintf("%-45s | %-20s | -- | EMPTY\n", $fileName, "N/A");
        continue;
    }

    try {
        defined('OFX_PARSE_DEBUG') || define('OFX_PARSE_DEBUG', false);
        
        $start = microtime(true);
        $data = $parser->loadFromFile($filePath);
        $elapsed = round(microtime(true) - $start, 2);
        
        $bankName = 'Unknown';
        $transactionCount = 0;

        if (!empty($data->bankAccounts)) {
            foreach ($data->bankAccounts as $account) {
                if ($account->institution && !empty($account->institution->organization)) {
                    $bankName = $account->institution->organization;
                }
                
                if ($account->statement && !empty($account->statement->transactions)) {
                    $transactionCount += count($account->statement->transactions);
                }
            }
        }

        if (!empty($data->creditCards)) {
            foreach ($data->creditCards as $account) {
                if ($account->institution && !empty($account->institution->organization)) {
                    $bankName = $account->institution->organization;
                }
                
                if ($account->statement && !empty($account->statement->transactions)) {
                    $transactionCount += count($account->statement->transactions);
                }
            }
        }

        printf("%-45s | %-20s | %2d | OK (%.2fs)\n",
            substr($fileName, 0, 45),
            substr($bankName, 0, 20),
            $transactionCount,
            $elapsed
        );
        flush();

    } catch (Exception $e) {
        $msg = substr($e->getMessage(), 0, 15);
        printf("%-45s | %-20s | -- | FAIL: %s\n",
            substr($fileName, 0, 45),
            "ERROR",
            $msg
        );
        flush();
    }
}

echo str_repeat("-", 85) . "\n";
echo "Done!\n";
