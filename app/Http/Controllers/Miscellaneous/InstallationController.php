<?php

namespace App\Http\Controllers\Miscellaneous;

use App\Http\Controllers\Controller;
use App\Models\Miscellaneous\WebsiteInstallation;
use App\Models\Miscellaneous\WebsiteSetting;
use App\Rules\ValidateInstallationKeyRule;
use App\Services\InstallationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstallationController extends Controller
{
    public function index(): View
    {
        return view('installation.index');
    }

    public function storeInstallationKey(Request $request): RedirectResponse
    {
        $request->validate([
            'installation_key' => ['required', 'string', 'max:255', new ValidateInstallationKeyRule],
        ]);

        $this->installation()->update([
            'step' => 1,
            'user_ip' => $request->ip(),
        ]);

        return to_route('installation.show-step', 1);
    }

    public function showStep(int $currentStep): View|RedirectResponse
    {
        // A resubmitted form, a stale tab or a hand-typed URL can ask for a
        // step either side of the wizard. Send them to the nearest real one
        // rather than showing them an exception.
        if ($currentStep !== $this->clamp($currentStep)) {
            return to_route('installation.show-step', $this->clamp($currentStep));
        }

        /** @var view-string $view */
        $view = "installation.step-{$currentStep}";

        return view($view, [
            'settings' => $this->getSettingsForStep($currentStep),
        ]);
    }

    public function saveStepSettings(Request $request): RedirectResponse
    {
        $this->updateSettings($request);

        $installation = $this->installation();
        $installation->update(['step' => $this->clamp($installation->step + 1)]);

        return to_route('installation.show-step', $installation->step);
    }

    public function previousStep(): RedirectResponse
    {
        $installation = $this->installation();
        $installation->update(['step' => $this->clamp($installation->step - 1)]);

        return to_route('installation.show-step', $installation->step);
    }

    public function restartInstallation(): RedirectResponse
    {
        $this->installation()->update([
            'step' => 0,
            'installation_key' => Str::uuid(),
            'user_ip' => null,
        ]);

        WebsiteSetting::where('key', 'theme')->update([
            'value' => 'atom',
        ]);

        return to_route('installation.index');
    }

    public function completeInstallation(): RedirectResponse
    {
        // Clear all caches before marking as complete
        Cache::forget('website_permissions');
        Cache::forget('website_settings');

        // Concurrent first-ever requests can each have created an installation
        // row; the wizard progressed on the oldest while completion previously
        // marked only the newest, leaving the row every check reads incomplete
        // forever. Mark them all so every reader agrees.
        WebsiteInstallation::query()->update([
            'completed' => true,
        ]);

        InstallationService::setComplete();

        return to_route('welcome');
    }

    private function updateSettings(Request $request): void
    {
        foreach ($request->except('_token') as $key => $value) {
            WebsiteSetting::where('key', '=', $key)->update([
                'value' => $value ?? '',
            ]);
        }

        // Cache will be automatically cleared by WebsiteSetting model events
    }

    private function installation(): WebsiteInstallation
    {
        return WebsiteInstallation::query()->oldest('id')->firstOrFail();
    }

    private function clamp(int $step): int
    {
        return max(WebsiteInstallation::FIRST_STEP, min(WebsiteInstallation::LAST_STEP, $step));
    }

    /** @return Collection<int, WebsiteSetting> */
    private function getSettingsForStep(int $step): Collection
    {
        $chunkSize = max(1, (int) ceil(WebsiteSetting::count() / WebsiteInstallation::SETTING_STEPS));
        $chunks = array_chunk(WebsiteSetting::all()->pluck('key')->toArray(), $chunkSize);

        // The completion step carries no settings, and so does any chunk the
        // split did not produce.
        $settings = $chunks[$step - 1] ?? [];

        return WebsiteSetting::query()
            ->whereIn('key', $settings)
            ->select(['key', 'value', 'comment'])
            ->get();
    }
}
