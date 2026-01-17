# How the OFX Parser Works

## What is OFX?

OFX (Open Financial Exchange) is a way for banks and financial institutions to share your account information with software like Quicken or Mint. Think of it like a special language that banks use to tell your computer about your transactions, balances, and account details.

OFX comes in **two flavors**:
1. **SGML format** - The older style (looks like `<TAG>value` without closing tags)
2. **XML format** - The newer style (looks like `<TAG>value</TAG>` with closing tags)

Both formats contain the same information, just written differently - like writing a letter in cursive vs printing.

## The Big Picture: How Parsing Works

Imagine you receive a big pile of papers from your bank. This parser's job is to:

1. **Read** the papers (the OFX file)
2. **Organize** the information into neat folders and labels (element tree)
3. **Create** easy-to-use objects (entities) that your program can work with

```
OFX File → Parser → Element Tree → Builder → Easy-to-Use Objects
```

## The Two Paths: SGML vs XML

When the parser receives an OFX file, it first checks which format it is by reading the header (the first few lines).

### Path 1: SGML (Native Parsing)

**Step 1: Tokenizing (Breaking into Pieces)**

The tokenizer is like someone reading the file character by character and grouping them into meaningful chunks called "tokens":

```
Input: <NAME>John Smith<CITY>Seattle

Tokens created:
  Token 1: Open tag "NAME"
  Token 2: Text "John Smith"
  Token 3: Open tag "CITY"
  Token 4: Text "Seattle"
```

The tokenizer hands these tokens to the parser one at a time, like dealing cards.

**Step 2: Building the Element Tree**

The parser takes those tokens and builds a tree structure - like a family tree, but for data:

```
OFX (root)
├── SIGNONMSGSRSV1 (sign-on information)
│   └── SONRS
│       ├── STATUS
│       │   ├── CODE: "0"
│       │   └── SEVERITY: "INFO"
│       └── DTSERVER: "20260117120000"
└── BANKMSGSRSV1 (banking messages)
    └── STMTTRNRS (statement)
        └── STMTRS
            ├── CURDEF: "USD"
            └── BANKTRANLIST (transaction list)
                └── STMTTRN (one transaction)
                    ├── TRNTYPE: "DEBIT"
                    ├── DTPOSTED: "20260105"
                    ├── TRNAMT: "-100.00"
                    └── NAME: "Coffee Shop"
```

**How the Tree is Built:**

Think of the parser as having a stack of boxes (like a tower). When it sees an open tag like `<STMTTRN>`, it:
1. Creates a new box labeled "STMTTRN"
2. Puts that box on top of the tower
3. Any tags it sees next go *inside* that box
4. When it detects the box is complete (sees the next tag at the same level), it takes the box off the tower and puts it in its parent box

**Step 3: Converting to Entities**

The SgmlOfxBuilder walks through this tree and creates real objects your program can use:

```
Element Tree         →    Entities
─────────────────────    ─────────────────
OFX Element          →    Ofx object
├─ BANKMSGSRSV1      →    bankAccounts array
   └─ STMTRS         →      BankAccount object
      └─ BANKTRANLIST →        Statement object
         └─ STMTTRN  →           Transaction object
                              - date: DateTime
                              - amount: 100.00
                              - name: "Coffee Shop"
```

### Path 2: XML (Using SimpleXML)

For XML files, we use PHP's built-in XML parser:

**Step 1: Parse with SimpleXML**

PHP has a built-in tool called SimpleXML that already knows how to read XML. We use it to parse the XML file quickly into a tree structure.

**Step 2: Walk the XML Tree**

An XML builder walks through the SimpleXML tree structure and directly creates the entity objects (BankAccount, Statement, Transaction, etc.) - no conversion to SGML needed!

**Step 3: Return Entities**

Both XML and SGML paths create the exact same entity objects - just from different source formats.

```
XML File → SimpleXML Tree → XML Builder → Entities (BankAccount, Statement, etc.)
```

**Note:** Both paths end up creating the same entity classes - the difference is just how we read and organize the OFX data initially.

