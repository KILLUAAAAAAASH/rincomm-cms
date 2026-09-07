<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use App\Models\ServicePlan;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(): View
    {
        $servicePlans = ServicePlan::where('is_active', true)
            ->orderBy('monthly_fee')
            ->get();

        $heroSlides = HeroSlide::published()
            ->ordered()
            ->take(5)
            ->get();

        return view('landing', compact(
            'servicePlans',
            'heroSlides'
        ));
    }
}