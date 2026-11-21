# 📈 Dividend Tracker

Track, forecast, and visualize dividend payouts using Symfony 6.4. Supports multi-portfolio strategies like FIRE and Income, with precision tax and exchange-rate calculations baked in.

---

## 🚀 Features

- Projected and historical dividend payouts
- Categorized views by strategy (e.g. FIRE, Income)
- Customizable tax and exchange rate resolution
- Monthly and pie-group reporting
- Exportable reports with Twig templates
- API/CLI ready for automation and integrations

---

## 🧰 Requirements

- PHP 8.2+
- Composer
- Symfony CLI *(optional but recommended)*
- PostgreSQL 14+
- Node.js + Yarn *(if using Webpack Encore)*

---

## 🛠 Installation

```bash
git clone https://gitlab.com/amavis442/dividend.git dividend-tracker
cd dividend-tracker

composer install

cp .env.example .env
# Configure DATABASE_URL and other environment variables

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### Deploying

Deploys run with Deployer (`deploy.php`). Some releases need a one-time step on the server,
such as setting `APP_ENCRYPTION_KEY` or running a conversion command: see
[DEPLOYMENT.md](DEPLOYMENT.md) before every deploy.

---

## 🖥️ Usage

### Start the local server

```bash
symfony server:start
# or
php -S localhost:8000 -t public
```

Open [http://localhost:8000](http://localhost:8000) in your browser to access the dashboard.

---

## 📊 Generating Reports

Main controller:
`App\Controller\Trading212\DividendForecastController`

Steps:
1. Choose snapshot date
2. View payout calendar by strategy and month
3. Export data via table view or aggregator

---

## 🧪 Tests

Run:

```bash
php bin/phpunit
```

For coverage:

```bash
./vendor/bin/phpunit --coverage-html var/coverage
```

---

## 📦 Frontend Assets

If using Webpack Encore:

```bash
yarn install
yarn dev
```

For production builds:

```bash
yarn build
```

---

## 👨‍💻 Contributing

We welcome PRs! Please:
- Follow PSR-12 code style
- Cover new features with tests
- Document significant logic changes

---

## 📜 License

Licensed under the MIT License. See `LICENSE` for details.

