<?php

namespace OfxParserTest\Entities;

use SimpleXMLElement;
use PHPUnit\Framework\TestCase;
use OfxParser\Entities\LoaderTrait;

/**
 * Need a dummy class to use the LoaderTrait
 */
class LoaderTraitContainer
{
    use LoaderTrait;

    /**
     * @var string
     */
    public $public1;

    /**
     * @var string
     */
    protected $protected1;

    /**
     * @var string
     */
    private $private1;

    /**
     * Testing helper
     */
    public function getProps()
    {
        return [
            'public1' => $this->public1,
            'protected1' => $this->protected1,
            'private1' => $this->private1,
        ];
    }
}


/**
 * @covers OfxParser\Entities\LoaderTrait
 */
class LoaderTraitTest extends TestCase
{
    /**
     * Let's try all the things
     */
    public function testLoadMap()
    {
        $testXML = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <testvals>
              <name>LoaderTrait</name>
              <type>Trait</type>
              <num-prop>1234</num-prop>
            </testvals>
        ');

        $tests = [
            'I can set values for any visibility' => [
                'input' => [
                    'map' => [
                        'public1' => 'name',
                        'protected1' => 'type',
                        'private1' => 'num-prop'
                    ],
                ],
                'expected' => [
                    'properties' => [
                        'public1' => 'LoaderTrait',
                        'protected1' => 'Trait',
                        'private1' => '1234',
                    ],
                ],
            ],
            'I ignore a default value if the node exists' => [
                'input' => [
                    'map' => [
                        'public1' => ['name', 'Traitzzz']
                    ],
                ],
                'expected' => [
                    'properties' => [
                        'public1' => 'LoaderTrait',
                        'protected1' => null,
                        'private1' => null,
                    ],
                ],
            ],
            'I accept the default value when the node does not exist' => [
                'input' => [
                    'map' => [
                        'public1' => ['namezzz', 'Traitzzz']
                    ],
                ],
                'expected' => [
                    'properties' => [
                        'public1' => 'Traitzzz',
                        'protected1' => null,
                        'private1' => null,
                    ],
                ],
            ],
        ];

        foreach ($tests as $testName => $data) {
            $input = $data['input'];
            $expected = $data['expected'];

            $testObj = new LoaderTraitContainer();
            $actual = $testObj->loadMap($input['map'], $testXML)->getProps();

            $this->assertEquals($actual, $expected['properties'], $testName . ' failed!');
        }
    }
}
