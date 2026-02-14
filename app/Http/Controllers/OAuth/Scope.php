<?php

namespace App\Http\Controllers\OAuth;

use App\Abstracts\Http\Controller;
use Laravel\Passport\Passport;

class Scope extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('auth');
    }

    /**
     * Get all of the defined OAuth scopes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $scopes = Passport::scopes()->all();

        return response()->json([
            'success' => true,
            'error' => false,
            'data' => $scopes,
        ]);
    }
}
