<?php

use Livewire\Blaze\Blaze;

/**
 * Blaze resolves every registered path through realpath() and silently skips
 * the ones that do not exist, so a mistyped directory costs a whole theme its
 * component compilation without raising anything.
 */
dataset('themes', ['atom', 'dusk']);

test('blaze compiles the components each theme actually ships', function (string $theme) {
    $directory = resource_path("themes/{$theme}/views/components");

    expect(is_dir($directory))->toBeTrue("Theme [{$theme}] has no components directory");

    $components = glob($directory . '/*.blade.php') ?: [];

    expect($components)->not->toBeEmpty("Theme [{$theme}] ships no Blade components");

    foreach ($components as $component) {
        expect(Blaze::optimize()->shouldCompile($component))
            ->toBeTrue(sprintf(
                'Blaze is not compiling [%s]; check the paths AppServiceProvider registers.',
                str_replace(resource_path() . '/', '', $component),
            ));
    }
})->with('themes');
