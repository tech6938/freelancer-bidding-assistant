<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\ProjectFilter;

class ProjectFiltersController extends Controller
{
    private function getUserFilter(): ProjectFilter
    {
        $userId = Auth::id();
        return ProjectFilter::firstOrCreate(['user_id' => $userId]);
    }
    public function countries()
    {
        $filter = $this->getUserFilter();
        $selectedCountries = $filter->countries ?? [];

        // Freelancer API se fetch karein aur 24 hours ke liye cache karein
        $allCountries = Cache::remember('freelancer_api_countries', 86400, function () {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(8)
                    ->get('https://www.freelancer.com/api/common/0.1/countries/?extra_details=true');
                    
                if ($response->successful()) {
                    return $response->json()['result']['countries'] ?? [];
                }
                return [];
            } catch (\Exception $e) {
                return [];
            }
        });

        return view('settings.filters.countries', compact('allCountries', 'selectedCountries'));
    }
    public function updateCountries(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasActiveSubscription()) {
            return redirect()->back()->with('error', '⚠️ Saving country filters requires an active subscription plan.');
        }

        $countries = (array) $request->input('countries', []);
        $maxLimit = (int) $user->getPlanFeature('countries_limit', 0);

        if ($maxLimit > 0 && count($countries) > $maxLimit) {
            return redirect()->back()->with('error', "⚠️ Your subscription plan allows a maximum of {$maxLimit} countries. Please upgrade your plan to select more.");
        }

        $filter = $this->getUserFilter();
        $filter->update(['countries' => $countries]);

        return redirect()->back()->with('success', 'Country filters saved successfully!');
    }

    public function languages()
    {
        $filter = $this->getUserFilter();
        $selectedLanguages = $filter->languages ?? ['en'];

        // Dynamic API se languages fetch karein aur 24 hours ke liye cache karein
        $allLanguages = Cache::remember('api_languages_list', 86400, function () {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(8)
                    ->get('https://cdn.jsdelivr.net/gh/umpirsky/language-list/data/en/language.json');

                if ($response->successful()) {
                    $list = $response->json() ?? [];
                    $result = [];
                    foreach ($list as $code => $name) {
                        $result[] = [
                            'code' => strtolower($code),
                            'name' => $name,
                        ];
                    }
                    usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));
                    return $result;
                }
                return [];
            } catch (\Exception $e) {
                return [];
            }
        });

        return view('settings.filters.languages', compact('allLanguages', 'selectedLanguages'));
    }

    public function updateLanguages(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasActiveSubscription()) {
            return redirect()->back()->with('error', '⚠️ Saving language filters requires an active subscription plan.');
        }

        $languages = (array) $request->input('languages', ['en']);
        $maxLimit = (int) $user->getPlanFeature('languages_limit', 0);

        if ($maxLimit > 0 && count($languages) > $maxLimit) {
            return redirect()->back()->with('error', "⚠️ Your subscription plan allows a maximum of {$maxLimit} languages. Please upgrade your plan to select more.");
        }

        $filter = $this->getUserFilter();
        $filter->update(['languages' => $languages]);

        return redirect()->back()->with('success', 'Language filters saved successfully!');
    }

    public function currencies()
    {
        $filter = $this->getUserFilter();
        $selectedCurrencies = $filter->currencies ?? ['USD'];

        // Freelancer API se fetch karein aur 24 hours ke liye cache karein
        $allCurrencies = Cache::remember('freelancer_api_currencies', 86400, function () {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(8)
                    ->get('https://www.freelancer.com/api/projects/0.1/currencies/');

                if ($response->successful()) {
                    return $response->json()['result']['currencies'] ?? [];
                }
                return [];
            } catch (\Exception $e) {
                return [];
            }
        });

        return view('settings.filters.currencies', compact('allCurrencies', 'selectedCurrencies'));
    }

    public function updateCurrencies(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasActiveSubscription()) {
            return redirect()->back()->with('error', '⚠️ Saving currency filters requires an active subscription plan.');
        }

        $currencies = (array) $request->input('currencies', ['USD']);
        $maxLimit = (int) $user->getPlanFeature('currencies_limit', 0);

        if ($maxLimit > 0 && count($currencies) > $maxLimit) {
            return redirect()->back()->with('error', "⚠️ Your subscription plan allows a maximum of {$maxLimit} currencies. Please upgrade your plan to select more.");
        }

        $filter = $this->getUserFilter();
        $filter->update(['currencies' => $currencies]);

        return redirect()->back()->with('success', 'Currency filters saved successfully!');
    }
}
