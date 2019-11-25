<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NoDuplicatedArrayKeyFixer
 */
final class NoDuplicatedArrayKeyFixerTest extends AbstractFixerTestCase
{
    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public static function provideFixCases(): iterable
    {
        yield [
            '<?php $x = [1, 1, 2, 2];',
        ];

        foreach (['1', '1.0', '"foo"', "'foo'", 'KEY_123', 'Constants::CONFIG_KEY', 'Library\\Constants::CONFIG_KEY'] as $duplicatedKey) {
            yield [
                \sprintf('<?php
                $x = [
                    "not_duplicated_key" => $v,
                    %s => $v,
                ];
            ', $duplicatedKey),
                \sprintf('<?php
                $x = [
                    %s => $v,
                    "not_duplicated_key" => $v,
                    %s => $v,
                ];
            ', $duplicatedKey, $duplicatedKey),
            ];
        }

        yield [
            '<?php $x = [2 => $e, 1 => $e];',
            '<?php $x = [1 => $e, 2 => $e, 1 => $e];',
        ];

        yield [
            '<?php
                $x = array(
                    2 => $e,
                    1 => $e,
                );
            ',
            '<?php
                $x = array(
                    1 => $e,
                    2 => $e,
                    1 => $e,
                );
            ',
        ];

        yield [
            '<?php
                $x = [
                    "bar" => 2,
                    "foo" => 3,
                ];
            ',
            '<?php
                $x = [
                    "foo" => 1,
                    "bar" => 2,
                    "foo" => 3,
                ];
            ',
        ];

        yield [
            '<?php
                $x = [
                    ("foo" + /* TODO: change to "baz" */ "bar") => 2,
                ];
            ',
            '<?php
                $x = [
                    ("foo" + "bar") => 2,
                    ("foo" + /* TODO: change to "baz" */ "bar") => 2,
                ];
            ',
        ];
    }
}
