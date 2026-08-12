<?php

declare(strict_types=1);

namespace Nodus\DevTools\Analysis;

/**
 * Checks that declared namespaces and imports are spelled exactly like the
 * PSR-4 prefix and the directories below it.
 *
 * Reads the source rather than the autoloader, because that is where the bug
 * hides: `namespace app\Models;` loads fine on a case-insensitive filesystem
 * and only fatals on Linux. Arch tests, which work on loaded classes, cannot
 * see it.
 */
final class NamespaceCasing
{
    /**
     * @param  string  $prefix  PSR-4 prefix without a trailing backslash
     * @param  string  $directory  The directory it maps to
     * @return list<NamespaceViolation>
     */
    public static function scan(string $prefix, string $directory): array
    {
        $prefix = trim($prefix, '\\');

        if (! is_dir($directory)) {
            return [];
        }

        $violations = [];

        foreach (self::phpFiles($directory) as $file) {
            $source = file_get_contents($file);

            if ($source === false) {
                continue;
            }

            $relative = ltrim(str_replace(rtrim($directory, '/'), '', $file), '/');

            foreach (self::inspect($source, $prefix, $relative, $file) as $violation) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    /**
     * @return list<NamespaceViolation>
     */
    private static function inspect(string $source, string $prefix, string $relative, string $file): array
    {
        $violations = [];
        $tokens = token_get_all($source);
        $expected = self::expectedNamespace($prefix, $relative);

        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $declared = self::readName($tokens, $index);

                if ($declared !== null && $declared !== $expected) {
                    $violations[] = new NamespaceViolation(
                        $file,
                        $token[2],
                        sprintf('namespace %s; expected: %s', $declared, $expected),
                    );
                }

                continue;
            }

            if ($token[0] === T_USE) {
                $imported = self::readName($tokens, $index);

                if ($imported === null) {
                    continue;
                }

                $root = explode('\\', $imported)[0];

                // Only a casing difference counts — a trait import or a foreign
                // package has a different root and is none of our business.
                if ($root !== $prefix && strcasecmp($root, $prefix) === 0) {
                    $violations[] = new NamespaceViolation(
                        $file,
                        $token[2],
                        sprintf('use %s; expected root spelling: %s', $imported, $prefix),
                    );
                }
            }
        }

        return $violations;
    }

    private static function expectedNamespace(string $prefix, string $relative): string
    {
        $directory = trim(str_replace('/', '\\', dirname($relative)), '\\.');

        return $directory === '' ? $prefix : $prefix.'\\'.$directory;
    }

    /**
     * Reads the name behind `namespace`/`use`, stopping at the first separator.
     *
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     */
    private static function readName(array $tokens, int $index): ?string
    {
        $name = '';
        $count = count($tokens);

        for ($i = $index + 1; $i < $count; $i++) {
            $token = $tokens[$i];

            // '{' group import, ';' / ',' end, '(' use in a closure.
            if (is_string($token)) {
                break;
            }

            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                if ($name !== '') {
                    break;
                }

                continue;
            }

            // `use function App\helper;` — the name follows the keyword.
            if (in_array($token[0], [T_FUNCTION, T_CONST], true)) {
                continue;
            }

            $name .= $token[1];
        }

        $name = trim($name, '\\');

        return $name === '' ? null : $name;
    }

    /**
     * @return list<string>
     */
    private static function phpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
