<?php

namespace OfxParserTest\Parsers;

use OfxParser\Entities\Profile\Profile;
use OfxParser\Entities\Profile\MessageSetInfo;
use OfxParser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for PROFMSGSRSV1 (Profile Message Set) parsing
 * 
 * Profile provides FI capability discovery - what services, message sets,
 * and features are supported by the financial institution.
 * 
 * Use Cases:
 * - Determine available services before making requests
 * - Check supported message set versions
 * - Validate transaction type support
 * - Discover limits and fees
 */
class ProfileTest extends TestCase
{
    /**
     * Test parsing basic profile information
     * 
     * Profile should contain:
     * - FI name and address
     * - Customer service contact info
     * - Supported message sets
     */
    public function testParseBasicProfileInformation(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20260117120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<PROFMSGSRSV1>
<PROFRS>
<MSGSETLIST>
<SIGNONMSGSET>
<SIGNONMSGSETV1>
<MSGSETCORE>
<VER>1
<URL>https://ofx.example.com
<OFXSEC>NONE
<TRANSPSEC>Y
<SIGNONREALM>Example Bank
<LANGUAGE>ENG
</MSGSETCORE>
</SIGNONMSGSETV1>
</SIGNONMSGSET>
<BANKMSGSET>
<BANKMSGSETV1>
<MSGSETCORE>
<VER>1
<URL>https://ofx.example.com
<OFXSEC>NONE
<TRANSPSEC>Y
<SIGNONREALM>Example Bank
<LANGUAGE>ENG
</MSGSETCORE>
<INVALIDACCTTYPE>CHECKING
<INVALIDACCTTYPE>SAVINGS
<CLOSINGAVAIL>Y
</BANKMSGSETV1>
</BANKMSGSET>
</MSGSETLIST>
<SIGNONINFOLIST>
<SIGNONINFO>
<SIGNONREALM>Example Bank
<MIN>8
<MAX>32
<CHARTYPE>ALPHAORNUMERIC
<CASESEN>N
<SPECIAL>Y
<SPACES>N
<PINCH>N
</SIGNONINFO>
</SIGNONINFOLIST>
<DTPROFUP>20260101120000
<FINAME>Example Bank
<ADDR1>123 Main Street
<CITY>Toronto
<STATE>ON
<POSTALCODE>M5H 2N2
<COUNTRY>CAN
<CSPHONE>1-800-555-1234
<TSPHONE>1-800-555-5678
<FAXPHONE>1-416-555-9999
<URL>https://www.examplebank.com
<EMAIL>support@examplebank.com
</PROFRS>
</PROFMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertInstanceOf(Profile::class, $ofx->profile);
        $this->assertEquals('Example Bank', $ofx->profile->fiName);
        $this->assertEquals('123 Main Street', $ofx->profile->address['line1']);
        $this->assertEquals('Toronto', $ofx->profile->city);
        $this->assertEquals('ON', $ofx->profile->state);
        $this->assertEquals('M5H 2N2', $ofx->profile->postalCode);
        $this->assertEquals('CAN', $ofx->profile->country);
        $this->assertEquals('1-800-555-1234', $ofx->profile->customerServicePhone);
        $this->assertEquals('1-800-555-5678', $ofx->profile->technicalSupportPhone);
        $this->assertEquals('support@examplebank.com', $ofx->profile->email);
        $this->assertInstanceOf(\DateTime::class, $ofx->profile->profileLastUpdated);
        $this->assertEquals('20260101', $ofx->profile->profileLastUpdated->format('Ymd'));
    }

