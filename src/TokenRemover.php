<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers;

use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class TokenRemover
{
    public static function removeWithLinesIfPossible(Tokens $tokens, int $index): void
    {
        $tokens->clearTokenMergeSurroundingWhitespaceRemoveLineIfLeftEmpty($index);
    }
}
