<?php declare(strict_types=1);

namespace OfxParser\Entities\Investment;

use OfxParser\Entities\AbstractEntity;

/**
 * Security List Container
 * 
 * What: Container for the complete list of securities referenced in an investment
 * statement, delivered via SECLISTMSGSRSV1.
 * 
 * Why: Acts as a "security master file" for the statement period, providing a
 * single source of truth for all security details. This allows:
 * - Efficient lookup of security details by ID
 * - Consistent security information across multiple transactions
 * - Portfolio-level security analysis
 * 
 * The security list is separate from transactions to avoid data duplication
 * (many transactions may reference the same security).
 */
class SecurityList extends AbstractEntity
{
    /**
     * Array of Security objects
     * @var Security[]
     */
    public $securities = [];
    
    /**
     * Add a security to the list
     * 
     * @param Security $security
     * @return void
     */
    public function addSecurity(Security $security): void
    {
        $this->securities[] = $security;
    }
    
    /**
     * Find security by ID
     * 
     * @param string $securityId
     * @return Security|null
     */
    public function findById(string $securityId): ?Security
    {
        foreach ($this->securities as $security) {
            if ($security->securityId === $securityId) {
                return $security;
            }
        }
        return null;
    }
    
    /**
     * Get all securities
     * 
     * @return Security[]
     */
    public function getSecurities(): array
    {
        return $this->securities;
    }
    
    /**
     * Get count of securities in list
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->securities);
    }
}
