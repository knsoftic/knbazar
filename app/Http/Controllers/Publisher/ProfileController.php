<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\PublisherProfileRequest;
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
        $user = $request->user()->load('publisherProfile');

        return view('publisher.profile', [
            'user' => $user,
            'profile' => $user->publisherProfile,
            'countries' => Countries::all(),
            'seo' => $seo->defaults('Publisher profile')->noindex(),
        ]);
    }

    public function update(PublisherProfileRequest $request, ImageService $images): RedirectResponse
    {
        $user = $request->user()->load('publisherProfile');
        $profile = $user->publisherProfile ?? $user->publisherProfile()->make()->forceFill(['status' => 'pending']);

        $user->fill($request->safe()->only(['name', 'email', 'phone']));
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $old = $user->avatar;
            $user->avatar = $images->storeSquare($request->file('avatar'), 'avatars', (int) config('marketplace.image.avatar_size'), field: 'avatar');
            $images->delete($old);
        }
        $user->save();

        $profile->fill($request->safe()->only(['display_name', 'company', 'bio', 'website', 'whatsapp', 'country']));
        if ($request->hasFile('logo')) {
            $old = $profile->logo;
            $profile->logo = $images->storeSquare($request->file('logo'), 'publishers', 400, field: 'logo');
            $images->delete($old);
        }
        $profile->save();

        return back()->with('success', 'Your publisher profile has been updated.');
    }
}
