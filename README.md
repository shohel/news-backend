## Installation

This is a news backend project that will collect news from various sources and store it in the database.

- Clone it `git@github.com:shohel/news-backend.git`
- Change path tho this project root from the terminal `cd /path/to/news-backend`
- Copy `.env-prod` to `.env`, it's containing API KEY, update the DB connection details.
- Install it by composer `composer install`
- Migrate the Database `php artisan migrate`
- Seed default user `php artisan db:seed`
- Run the schedule to get news `php artisan schedule:work` It will continuously retrieve news every 5 minutes. However, if you don't want to wait, you can directly import the database from `sql/local.sql` file

### Adding API key to env value

To retrieve news from various sources, you need to include these API keys.

- `NEWS_API="ac5407da75834a58935c8d1381ff0617"`
- `THE_GUARDIAN_API="bcbec10f-c291-42cb-9ed9-82822abfa288"`
- `NYTIMES="DK8suTXPtTC4bPeMmjOeTxfPC2xy8jM6"`

Run this application and copy the backend application's URL, as you will need it to configure the ReactJS application.
