<?php

namespace App\Http\Controllers\Settings;

use App\Abstracts\Http\SettingController;

class OAuth extends SettingController
{
    /**
     * Show the form for editing OAuth settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        // Get all available scopes from config
        $scopes = config('oauth.scopes', []);
        
        // Format scopes for select box
        $scope_options = [];
        foreach ($scopes as $key => $description) {
            $scope_options[$key] = $key . ' - ' . $description;
        }

        // Token lifetime options (in minutes)
        $lifetime_options = [
            15 => '15 ' . trans('settings.oauth.minutes'),
            30 => '30 ' . trans('settings.oauth.minutes'),
            60 => '1 ' . trans('settings.oauth.hour'),
            120 => '2 ' . trans('settings.oauth.hours'),
            180 => '3 ' . trans('settings.oauth.hours'),
            360 => '6 ' . trans('settings.oauth.hours'),
            720 => '12 ' . trans('settings.oauth.hours'),
            1440 => '1 ' . trans('settings.oauth.day'),
            10080 => '1 ' . trans('settings.oauth.week'),
            20160 => '2 ' . trans('settings.oauth.weeks'),
            43200 => '1 ' . trans('settings.oauth.month'),
            525600 => '1 ' . trans('settings.oauth.year'),
        ];

        // DCR max clients options
        $dcr_max_options = [
            5 => '5',
            10 => '10',
            20 => '20',
            50 => '50',
            100 => '100',
        ];

        return view('settings.oauth.edit', compact(
            'scope_options',
            'lifetime_options',
            'dcr_max_options'
        ));
    }
}
