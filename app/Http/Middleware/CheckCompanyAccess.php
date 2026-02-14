<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ?? $request->input('company_id');
        
        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 400);
        }
        
        // Kullanıcının bu şirkete erişimi var mı kontrol et
        if (!$user->companies()->where('id', $companyId)->exists()) {
            return response()->json(['error' => 'Unauthorized company access'], 403);
        }
        
        // Aktif şirketi ayarla
        session(['company_id' => $companyId]);
        app()->instance('company', \App\Models\Company::find($companyId));
        
        return $next($request);
    }
}