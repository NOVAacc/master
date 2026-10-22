<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        // The public homepage introduces BOSH; account discovery belongs in admin.
        // Never serialize master records into the homepage's Inertia payload.
        return Inertia::render('Home');
    }
}
