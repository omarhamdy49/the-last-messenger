<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * ExportGateway
 *
 * Single entry point for all module export operations.
 * Resolves the correct module adapter by key and delegates
 * exporter construction to the adapter — keeping this class and
 * the controller free from domain-module imports.
 *
 * Mirrors the DashboardService provider-registry pattern.
 */
class ExportGateway
{
    /** @var array<string, ExportableModuleInterface> */
    private array $modules = [];

    /**
     * @param  ExportableModuleInterface[]  $modules
     */
    public function __construct(array $modules = [])
    {
        foreach ($modules as $module) {
            $this->modules[$module->getModuleKey()] = $module;
        }
    }

    /**
     * Build and return a configured exporter for the given module and parameters.
     *
     * @throws BusinessRuleViolationException when the module key is unrecognised
     * @throws BusinessRuleViolationException when the format is not supported by the module
     */
    public function buildExporter(
        string $moduleKey,
        string $format,
        string $language = 'en',
        array $filters = [],
        array $columns = [],
    ): BaseExporter {
        $module = $this->resolveModule($moduleKey);

        $this->assertFormatSupported($module, $moduleKey, $format);

        return $module->buildExporter($language, $filters, $columns);
    }

    /**
     * Return available and default column definitions for a module.
     *
     * @return array{available_columns: string[], default_columns: string[]}
     *
     * @throws BusinessRuleViolationException when the module key is unrecognised
     */
    public function getColumnsForModule(string $moduleKey): array
    {
        $module = $this->resolveModule($moduleKey);

        return [
            'available_columns' => $module->getAvailableColumns(),
            'default_columns' => $module->getDefaultColumns(),
        ];
    }

    /**
     * Keys of all registered modules.
     *
     * @return string[]
     */
    public function getRegisteredModuleKeys(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Resolve a registered module or throw.
     *
     * @throws BusinessRuleViolationException
     */
    private function resolveModule(string $moduleKey): ExportableModuleInterface
    {
        return $this->modules[$moduleKey]
            ?? throw BusinessRuleViolationException::forRule(
                ruleName: 'UnknownExportModule',
                message: "No exporter registered for module: [{$moduleKey}]",
                context: ['module_key' => $moduleKey, 'registered' => array_keys($this->modules)],
            );
    }

    /**
     * Assert that the requested format is supported by the module.
     *
     * @throws BusinessRuleViolationException
     */
    private function assertFormatSupported(
        ExportableModuleInterface $module,
        string $moduleKey,
        string $format,
    ): void {
        if (! in_array($format, $module->getSupportedFormats(), true)) {
            throw BusinessRuleViolationException::forRule(
                ruleName: 'UnsupportedExportFormat',
                message: "Format [{$format}] is not supported by module [{$moduleKey}]. "
                    .'Supported: '.implode(', ', $module->getSupportedFormats()),
                context: [
                    'module_key' => $moduleKey,
                    'requested_format' => $format,
                    'supported_formats' => $module->getSupportedFormats(),
                ],
            );
        }
    }
}
