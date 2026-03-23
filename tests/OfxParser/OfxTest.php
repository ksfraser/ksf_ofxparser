<?php

namespace OfxParserTest;

use OfxParser\Ofx;
use PHPUnit\Framework\TestCase;

/**
 * @covers OfxParser\Ofx
 */
class OfxTest extends TestCase
{
    /**
     * @var \SimpleXMLElement
     */
    protected $ofxData;

    public function setUp(): void
    {
        $ofxFile = dirname(__DIR__).'/fixtures/ofxdata-xml.ofx';

        if (!file_exists($ofxFile)) {
            self::markTestSkipped('Could not find data file, cannot test Ofx Class');
        }
        $this->ofxData = simplexml_load_string(file_get_contents($ofxFile));
    }

    public function amountConversionProvider()
    {
        return [
            '1000.00' => ['1000.00', 1000.0],
            '1000,00' => ['1000,00', 1000.0],
            '1,000.00' => ['1,000.00', 1000.0],
            '1.000,00' => ['1.000,00', 1000.0],
            '-1000.00' => ['-1000.00', -1000.0],
            '-1000,00' => ['-1000,00', -1000.0],
            '-1,000.00' => ['-1,000.00', -1000.0],
            '-1.000,00' => ['-1.000,00', -1000.0],
            '1' => ['1', 1.0],
            '10' => ['10', 10.0],
            '100' => ['100', 100.0], // Fixed: was expecting 1.0 which was a bug in old implementation
            '+1' => ['+1', 1.0],
            '+10' => ['+10', 10.0],
            '+1000.00' => ['+1000.00', 1000.0],
            '+1000,00' => ['+1000,00', 1000.0],
            '+1,000.00' => ['+1,000.00', 1000.0],
            '+1.000,00' => ['+1.000,00', 1000.0],
        ];
    }

    /**
     * @param string $input
     * @param float $output
     * @dataProvider amountConversionProvider
     */
    public function testCreateAmountFromStr($input, $output)
    {
        $method = new \ReflectionMethod(Ofx::class, 'createAmountFromStr');
        $method->setAccessible(true);

        $ofx = new Ofx($this->ofxData);

        self::assertSame($output, $method->invoke($ofx, $input));
    }

    public function testCreateDateTimeFromOFXDateFormats()
    {
        // October 5, 2008, at 1:22 and 124 milliseconds pm, Easter Standard Time
        $expectedDateTime = new \DateTime('2008-10-05 13:22:00');

        $method = new \ReflectionMethod(Ofx::class, 'createDateTimeFromStr');
        $method->setAccessible(true);

        $Ofx = new Ofx($this->ofxData);

        // Test OFX Date Format YYYYMMDDHHMMSS.XXX[gmt offset:tz name]
        $DateTimeOne = $method->invoke($Ofx, '20081005132200.124[-5:EST]');
        self::assertEquals($expectedDateTime->getTimestamp(), $DateTimeOne->getTimestamp());

        // Test YYYYMMDD
        $DateTimeTwo = $method->invoke($Ofx, '20081005');
        self::assertEquals($expectedDateTime->format('Y-m-d'), $DateTimeTwo->format('Y-m-d'));

        // Test YYYYMMDDHHMMSS
        $DateTimeThree = $method->invoke($Ofx, '20081005132200');
        self::assertEquals($expectedDateTime->getTimestamp(), $DateTimeThree->getTimestamp());

        // Test YYYYMMDDHHMMSS.XXX
        $DateTimeFour = $method->invoke($Ofx, '20081005132200.124');
        self::assertEquals($expectedDateTime->getTimestamp(), $DateTimeFour->getTimestamp());
    }

    public function testBuildsSignOn()
    {
        $ofx = new Ofx($this->ofxData);
        self::assertEquals('', $ofx->signOn->status->message);
        self::assertEquals('0', $ofx->signOn->status->code);
        self::assertEquals('INFO', $ofx->signOn->status->severity);
        self::assertEquals('Success', $ofx->signOn->status->codeDesc);

        self::assertInstanceOf('DateTime', $ofx->signOn->date);
        self::assertEquals('ENG', $ofx->signOn->language);
        self::assertEquals('MYBANK', $ofx->signOn->institute->name);
        self::assertEquals('01234', $ofx->signOn->institute->id);
    }

