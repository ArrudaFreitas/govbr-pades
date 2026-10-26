<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Parser;

use ArrudaFreitas\GovbrPades\Pdf\Parser\Cursor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Parser\Cursor
 */
final class CursorTest extends TestCase
{
    public function testStartsWhereItIsToldAndMovesOnlyWhenAsked(): void
    {
        $cursor = new Cursor('0123456789', 3);

        self::assertSame(3, $cursor->position());
        self::assertSame(10, $cursor->length());

        $cursor->advance(2);
        self::assertSame(5, $cursor->position());

        $cursor->seek(9);
        self::assertSame(9, $cursor->position());
        self::assertFalse($cursor->atEnd());

        $cursor->advance(1);
        self::assertTrue($cursor->atEnd());
    }

    public function testSkipsEveryPdfWhitespaceCharacter(): void
    {
        $cursor = new Cursor("\x00\x09\x0A\x0C\x0D\x20\x0Bx");
        $cursor->skipWhitespace();

        self::assertSame(6, $cursor->position(), 'Vertical tab is not PDF whitespace.');
    }

    public function testSkipsCommentsAsWhitespace(): void
    {
        $text = "  % the catalogue\r\n% another\n  /Type";
        $cursor = new Cursor($text);
        $cursor->skipWhitespace();

        self::assertSame(strpos($text, '/Type'), $cursor->position());
    }

    public function testACommentMayRunToTheEndOfTheText(): void
    {
        $cursor = new Cursor('1 % no line break after this');
        $cursor->advance(1);
        $cursor->skipWhitespace();

        self::assertTrue($cursor->atEnd());
    }

    public function testSkippingWhitespaceWhereThereIsNoneDoesNotMove(): void
    {
        $cursor = new Cursor('/Type');
        $cursor->skipWhitespace();

        self::assertSame(0, $cursor->position());
    }

    public function testPeekingNearTheEndReturnsWhatIsLeftInsteadOfFailing(): void
    {
        $cursor = new Cursor('ab', 1);

        self::assertSame('b', $cursor->peek(2));
        self::assertSame('b', $cursor->peekAt(0, 1));
        self::assertSame('', $cursor->peekAt(1, 1));
        self::assertSame(1, $cursor->position());

        $cursor->advance(1);
        self::assertSame('', $cursor->peek());
    }

    public function testConsumeIfMovesOnlyOnAMatch(): void
    {
        $cursor = new Cursor('endobj');

        self::assertFalse($cursor->consumeIf('stream'));
        self::assertSame(0, $cursor->position());

        self::assertTrue($cursor->consumeIf('end'));
        self::assertSame(3, $cursor->position());
    }

    public function testMatchHereIsAnchoredAtThePosition(): void
    {
        $cursor = new Cursor('abc 12 0 R');

        self::assertNull($cursor->matchHere('(\d+) (\d+) R'));
        self::assertSame(0, $cursor->position());

        $cursor->seek(4);
        $match = $cursor->matchHere('(\d+) (\d+) R');

        self::assertSame(['12 0 R', '12', '0'], $match);
        self::assertTrue($cursor->atEnd());
    }

    public function testFindSearchesFromThePositionAndSaysNullWhenAbsent(): void
    {
        $cursor = new Cursor('<a> <b>', 2);

        self::assertSame(2, $cursor->find('>'));
        self::assertSame(4, $cursor->find('<'));
        self::assertNull($cursor->find('['));
        self::assertSame(2, $cursor->position());
    }

    public function testSliceReadsAnyPieceWithoutMoving(): void
    {
        $cursor = new Cursor('<< /Type >>', 6);

        self::assertSame('/Type', $cursor->slice(3, 5));
        self::assertSame(6, $cursor->position());
    }

    public function testSampleNeverDumpsControlBytes(): void
    {
        $cursor = new Cursor("x)\x00\n\x1Fabc", 1);

        self::assertSame(')...a', $cursor->sample(5));
    }
}
