#!/usr/bin/env python3
import re

filepath = r'c:\Users\prote\Documents\software-devel\ksf_ofxparser\tests\EdgeCases\DataFormatVariationTest.php'

with open(filepath, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Replace patterns where $ofx = $this->parser->loadFromString($content);
# Keep lines that use $ofxContent (already wrapped)
newlines = []
for line in lines:
    # Only replace lines with ->loadFromString($content) not ->loadFromString($ofxContent)
    if '$this->parser->loadFromString($content)' in line:
        # Replace $this->parser->loadFromString with $this->parseOFX
        line = line.replace('$this->parser->loadFromString($content)', '$this->parseOFX($content)')
    newlines.append(line)

with open(filepath, 'w', encoding='utf-8') as f:
    f.writelines(newlines)

print("Updated DataFormatVariationTest.php successfully")

