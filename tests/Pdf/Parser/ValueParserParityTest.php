<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Parser;

use ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException;
use ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException;
use ArrudaFreitas\GovbrPades\Pdf\Parser\Cursor;
use ArrudaFreitas\GovbrPades\Pdf\Parser\ValueParser;
use ArrudaFreitas\GovbrPades\Test\Pdf\CanonicalForm;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Parser\ValueParser
 *
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Parser\Cursor
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfArray
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfBoolean
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfDictionary
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfName
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfNull
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfReal
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfReference
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfString
 */
final class ValueParserParityTest extends TestCase
{
    private const GOLDEN = __DIR__ . '/../../fixtures/pdf/golden/values.jsonl';

    private const EXPECTED_CASES = 1812;

    private const CATEGORIES = [
        InvalidPdfException::class => '!INVALIDO',
        PdfLimitExceededException::class => '!LIMITE',
    ];

    public function testEveryValueOfTheCorpusReadsAsInTheDesafio(): void
    {
        $cases = 0;
        $differences = [];

        foreach ($this->golden() as $case) {
            $cases++;

            $parser = new ValueParser();

            try {
                $result = CanonicalForm::of($parser->parse(new Cursor($case['text'])));
            } catch (InvalidPdfException | PdfLimitExceededException $refusal) {
                $result = self::CATEGORIES[get_class($refusal)];
            }

            $warnings = count($parser->warnings());

            if ($result !== $case['value'] || $warnings !== $case['warnings']) {
                $differences[] = sprintf(
                    "%s object %d:\n  Desafio: %s (%d warning(s))\n  here:    %s (%d warning(s))",
                    $case['fixture'],
                    $case['object'],
                    self::shorten($case['value']),
                    $case['warnings'],
                    self::shorten($result),
                    $warnings
                );
            }
        }

        self::assertSame(self::EXPECTED_CASES, $cases, 'The golden does not hold the number of values it should.');
        self::assertSame([], $differences);
    }

    /**
     * @return iterable<array{fixture: string, object: int, text: string, value: string, warnings: int}>
     */
    private function golden(): iterable
    {
        $lines = file(self::GOLDEN, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new RuntimeException('Cannot read the golden ' . self::GOLDEN . '.');
        }

        foreach ($lines as $number => $line) {
            $entry = json_decode($line, true);
            $text = is_array($entry) ? base64_decode((string) ($entry['texto'] ?? ''), true) : false;

            if (
                !is_array($entry)
                || $text === false
                || !is_string($entry['fixture'] ?? null)
                || !is_int($entry['objeto'] ?? null)
                || !is_string($entry['valor'] ?? null)
                || !is_int($entry['avisos'] ?? null)
            ) {
                throw new RuntimeException(sprintf('Line %d of the golden is malformed.', $number + 1));
            }

            yield [
                'fixture' => $entry['fixture'],
                'object' => $entry['objeto'],
                'text' => $text,
                'value' => $entry['valor'],
                'warnings' => $entry['avisos'],
            ];
        }
    }

    private static function shorten(string $canonical): string
    {
        return strlen($canonical) > 160 ? substr($canonical, 0, 160) . '…' : $canonical;
    }
}