    /**
     * Test parsing supported message sets
     * 
     * MessageSetInfo should contain:
     * - Message set type (SIGNON, BANK, CREDITCARD, etc.)
     * - Version number
     * - Service URL
     * - Security requirements
     */
    public function testParseSupportedMessageSets(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<DTSERVER>20260117120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<PROFMSGSRSV1>
<PROFRS>
<MSGSETLIST>
<SIGNONMSGSET>
<SIGNONMSGSETV1>
<MSGSETCORE>
<VER>1
<URL>https://ofx.example.com
<OFXSEC>NONE
<TRANSPSEC>Y
<SIGNONREALM>Example Bank
<LANGUAGE>ENG
</MSGSETCORE>
</SIGNONMSGSETV1>
</SIGNONMSGSET>
<BANKMSGSET>
<BANKMSGSETV1>
<MSGSETCORE>
<VER>1
<URL>https://ofx.example.com
<OFXSEC>NONE
<TRANSPSEC>Y
<SIGNONREALM>Example Bank
<LANGUAGE>ENG
</MSGSETCORE>
</BANKMSGSETV1>
</BANKMSGSET>
<CREDITCARDMSGSET>
<CREDITCARDMSGSETV1>
<MSGSETCORE>
<VER>1
<URL>https://ofx.example.com
<OFXSEC>NONE
<TRANSPSEC>Y
<SIGNONREALM>Example Bank
<LANGUAGE>ENG
</MSGSETCORE>
</CREDITCARDMSGSETV1>
</CREDITCARDMSGSET>
<INVSTMTMSGSET>
<INVSTMTMSGSETV1>
<MSGSETCORE>
<VER>1
<URL>https://ofx.example.com
<OFXSEC>NONE
<TRANSPSEC>Y
<SIGNONREALM>Example Bank
<LANGUAGE>ENG
</MSGSETCORE>
</INVSTMTMSGSETV1>
</INVSTMTMSGSET>
</MSGSETLIST>
<SIGNONINFOLIST>
<SIGNONINFO>
<SIGNONREALM>Example Bank
<MIN>8
<MAX>32
<CHARTYPE>ALPHAORNUMERIC
<CASESEN>N
<SPECIAL>Y
<SPACES>N
<PINCH>N
</SIGNONINFO>
</SIGNONINFOLIST>
<DTPROFUP>20260101120000
<FINAME>Example Bank
</PROFRS>
</PROFMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertIsArray($ofx->profile->messageSets);
        $this->assertCount(4, $ofx->profile->messageSets);

        // Check SIGNON message set
        $signonMsgSet = $this->findMessageSet($ofx->profile->messageSets, 'SIGNON');
        $this->assertNotNull($signonMsgSet);
        $this->assertEquals(1, $signonMsgSet->version);
        $this->assertEquals('https://ofx.example.com', $signonMsgSet->url);
        $this->assertEquals('Example Bank', $signonMsgSet->realm);
        $this->assertTrue($signonMsgSet->transportSecurity);

        // Check BANK message set
        $bankMsgSet = $this->findMessageSet($ofx->profile->messageSets, 'BANK');
        $this->assertNotNull($bankMsgSet);
        $this->assertEquals(1, $bankMsgSet->version);

        // Check CREDITCARD message set
        $ccMsgSet = $this->findMessageSet($ofx->profile->messageSets, 'CREDITCARD');
        $this->assertNotNull($ccMsgSet);

        // Check INVSTMT message set
        $invMsgSet = $this->findMessageSet($ofx->profile->messageSets, 'INVSTMT');
        $this->assertNotNull($invMsgSet);
    }

    /**
     * Test parsing signon requirements
     * 
     * SignonInfo should contain:
     * - Password requirements (min/max length, character types)
     * - Case sensitivity
     * - Special character support
     */
    public function testParseSignonRequirements(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<DTSERVER>20260117120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<PROFMSGSRSV1>
<PROFRS>
<MSGSETLIST>
<SIGNONMSGSET>
<SIGNONMSGSETV1>
<MSGSETCORE>
<VER>1
<URL>https://ofx.example.com
<OFXSEC>NONE
<TRANSPSEC>Y
<SIGNONREALM>Example Bank
<LANGUAGE>ENG
</MSGSETCORE>
</SIGNONMSGSETV1>
</SIGNONMSGSET>
</MSGSETLIST>
<SIGNONINFOLIST>
<SIGNONINFO>
<SIGNONREALM>Example Bank
<MIN>8
<MAX>32
<CHARTYPE>ALPHAORNUMERIC
<CASESEN>Y
<SPECIAL>Y
<SPACES>N
<PINCH>Y
<CHGPINFIRST>N
</SIGNONINFO>
</SIGNONINFOLIST>
<DTPROFUP>20260101120000
<FINAME>Example Bank
</PROFRS>
</PROFMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertNotNull($ofx->profile->signonInfo);
        $this->assertEquals('Example Bank', $ofx->profile->signonInfo->realm);
        $this->assertEquals(8, $ofx->profile->signonInfo->minPasswordLength);
        $this->assertEquals(32, $ofx->profile->signonInfo->maxPasswordLength);
        $this->assertEquals('ALPHAORNUMERIC', $ofx->profile->signonInfo->charType);
        $this->assertTrue($ofx->profile->signonInfo->caseSensitive);
        $this->assertTrue($ofx->profile->signonInfo->specialCharsAllowed);
        $this->assertFalse($ofx->profile->signonInfo->spacesAllowed);
        $this->assertTrue($ofx->profile->signonInfo->pinChangeSupported);
    }

