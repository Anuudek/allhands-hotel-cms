<?php

namespace App\Http\Controllers\User;

use App\Contracts\Rcon;
use App\Emulator\Contracts\PlayerSettingsRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountSettingsFormRequest;
use App\Services\User\SessionService;
use App\Support\AuthenticatedUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function edit(PlayerSettingsRepository $settings): View
    {
        $user = AuthenticatedUser::current();

        return view('user.settings.account', [
            'user' => $user,
            'canChangeName' => $settings->canChangeName($user),
        ]);
    }

    public function sessionLogs(Request $request, SessionService $sessionService): View
    {
        $sessions = $sessionService->fetchSessionLogs($request);

        return view('user.settings.session-logs', [
            'logs' => $sessions,
        ]);
    }

    public function update(AccountSettingsFormRequest $request, Rcon $rcon): RedirectResponse
    {
        $user = AuthenticatedUser::from($request);
        $connected = $rcon->isConnected();

        if (! $connected && $user->online) {
            return back()->withErrors('You must be offline to change your account settings');
        }

        // The motto is nullable in validation; clearing it means an empty string.
        $motto = (string) $request->input('motto');
        $mail = $request->input('mail');

        // Both fields move together, so a failure part way through cannot leave
        // the account with one of them changed.
        DB::transaction(function () use ($user, $mail, $motto, $rcon, $connected): void {
            $changes = [];

            if ($user->mail !== $mail) {
                $changes['mail'] = $mail;
            }

            if ($user->motto !== $motto) {
                $changes['motto'] = $motto;

                // Only a live emulator needs telling. Offline, and on drivers
                // with no RCON bridge at all, the database write is the whole
                // change.
                if ($connected) {
                    $rcon->setMotto($user, $motto);
                }
            }

            if ($changes !== []) {
                $user->update($changes);
            }
        });

        return redirect()->route('settings.account.show')->with('success', __('Your account settings has been updated'));
    }

    public function twoFactor(): View
    {
        return view('user.settings.two-factor');
    }
}
