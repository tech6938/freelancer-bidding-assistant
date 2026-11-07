<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FreelancerController;
use App\Http\Controllers\AIProjectMatchController;
use App\Http\Controllers\SkillsSettingsController;
use App\Http\Controllers\AiBidSettingController;
use App\Http\Controllers\ProjectFiltersController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BidSettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AiBidLogController;
use App\Http\Controllers\BidPriceController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\BidPackageController;
use App\Http\Controllers\Admin\BankCardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ContestController;

/*
|--------------------------------------------------------------------------
| Guest Routes (Unauthenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Freelancer OAuth routes — accessible by both guests and authenticated users
Route::get('/freelancer/connect', [FreelancerController::class, 'connect'])->name('freelancer.connect');
Route::get('/freelancer/callback', [FreelancerController::class, 'callback'])->name('freelancer.callback');

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Common for all authenticated users: Admin & User)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Logout
    Route::get('change/password', [AuthController::class, 'showChangePasswordForm'])->name('password.change');
    Route::put('change/password', [AuthController::class, 'updatePassword'])->name('password.update.custom');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| User-Only Authenticated Routes (Protected by auth and user middleware)
|--------------------------------------------------------------------------
*/ 
Route::middleware('auth', 'user')->group(function () {
 
    // Set Password (shown after Freelancer API registration)
    Route::get('/set-password', [AuthController::class, 'showSetPassword'])->name('password.set');
    Route::post('/set-password', [AuthController::class, 'storePassword'])->name('password.store');

    // Home redirect
    Route::get('/', function () {
        return redirect()->route('freelancer.index');
    });

    // Freelancer Dashboard
    Route::get('/freelancer', [FreelancerController::class, 'index'])->name('freelancer.index');
    Route::post('/freelancer/place-bid', [FreelancerController::class, 'placeBid'])
        ->middleware('throttle:bid')->name('freelancer.placeBid');
    Route::get('/freelancer/live-feed', [FreelancerController::class, 'liveFeed'])
        ->middleware('throttle:live-feed')->name('freelancer.liveFeed');
    Route::post('/freelancer/generate-ai-proposal', [FreelancerController::class, 'generateAiProposal'])
        ->middleware('throttle:ai-proposal')->name('freelancer.generateAiProposal');
    Route::post('/freelancer/disconnect', [FreelancerController::class, 'disconnect'])->name('freelancer.disconnect');

    Route::prefix('filters')->name('settings.filters.')->group(function () {
        // 1. Countries
        Route::get('/countries', [ProjectFiltersController::class, 'countries'])->name('countries');
        Route::post('/countries', [ProjectFiltersController::class, 'updateCountries'])->name('countries.update');

        // 2. Languages
        Route::get('/languages', [ProjectFiltersController::class, 'languages'])->name('languages');
        Route::post('/languages', [ProjectFiltersController::class, 'updateLanguages'])->name('languages.update');

        // 3. Currencies
        Route::get('/currencies', [ProjectFiltersController::class, 'currencies'])->name('currencies');
        Route::post('/currencies', [ProjectFiltersController::class, 'updateCurrencies'])->name('currencies.update');
    });

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/ai-project-match', [AIProjectMatchController::class, 'index'])->name('settings.aiProjectMatch');
        Route::post('/ai-project-match', [AIProjectMatchController::class, 'store'])->name('settings.aiProjectMatch.store');
        Route::put('/ai-project-match/{id}', [AIProjectMatchController::class, 'update'])->name('settings.aiProjectMatch.update');
        Route::post('/ai-project-match/{id}/add-all-skills', [AIProjectMatchController::class, 'addAllSkills'])->name('settings.aiProjectMatch.addAllSkills');
        Route::post('/ai-project-match/{id}/duplicate', [AIProjectMatchController::class, 'duplicate'])->name('settings.aiProjectMatch.duplicate');
        Route::delete('/ai-project-match/{id}', [AIProjectMatchController::class, 'destroy'])->name('settings.aiProjectMatch.destroy');

        // Ai Bid Settings (edit + update below)

        // Skills
        Route::get('/skills', [SkillsSettingsController::class, 'skills'])->name('settings.skills');
        Route::post('/skill/sync', [SkillsSettingsController::class, 'syncFromFreelancer'])->name('settings.skills.sync');
        Route::get('/skills/search-api', [SkillsSettingsController::class, 'searchApi'])->name('settings.skills.searchApi');
        Route::post('/skills/store', [SkillsSettingsController::class, 'store'])->name('settings.skills.store');
        Route::post('/skills/{id}/toggle', [SkillsSettingsController::class, 'toggle'])->name('settings.skills.toggle');
        Route::delete('/skills/{id}', [SkillsSettingsController::class, 'destroy'])->name('settings.skills.destroy');

        // Skill Sets
        Route::get('/skill-sets', [SkillsSettingsController::class, 'skillSets'])->name('settings.skillSets');
        Route::post('/skill-sets', [SkillsSettingsController::class, 'storeSkillSet'])->name('settings.skillSets.store');
        Route::put('/skill-sets/{id}', [SkillsSettingsController::class, 'updateSkillSet'])->name('settings.skillSets.update');
        Route::post('/skill-sets/{id}/duplicate', [SkillsSettingsController::class, 'duplicateSkillSet'])->name('settings.skillSets.duplicate');
        Route::delete('/skill-sets/{id}', [SkillsSettingsController::class, 'destroySkillSet'])->name('settings.skillSets.destroy');

        //bidsettings
        Route::get('/bid-settings', [BidSettingController::class, 'edit'])->name('settings.bidSettings');
    Route::post('/bid-settings', [BidSettingController::class, 'update'])->name('settings.bidSettings.update');
    Route::get('/profile', [ProfileController::class, 'index'])->name('settings.profile');
    //ai bid settings
    Route::get('ai-bid-settings', [AiBidSettingController::class, 'edit'])->name('settings.aiBidSettings');
    Route::put('ai-bid-settings', [AiBidSettingController::class, 'update'])->name('settings.aiBidSettings.update');
    Route::post('ai-bid-settings/toggle-status', [AiBidSettingController::class, 'toggleStatus'])->name('settings.aiBidSettings.toggleStatus');

    //bidprice
    Route::get('/settings/bid-price', [BidPriceController::class, 'index'])->name('bid-price.index');
    Route::post('/settings/bid-price', [BidPriceController::class, 'store'])->name('bid-price.store');

    //ai bid logs
    Route::get('/ai-bid-logs', [AiBidLogController::class, 'index'])->name('settings.aibidslog');
    Route::get('/contests', [ContestController::class, 'index'])->name('settings.contests');
    
    });
    

    // Purchase & Monetization Flow (Authenticated Users)
    Route::get('/purchase', [PurchaseController::class, 'index'])->name('purchase.index');
    Route::post('/purchase/subscription', [PurchaseController::class, 'purchaseSubscription'])->name('purchase.subscription');
    Route::post('/purchase/bid-package', [PurchaseController::class, 'purchaseBidPackage'])->name('purchase.bidPackage');
});


