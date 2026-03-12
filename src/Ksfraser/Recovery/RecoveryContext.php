<?php declare(strict_types=1);

namespace OfxParser\Recovery;

use OfxParser\Config\DefensiveParsingConfig;

/**
 * Orchestrates recovery strategies - delegates to appropriate strategy
 */
class RecoveryContext
{
    /** @var DefensiveParsingConfig */
    private DefensiveParsingConfig $config;
    
    public function __construct(DefensiveParsingConfig $config)
    {
        $this->config = $config;
    }
    
    /**
     * Check if we can recover from this exception
     */
    public function canRecover(\Exception $exception): bool
    {
        if ($this->config->isStrictMode()) {
            return false;
        }
        
        $strategy = $this->config->getStrategyForException($exception);
        return $strategy !== null && $strategy->canHandle($exception);
    }
    
    /**
     * Recover from exception using configured strategy
     *
     * @param \Exception $exception
     * @param mixed $context Additional context (SimpleXMLElement, field name, etc.)
     * @param mixed $additionalData Additional data for recovery
     * @return mixed
     */
    public function recover(\Exception $exception, $context, $additionalData = null)
    {
        $strategy = $this->config->getStrategyForException($exception);
        
        if ($strategy === null) {
            throw $exception;
        }
        
        if (!$strategy->canHandle($exception)) {
            throw $exception;
        }
        
        $recoveryContext = [
            'exception' => $exception,
            'context' => $context,
            'additional_data' => $additionalData
        ];
        
        return $strategy->recover($exception, $recoveryContext);
    }
    
    /**
     * Get configuration
     */
    public function getConfig(): DefensiveParsingConfig
    {
        return $this->config;
    }
}
