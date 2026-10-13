<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\PasswordUpdateRequest;
use App\Http\Requests\Account\ProfileUpdateRequest;
use App\Services\ActivityLogger;
use App\Services\ImageService;
use App\Services\SeoService;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request, SeoService $seo): View
    {
        return view('account.profile', [
            'user' => $request->user(),
            'countries' => Countries::all(),
            'seo' => $seo->defaults('My profile')->noindex(),
        ]);
    }

    public function update(ProfileUpdateRequest $request, ImageService $images): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email', 'phone', 'country', 'address']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $old = $user->avatar;
            $user->avatar = $images->storeSquare($request->file('avatar'), 'avatars', (int) config('marketplace.image.avatar_size'), field: 'avatar');
            $images->delete($old);
        } elseif ($request->boolean('remove_avatar') && $user->avatar) {
            $images->delete($user->avatar);
            $user->avatar = null;
        }

        $user->save();

        return back()->with('success', 'Your profile has been updated.');
    }

    public function password(PasswordUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password')])->save();

        $logger->log('auth.password_changed', $user, 'Password changed', user: $user);

        return back()->with('success', 'Your password has been changed.');
    }
}
