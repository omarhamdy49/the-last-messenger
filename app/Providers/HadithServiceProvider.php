<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HadithServiceProvider extends ServiceProvider
{
    /**
     * Intentionally empty while the module is being rebuilt.
     *
     * The repository bindings and the `HadithVisibility` tag land here once
     * app/Domain/Hadith exists again. They cannot be declared earlier:
     * PublicVisibilityRegistry resolves every tagged provider eagerly, so
     * tagging a class that does not yet exist breaks the container at boot.
     */
    public function register(): void {}
}