    public function testBuildsMultipleBankAccounts()
    {
        $multiOfxFile = dirname(__DIR__).'/fixtures/ofx-multiple-accounts-xml.ofx';
        if (!file_exists($multiOfxFile)) {
            self::markTestSkipped('Could not find multiple account data file, cannot fully test Multiple Bank Accounts');
        }
        $multiOfxData = simplexml_load_string(file_get_contents($multiOfxFile));
        $ofx = new Ofx($multiOfxData);

        self::assertCount(3, $ofx->bankAccounts);
        self::assertEmpty($ofx->bankAccount);
    }

    public function testBuildsBankAccount()
    {
        $Ofx = new Ofx($this->ofxData);

        $bankAccount = $Ofx->bankAccount;
        self::assertEquals('23382938', $bankAccount->transactionUid);
        self::assertEquals('999-999', $bankAccount->accountNumber);
        self::assertEquals('999999999', $bankAccount->routingNumber);
        self::assertEquals('SAVINGS', $bankAccount->accountType);
        self::assertEquals('5250.00', $bankAccount->balance);
        self::assertInstanceOf('DateTime', $bankAccount->balanceDate);

        $statement = $bankAccount->statement;
        self::assertEquals('USD', $statement->currency);
        self::assertInstanceOf('DateTime', $statement->startDate);
        self::assertInstanceOf('DateTime', $statement->endDate);

        $transactions = $statement->transactions;
        self::assertCount(3, $transactions);

        $expectedTransactions = [
           [
              'type' => 'CREDIT',
              'typeDesc' => 'Generic credit',
              'amount' => '200.00',
              'uniqueId' => '980315001',
              'name' => 'DEPOSIT',
              'memo' => 'automatic deposit',
              'sic' => '',
              'checkNumber' => ''
           ],
           [
               'type' => 'CREDIT',
               'typeDesc' => 'Generic credit',
               'amount' => '150.00',
               'uniqueId' => '980310001',
               'name' => 'TRANSFER',
               'memo' => 'Transfer from checking',
               'sic' => '',
               'checkNumber' => ''
           ],
           [
               'type' => 'CHECK',
               'typeDesc' => 'Cheque',
               'amount' => '-100.00',
               'uniqueId' => '980309001',
               'name' => 'Cheque',
               'memo' => '',
               'sic' => '',
               'checkNumber' => '1025'
           ],

        ];

        foreach ($transactions as $i => $transaction) {
            self::assertEquals($expectedTransactions[$i]['type'], $transaction->type);
            self::assertEquals($expectedTransactions[$i]['typeDesc'], $transaction->typeDesc);
            self::assertEquals($expectedTransactions[$i]['amount'], $transaction->amount);
            self::assertEquals($expectedTransactions[$i]['uniqueId'], $transaction->uniqueId);
            self::assertEquals($expectedTransactions[$i]['name'], $transaction->name);
            self::assertEquals($expectedTransactions[$i]['memo'], $transaction->memo);
            self::assertEquals($expectedTransactions[$i]['sic'], $transaction->sic);
            self::assertEquals($expectedTransactions[$i]['checkNumber'], $transaction->checkNumber);
            self::assertInstanceOf('DateTime', $transaction->date);
            self::assertInstanceOf('DateTime', $transaction->userInitiatedDate);
        }
    }

    public function testBuildHeaderSetsHeaderProperty()
    {
        $headerData = [
            'OFXHEADER' => '100',
            'DATA' => 'OFXSGML',
            'VERSION' => '102',
            'SECURITY' => 'NONE',
            'ENCODING' => 'USASCII',
            'CHARSET' => '1252',
            'COMPRESSION' => 'NONE',
            'OLDFILEUID' => 'NONE',
            'NEWFILEUID' => 'NONE'
        ];
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $ofx->buildHeader($headerData);
        
        self::assertIsArray($ofx->header);
        self::assertEquals('100', $ofx->header['OFXHEADER']);
        self::assertEquals('OFXSGML', $ofx->header['DATA']);
        self::assertEquals('102', $ofx->header['VERSION']);
    }

