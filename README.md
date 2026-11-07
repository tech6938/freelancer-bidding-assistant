# Bidder

Bidder is a Laravel web application that helps freelancers discover suitable projects on Freelancer.com, prepare proposals, and manage or automate bidding from one dashboard.

It combines configurable skill and project filters, bid-pricing rules, and optional AI-generated proposal text. Automatic bidding is opt-in and subject to each user's settings, subscription permissions, and bid balance.

## Why this project exists

Freelancers often spend time repeatedly searching for relevant projects, checking whether a client or budget meets their preferences, calculating a bid, and writing a proposal. Bidder brings those steps into one configurable workflow: it can scan projects against a freelancer's skills and constraints, help prepare a tailored pitch, and optionally submit bids through the freelancer's connected account.

## How Bidder helps freelancers

Bidder is designed to make the project-search and bidding workflow more focused and less repetitive:

- **Spend less time searching:** Surface projects related to the skills the freelancer has enabled instead of starting every search from scratch.
- **Focus on suitable opportunities:** Apply personal budget, project-type, client, location, language, and keyword preferences to filter projects.
- **Prepare proposals faster:** Generate a first-draft pitch from project details and required skills, then review and edit it before submitting. Reusable templates can help keep a consistent voice.
- **Bid more consistently:** Use configured pricing rules, delivery estimates, and daily limits instead of recalculating the same settings for each opportunity.
- **Choose the level of automation:** Review projects and place bids manually, preview matches with a dry run, or enable auto-bidding when eligible. Auto-bidding can submit proposals and bids without a manual review, so freelancers should configure it carefully and monitor their bid history.
- **Keep activity in one place:** Manage skills, filters, bid settings, and bid logs through one dashboard.

The platform is a workflow assistant, not a guarantee of winning work or earning income. Freelancers remain responsible for reviewing project details, generated proposals, and bid settings, and for complying with Freelancer.com's terms.

## Main features

- **Freelancer account connection:** OAuth-based connection to Freelancer.com and access to project data through its API.
- **Project dashboard and live feed:** Browse current projects and inspect project/client details before bidding.
- **Skill management:** Sync or manage skills, activate or deactivate them, and organize them into reusable skill sets.
- **Project matching:** Match projects against configured skills. A template-matching helper also scores skill overlap using Jaccard similarity for proposal-template selection.
- **Proposal assistance:** Generate a project-specific proposal with Groq, or use a structured fallback in the automated proposal flow when the AI service is unavailable. Users can also manage reusable proposal templates.
- **Configurable bid rules:** Set budget limits, fixed/hourly project preferences, delivery time, bidding delay, and maximum daily bids. Bid amounts can use minimum, average, or maximum calculations; a price matrix is available when permitted by the user's plan.
- **Project and client filters:** Filter by country, language, currency, client payment verification, minimum client rating/reviews, project type, and excluded keywords.
- **Optional auto-bidding:** Scan projects, apply the user's skills and filters, calculate a bid, create a proposal, and queue bid placement. Users can preview matching with dry-run commands.
- **Bid history and activity:** Review bid logs and current auto-bid status.
- **Subscriptions and AI bid balance:** Admin-managed subscription plans and bid packages control access and proposal/auto-bid usage.
- **Admin tools:** Manage users, plans, packages, bank payment methods, and submitted orders. Admins can approve or reject payment submissions.
- **Queue and scheduler support:** Background jobs handle bid placement, with scheduled project ingestion and queue processing.

## How the AI works

The AI integration uses the **Groq chat-completions API**. The configured model (default: `openai/gpt-oss-20b`) receives a prompt containing project information—title, description, client name when available, and required skills—and is asked to produce a concise professional proposal. The API key and model are configured with `GROQ_API_KEY` and `GROQ_MODEL`.

The AI is used to write proposal text; it does **not** make the project's match decision or independently choose a bid amount. Those steps are primarily rule-based:

1. The application fetches project data from Freelancer.com.
2. Active skill IDs/names and configured filters determine whether a project qualifies.
3. Bid settings, project budget/statistics, and optionally the price matrix determine the bid amount.
4. Groq generates proposal wording from the project details. In the automated proposal service, a structured fallback is used if Groq is not configured or the request fails.
5. If auto-bidding is enabled and the account has the required access and balance, the bid is queued and submitted through the connected Freelancer account.

Project details are sent to Groq when its proposal-generation endpoint is used. Configure the provider and review its terms and data-handling policies before using real client or project data. The interactive proposal endpoint requires a configured Groq key; the automated proposal service has a text fallback.

## Technology

- PHP 8.2+
- Laravel 12
- Blade, JavaScript, Tailwind CSS 4, and Vite
- SQL database supported by the Laravel application configuration (SQLite is the default in the sample environment)
- Laravel queues and cache (database is the sample default; configure a production driver as appropriate)
- Freelancer.com API and OAuth
- Groq API for AI-generated proposal text

## Requirements

- PHP 8.2 or newer with the PHP extensions required by Laravel
- Composer
- Node.js and npm
- A database supported by Laravel
- Freelancer.com developer app credentials for account connection and project/bid API operations
- A Groq API key for AI proposal generation

## Local setup

1. Install PHP dependencies:

   ```sh
   composer install
   ```

2. Create a local environment file and application key:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

   On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

3. Configure the database in `.env`. For SQLite, create the database file if it does not exist:

   ```sh
   php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
   ```

4. Add your integration settings to `.env`:

   ```dotenv
   FREELANCER_CLIENT_ID=
   FREELANCER_CLIENT_SECRET=
   FREELANCER_REDIRECT_URI="${APP_URL}/freelancer/callback"

   GROQ_API_KEY=
   GROQ_MODEL=openai/gpt-oss-20b
   ```

   Register the same callback URL with your Freelancer.com OAuth application. Keep real credentials only in your local `.env` or deployment secret store—never commit them.

5. Run database migrations and seed the configured initial data:

   ```sh
   php artisan migrate --seed
   ```

   Review the development admin seeder and change its credentials/provisioning before using the application outside a local environment.

6. Install frontend dependencies and build assets:

   ```sh
   npm install
   npm run build
   ```

7. Start the local web server:

   ```sh
   php artisan serve
   ```

   For auto-bidding workflows, also run a queue worker and Laravel's scheduler in separate terminals:

   ```sh
   php artisan queue:work
   php artisan schedule:work
   ```

   Auto-bidding additionally requires a connected Freelancer account, configured skills and filters, an active plan with auto-bid access, and sufficient AI bid balance.

## Artisan commands

- `php artisan autobid:ingest --dry-run` — fetch projects and preview reverse matches without dispatching bid jobs.
- `php artisan autobid:ingest --daemon` — continuously ingest projects. Use a supervised process in a suitable environment.
- `php artisan autobid:process --dry-run` — preview the per-user scan without submitting bids.
- `php artisan schedule:work` — run the application's scheduled tasks locally.

Dry-run prevents bid-job dispatch/submission; it can still make external project-data requests and may use configured services during preview processing.

## Testing

Run the Laravel test suite with:

```sh
php artisan test
```

## Before publishing or deploying

- Keep `.env`, API keys, OAuth secrets, access tokens, and production credentials out of Git.
- Do not commit the local SQLite database, logs, payment proof uploads, or other user data. Check staged files before every push.
- Replace development admin credentials and review payment handling, privacy, and production configuration before deployment.
- Confirm usage of Freelancer.com and Groq APIs complies with their current terms and rate limits.
- Add a project license before publishing if you want to grant others explicit reuse rights.
