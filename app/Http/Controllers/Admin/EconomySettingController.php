<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EconomySettingsService;
use App\Support\ObSupplementRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EconomySettingController extends Controller
{
    public function __construct(
        private EconomySettingsService $economySettings,
    ) {}

    public function edit(): View
    {
        return view('admin.settings.economy', [
            'settings' => $this->economySettings->all(),
            'obWindows' => ObSupplementRules::windows(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            EconomySettingsService::KEY_GUIDE_HOURLY_COST => ['required', 'numeric', 'min:0', 'max:99999'],
            EconomySettingsService::KEY_RESTAURANT_HOURLY_COST => ['required', 'numeric', 'min:0', 'max:99999'],
            EconomySettingsService::KEY_OB_HOURLY_AMOUNT => ['required', 'numeric', 'min:0', 'max:99999'],
            EconomySettingsService::KEY_PRICE_ADULT => ['required', 'numeric', 'min:0', 'max:99999'],
            EconomySettingsService::KEY_PRICE_YOUTH => ['required', 'numeric', 'min:0', 'max:99999'],
            EconomySettingsService::KEY_PRICE_CHILD => ['required', 'numeric', 'min:0', 'max:99999'],
            EconomySettingsService::KEY_CHILD_UNDER4_PERCENT => ['required', 'numeric', 'min:0', 'max:100'],
            EconomySettingsService::KEY_NOTIFICATION_EMAIL => ['nullable', 'email', 'max:255'],
        ]);

        $this->economySettings->update($data);

        return redirect()
            ->route('admin.economy-settings.edit')
            ->with('success', 'Ekonomiinställningar sparade.');
    }
}
