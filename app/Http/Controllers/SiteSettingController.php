<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SiteSetting;

class SiteSettingController extends Controller
{
    public function isSuspended(Request $request)
    {
        // Update only if suspended query param exists
        if ($request->has('suspended')) {

            $status = filter_var(
                $request->query('suspended'),
                FILTER_VALIDATE_BOOLEAN
            );

            SiteSetting::updateOrCreate(
                ['id' => 1],
                [
                    'suspended' => $status,
                    'suspended_at' => $status ? now() : null,
                ]
            );
        }

        $setting = SiteSetting::first();

        return response()->json([
            'success' => true,
            'suspended' => $setting?->suspended ?? false,
            'suspended_at' => $setting?->suspended_at,
        ]);
    }
}
