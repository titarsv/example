<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Setting;

class MaintenanceController extends Controller
{
    // Включить режим обслуживания
    public function down(Request $request)
    {
        $settings = $this->getMaintenanceSettings();
        $secret = 'admin-access';

        Artisan::call('down', [
            '--secret'  => $secret,
            '--retry'   => $request->input('retry', $settings['retry']),
            '--render'  => 'errors::maintenance',
        ]);

        $path = storage_path('framework/down');

        if (file_exists($path)) {
            $config = json_decode(file_get_contents($path), true);

            $config['template'] = str_replace(
                ['{message}', '{title}'],
                [$settings['message'], $settings['title'] ?? 'Техработы'],
                $config['template']
            );

            file_put_contents($path, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return response()->json([
            'success' => true,
            'secret'  => $secret,
            'url'     => url("/{$secret}")
        ]);
    }

    private function getMaintenanceSettings()
    {
        $defaults = [
            'message' => 'We are currently performing scheduled maintenance. Please check back soon.',
            'retry' => 60
        ];

        $settings = Setting::where('key', 'maintenance_settings')->first();

        if ($settings) {
            $savedSettings = json_decode($settings->value, true);
            return array_merge($defaults, $savedSettings['maintenance'] ?? []);
        }

        return $defaults;
    }

    // Выключить режим обслуживания
    public function up()
    {
        Artisan::call('up');

        return response()->json(['success' => true]);
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'retry' => 'nullable|integer|min:10|max:3600',
        ]);

        $settings = [
            'maintenance' => [
                'message' => $validated['message'] ?? null,
                'retry' => $validated['retry'] ?? null,
            ]
        ];

        // Remove null values to keep existing settings
        $settings['maintenance'] = array_filter($settings['maintenance'], function($value) {
            return $value !== null;
        });

        // Get existing settings
        $existingSettings = $this->getMaintenanceSettings();

        // Merge with existing settings
        $mergedSettings = array_merge($existingSettings, $settings['maintenance']);

        // Save to database
        Setting::updateOrCreate(
            ['key' => 'maintenance_settings'],
            ['value' => json_encode(['maintenance' => $mergedSettings])]
        );

        return response()->json([
            'success' => true,
            'message' => 'Maintenance settings saved successfully',
            'settings' => $mergedSettings
        ]);
    }
}
