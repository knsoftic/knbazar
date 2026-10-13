<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Services\ActivityLogger;
use App\Services\CatalogCache;
use App\Services\FileUploadService;
use App\Services\ImageService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Settings screens generated from config/settings.php. The "seo" group needs
 * manage_seo, "homepage" needs manage_homepage, all others manage_settings.
 */
class SettingController extends Controller
{
    public function __construct(protected SettingService $settings) {}

    public function seo(Request $request): View
    {
        return $this->render($request, 'seo');
    }

    public function edit(Request $request, ?string $group = null): View
    {
        $group ??= collect($this->allowedGroups($request))->first() ?? 'general';

        return $this->render($request, $group);
    }

    public function update(SettingsRequest $request, string $group, ImageService $images, FileUploadService $files, ActivityLogger $logger): RedirectResponse
    {
        $this->authorizeGroup($group);

        $values = [];

        foreach ($request->definitions() as $key => $definition) {
            if ($definition['type'] === 'image') {
                $current = $this->settings->get($key);

                if ($request->hasFile($key)) {
                    $values[$key] = ($definition['accept'] ?? null) === 'png,ico'
                        ? $files->storePublicRaw($request->file($key), 'settings')
                        : $images->store($request->file($key), 'settings', 1200)['path'];
                    $images->delete($current);
                } elseif ($request->boolean('remove_'.$key)) {
                    $images->delete($current);
                    $values[$key] = null;
                }

                continue;
            }

            $values[$key] = $request->validated($key);
        }

        $this->settings->setMany($values);
        CatalogCache::flush();

        $logger->log('settings.updated', null, 'Settings group "'.$group.'" updated', ['keys' => array_keys($values)]);

        return redirect()
            ->to($group === 'seo' ? route('admin.seo.edit') : route('admin.settings.edit', $group))
            ->with('success', config("settings.groups.$group.label").' settings saved.');
    }

    protected function render(Request $request, string $group): View
    {
        abort_unless(array_key_exists($group, config('settings.groups')), 404);
        $this->authorizeGroup($group);

        return view('admin.settings.edit', [
            'group' => $group,
            'groups' => array_intersect_key(config('settings.groups'), array_flip($this->allowedGroups($request))),
            'definitions' => $this->settings->definitionsFor($group),
            'settings' => $this->settings,
        ]);
    }

    protected function authorizeGroup(string $group): void
    {
        Gate::authorize($this->abilityFor($group));
    }

    protected function abilityFor(string $group): string
    {
        return match ($group) {
            'seo' => 'manage_seo',
            'homepage' => 'manage_homepage',
            default => 'manage_settings',
        };
    }

    /**
     * @return array<int, string>
     */
    protected function allowedGroups(Request $request): array
    {
        return array_values(array_filter(
            array_keys(config('settings.groups')),
            fn (string $group) => $request->user()->can($this->abilityFor($group))
        ));
    }
}
