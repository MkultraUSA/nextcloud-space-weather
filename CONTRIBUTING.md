# Contributing to Nextcloud Space Weather Dashboard

Thanks for your interest in contributing! This document covers the
contribution workflow and quality standards. For environment setup
and architecture details, see [DEVELOPMENT.md](DEVELOPMENT.md) first.

## Code of Conduct

Be respectful, assume good faith, and keep feedback constructive.
We're here to build a great tool for the amateur radio and space
weather community.

## Quick Start

1. Fork [MkultraUSA/nextcloud-space-weather](https://github.com/MkultraUSA/nextcloud-space-weather)
2. Clone into your Nextcloud `apps/` directory
3. Follow the setup steps in [DEVELOPMENT.md](DEVELOPMENT.md) (PHP 8.0+,
   Nextcloud 27+, `composer install`)
4. Create a feature branch: `git checkout -b feat/my-feature`

There is no JavaScript build step — all frontend code is plain vanilla
JS served directly from `js/`.

## Coding Standards

### PHP — PSR-12

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) strictly
- `declare(strict_types=1);` at the top of every PHP file
- Type-hint all parameters and return values
- Document public methods with PHPDoc blocks
- Wrap external calls in try-catch; log errors via `LoggerInterface`

Validate before submitting:
```bash
./vendor/bin/php-cs-fixer fix --dry-run --diff
./vendor/bin/psalm
```

### JavaScript — CSP-Safe Vanilla JS

The frontend uses **no frameworks** (no Vue, React, jQuery, etc.).
All code must pass Nextcloud's strict Content Security Policy:

- Use `document.createElement()` and `textContent` — **never `innerHTML`**
- Use `addEventListener()` — **no inline event handlers** (`onclick`, etc.)
- **No `eval()` or `new Function()`**; no dynamically constructed scripts
- Never inject raw HTML from API responses — always build DOM nodes
- Keep third-party dependencies to zero — no npm, no bundler, no build step
- Wrap code in IIFEs: `(function () { 'use strict'; ... })()`

### CSS

- Mobile-first, responsive design with `@media (min-width: …)` breakpoints
- Follow the existing naming patterns in `css/style.css`
- No `!important` unless unavoidable

## Running Tests

```bash
./vendor/bin/psalm                        # Static analysis
./vendor/bin/php-cs-fixer fix --dry-run   # PSR-12 compliance
./vendor/bin/phpunit                      # Unit tests (when populated)

# Manual API smoke test
curl http://localhost/apps/space_weather/api/v1/kp-index
```

Test manually across Firefox + Chrome before opening a PR. Confirm the
browser console is clean and all API calls succeed in the Network tab.

## Commit Messages

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <short summary>
```

**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`,
`chore`, `ci`

**Scopes:** `api`, `ui`, `cache`, `satellite`, `kp`, `solar`, `band`,
`css`, `docs`, `config`

Keep the summary under 72 characters. Use an optional body to explain
_why_, not just _what_.

Examples:
```
feat(api): add solar flare detection endpoint
fix(ui): handle missing satellite image gracefully
docs: update README with new data source links
chore(deps): bump guzzle to 7.9
```

## Pull Request Process

1. **Branch naming:** Use `feat/`, `fix/`, `docs/`, or `chore/` prefixes
2. **Keep PRs focused.** One feature or fix per pull request
3. **Update docs** in the same PR if user-facing behavior changes
4. **Clean your diff:** no debug logging (`console.log`, `var_dump`),
   no commented-out code, no secrets or API keys
5. **Open PR against `main`.** Link related issues, describe what you
   changed and how you tested it
6. **CI must pass.** Psalm and PSR-12 checks must be clean
7. A maintainer will review. Expect feedback — address comments and push
   follow-up commits; the PR updates automatically

## License

This project is licensed under the **GNU Affero General Public License
v3.0 or later (AGPL-3.0-or-later)**. By contributing, you agree your
work is distributed under the same license. Third-party code you
include must be AGPL-compatible.

---

Happy hacking, and thanks for helping make space weather data accessible
to everyone! 73 de KW5GP