    /**
     * Test parsing XML format profile
     */
    public function testParseXmlFormatProfile(): void
    {
        $ofxContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20260117120000</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
<PROFMSGSRSV1>
<PROFRS>
<MSGSETLIST>
<SIGNONMSGSET>
<SIGNONMSGSETV1>
<MSGSETCORE>
<VER>1</VER>
<URL>https://ofx.example.com</URL>
<OFXSEC>NONE</OFXSEC>
<TRANSPSEC>Y</TRANSPSEC>
<SIGNONREALM>Example Bank</SIGNONREALM>
<LANGUAGE>ENG</LANGUAGE>
</MSGSETCORE>
</SIGNONMSGSETV1>
</SIGNONMSGSET>
<BANKMSGSET>
<BANKMSGSETV1>
<MSGSETCORE>
<VER>1</VER>
<URL>https://ofx.example.com</URL>
<OFXSEC>NONE</OFXSEC>
<TRANSPSEC>Y</TRANSPSEC>
<SIGNONREALM>Example Bank</SIGNONREALM>
<LANGUAGE>ENG</LANGUAGE>
</MSGSETCORE>
</BANKMSGSETV1>
</BANKMSGSET>
</MSGSETLIST>
<SIGNONINFOLIST>
<SIGNONINFO>
<SIGNONREALM>Example Bank</SIGNONREALM>
<MIN>8</MIN>
<MAX>32</MAX>
<CHARTYPE>ALPHAORNUMERIC</CHARTYPE>
<CASESEN>N</CASESEN>
<SPECIAL>Y</SPECIAL>
<SPACES>N</SPACES>
<PINCH>N</PINCH>
</SIGNONINFO>
</SIGNONINFOLIST>
<DTPROFUP>20260101120000</DTPROFUP>
<FINAME>Example Bank</FINAME>
<ADDR1>123 Main Street</ADDR1>
<CITY>Toronto</CITY>
<STATE>ON</STATE>
<POSTALCODE>M5H 2N2</POSTALCODE>
<COUNTRY>CAN</COUNTRY>
<CSPHONE>1-800-555-1234</CSPHONE>
</PROFRS>
</PROFMSGSRSV1>
</OFX>
XML;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertInstanceOf(Profile::class, $ofx->profile);
        $this->assertEquals('Example Bank', $ofx->profile->fiName);
        $this->assertEquals('Toronto', $ofx->profile->city);
        $this->assertIsArray($ofx->profile->messageSets);
        $this->assertCount(2, $ofx->profile->messageSets);
    }

    /**
     * Test OFX without profile returns null
     */
    public function testOfxWithoutProfileReturnsNull(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<DTSERVER>20260117120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456
<ACCTID>987654321
<ACCTTYPE>CHECKING
</BANKACCTFROM>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertNull($ofx->profile);
    }

    /**
     * Helper to find a message set by type
     */
    private function findMessageSet(array $messageSets, string $type): ?MessageSetInfo
    {
        foreach ($messageSets as $msgSet) {
            if ($msgSet->type === $type) {
                return $msgSet;
            }
        }
        return null;
    }
}
