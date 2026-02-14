<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as Provider;
use Laravel\Passport\Passport;

class Auth extends Provider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        //'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Configure Passport token expiration and scopes
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Example scopes for an invoicing application
        Passport::tokensCan([
            'invoices-read' => 'Faturaları görüntüleme',
            'invoices-write' => 'Fatura oluşturma/düzenleme',
            'customers-read' => 'Müşterileri görüntüleme',
            'customers-write' => 'Müşteri oluşturma/düzenleme',
            'reports-read' => 'Raporları görüntüleme',
        ]);

        // Set default scopes for clients that don't specify any
        Passport::setDefaultScope([
            'invoices-read',
            'customers-read',
        ]);
    }
}