    public function testBuildHeaderWithEmptyArray()
    {
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $result = $ofx->buildHeader([]);
        
        self::assertInstanceOf(\OfxParser\Ofx::class, $result);
        self::assertIsArray($ofx->header);
        self::assertEmpty($ofx->header);
    }

    public function testCreateTagsAddsMissingSignonMsgsrsv1()
    {
        $xmlWithoutSignOn = simplexml_load_string('<OFX><BANKMSGSRSV1><STMTTRNRS><TRNUID>1</TRNUID></STMTTRNRS></BANKMSGSRSV1></OFX>');
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $result = $ofx->createTags($xmlWithoutSignOn);
        
        self::assertTrue(property_exists($result, 'SIGNONMSGSRSV1'));
        self::assertTrue(property_exists($result->SIGNONMSGSRSV1, 'SONRS'));
    }

    public function testCreateTagsAddsMissingSonrs()
    {
        $xmlWithoutSonrs = simplexml_load_string('<OFX><SIGNONMSGSRSV1></SIGNONMSGSRSV1><BANKMSGSRSV1><STMTTRNRS><TRNUID>1</TRNUID></STMTTRNRS></BANKMSGSRSV1></OFX>');
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $result = $ofx->createTags($xmlWithoutSonrs);
        
        self::assertTrue(property_exists($result->SIGNONMSGSRSV1, 'SONRS'));
    }

    public function testCreateTagsDoesNotModifyCompleteXml()
    {
        $completeXml = simplexml_load_string('<OFX><SIGNONMSGSRSV1><SONRS></SONRS></SIGNONMSGSRSV1></OFX>');
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $result = $ofx->createTags($completeXml);
        
        self::assertEquals('OFX', $result->getName());
    }

    public function testBuildTransactionsHandlesEmptyTransactions()
    {
        $emptyTransactions = simplexml_load_string('<BANKTRANLIST><STMTTRN></STMTTRN></BANKTRANLIST>');
        
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildTransactions');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        
        // Empty transaction should throw RuntimeException when trying to parse empty date
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to initialize DateTime for string:');
        $method->invoke($ofx, $emptyTransactions);
    }

    public function testBuildTransactionsWithPayee()
    {
        $transactionWithPayee = simplexml_load_string(
            '<BANKTRANLIST><STMTTRN><TRNTYPE>DEBIT</TRNTYPE><DTPOSTED>20200101</DTPOSTED><TRNAMT>-100.00</TRNAMT><FITID>123</FITID><NAME>Test</NAME><MEMO>Test memo</MEMO><PAYEE><NAME>Payee Name</NAME><CITY>City</CITY><STATE>ST</STATE><POSTALCODE>12345</POSTALCODE><PHONE>1234567890</PHONE></PAYEE></STMTTRN></BANKTRANLIST>'
        );
        
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildTransactions');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $transactions = $method->invoke($ofx, $transactionWithPayee);
        
        self::assertCount(1, $transactions);
        self::assertNotNull($transactions[0]->payee);
        self::assertEquals('Payee Name', $transactions[0]->payee->name);
        self::assertEquals('City', $transactions[0]->payee->city);
    }

    public function testBuildTransactionsWithBankAccountTo()
    {
        $transactionWithBankTo = simplexml_load_string(
            '<BANKTRANLIST><STMTTRN><TRNTYPE>XFER</TRNTYPE><DTPOSTED>20200101</DTPOSTED><TRNAMT>-100.00</TRNAMT><FITID>123</FITID><NAME>Transfer</NAME><BANKACCTTO><BANKID>123456</BANKID><ACCTID>9876543210</ACCTID><ACCTTYPE>CHECKING</ACCTTYPE></BANKACCTTO></STMTTRN></BANKTRANLIST>'
        );
        
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildTransactions');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $transactions = $method->invoke($ofx, $transactionWithBankTo);
        
        self::assertCount(1, $transactions);
        self::assertNotNull($transactions[0]->bankAccountTo);
        self::assertEquals('123456', $transactions[0]->bankAccountTo->routingNumber);
        self::assertEquals('9876543210', $transactions[0]->bankAccountTo->accountNumber);
    }

