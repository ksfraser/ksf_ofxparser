# CURRENCY Element SRP Refactoring

## Problem Statement

The CURRENCY element in OFX can appear in two formats:
- **Simple value**: `<CURRENCY>USD`
- **Container format**: `<CURRENCY><CURSYM>USD</CURSYM><CURRATE>1.25</CURRATE></CURRENCY>`

Initial implementation attempted to use UnknownElement for this dual behavior, which violated the Single Responsibility Principle (SRP). UnknownElement is intended for truly unknown/future OFX elements, not for known elements with context-dependent behavior.

## Solution

Created a dedicated `CurrencyElement` class following SRP - a single class with the dedicated responsibility of handling the CURRENCY element's known dual-format behavior.

## Changes

### 1. New CurrencyElement Class

**File**: `src/Ksfraser/Sgml/Elements/CurrencyElement.php`

```php
class CurrencyElement extends Element
{
    public function canHaveChildren(): bool
    {
        return true; // Supports both simple and container formats
    }
    
    public function getCurrencyCode(): ?string
    {
        // Simple format: <CURRENCY>USD
        if ($this->textValue !== null) {
            return $this->textValue;
        }
        
        // Container format: <CURRENCY><CURSYM>USD</CURSYM>...
        $cursym = $this->findChild('CURSYM');
        return $cursym ? $cursym->getValue() : null;
    }
    
    public function getExchangeRate(): ?float
    {
        // Only available in container format
        $currate = $this->findChild('CURRATE');
        return $currate ? (float)$currate->getValue() : null;
    }
    
    public function getValue(): ?string
    {
        return $this->getCurrencyCode();
    }
}
```

**Responsibilities**:
- Handle both OFX CURRENCY formats
- Extract currency code from either format
- Extract exchange rate when in container format
- Provide consistent getValue() interface

### 2. ElementFactory Enhancement

**File**: `src/Ksfraser/Sgml/ElementFactory.php`

Added special case in `createElement()`:

```php
public function createElement(string $tagName, int $line = 0, int $column = 0): Element
{
    $tagUpper = strtoupper($tagName);
    
    // Special handling for CURRENCY (hybrid element)
    if ($tagUpper === 'CURRENCY') {
        return new CurrencyElement($tagUpper, $line, $column);
    }
    
    // ... rest of factory logic
}
```

### 3. Parser Fix for Hybrid Elements

**File**: `src/Ksfraser/Sgml/Parser.php`

Enhanced `parseChildren()` to handle elements that can contain either text OR children:

```php
private function parseChildren(Element $parent): void
{
    // Check if next token is text (for hybrid elements like CURRENCY)
    $firstToken = $this->tokenizer->peekToken();
    if ($firstToken->isText()) {
        // This element has text content, not children
        $this->tokenizer->nextToken();
        $parent->setTextValue($firstToken->value);
        
        // Skip to closing tag
        $closeToken = $this->tokenizer->peekToken();
        if ($closeToken->isCloseTag() && $closeToken->value === $parent->getTagName()) {
            $this->tokenizer->nextToken();
        }
        return;
    }
    
    // ... continue with child element parsing
}
```

**Critical Fix**: Parser now checks first token type before processing children. This enables hybrid elements to have either text content or child elements, not both.

### 4. Test Updates

**File**: `tests/OfxParser/Sgml/ElementFactoryTest.php`

Updated test from `testCurrencyIsUnknownElement` to `testCurrencyIsCurrencyElement`:

```php
public function testCurrencyIsCurrencyElement(): void
{
    $currency = $this->factory->createElement('CURRENCY');
    $this->assertInstanceOf(Elements\CurrencyElement::class, $currency);
}
```

### 5. Payee Address Logic Refinement

**File**: `src/Ksfraser/Builders/SgmlOfxBuilder.php`

Refined `buildPayee()` address handling to match OFX spec behavior:

```php
if ($addr1Element && $addr2Element && $addr3Element) {
    // All three present - preserve positions even if empty
    $payee->address = [
        $this->getValue($payeeElement, 'ADDR1', ''),
        $this->getValue($payeeElement, 'ADDR2', ''),
        $this->getValue($payeeElement, 'ADDR3', '')
    ];
} elseif ($addr1Element || $addr2Element || $addr3Element) {
    // Only some present - compact array with non-empty values
    $address = [];
    if ($addr1Element && ($val = $this->getValue($payeeElement, 'ADDR1', '')) !== '') {
        $address[] = $val;
    }
    // ... repeat for ADDR2, ADDR3
    $payee->address = !empty($address) ? $address : null;
}
```

**Logic**:
- If all 3 address tags present → return array[3] preserving positions
- If only some tags present → return compact array with non-empty values
- If no address tags → return null

## Benefits

### 1. SRP Compliance
- CurrencyElement has single responsibility: handle CURRENCY element behavior
- UnknownElement reserved for truly unknown elements
- Clear separation of concerns

### 2. Type Safety
- Explicit CurrencyElement type provides better IDE support
- Clear method contracts: `getCurrencyCode()`, `getExchangeRate()`
- Type hints enable better static analysis

### 3. Maintainability
- Future CURRENCY format changes contained in one class
- Clear documentation of CURRENCY element behavior
- Easy to test and verify

### 4. Extensibility
- Pattern established for other hybrid elements if needed
- Factory pattern makes it easy to add special cases
- Parser supports hybrid elements generically

## Test Results

**Before**:
- 456 tests, 2 failures
- testSecurityWithPriceInformation: CURRENCY returned null
- testBuildPayeeWithEmptyAddressLines: address returned null

**After**:
- 456 tests, **0 failures**, 1582 assertions
- All CURRENCY parsing works correctly
- Payee address handling matches OFX spec
- 5 pre-existing risky tests (deprecation warnings)

## Architecture Principles Applied

1. **Single Responsibility Principle**: Each element type has dedicated class
2. **Open/Closed Principle**: ElementFactory extensible via special cases
3. **Dependency Inversion**: Builder depends on Element abstraction
4. **Factory Pattern**: ElementFactory creates appropriate Element subclass
5. **Separation of Concerns**: Parsing, element structure, and building separated

## Future Considerations

- Monitor for other OFX hybrid elements requiring similar treatment
- Consider abstract HybridElement base class if pattern repeats
- Document hybrid element parsing behavior in developer guide

## Conclusion

The refactoring successfully addressed the SRP violation while fixing the CURRENCY parsing bug. The solution is clean, maintainable, and follows established OOP principles. All tests pass, confirming correct behavior for both simple and container CURRENCY formats.
