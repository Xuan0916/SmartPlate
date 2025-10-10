<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Example data (replace with actual queries later)
        $mealProgress = '60%';
        $foodSavedLastMonth = '12 kg';
        $totalMeals = 27;

        return view('dashboard', compact('mealProgress', 'foodSavedLastMonth', 'totalMeals'));
    }
}
