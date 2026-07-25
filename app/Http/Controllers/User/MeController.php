<?php

namespace App\Http\Controllers\User;

use App\Emulator\Contracts\RankRepository;
use App\Http\Controllers\Controller;
use App\Models\Articles\WebsiteArticle;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MeController extends Controller
{
    public function __invoke(): View
    {

        return view('user.me', [
            'onlineFriends' => Auth::user()?->getOnlineFriends(),
            'user' => Auth::user()?->load(['permission' => fn ($query) => app(RankRepository::class)->forDisplay($query)]),
            'articles' => WebsiteArticle::whereHas('user')->with('user:id,username,look')->latest()->take(5)->get(),
        ]);
    }
}
