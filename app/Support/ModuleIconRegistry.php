<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class ModuleIconRegistry
{
    /**
     * @var array<string, string|null>
     */
    private static array $cache = [];

    /**
     * @param array<string, mixed>|null $module
     */
    public function svgForModule(?array $module): ?string
    {
        if (!$module) {
            return null;
        }

        $icon = trim((string) ($module['icon'] ?? ''));
        $dir = trim((string) ($module['_dir'] ?? ''));

        if ($icon !== '' && $dir !== '') {
            return $this->svgFromPath(base_path('app/Modules/' . $dir . '/' . ltrim($icon, '/')));
        }

        $slug = trim((string) ($module['slug'] ?? ''));

        return $slug !== '' ? $this->svgForSlug($slug) : null;
    }

    public function svgForSlug(string $slug): ?string
    {
        $module = app(ModuleManager::class)->all()[$slug] ?? null;

        return $this->svgForModule($module);
    }

    public function svgForChannel(string $channel): ?string
    {
        $slug = match (strtolower(trim($channel))) {
            'wa_api' => 'whatsapp_api',
            'wa_web', 'wa_bro' => 'whatsapp_web',
            'social_dm', 'social' => 'social_media',
            'live_chat' => 'live_chat',
            'internal' => '__internal__',
            default => 'conversations',
        };

        if ($slug === '__internal__') {
            return $this->internalSvg();
        }

        return $this->svgForSlug($slug);
    }

    private function svgFromPath(string $path): ?string
    {
        if (array_key_exists($path, self::$cache)) {
            return self::$cache[$path];
        }

        if (!File::exists($path)) {
            return self::$cache[$path] = null;
        }

        $svg = trim((string) File::get($path));

        return self::$cache[$path] = ($svg !== '' ? $svg : null);
    }

    private function internalSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
  <circle cx="12" cy="8" r="4" fill="#6b5ce7"/>
  <path d="M5 20a7 7 0 0 1 14 0" fill="#c7c2ff"/>
</svg>
SVG;
    }
}
