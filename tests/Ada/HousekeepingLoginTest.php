<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/**
 * Housekeeping authenticates against the users table, which on Ada is the
 * compatibility mirror rather than the emulator's own player table. If the
 * mirror ever stopped carrying the credential columns, staff would be locked
 * out of the panel on this driver only.
 */
test('the panel resolves the same login and dashboard pages on ada', function () {
    expect(Route::getRoutes()->getByName('filament.housekeeping.auth.login')?->getActionName())
        ->toBe(Login::class)
        ->and(Route::getRoutes()->getByName('filament.housekeeping.pages.dashboard')?->getActionName())
        ->toBe(Dashboard::class);
});

test('staff log into housekeeping with their username on ada', function () {
    installHotel();
    grantHousekeepingPermission('can_access_housekeeping', 6);

    $staff = User::factory()->create(['rank' => 6]);

    Filament::setCurrentPanel(Filament::getPanel('housekeeping'));

    Livewire::test(Login::class)
        ->set('data.username', $staff->username)
        ->set('data.password', 'password')
        ->set('data.remember', false)
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($staff);
});

test('staff with housekeeping access reach the dashboard on ada', function () {
    installHotel();
    grantHousekeepingPermission('can_access_housekeeping', 6);
    setSetting('force_staff_2fa', '0');

    $staff = User::factory()->create(['rank' => 6]);

    $this->actingAs($staff)
        ->get('/housekeeping')
        ->assertOk();
});