## Data Representation: The Element Classes

Every piece of data in the tree is an "Element" - think of it as a container that holds information. There are different types:

### 1. ValueElement (Simple Values)

Holds one piece of text:
- Example: `<TRNAMT>-100.00` becomes a ValueElement with value "-100.00"
- Like a single-item box

### 2. ContainerElement (Parent with Children)

Holds other elements inside it:
- Example: `<PAYEE>` contains `<NAME>`, `<ADDR1>`, `<CITY>`, etc.
- Like a box with smaller boxes inside

### 3. CurrencyElement (Special Hybrid)

Can be either a value OR a container:
- Simple: `<CURRENCY>USD` (just text)
- Container: `<CURRENCY><CURSYM>USD</CURSYM><CURRATE>1.18</CURRATE></CURRENCY>`
- Like a box that can hold either a single item or multiple items

### 4. UnknownElement (Future-Proof)

When the parser sees a tag it doesn't recognize (maybe from a newer version of OFX):
- Creates an UnknownElement that can adapt
- Like a flexible container that figures out what it holds as it goes

## Iteration: How the Parser Moves Through Data

The parser uses two main patterns to move through data:

### Pattern 1: Token Stream (Tokenizer)

Like reading a book word by word:
1. Peek at next token (look ahead without moving)
2. If it's what we want, consume it (move to next)
3. Repeat until done

```
Flow:
Start → Peek token → Is it open tag? → Yes → Create element
                   ↓                         ↓
                   No                    Peek next → Is it text? → Yes → Store text
                   ↓                                ↓
              Skip/Handle                          No → Parse children
```

### Pattern 2: Tree Walking (Builder)

Like exploring a building floor by floor:
1. Start at root (OFX element)
2. Look for specific children we need (SIGNONMSGSRSV1, BANKMSGSRSV1, etc.)
3. Go into each child and look for its children
4. Extract values and create objects
5. Move back up and continue

```
Visit OFX root
  → Find SIGNONMSGSRSV1 child
    → Find SONRS child
      → Find STATUS child
        → Extract CODE and SEVERITY
      ← Back up to SONRS
      → Find DTSERVER
    ← Back up to SIGNONMSGSV1
  ← Back up to OFX root
  → Find BANKMSGSRSV1 child
    → ... continue ...
```

## What Gets Returned

The parser returns different things depending on what type of OFX file you have:

### For Banking/Credit Card Files:
Returns an **Ofx** object containing:
- `signOn` - Sign-on information (bank name, date/time, status)
- `bankAccounts` - Array of BankAccount objects, each with:
  - Account number, routing number, type
  - Balance and balance date
  - `statement` - Statement object with:
    - Start date and end date
    - Currency (USD, EUR, etc.)
    - `transactions` - Array of Transaction objects with:
      - Date, amount, type (debit/credit)
      - Name, memo, check number
      - Payee information if available

### For Investment Files:
Returns an **Investment** object containing:
- Same signOn information
- `bankAccounts` - Array of investment accounts with:
  - Broker ID and account number
  - `statement` with investment transactions:
    - Buy/sell stocks, mutual funds
    - Reinvestments, dividends
    - Cash movements

### For Files with Other Data:
The same Ofx object can also contain:
- `securityList` - List of stocks, bonds, mutual funds
- `loanAccounts` - Mortgage, car loan, line of credit accounts
- `profile` - Bank's service information
- `interXfers` - Transfers between accounts

## Known Gotchas and Special Cases

### 1. SGML Has No Closing Tags

In SGML, the parser has to guess when a tag ends by looking at what comes next:
- If next tag is a sibling or parent → current tag is done
- If next tag could be a child → current tag continues

**Example:**
```
<STMTTRN>
  <TRNTYPE>DEBIT    ← This ends when DTPOSTED starts
  <DTPOSTED>20260105 ← This ends when TRNAMT starts
  <TRNAMT>-100.00    ← This ends when STMTTRN ends (next STMTTRN or parent close)
```

