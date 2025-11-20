<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

/**
 * Helper class for working with integration configurations
 * Provides methods to retrieve and validate integration settings
 */
class IntegrationHelper
{
    /**
     * Gets all available integrations from config
     */
    public static function getAvailableIntegrations(): array
    {
        return Config::get('integrations.available', []);
    }

    /**
     * Gets integration configuration by name
     */
    public static function getIntegration(string $integrationName): ?array
    {
        $integrations = self::getAvailableIntegrations();

        return $integrations[$integrationName] ?? null;
    }

    /**
     * Checks if integration requires Pipedream
     */
    public static function requiresPipedream(string $integrationName): bool
    {
        $integration = self::getIntegration($integrationName);

        return $integration['requires_pipedream'] ?? false;
    }

    /**
     * Gets integrations by category
     */
    public static function getIntegrationsByCategory(string $category): array
    {
        $integrations = self::getAvailableIntegrations();

        return array_filter($integrations, function ($integration) use ($category) {
            return ($integration['category'] ?? '') === $category;
        });
    }

    /**
     * Gets all integration categories
     */
    public static function getCategories(): array
    {
        return Config::get('integrations.categories', []);
    }

    /**
     * Validates if integration name is valid
     */
    public static function isValidIntegration(string $integrationName): bool
    {
        return self::getIntegration($integrationName) !== null;
    }
}
