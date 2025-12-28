<?php

namespace OfxParser\Entities;

use SimpleXMLElement;

/**
 * Helpers for loading entity values
 */
trait LoaderTrait
{
    /**
     * Populates instance properties from a node for the map provided.
     * If the requested node does not exist, no value will be set.
     *
     * @param array $map array(property_name => node_detail, ...)
     *
     *      If node_detail is a string, the value of that xml node will
     *      be assigned to the instance property in property_name.
     *
     *      If node_detail is an array, the first value will be used
     *      as the xml node name and the second value will be used as
     *      the default value, in case the node does not exist.
     *
     *      Example: $map = [ 'gain' => 'GAIN' ]
     *          Pull the value of the GAIN xml node and assign to $this->gain,
     *          if the GAIN xml node exists.
     *
     *      Example: $map = [ 'fees' => ['FEES', 0]]
     *          Pull the value of the FEES xml node and assign to $this->fees,
     *          if the FEES xml node exists. If the node does not exist, assign
     *          zero to $this->fees.
     *
     * @param SimpleXMLElement $node
     * @return $this
     */
    public function loadMap($map, $node)
    {
        foreach ($map as $propName => $detail) {
            $default = null;
            $defaultProvided = false;

            if (is_array($detail)) {
                $defaultProvided = true;
                $detail = array_values($detail);
                list($nodeName, $default) = $detail;
            } else {
                $nodeName = $detail;
            }

            if (@count($node->{$nodeName}) > 0) {
                $this->{$propName} = (string) $node->{$nodeName};
            } elseif ($defaultProvided) {
                $this->{$propName} = $default;
            }
        }

        return $this;
    }
}
