<?php

declare(strict_types=1);

use Nodus\DevTools\Analysis\NamespaceCasing;

/**
 * @return list<string>
 */
function scanFixtures(): array
{
    return array_map(strval(...), NamespaceCasing::scan('App', fixturePath('app')));
}

it('finds a namespace whose casing differs from the path', function () {
    expect(implode("\n", scanFixtures()))
        ->toContain('Services/BrokenCase.php')
        ->toContain('namespace app\Services; expected: App\Services');
});

it('finds an import with a wrongly cased root', function () {
    expect(implode("\n", scanFixtures()))
        ->toContain('Support/WrongImport.php')
        ->toContain('use app\Models\User;');
});

it('finds a namespace that does not match its directory', function () {
    expect(implode("\n", scanFixtures()))
        ->toContain('Legacy/Misplaced.php')
        ->toContain('expected: App\Legacy');
});

it('reports exactly the three known violations and nothing else', function () {
    expect(scanFixtures())->toHaveCount(3);
});

it('leaves correct and deeply nested namespaces alone', function () {
    $found = implode("\n", scanFixtures());

    expect($found)
        ->not->toContain('Models/User.php')
        ->not->toContain('Nested/Deep/Item.php');
});

it('accepts a file without a namespace', function () {
    // Global helper files (app/helpers.php) are convention, not a violation.
    expect(implode("\n", scanFixtures()))->not->toContain('helpers.php');
});

it('mistakes neither a trait use nor a closure use for an import', function () {
    expect(implode("\n", scanFixtures()))->not->toContain('Closures/UsesClosure.php');
});

it('returns an empty list for a missing directory', function () {
    expect(NamespaceCasing::scan('App', fixturePath('does-not-exist')))->toBe([]);
});

it('compares case-sensitively — a differently spelled prefix matches nothing', function (string $prefix, int $expected) {
    expect(NamespaceCasing::scan($prefix, fixturePath('app')))->toHaveCount($expected);
})->with([
    ['App', 3],
    // With 'APP' nothing in the fixture tree is correct: six declarations and
    // five imports differ.
    ['APP', 11],
]);
