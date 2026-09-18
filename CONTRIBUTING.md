# Contributing

This repository contains sanitized applications derived from a private homelab environment.

## Requirements

Contributions must not introduce:

- credentials, tokens or private keys;
- production environment files;
- private network addresses;
- internal production paths;
- databases, logs or runtime state;
- personal or multimedia content.

## Validation

Before committing PHP changes, validate the repository with PHP 8.2.

The GitHub Actions workflow performs the same syntax validation automatically on pushes and pull requests targeting `main`.

## Commits

Use focused commits with concise messages that describe one logical change.

Examples:

- `fix: handle unavailable projection safely`
- `docs: clarify local configuration`
- `ci: update syntax validation workflow`

## Security

Potential security issues should follow the process documented in `SECURITY.md`.
