<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixerCustomFixers\Analyzer\Analysis\ArrayArgumentAnalysis;
use PhpCsFixerCustomFixers\Analyzer\ArrayAnalyzer;
use PhpCsFixerCustomFixers\TokenRemover;

final class NoDuplicatedArrayKeyFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Duplicated array keys must be removed.',
            [new CodeSample('<?php
$x = [
    "foo" => 1,
    "bar" => 2,
    "foo" => 3,
];
')]
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$tokens[$index]->isGivenKind([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN])) {
                continue;
            }

            $this->fixArray($tokens, $index);
        }
    }

    private function fixArray(Tokens $tokens, int $index): void
    {
        $arrayAnalyzer = new ArrayAnalyzer();

        $keys = [];
        /** @var ArrayArgumentAnalysis $arrayArgumentAnalysis */
        foreach (\array_reverse($arrayAnalyzer->getArguments($tokens, $index)) as $arrayArgumentAnalysis) {
            if ($arrayArgumentAnalysis->getKeyEndIndex() === null) {
                continue;
            }
            $key = $this->getMeaningfulContent($tokens, $arrayArgumentAnalysis->getKeyStartIndex(), $arrayArgumentAnalysis->getKeyEndIndex());
            if (isset($keys[$key])) {
                $endIndex = $tokens->getNextMeaningfulToken($arrayArgumentAnalysis->getArgumentEndIndex());
                if ($tokens[$endIndex + 1]->isWhitespace() && Preg::match('/^\h+$/', $tokens[$endIndex + 1]->getContent()) === 1) {
                    $endIndex++;
                }
                $tokens->overrideRange($arrayArgumentAnalysis->getKeyStartIndex(), $endIndex, [new Token('')]);
                TokenRemover::removeWithLinesIfPossible($tokens, $arrayArgumentAnalysis->getKeyStartIndex());
                continue;
            }
            $keys[$key] = true;
        }
    }

    private function getMeaningfulContent(Tokens $tokens, int $startIndex, int $endIndex): string
    {
        $content = '';
        for ($index = $startIndex; $index <= $endIndex; $index++) {
            if ($tokens[$index]->isWhitespace()) {
                continue;
            }
            if ($tokens[$index]->isComment()) {
                continue;
            }
            $content .= $tokens[$index]->getContent();
        }

        return $content;
    }
}
