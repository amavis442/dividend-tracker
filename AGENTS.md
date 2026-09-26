> This project uses `.ai/rules` for coding conventions, architecture, and tooling, and `.ai/skills/` for task-specific workflows. Read both before starting any work.

# Dividend — Portfolio Tracker

A portfolio tracking application that monitors instruments, positions, and dividend payments.

## Features

- Track instruments (stocks, ETFs) and positions across multiple portfolios
- Monitor dividend payments, forecasts, and yields
- Import/export transactions (CSV, Trading212)
- Currency conversion via exchange rate APIs
- Price data from financial data providers (Financial Modeling Prep, MASSIVE API, IncomesShares)
- Visualisation via ChartJS (dividend trends, yield charts, pie allocations)
- Multi-tenant: user-scoped portfolios, pies, positions, and transactions
- Dutch and English locale support

## Data sources

- **[Trading212 API](https://docs.trading212.com/api)** — instruments, positions, orders, payments, dividend data. Uses the simple API token authentication (single token via `%env(API_KEY)%`, not the `api_user:api_secret` two-token pattern).
- **Exchange rates** — via configured provider in `config/services.yaml`
- **Financial Modeling Prep API** — stock price lookups
- **MASSIVE API** — alternative price data
- **IncomesShares** — dividend yield data

## Tech stack

- **Symfony 8.1** (PHP framework) — upgrade from 6.4 in progress
- **PHP 8.4+** — strict typed mode (`declare(strict_types=1)`, typed properties, native return types, `#[...]` attributes)
- **Doctrine ORM 4.0** — entities, repositories, lifecycle callbacks
- **API Platform 4.3** — REST exposure with serialization groups and security
- **Twig 3.0** — server-side templates
- **Tailwind CSS** — styling (via `symfonycasts/tailwind-bundle`)
- **UX bundles** — ChartJS, Autocomplete, Turbo, Dropzone, Twig Components
- **PostgreSQL** — database (via Docker Compose)

## Testing & quality

- **PHPUnit 12** — unit and integration tests (`tests/`)
- **Behat** — feature tests (`tests/Features/`)
- **PHPSpec 8** — spec-style testing
- **PHPStan** — static analysis (level 5)
- **Rector** — automated refactoring (annotations-to-attributes migration)
- **phpcs/phpcbf** — PSR coding standard checks

## Project structure

```
src/
  Controller/       ← HTTP routing & rendering
  Form/             ← Input validation & construction
  Entity/           ← Domain models (Doctrine ORM)
  Repository/       ← Data access layer
  Contracts/        ← Interfaces (Service/, etc.)
  Helper/           ← Pure utility functions
  Serializer/       ← API Platform context builders
  State/            ← Post-processors
  Autocompleter/    ← UX autocomplete providers
  EventListener/    ← Kernel event listeners
  Doctrine/         ← Custom Doctrine types
migrations/         ← Doctrine migration versions
config/             ← Symfony YAML config
translations/       ← Locale files (nl, de, fr, en)
tests/              ← PHPUnit, Behat, PHPSpec
public/             ← Web root (assets, uploads)
```

## Quick commands

```bash
composer phpstan         # static analysis (level 5)
composer cscheck         # coding standards check
composer csfix           # auto-fix coding standards
composer phpunit         # run tests with coverage
vendor/bin/rector process  # run Rector refactoring
```

## Team & workflow

This project uses **9 personas**, each with a strict role. Work flows through them in a defined order.

```
Morgan (PO) ──defines── Alex/Taylor/Dana ──implements── Sam ──tests── Jordan ──reviews
                                                                   │
                                                                   └── follow-up items (out-of-scope findings)
Casey ──audits── (security, dependencies)
Riley ──manages── (infra, CI/CD, DDEV)
Quinn ──designs── (before implementation, wireframes → Taylor)
```

### Standard order

1. **Morgan** (PO) writes a work item with acceptance criteria on the GitLab board
2. **Quinn** (UX) designs wireframes/flows — only when a new UI component is involved
3. **Alex/Taylor/Dana** picks up the work item: view → set status Doing → implement
4. **Sam** (Tester) writes tests — BDD/Gherkin for features, PHPUnit for units
5. **Jordan** (Reviewer) reviews the code — out-of-scope findings become separate work items
6. **Casey** (Security) audits — periodically or on sensitive changes (auth, data, API keys)
7. **Riley** (DevOps) manages CI/CD and infra — involved when DDEV/Docker/CI changes

### Roles at a glance

| Person | Does | Does NOT |
|---|---|---|
| **Morgan** | Work items, epics, priorities, acceptance criteria | Code, implementation |
| **Alex** | PHP backend, Symfony, Doctrine, API Platform | Tests, frontend, review |
| **Taylor** | JS, Stimulus, Turbo, Tailwind, importmap | PHP backend, database |
| **Dana** | DB schema, migrations, indexes, Doctrine advice | App code, frontend |
| **Sam** | BDD/Gherkin, Behat, PHPUnit tests | Production code, review |
| **Jordan** | Code review, follow-up items | Writing code |
| **Casey** | Security audit, reporting | Code fixes |
| **Riley** | DDEV, Docker, GitLab CI, secrets | App code |
| **Quinn** | Wireframes, flows, WCAG, design tokens | Code |

### Warning

This file is read by Hermes Agent. Do not deviate from the team flow without explicitly stating so.