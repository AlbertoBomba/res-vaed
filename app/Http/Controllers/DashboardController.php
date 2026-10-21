<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return to_route($request->user()->isAdministrator() ? 'admin.dashboard' : 'customer.dashboard');
    }

    public function admin(Request $request): View
    {
        abort_unless($request->user()->isAdministrator(), 403);

        return view('dashboard', ['isAdmin' => true]);
    }

    public function customer(Request $request): View
    {
        abort_if($request->user()->isAdministrator(), 403);

        return view('dashboard', ['isAdmin' => false]);
    }
}
