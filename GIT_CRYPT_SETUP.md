# Git-Crypt Setup Guide

This repository uses **git-crypt** to encrypt sensitive fixture documentation.

## Installation

### Windows (via Chocolatey)
```powershell
choco install git-crypt
```

### Windows (via Scoop)
```powershell
scoop install git-crypt
```

### Windows (Manual via WSL or Git Bash)
If package managers aren't available, consider using WSL 2:
```bash
sudo apt-get install git-crypt
```

### macOS
```bash
brew install git-crypt
```

### Linux
```bash
sudo apt-get install git-crypt
```

## Initial Setup (Repository Owner)

1. **Install git-crypt** (see above)

2. **Initialize git-crypt in the repository:**
   ```bash
   cd ksf_ofxparser
   git-crypt init
   ```
   This creates `.git/git-crypt/keys/default` (do NOT commit this)

3. **Generate a GPG key pair** (if you don't have one):
   ```bash
   gpg --gen-key
   ```
   Choose RSA with 4096 bits, set expiration to 2+ years

4. **Add team members' GPG keys to git-crypt:**
   ```bash
   # Get their public key
   gpg --import teammate_public_key.asc
   
   # Add to git-crypt
   git-crypt add-gpg-user --trusted [THEIR_GPG_KEY_ID]
   ```

5. **Verify encryption is active:**
   ```bash
   git-crypt status
   ```
   Should show `tests/fixtures/FIXTURE_SOURCES.md` as encrypted

6. **Commit the configuration:**
   ```bash
   git add .gitattributes .git-crypt/*
   git commit -m "chore: initialize git-crypt for fixture source documentation"
   ```

## For Team Members (New Developers)

1. **Install git-crypt** (see Installation section above)

2. **Share your GPG public key with repo owner:**
   ```bash
   gpg --export --armor [YOUR_KEY_ID] > my_public_key.asc
   ```

3. **After owner adds your key, unlock the repository:**
   ```bash
   git clone <repo>
   cd ksf_ofxparser
   git-crypt unlock
   ```

4. **Verify you can read the encrypted file:**
   ```bash
   cat tests/fixtures/FIXTURE_SOURCES.md
   ```
   Should display readable markdown (not binary)

## Working with Encrypted Files

### Checking Status
```bash
git-crypt status
```
Shows which files are encrypted (binary state) vs unencrypted (readable state)

### Sharing Keys
To share the repository key with a new teammate:
1. Add their GPG key to git-crypt
2. Commit the changes
3. They run `git-crypt unlock` once

### Re-encrypting (if key compromised)
```bash
git-crypt export-key /path/to/new/keyfile
git-crypt lock
# Remove old keys, distribute new ones
git-crypt unlock /path/to/new/keyfile
```

## Important Notes

- **Never commit `.git/git-crypt/keys/default`** - Add to `.gitignore` if not already excluded
- **Back up your GPG keys** in a secure location
- **FIXTURE_SOURCES.md** remains readable locally but appears as binary in diffs once locked
- Team members need GPG keys set up before they can clone/pull

## Troubleshooting

### "git-crypt: command not found"
Install git-crypt using package manager (see Installation section)

### File stays unencrypted after commit
Ensure `.gitattributes` is committed:
```bash
git add .gitattributes
git commit -m "Add gitattributes for encryption"
```

### Can't unlock after clone
```bash
# Ensure your GPG key is imported
gpg --import your_secret_key.asc
git-crypt unlock
```

### "No such file or directory: .git/git-crypt/keys/default"
Repository hasn't been initialized yet. As owner, run:
```bash
git-crypt init
```

## References
- [git-crypt GitHub](https://github.com/AGWA/git-crypt)
- [Git Attributes Documentation](https://git-scm.com/book/en/v2/Customizing-Git-Git-Attributes)
