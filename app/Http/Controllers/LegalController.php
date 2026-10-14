<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LegalController extends Controller
{
    public function terms()
    {
        return Inertia::render('Legal/Terms', [
            'version' => config('legal.terms_version'),
        ]);
    }

    public function privacy()
    {
        return Inertia::render('Legal/Privacy', [
            'version' => config('legal.privacy_version'),
        ]);
    }
}

