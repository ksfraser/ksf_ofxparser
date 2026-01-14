<?php declare(strict_types=1);

namespace OfxParser\Entities;

use SimpleXMLElement;

use OfxParser\Entities\LoaderTrait;

abstract class Investment extends AbstractEntity implements Inspectable, OfxLoadable
{
    /**
     * Make loadMap() available to all Invesment entities.
     * @trait
     */
    use LoaderTrait;

    /**
     * Get a list of properties defined for this entity.
     *
     * Since Traits are being used for multiple inheritance,
     * it can be challenging to know which properties exist
     * in the entity. 
     *
     * @return array array('prop_name' => 'prop_name', ...)
     */
    public function getProperties(): array
    {
        $props = array_keys(get_object_vars($this));

        return array_combine($props, $props);
    }

    /**
     * All Investment entities require a loadOfx method.
     * @param SimpleXMLElement $node
     * @return $this For chaining
     * @throws \Exception
     */
    public function loadOfx(SimpleXMLElement $node): self
    {
        throw new \Exception('loadOfx method not defined in class "' . get_class($this) . '"');
    }
   /**
     * Populates instance properties from a node for the map provided.
     * 
     * from gitmathias repo
     * 
     * @param array $map array(property_name => node_name, ...)
     * @param SimpleXMLElement $node
     * @return $this
     */
    public function loadMap(array $map, SimpleXMLElement $node): self
    {
        foreach ($map as $propName => $nodeName) {
            if (@count($node->{$nodeName}) > 0) {
                $this->{$propName} = (string) $node->{$nodeName};
            }
        }
        return $this;
    }
}

