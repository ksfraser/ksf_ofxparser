<?php

require_once __DIR__ . '/../vendor/autoload.php';

$ofxFile = __DIR__ . '/fixtures/ofxdata-xml.ofx';
$ofxData = simplexml_load_string(file_get_contents($ofxFile));

echo "Creating Ofx object...\n";
try {
    $ofx = new \OfxParser\Ofx($ofxData);
    echo "SUCCESS: Ofx object created\n";
    echo "Bank accounts: " . count($ofx->bankAccounts) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nNow testing buildTransactions like the test does...\n";
$transactionWithPayee = simplexml_load_string(
    '<STMTTRN><TRNTYPE>DEBIT</TRNTYPE><DTPOSTED>20200101</DTPOSTED><TRNAMT>-100.00</TRNAMT><FITID>123</FITID><NAME>Test</NAME><MEMO>Test memo</MEMO><PAYEE><NAME>Payee Name</NAME><CITY>City</CITY><STATE>ST</STATE><POSTALCODE>12345</POSTALCODE><PHONE>1234567890</PHONE></PAYEE></STMTTRN>'
);

echo "Transaction XML loaded\n";
echo "DTPOSTED value: '" . (string)$transactionWithPayee->DTPOSTED . "'\n";
echo "TRNTYPE value: '" . (string)$transactionWithPayee->TRNTYPE . "'\n";

$method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildTransactions');
$method->setAccessible(true);

try {
    $transactions = $method->invoke($ofx, $transactionWithPayee);
    echo "SUCCESS: buildTransactions returned " . count($transactions) . " transactions\n";
    if (count($transactions) > 0) {
        echo "Transaction has payee: " . ($transactions[0]->payee !== null ? "YES" : "NO") . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
