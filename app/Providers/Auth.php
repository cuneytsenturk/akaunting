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
            'company:read' => 'Şirket bilgilerini görüntüleme',
            'company:write' => 'Şirket bilgilerini düzenleme',
            
            'invoices:read' => 'Faturaları görüntüleme',
            'invoices:write' => 'Fatura oluşturma/düzenleme',
            'invoices:delete' => 'Fatura silme',
            
            'customers:read' => 'Müşterileri görüntüleme',
            'customers:write' => 'Müşteri oluşturma/düzenleme',
            'customers:delete' => 'Müşteri silme',
            
            'items:read' => 'Ürünleri görüntüleme',
            'items:write' => 'Ürün oluşturma/düzenleme',
            
            'reports:read' => 'Raporları görüntüleme',
            'reports:export' => 'Rapor dışa aktarma',
            
            'transactions:read' => 'İşlemleri görüntüleme',
            'transactions:write' => 'İşlem oluşturma',
        ]);

        // Set default scopes for clients that don't specify any
        Passport::setDefaultScope([
            'invoices-read',
            'customers-read',
        ]);
    }
}