    public function testBuildTransactionsWithCardAccountTo()
    {
        $transactionWithCardTo = simplexml_load_string(
            '<BANKTRANLIST><STMTTRN><TRNTYPE>XFER</TRNTYPE><DTPOSTED>20200101</DTPOSTED><TRNAMT>-100.00</TRNAMT><FITID>123</FITID><NAME>Transfer</NAME><CCACCTTO><ACCTID>1234567890123456</ACCTID></CCACCTTO></STMTTRN></BANKTRANLIST>'
        );
        
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildTransactions');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $transactions = $method->invoke($ofx, $transactionWithCardTo);
        
        self::assertCount(1, $transactions);
        self::assertNotNull($transactions[0]->cardAccountTo);
        self::assertEquals('1234567890123456', $transactions[0]->cardAccountTo->accountNumber);
    }

    public function testBuildStatusWithAllFields()
    {
        $statusXml = simplexml_load_string('<STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY><MESSAGE>Success</MESSAGE></STATUS>');
        
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildStatus');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $status = $method->invoke($ofx, $statusXml);
        
        self::assertInstanceOf(\OfxParser\Entities\Status::class, $status);
        self::assertEquals(0, $status->code);
        self::assertEquals('INFO', $status->severity);
        self::assertEquals('Success', $status->message);
    }

    public function testBuildPayeeWithAllFields()
    {
        $payeeXml = simplexml_load_string(
            '<PAYEE><NAME>John Doe</NAME><ADDR1>123 Main St</ADDR1><ADDR2>Apt 4B</ADDR2><ADDR3>Building C</ADDR3><CITY>Springfield</CITY><STATE>IL</STATE><POSTALCODE>62701</POSTALCODE><PHONE>555-1234</PHONE></PAYEE>'
        );
        
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildPayee');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $payee = $method->invoke($ofx, $payeeXml);
        
        self::assertInstanceOf(\OfxParser\Entities\Payee::class, $payee);
        self::assertEquals('John Doe', $payee->name);
        self::assertIsArray($payee->address);
        self::assertCount(3, $payee->address);
        self::assertEquals('Springfield', $payee->city);
        self::assertEquals('IL', $payee->state);
        self::assertEquals('62701', $payee->postalCode);
        self::assertEquals('555-1234', $payee->phone);
    }

    public function testBuildPayeeWithMinimalFields()
    {
        $payeeXml = simplexml_load_string('<PAYEE><NAME>Minimal Payee</NAME></PAYEE>');
        
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'buildPayee');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        $payee = $method->invoke($ofx, $payeeXml);
        
        self::assertInstanceOf(\OfxParser\Entities\Payee::class, $payee);
        self::assertEquals('Minimal Payee', $payee->name);
        self::assertNull($payee->address);
    }

    public function testCreateAmountFromStrWithScientificNotation()
    {
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'createAmountFromStr');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        
        self::assertEquals(1000.0, $method->invoke($ofx, '1e3'));
        self::assertEquals(0.01, $method->invoke($ofx, '1e-2'));
    }

    public function testCreateDateTimeFromStrWithTimezone()
    {
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'createDateTimeFromStr');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        
        $date = $method->invoke($ofx, '20200101120000[-5:EST]');
        
        self::assertInstanceOf(\DateTime::class, $date);
        self::assertEquals('2020-01-01', $date->format('Y-m-d'));
    }

    public function testCreateDateTimeFromStrWithMilliseconds()
    {
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'createDateTimeFromStr');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        
        $date = $method->invoke($ofx, '20200101120000.123');
        
        self::assertInstanceOf(\DateTime::class, $date);
        self::assertEquals('2020-01-01 12:00:00', $date->format('Y-m-d H:i:s'));
    }

    public function testCreateDateTimeFromStrReturnsNullOnInvalidDate()
    {
        $method = new \ReflectionMethod(\OfxParser\Ofx::class, 'createDateTimeFromStr');
        $method->setAccessible(true);
        
        $ofx = new \OfxParser\Ofx($this->ofxData);
        
        $date = $method->invoke($ofx, 'invalid', true);
        
        self::assertNull($date);
    }
}