### 2. Hybrid Elements (CURRENCY)

CURRENCY can appear in two formats in the same file:
- Transaction: `<CURRENCY>USD` (simple text)
- Security: `<CURRENCY><CURSYM>USD</CURSYM><CURRATE>1.0</CURRATE></CURRENCY>` (container)

The parser checks if the next token is text or an open tag to decide which format it is.

### 3. Empty vs Missing Tags

- `<ADDR1>` (tag present but empty) vs tag not present at all
- For address lines: only non-empty lines are returned in the array
- For required fields: empty string vs null matters

### 4. Date Format Variations

OFX dates can include:
- Date only: `20260117` (YYYYMMDD)
- Date and time: `20260117120000` (YYYYMMDDHHmmss)
- With timezone: `20260117120000[-5:EST]`
- With milliseconds: `20260117120000.000[-5:EST]`

The parser strips timezone and milliseconds, then parses the remaining digits.

### 5. Case Sensitivity

- All tag names are converted to UPPERCASE
- Values keep their original case
- This is why you see TRNTYPE not TrnType

### 6. Multi-Currency Transactions

When a transaction is in a different currency than the account:
- `CURDEF` in statement = account currency (e.g., EUR)
- `CURRENCY` in transaction = transaction currency + exchange rate (e.g., USD @ 1.18)
- The amount is always in the account currency
- Original amount = amount ÷ exchange rate

### 7. Multiple Account Types

Some OFX files mix different accounts in one file:
- Bank accounts (checking/savings)
- Credit cards
- Investment accounts
- Loans

They all get parsed and returned in the same Ofx object but in different arrays.

### 8. Element Factory Pattern

Not all tags are created equal:
- Some tags can ONLY have a value (TRNTYPE, FITID)
- Some tags can ONLY have children (STMTRS, PAYEE)
- Some tags can have EITHER value or children (CURRENCY)
- Unknown tags are flexible (might be new OFX spec)

The ElementFactory knows which type to create for each tag name.

## Summary: The Complete Flow

```
1. Read OFX file
   ↓
2. Check format (SGML or XML)
   ↓
3a. If SGML:                    3b. If XML:
    Tokenize → Parse → Tree         SimpleXML → XML Tree
   ↓                                 ↓
4. SGML Builder walks tree      4. XML Builder walks tree
   ↓                                 ↓
5. Both create same entities: Ofx, BankAccount, Transaction, etc.
   ↓
6. Returns Ofx object to your program
   ↓
7. Your program uses the objects to display/process data
```

## Why Two Parsers?

You might wonder why Separate Paths?

You might wonder: why not convert everything to XML and use PHP's SimpleXML, or convert XML to SGML?

**Reasons for keeping both paths separate:**

1. **Performance** - Each path is optimized for its format (no conversion overhead)
2. **Accuracy** - Reading the native format directly preserves all information
3. **Error handling** - Format-specific errors are easier to diagnose
4. **Reliability** - SimpleXML is battle-tested for XML; our SGML parser handles SGML quirks
5. **Simplicity** - Each parser focuses on one format without compromising for the other

**The key insight:** Both paths end up creating the same entity objects (BankAccount, Transaction, etc.), so the rest of your program doesn't need to care which format the file was in

If you want to add support for new OFX fields:
1. Add the tag name to ElementFactory (if needed)
2. Update SgmlOfxBuilder to extract the new field
3. Update the entity class to hold the new data
4. Add tests to verify it works

If you find a bank's OFX file that doesn't parse:
1. Look at the tokenizer output - are tokens correct?
2. Look at the element tree - is structure correct?
3. Look at the builder - is it looking for the right tags?
4. Check for unknown tags - might need special handling

## Conclusion

The OFX parser is like a translator that takes bank language (OFX format) and converts it into objects your program can easily work with. It handles two dialects (SGML and XML), builds an organized tree structure, and creates simple objects you can use to display account information, transactions, and more.

The key insight: **Parse once, build cleanly, use everywhere.**
