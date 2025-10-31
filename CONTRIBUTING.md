# Contributing to ESC/POS Printer Server

First off — thank you for considering contributing to **ESC/POS Printer Server**!
Your help makes this project better for the community and ensures that point-of-sale systems remain open, reliable, and cross-platform.

This document outlines the process for contributing code, reporting issues, improving documentation, and submitting feature requests.

---

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [How to Contribute](#how-to-contribute)
3. [Reporting Bugs](#reporting-bugs)
4. [Requesting Features](#requesting-features)
5. [Development Setup](#development-setup)
6. [Code Style & Standards](#code-style--standards)
7. [Testing](#testing)
8. [Pull Request Guidelines](#pull-request-guidelines)
9. [Release & Build Process](#release--build-process)
10. [Community](#community)

---

## Code of Conduct

This project follows the [Contributor Covenant](https://www.contributor-covenant.org/). All contributors are expected to adhere to respectful, inclusive communication and behavior.

By participating, you agree to uphold this code of conduct.

---

## How to Contribute

There are several ways you can contribute:

* Report a bug or suggest improvements.
* Fix a bug and submit a pull request.
* Add new printer interfaces or templates.
* Improve documentation, examples, or comments.
* Enhance the WebSocket or HTTP server logic.
* Create integrations for new POS front-ends.

If you’re new to open source, you can start by checking the [`good first issue`](https://github.com/darkterminal/escpos-printer-server/labels/good%20first%20issue) label.

---

## Reporting Bugs

Before submitting a new bug report:

1. **Search existing issues** to see if the problem has already been reported.
2. If not found, open a new issue with the following details:

   * A clear **title** describing the problem.
   * Steps to **reproduce** the issue.
   * Expected vs. actual behavior.
   * System details: OS, PHP version, and interface used (CUPS, Ethernet, etc.).
   * Relevant log output or screenshots.

Example issue title:

```
[Bug] Printing over SMB fails on Windows Server 2022
```

---

## Requesting Features

Feature requests are welcome!
Please include:

* A short description of the feature.
* Why it’s valuable (e.g., improves compatibility or UX).
* Any example usage or code reference if available.

Example:

```
[Feature] Add WebSocket authentication using API tokens
```

---

## Development Setup

### Prerequisites

- PHP >= 8.1
    - Extension should enabled:
        - sockets
        - intl
        - openssl
- Composer
- Workerman (auto-installed via Composer)
- ESC/POS-PHP library (mike42/escpos-php)
- Box (for PHAR builds)

### Clone and Install

```bash
git clone https://github.com/darkterminal/escpos-printer-server.git
cd escpos-printer-server
composer install
```

### Running the Server


**Windows**

Windows doesn't support multi-worker.

```bash
php eps --role ws # terminal 1
php eps --role http # terminal 2
```

**Linux/MacOS**
```bash
php eps
```

Access the Web GUI:

```
http://localhost:1100
```

Logs:

```
logs/workerman.log
```

---

## Code Style & Standards

This project follows **PSR-12** coding standards.

Please ensure your code adheres to:

* Strict typing (`declare(strict_types=1);`)
* Meaningful class and method names
* Proper namespace organization (`Darkterminal\EscposPrinterServer`)
* Consistent indentation and spacing (4 spaces)
* Docblocks for all public methods

Run the linter before committing:

```bash
composer run lint
```

---

## Testing

Tests are written using [PestPHP](https://pestphp.com/) for simplicity and readability.

Run all tests:

```bash
composer test
```

If you add or modify features, include corresponding tests in the `tests/` directory.

---

## Pull Request Guidelines

1. **Fork** the repository and create your branch:

   ```bash
   git checkout -b feature/add-new-interface
   ```
2. **Commit clearly and logically**:

   ```bash
   git commit -m "feat: add SMB printer support for Linux hosts"
   ```
3. Run tests and ensure your code builds successfully:

   ```bash
   composer test
   ```
4. Submit a pull request to the `main` branch.
5. Describe the changes and reference any related issues (`#issue-number`).

Your PR will be reviewed for:

* Code clarity and maintainability
* Backward compatibility
* Proper error handling and logging
* Documentation updates

---

## Release & Build Process

Releases are automatically handled via **GitHub Actions**:

* On each tagged release, the workflow:

  * Builds the PHAR archive (`eps.phar`)
  * Packages the Windows bundle with PHP runtime and NSSM
  * Uploads both ZIP files to GitHub Releases

You can also build locally:

```bash
composer run create:phar
```

Windows bundle:

```bash
composer run create:bundle
```

---

## Community

Join the discussion or follow updates:

* GitHub: [@darkterminal](https://github.com/darkterminal)
* Discussions: [GitHub Discussions](https://github.com/darkterminal/escpos-printer-server/discussions)
* Issue tracker: [Issues Page](https://github.com/darkterminal/escpos-printer-server/issues)

We appreciate your contribution — even small fixes make a big difference.

---

**Thank you for helping improve ESC/POS Printer Server!**
Together we can make cross-platform receipt printing simpler, faster, and more open.
