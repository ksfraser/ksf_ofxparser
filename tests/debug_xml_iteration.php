<?php

require __DIR__ . '/../vendor/autoload.php';

$xmlFile = __DIR__ . '/fixtures/ofxdata-xml.ofx';
$xml = simplexml_load_string(file_get_contents($xmlFile));

$transactions = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN;

echo "Type: " . gettype($transactions) . "\n";
echo "Class: " . get_class($transactions) . "\n";
echo "Name: " . $transactions->getName() . "\n";
echo "Count: " . count($transactions) . "\n";

echo "\n--- Testing foreach: ---\n";
$i = 0;
foreach ($transactions as $t) {
    $i++;
    echo "Transaction $i: " . $t->getName() . " FITID: " . (string)$t->FITID . "\n";
}

echo "\n--- Testing with single element: ---\n";
$singleXml = '<BANKTRANLIST><STMTTRN><FITID>test1</FITID><TRNTYPE>DEBIT</TRNTYPE></STMTTRN></BANKTRANLIST>';
$singleRoot = simplexml_load_string($singleXml);
$singleTrans = $singleRoot->STMTTRN;

echo "Single Type: " . gettype($singleTrans) . "\n";
echo "Single Name: " . $singleTrans->getName() . "\n";
echo "Single Count: " . count($singleTrans) . "\n";
echo "Single Children: " . count($singleTrans->children()) . "\n";

echo "\n--- Testing foreach with single: ---\n";
$j = 0;
foreach ($singleTrans as $t) {
    $j++;
    echo "Single Transaction $j: " . $t->getName() . "\n";
    if (isset($t->FITID)) {
        echo "  FITID: " . (string)$t->FITID . "\n";
    }
}

echo "\n--- Now test with the actual STMTTRN data from the test: ---\n";
$testXml = '<BANKTRANLIST><STMTTRN><TRNTYPE>DEBIT</TRNTYPE><DTPOSTED>20200101</DTPOSTED><TRNAMT>-100.00</TRNAMT><FITID>test123</FITID><PAYEE><NAME>Test Payee</NAME></PAYEE></STMTTRN></BANKTRANLIST>';
$testRoot = simplexml_load_string($testXml);
$testElement = $testRoot->STMTTRN;  // Access via child selector

echo "Test Element Name: " . $testElement->getName() . "\n";
echo "Test Element Count: " . count($testElement) . "\n";
echo "Test Element Children: " . count($testElement->children()) . "\n";

echo "\n--- Foreach over test element (accessed via ->STMTTRN): ---\n";
$k = 0;
foreach ($testElement as $child) {
    $k++;
    echo "Child $k: " . $child->getName() . "\n";
    if (isset($child->FITID)) {
        echo "  FITID: " . (string)$child->FITID . "\n";
    }
}

echo "\n--- Direct test: ---\n";
$directXml = simplexml_load_string('<STMTTRN><TRNTYPE>DEBIT</TRNTYPE><DTPOSTED>20200101</DTPOSTED><TRNAMT>-100.00</TRNAMT><FITID>test123</FITID><PAYEE><NAME>Test Payee</NAME></PAYEE></STMTTRN>');
echo "Direct Element Name: " . $directXml->getName() . "\n";
echo "Direct Element Count: " . count($directXml) . "\n";

echo "\n--- Foreach over direct element: ---\n";
$m = 0;
foreach ($directXml as $child) {
    $m++;
    echo "Direct Child $m: " . $child->getName() . " = " . (string)$child . "\n";
}

echo "\n--- Testing xpath approach: ---\n";
// Test xpath on multiple transactions
$xpathResult = $transactions->xpath('.');
echo "Xpath on multiple STMTTRN: count = " . count($xpathResult) . "\n";
foreach ($xpathResult as $idx => $xr) {
    echo "  Result $idx: " . $xr->getName() . " FITID: " . (string)$xr->FITID . "\n";
}

// Test xpath on single transaction
$singleXpath = $singleTrans->xpath('.');
echo "\nXpath on single STMTTRN: count = " . count($singleXpath) . "\n";
foreach ($singleXpath as $idx => $xr) {
    echo "  Result $idx: " . $xr->getName() . " FITID: " . (string)$xr->FITID . "\n";
}

// Test xpath on direct element
$directXpath = $directXml->xpath('.');
echo "\nXpath on direct STMTTRN: count = " . count($directXpath) . "\n";
foreach ($directXpath as $idx => $xr) {
    echo "  Result $idx: " . $xr->getName() . " FITID: " . (string)$xr->FITID . "\n";
}



