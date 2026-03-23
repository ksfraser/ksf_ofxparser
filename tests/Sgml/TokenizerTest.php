<?php

namespace Tests\Sgml;

use PHPUnit\Framework\TestCase;
use OfxParser\Sgml\Tokenizer;
use OfxParser\Sgml\Token;

class TokenizerTest extends TestCase
{
    public function testTokenizesOpeningTag()
    {
        $tokenizer = new Tokenizer('<TAGNAME>');
        $token = $tokenizer->nextToken();
        
        $this->assertTrue($token->isOpenTag());
        $this->assertEquals('TAGNAME', $token->value);
    }

    public function testTokenizesClosingTag()
    {
        $tokenizer = new Tokenizer('</TAGNAME>');
        $token = $tokenizer->nextToken();
        
        $this->assertTrue($token->isCloseTag());
        $this->assertEquals('TAGNAME', $token->value);
    }

    public function testTokenizesTextContent()
    {
        $tokenizer = new Tokenizer('<TAG>some text</TAG>');
        
        $openTag = $tokenizer->nextToken();
        $this->assertTrue($openTag->isOpenTag());
        
        $text = $tokenizer->nextToken();
        $this->assertTrue($text->isText());
        $this->assertEquals('some text', $text->value);
        
        $closeTag = $tokenizer->nextToken();
        $this->assertTrue($closeTag->isCloseTag());
    }

    public function testHandlesUnclosedTag()
    {
        $tokenizer = new Tokenizer('<DTSERVER>20151209<LANGUAGE>POR');
        
        $tag1 = $tokenizer->nextToken();
        $this->assertEquals('DTSERVER', $tag1->value);
        
        $text1 = $tokenizer->nextToken();
        $this->assertEquals('20151209', $text1->value);
        
        $tag2 = $tokenizer->nextToken();
        $this->assertEquals('LANGUAGE', $tag2->value);
        
        $text2 = $tokenizer->nextToken();
        $this->assertEquals('POR', $text2->value);
    }

    public function testSkipsWhitespaceBetweenTags()
    {
        $tokenizer = new Tokenizer("<TAG1>  \n  <TAG2>");
        
        $tag1 = $tokenizer->nextToken();
        $this->assertEquals('TAG1', $tag1->value);
        
        $tag2 = $tokenizer->nextToken();
        $this->assertEquals('TAG2', $tag2->value);
    }
}
