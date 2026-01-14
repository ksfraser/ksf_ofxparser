<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Metrics\ParsingMetrics;

class ParsingMetricsTest extends TestCase
{
    private ParsingMetrics $metrics;
    
    protected function setUp(): void
    {
        $this->metrics = new ParsingMetrics();
    }
    
    public function testInitialState(): void
    {
        $this->assertEquals(0, $this->metrics->getTotalTransactions());
        $this->assertEquals(0, $this->metrics->getSuccessfulTransactions());
        $this->assertEquals(0, $this->metrics->getIncompleteTransactions());
        $this->assertEquals(0, $this->metrics->getCorruptTransactions());
        $this->assertEquals(0, $this->metrics->getUnexpectedErrors());
        $this->assertEquals(0.0, $this->metrics->getSuccessRate());
    }
    
    public function testIncrementSuccessfulTransaction(): void
    {
        $this->metrics->incrementSuccessfulTransaction();
        $this->metrics->incrementSuccessfulTransaction();
        
        $this->assertEquals(2, $this->metrics->getSuccessfulTransactions());
        $this->assertEquals(2, $this->metrics->getTotalTransactions());
    }
    
    public function testIncrementIncompleteTransaction(): void
    {
        $this->metrics->incrementIncompleteTransaction();
        
        $this->assertEquals(1, $this->metrics->getIncompleteTransactions());
        $this->assertEquals(1, $this->metrics->getTotalTransactions());
    }
    
    public function testIncrementCorruptTransaction(): void
    {
        $this->metrics->incrementCorruptTransaction();
        
        $this->assertEquals(1, $this->metrics->getCorruptTransactions());
        $this->assertEquals(1, $this->metrics->getTotalTransactions());
    }
    
    public function testIncrementUnexpectedError(): void
    {
        $this->metrics->incrementUnexpectedError();
        
        $this->assertEquals(1, $this->metrics->getUnexpectedErrors());
    }
    
    public function testSuccessRateCalculation(): void
    {
        $this->metrics->incrementSuccessfulTransaction();
        $this->metrics->incrementSuccessfulTransaction();
        $this->metrics->incrementSuccessfulTransaction();
        $this->metrics->incrementCorruptTransaction();
        
        // 3 successful out of 4 total = 75%
        $this->assertEquals(75.0, $this->metrics->getSuccessRate());
    }
    
    public function testSuccessRateWithZeroTransactions(): void
    {
        $this->assertEquals(0.0, $this->metrics->getSuccessRate());
    }
    
    public function testIncrementMissingRequiredField(): void
    {
        $this->metrics->incrementMissingRequiredField('FITID');
        $this->metrics->incrementMissingRequiredField('FITID');
        $this->metrics->incrementMissingRequiredField('TRNAMT');
        
        $missing = $this->metrics->getMissingRequiredFields();
        $this->assertArrayHasKey('FITID', $missing);
        $this->assertArrayHasKey('TRNAMT', $missing);
        $this->assertEquals(2, $missing['FITID']);
        $this->assertEquals(1, $missing['TRNAMT']);
    }
    
    public function testIncrementMissingOptionalField(): void
    {
        $this->metrics->incrementMissingOptionalField('MEMO');
        $this->metrics->incrementMissingOptionalField('NAME');
        $this->metrics->incrementMissingOptionalField('MEMO');
        
        $missing = $this->metrics->getMissingOptionalFields();
        $this->assertArrayHasKey('MEMO', $missing);
        $this->assertArrayHasKey('NAME', $missing);
        $this->assertEquals(2, $missing['MEMO']);
        $this->assertEquals(1, $missing['NAME']);
    }
    
    public function testIncrementFieldRecovery(): void
    {
        $this->metrics->incrementFieldRecovery('MEMO');
        $this->metrics->incrementFieldRecovery('MEMO');
        
        $recoveries = $this->metrics->getFieldRecoveries();
        $this->assertArrayHasKey('MEMO', $recoveries);
        $this->assertEquals(2, $recoveries['MEMO']);
    }
    
    public function testLogCorruptTransaction(): void
    {
        $this->metrics->logCorruptTransaction(42, 'Missing FITID', '<STMTTRN>...');
        
        $logs = $this->metrics->getCorruptTransactionLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals(42, $logs[0]['transaction_number']);
        $this->assertEquals('Missing FITID', $logs[0]['reason']);
        $this->assertEquals('<STMTTRN>...', $logs[0]['xml_snippet']);
        $this->assertInstanceOf(\DateTime::class, $logs[0]['timestamp']);
    }
    
    public function testLogIncompleteTransaction(): void
    {
        $missingFields = ['NAME', 'MEMO'];
        $this->metrics->logIncompleteTransaction(10, $missingFields);
        
        $logs = $this->metrics->getIncompleteTransactionLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals(10, $logs[0]['transaction_number']);
        $this->assertEquals($missingFields, $logs[0]['missing_fields']);
    }
    
    public function testLogUnexpectedError(): void
    {
        $this->metrics->logUnexpectedError(99, 'Some error', 'stack trace');
        
        $logs = $this->metrics->getUnexpectedErrorLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals(99, $logs[0]['transaction_number']);
        $this->assertEquals('Some error', $logs[0]['error']);
        $this->assertEquals('stack trace', $logs[0]['trace']);
    }
    
    public function testToArray(): void
    {
        $this->metrics->incrementSuccessfulTransaction();
        $this->metrics->incrementCorruptTransaction();
        $this->metrics->incrementMissingRequiredField('FITID');
        
        $array = $this->metrics->toArray();
        
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('field_issues', $array);
        $this->assertArrayHasKey('logs', $array);
        
        $this->assertEquals(2, $array['summary']['total']);
        $this->assertEquals(1, $array['summary']['successful']);
        $this->assertEquals('50%', $array['summary']['success_rate']);
    }
    
    public function testReset(): void
    {
        $this->metrics->incrementSuccessfulTransaction();
        $this->metrics->incrementCorruptTransaction();
        $this->metrics->incrementMissingRequiredField('FITID');
        $this->metrics->logCorruptTransaction(1, 'test', null);
        
        $this->metrics->reset();
        
        $this->assertEquals(0, $this->metrics->getTotalTransactions());
        $this->assertEquals(0, $this->metrics->getSuccessfulTransactions());
        $this->assertEquals(0, $this->metrics->getCorruptTransactions());
        $this->assertEmpty($this->metrics->getMissingRequiredFields());
        $this->assertEmpty($this->metrics->getCorruptTransactionLogs());
    }
}
