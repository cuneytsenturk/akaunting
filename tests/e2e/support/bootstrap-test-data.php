<?php

/**
 * Standalone bootstrap for the E2E suite's fixture data. Run against an
 * already-installed instance (php artisan install / install:refresh).
 *
 * Idempotent — safe to run before every test run.
 */

require __DIR__ . '/../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Common\Company;
use App\Models\Common\Contact;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

// Akaunting calls out to api.akaunting.com for plan/user/invoice limits on every
// "/create" page, even for self-hosted installs. Without a registered API key
// that call 403s and the app fails closed, redirecting every create page away.
// Pre-seed the cache it reads so E2E runs don't depend on that live API.
$limit = (object) ['action_status' => true, 'view_status' => true, 'message' => 'Success'];
Cache::forever('plans.limits', (object) [
    'user' => clone $limit,
    'company' => clone $limit,
    'invoice' => clone $limit,
]);

$company = Company::first();

if (! $company) {
    fwrite(STDERR, "No company found. Run `php artisan install` (or install:refresh) first.\n");
    exit(1);
}

$company->makeCurrent();

// Skip the setup wizard redirect that gates every admin page until completed.
if (setting('wizard.completed', 0) != 1) {
    setting()->set('wizard.completed', 1);
    setting()->save();
}

// Restricted-permission fixture user, for the permission-boundary E2E test.
$email = 'e2e.accountant@akaunting.test';

if (! User::where('email', $email)->exists()) {
    $accountant_role_id = Role::where('name', 'accountant')->value('id');

    $user = new User();
    $user->name = 'E2E Accountant';
    $user->email = $email;
    $user->password = 'Password123!';
    $user->locale = 'en-GB';
    $user->enabled = true;
    $user->save();

    $user->companies()->attach([$company->id]);
    $user->roles()->attach([$accountant_role_id]);

    Artisan::call('user:seed', ['user' => $user->id, 'company' => $company->id]);
}

// Client-portal fixture user, for the Portal domain E2E tests. The portal
// ("customer" role) requires a Contact record linked via Contact.user_id —
// unlike the accountant fixture above, a bare User isn't enough.
// Note: not @akaunting.test — this user's email gets re-validated on every
// portal profile save (backend uses a DNS-checking email rule), and .test
// is not a resolvable TLD. Same root cause hit earlier for the Auth invite
// flow; see webpack-to-vite-roadmap.md 5f.
$portal_email = 'e2e.portal@gmail.com';

if (! User::where('email', $portal_email)->exists()) {
    $customer_role_id = Role::where('name', 'customer')->value('id');

    $user = new User();
    $user->name = 'E2E Portal Customer';
    $user->email = $portal_email;
    $user->password = 'Password123!';
    $user->locale = 'en-GB';
    $user->enabled = true;
    $user->save();

    $user->companies()->attach([$company->id]);
    $user->roles()->attach([$customer_role_id]);

    Artisan::call('user:seed', ['user' => $user->id, 'company' => $company->id]);

    $contact = new Contact();
    $contact->company_id = $company->id;
    $contact->type = 'customer';
    $contact->name = 'E2E Portal Customer';
    $contact->email = $portal_email;
    $contact->user_id = $user->id;
    $contact->currency_code = setting('default.currency', 'USD');
    $contact->enabled = true;
    $contact->save();
}

// Non-English-locale fixture user — dedicated so switching its locale can
// never affect the text-based assertions other specs make against the
// shared admin fixture. Exists to smoke-test the class of bug found in
// wizard/Company.vue (see webpack-to-vite-roadmap.md 5h): a dynamic
// require()/import() for a per-locale file (flatpickr l10n) that only
// executes for non-English locales, so English-only test coverage can miss
// a real Rollup incompatibility entirely.
$locale_email = 'e2e.locale@gmail.com';

if (! User::where('email', $locale_email)->exists()) {
    $admin_role_id = Role::where('name', 'admin')->value('id');

    $user = new User();
    $user->name = 'E2E Locale User';
    $user->email = $locale_email;
    $user->password = 'Password123!';
    $user->locale = 'de-DE';
    $user->enabled = true;
    $user->save();

    $user->companies()->attach([$company->id]);
    $user->roles()->attach([$admin_role_id]);

    Artisan::call('user:seed', ['user' => $user->id, 'company' => $company->id]);
}

echo "E2E fixture data ready.\n";