Route::middleware('auth', 'admin')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users/{user}/toggle-ban', [AdminController::class, 'toggleBan'])->name('admin.users.toggleBan');

    // 1. Subscription Plans Management (Dynamic Checkbox/Inputs & SoftDeletes)
    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->name('admin.plans.index');
    Route::get('/subscription-plans/create', [SubscriptionPlanController::class, 'create'])->name('admin.plans.create');
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store'])->name('admin.plans.store');
    Route::get('/subscription-plans/{id}/edit', [SubscriptionPlanController::class, 'edit'])->name('admin.plans.edit');
    Route::put('/subscription-plans/{id}', [SubscriptionPlanController::class, 'update'])->name('admin.plans.update');
    Route::delete('/subscription-plans/{id}', [SubscriptionPlanController::class, 'destroy'])->name('admin.plans.destroy');
    Route::post('/subscription-plans/{id}/restore', [SubscriptionPlanController::class, 'restore'])->name('admin.plans.restore');

    // 2. AI Bid Packages Management (Direct Quantities & SoftDeletes)
    Route::get('/bid-packages', [BidPackageController::class, 'index'])->name('admin.packages.index');
    Route::post('/bid-packages', [BidPackageController::class, 'store'])->name('admin.packages.store');
    Route::put('/bid-packages/{id}', [BidPackageController::class, 'update'])->name('admin.packages.update');
    Route::delete('/bid-packages/{id}', [BidPackageController::class, 'destroy'])->name('admin.packages.destroy');
    Route::post('/bid-packages/{id}/restore', [BidPackageController::class, 'restore'])->name('admin.packages.restore');

    // 3. Bank Cards / Accounts Management (Added by Admin for User Checkout)
    Route::get('/bank-cards', [BankCardController::class, 'index'])->name('admin.bank-cards.index');
    Route::post('/bank-cards', [BankCardController::class, 'store'])->name('admin.bank-cards.store');
    Route::put('/bank-cards/{id}', [BankCardController::class, 'update'])->name('admin.bank-cards.update');
    Route::post('/bank-cards/{id}/toggle', [BankCardController::class, 'toggleActive'])->name('admin.bank-cards.toggle');
    Route::delete('/bank-cards/{id}', [BankCardController::class, 'destroy'])->name('admin.bank-cards.destroy');
    Route::post('/bank-cards/{id}/restore', [BankCardController::class, 'restore'])->name('admin.bank-cards.restore');

    // 4. Transactions & Subscriptions Audit + Verification
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::post('/orders/{id}/approve', [AdminOrderController::class, 'approve'])->name('admin.orders.approve');
    Route::post('/orders/{id}/reject', [AdminOrderController::class, 'reject'])->name('admin.orders.reject');
});