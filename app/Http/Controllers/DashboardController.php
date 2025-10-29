<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index()
    {
        return view('dashboard.index');
    }

    /**
     * Show the profile page.
     */
    public function profile()
    {
        return view('dashboard.profile');
    }

    /**
     * Show the settings page.
     */
    public function settings()
    {
        return view('dashboard.settings', ['showFooter' => true]);
    }

    /**
     * Show the import data page.
     */
    public function import()
    {
        return view('dashboard.import');
    }
}