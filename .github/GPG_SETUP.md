# 🔐 Setting up GPG Commit Signing for GitHub Actions

## Overview

This guide will help you configure automatic GPG commit signing in GitHub Actions workflow for automated code fixes. This ensures verification of all automated commits made by actions like Super Linter.

## Step 1: Generate GPG Key

On your local machine:

```
bash gpg --full-generate-key
```

During generation, choose:

- Key type: RSA and RSA
- Key size: 4096 bits
- Expiration: (key doesn't expire) or set your own expiration 0
- Enter name and email (recommended to use your GitHub email)
- Set a strong passphrase

Export private key:

```
# Find your key ID
gpg --list-secret-keys --keyid-format=long
# Export private key (replace YOUR_KEY_ID with actual ID)
gpg --armor --export-secret-keys YOUR_KEY_ID
```

Copy the entire output  
(including `-----BEGIN PGP PRIVATE KEY BLOCK-----` and `-----END PGP PRIVATE KEY BLOCK-----`)

## Step 2: Add Secrets to Repository

In your repository:

1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Add two secrets:
   - **Name:** `GPG_PRIVATE_KEY`  
     **Value:** [Private key from Step 1]
   - **Name:** `GPG_PASSPHRASE`  
     **Value:** [Passphrase for your GPG key]

## Step 3: Configure GitHub Actions Workflow

Add GPG import and signing configuration to your workflow:

```yaml
- name: Import GPG key
  id: import-gpg
  uses: crazy-max/ghaction-import-gpg@v6
  with:
    gpg_private_key: ${{ secrets.GPG_PRIVATE_KEY }}
    passphrase: ${{ secrets.GPG_PASSPHRASE }}
    git_user_signingkey: true
    git_commit_gpgsign: true

- name: Commit and push changes
  uses: stefanzweifel/git-auto-commit-action@v5
  with:
    commit_author: '${{ steps.import-gpg.outputs.name }} <${{ steps.import-gpg.outputs.email }}>'
    commit_user_name: ${{ steps.import-gpg.outputs.name }}
    commit_user_email: ${{ steps.import-gpg.outputs.email }}
    commit_options: '--signoff --gpg-sign'
```

## Verification

After setup:

- Create a Pull Request with code changes
- Automated tools (like Super Linter) will make corrections
- The commit will have a "Verified" badge ✅ on GitHub

## Security

⚠️ **Important:**

- Never share your private GPG key
- Use a strong passphrase
- Rotate keys regularly (recommended annually)
- GitHub secrets have restricted access only for repository maintainers
- GPG keys are stored in repository secrets, not in GitHub profile (for organization compatibility)

## References

- [stefanzweifel/git-auto-commit-action](https://github.com/stefanzweifel/git-auto-commit-action)

This documentation allows each contributor to independently set up GPG signing in their repository fork while maintaining security best practices for GitHub organizations.
