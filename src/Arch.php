<?php

declare(strict_types=1);

namespace Nodus\DevTools;

use Nodus\DevTools\Analysis\NamespaceCasing;

/**
 * The Nodus stage-1 architecture rules, as Pest tests.
 *
 * Call from a test file so updates travel with the package version:
 *
 *     // tests/Arch/NodusBaselineTest.php
 *     Nodus\DevTools\Arch::baseline();
 *
 * Every rule is also callable on its own, so a project can drop one and keep
 * the rest. Requires Pest in the consuming project.
 */
final class Arch
{
    public const DEBUG_FUNCTIONS = ['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die'];

    /**
     * @param  string  $namespace  PSR-4 prefix of the application code
     * @param  string  $path  Matching directory, relative to the project root
     * @param  list<string>  $ignoring  Justified exceptions to the Pest presets
     */
    public static function baseline(string $namespace = 'App', string $path = 'app', array $ignoring = []): void
    {
        self::noDebugCalls($namespace);
        self::strictTypes($namespace);
        self::namespaceCasing($namespace, $path);
        self::httpOnlyInIntegrations($namespace);
        self::pestPresets($ignoring);
    }

    public static function noDebugCalls(string $namespace = 'App'): void
    {
        arch('nodus: no debug calls in application code')
            ->expect(self::DEBUG_FUNCTIONS)
            ->not->toBeUsedIn($namespace);
    }

    public static function strictTypes(string $namespace = 'App'): void
    {
        arch('nodus: strict types everywhere')
            ->expect($namespace)
            ->toUseStrictTypes();
    }

    /** Namespace casing matches the path, and imports use the exact root. */
    public static function namespaceCasing(string $namespace = 'App', string $path = 'app'): void
    {
        // Resolved out here: Pest binds the closure to the TestCase, where
        // `self::` would point at the test class.
        $directory = self::basePath().'/'.trim($path, '/');

        test('nodus: namespace casing matches the path', function () use ($namespace, $directory): void {
            $violations = NamespaceCasing::scan($namespace, $directory);

            expect(array_map(strval(...), $violations))->toBe([]);
        });
    }

    /** HTTP clients belong in `<Namespace>\Integrations`, behind a connector. */
    public static function httpOnlyInIntegrations(string $namespace = 'App'): void
    {
        arch('nodus: HTTP clients only in Integrations')
            ->expect(['Illuminate\Support\Facades\Http', 'GuzzleHttp\Client'])
            ->toOnlyBeUsedIn($namespace.'\Integrations');
    }

    /**
     * @param  list<string>  $ignoring  Functions a project deliberately needs
     */
    public static function pestPresets(array $ignoring = []): void
    {
        arch()->preset()->php()->ignoring($ignoring);
        arch()->preset()->security()->ignoring($ignoring);
    }

    private static function basePath(): string
    {
        if (function_exists('base_path')) {
            return base_path();
        }

        return getcwd() ?: '.';
    }
}
