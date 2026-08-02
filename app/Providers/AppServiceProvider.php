<?php

namespace App\Providers;

use App\Services\AI\AnthropicProvider;
use App\Services\AI\ExplicationProviderInterface;
use App\Services\AI\GeminiProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExplicationProviderInterface::class, function ($app) {
            $provider = config('services.ai_explication.provider', 'gemini');

            return match ($provider) {
                'anthropic' => new AnthropicProvider(
                    config('services.anthropic.api_key'),
                    config('services.anthropic.model'),
                ),
                'gemini' => new GeminiProvider(
                    config('services.gemini.api_key'),
                    config('services.gemini.model'),
                ),
                default => throw new \InvalidArgumentException("Fournisseur IA inconnu : {$provider}"),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}