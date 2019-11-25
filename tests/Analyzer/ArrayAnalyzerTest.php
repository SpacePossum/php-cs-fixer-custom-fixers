<?php

declare(strict_types = 1);

namespace Tests\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayArgumentAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ArrayAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Analyzer\ArrayAnalyzer
 */
final class ArrayAnalyzerTest extends TestCase
{
    public function testForNotArray(): void
    {
        $analyzer = new ArrayAnalyzer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 3 is not an array.');

        $analyzer->getArguments(Tokens::fromCode('<?php $a;$b;$c;'), 3);
    }

    /**
     * @dataProvider provideGettingArrayArgumentsCases
     */
    public function testGettingArrayArguments(array $expected, string $code, int $index): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new ArrayAnalyzer();

        static::assertSame(\serialize($expected), \serialize($analyzer->getArguments($tokens, $index)));
    }

    public static function provideGettingArrayArgumentsCases(): iterable
    {
        yield [
            [],
            '<?php $a = [];',
            5,
        ];

        yield [
            [new ArrayArgumentAnalysis(null, null, 6, 6)],
            '<?php $a = [42];',
            5,
        ];

        yield [
            [new ArrayArgumentAnalysis(6, 6, 10, 10)],
            '<?php $a = [4 => 42];',
            5,
        ];

        yield [
            [new ArrayArgumentAnalysis(7, 7, 11, 11)],
            '<?php $a = array(4 => 42);',
            5,
        ];

        yield [
            [
                new ArrayArgumentAnalysis(7, 7, 11, 11),
                new ArrayArgumentAnalysis(14, 14, 18, 18),
                new ArrayArgumentAnalysis(21, 21, 25, 25),
                new ArrayArgumentAnalysis(28, 28, 32, 32),
            ],
            '<?php $a = [
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4
            ];',
            5,
        ];

        yield [
            [
                new ArrayArgumentAnalysis(7, 13, 17, 25),
                new ArrayArgumentAnalysis(28, 34, 38, 49),
            ],
            '<?php $a = [
                ("Foo" . "Bar") => $this->getContent("Foo", "Bar"),
                ("Foo" . "Baz") => $this->getContent("Foo", "Baz", false),
            ];',
            5,
        ];

        yield [
            [
                new ArrayArgumentAnalysis(7, 7, 11, 19),
                new ArrayArgumentAnalysis(22, 22, 26, 35),
            ],
            '<?php $a = [
                1 => [11, 12, 13],
                2 => array(21, 22, 23),
            ];',
            5,
        ];

        yield [
            [
                new ArrayArgumentAnalysis(7, 7, 11, 17),
                new ArrayArgumentAnalysis(20, 20, 24, 50),
            ],
            '<?php $a = [
                1 => foo(1, 2),
                2 => $bar = function ($x, $y) { return max($x, $y); },
            ];',
            5,
        ];
    }
}
