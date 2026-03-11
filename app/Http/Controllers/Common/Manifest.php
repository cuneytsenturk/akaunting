<?php

namespace App\Http\Controllers\Common;

use App\Abstracts\Http\Controller;

class Manifest extends Controller
{
    public function show()
    {
        $iconBase  = asset('public/img/pwa');

        $manifest = [
            'name'             => config('app.name'),
            'short_name'       => config('app.name'),
            'id'               => route('dashboard', ['utm_source' => 'pwa']),
            'lang'             => app()->getLocale(),
            'description'      => 'Free invoicing and accounting software for small businesses and freelancers.',
            'categories'       => ['finance', 'business'],
            'start_url'        => route('dashboard', ['utm_source' => 'pwa']),
            'display'          => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone'],
            'theme_color'      => '#ffffff',
            'background_color' => '#ffffff',
            'orientation'      => 'any',
            'android_package_name'        => 'com.akaunting.akaunting',
            'prefer_related_applications' => false,
            'related_applications' => [
                [
                    'id'       => 'com.akaunting.akaunting',
                    'platform' => 'play',
                    'url'      => 'https://play.google.com/store/apps/details?id=com.akaunting.akaunting',
                ],
                [
                    'platform' => 'itunes',
                    'url'      => 'https://apps.apple.com/us/app/akaunting/id1573240410',
                ],
            ],
            'icons' => [
                [
                    'src'   => $iconBase . '/icon-192x192.png',
                    'type'  => 'image/png',
                    'sizes' => '192x192',
                ],
                [
                    'purpose' => 'maskable',
                    'src'     => $iconBase . '/icon-192x192-maskable.png',
                    'type'    => 'image/png',
                    'sizes'   => '192x192',
                ],
                [
                    'src'   => $iconBase . '/icon-512x512.png',
                    'sizes' => '512x512',
                    'type'  => 'image/png',
                ],
                [
                    'purpose' => 'maskable',
                    'src'     => $iconBase . '/icon-512x512-maskable.png',
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                ],
            ],
            'screenshots' => [
                [
                    'src'         => $iconBase . '/screenshot-dashboard.png',
                    'sizes'       => '1932x1394',
                    'form_factor' => 'wide',
                    'type'        => 'image/png',
                ],
                [
                    'src'         => $iconBase . '/screenshot-invoice.png',
                    'sizes'       => '2748x1986',
                    'form_factor' => 'wide',
                    'type'        => 'image/png',
                ],
            ],
            'shortcuts' => [
                [
                    'name'        => trans('general.title.new', ['type' => setting('invoice.title', trans_choice('general.invoices', 1))]),
                    'short_name'  => trans('general.title.new', ['type' => setting('invoice.title', trans_choice('general.invoices', 1))]),
                    'description' => trans('general.empty.invoices'),
                    'url'         => route('invoices.create', ['utm_source' => 'pwa']),
                    'icons'       => [['src' => $iconBase . '/shortcut-invoice.svg', 'sizes' => '96x96', 'type' => 'image/svg+xml']],
                ],
                [
                    'name'        => trans('general.title.new', ['type' => setting('income.title', trans_choice('general.incomes', 1))]),
                    'short_name'  => trans('general.title.new', ['type' => setting('income.title', trans_choice('general.incomes', 1))]),
                    'description' => trans('general.empty.transactions'),
                    'url'         => route('banking.transactions.create', ['type' => 'income', 'utm_source' => 'pwa']),
                    'icons'       => [['src' => $iconBase . '/shortcut-income.svg', 'sizes' => '96x96', 'type' => 'image/svg+xml']],
                ],
                [
                    'name'        => trans('general.title.new', ['type' => setting('bill.title', trans_choice('general.bills', 1))]),
                    'short_name'  => trans('general.title.new', ['type' => setting('bill.title', trans_choice('general.bills', 1))]),
                    'description' => trans('general.empty.bills'),
                    'url'         => route('purchases.bills.create', ['utm_source' => 'pwa']),
                    'icons'       => [['src' => $iconBase . '/shortcut-bill.svg', 'sizes' => '96x96', 'type' => 'image/svg+xml']],
                ],
                [
                    'name'        => trans('general.title.new', ['type' => setting('expense.title', trans_choice('general.expenses', 1))]),
                    'short_name'  => trans('general.title.new', ['type' => setting('expense.title', trans_choice('general.expenses', 1))]),
                    'description' => trans('general.empty.transactions'),
                    'url'         => route('banking.transactions.create', ['type' => 'expense', 'utm_source' => 'pwa']),
                    'icons'       => [['src' => $iconBase . '/shortcut-expense.svg', 'sizes' => '96x96', 'type' => 'image/svg+xml']],
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'no-store');
    }
}
