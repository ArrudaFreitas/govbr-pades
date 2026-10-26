<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Parser;

use ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException;
use ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfArray;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfBoolean;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfDictionary;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfName;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfNull;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfReal;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfReference;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfString;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfValue;

final class ValueParser
{
    public const MAXIMUM_DEPTH = 256;

    private int $depth = 0;

    /** @var list<string> */
    private array $warnings = [];

    public function parse(Cursor $cursor): PdfValue
    {
        if ($this->depth >= self::MAXIMUM_DEPTH) {
            throw PdfLimitExceededException::nestingTooDeep(self::MAXIMUM_DEPTH, $cursor->position());
        }

        $this->depth++;

        try {
            return $this->dispatch($cursor);
        } finally {
            $this->depth--;
        }
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    private function dispatch(Cursor $cursor): PdfValue
    {
        $cursor->skipWhitespace();

        $one = $cursor->peek(1);
        $two = $cursor->peek(2);

        if ($two === '<<') {
            return $this->readDictionary($cursor);
        }

        if ($one === '[') {
            return $this->readArray($cursor);
        }

        if ($one === '/') {
            return $this->readName($cursor);
        }

        if ($one === '(') {
            return $this->readLiteralString($cursor);
        }

        if ($one === '<') {
            return $this->readHexString($cursor, false);
        }

        $value = $this->readNumberOrReference($cursor);

        if ($value !== null) {
            return $value;
        }

        $value = $this->readKeyword($cursor);

        if ($value !== null) {
            return $value;
        }

        throw InvalidPdfException::unrecognisedValue($cursor->position(), $cursor->sample(8));
    }

    private function readName(Cursor $cursor): PdfName
    {
        $match = $cursor->matchHere('/([^\x00\x09\x0A\x0C\x0D\x20()<>\[\]{}\/%]*)');

        if ($match === null) {
            throw InvalidPdfException::invalidName($cursor->position());
        }

        $name = $match[1];

        if (strpos($name, '#') !== false) {
            $name = (string) preg_replace_callback(
                '/#([0-9A-Fa-f]{2})/',
                static function (array $found): string {
                    return chr((int) hexdec($found[1]));
                },
                $name
            );
        }

        return PdfName::of($name);
    }

    private function readDictionary(Cursor $cursor): PdfDictionary
    {
        $cursor->advance(2);

        $entries = [];
        $repeated = [];
        $illegible = null;

        while (true) {
            $cursor->skipWhitespace();

            if ($cursor->consumeIf('>>')) {
                break;
            }

            if ($cursor->atEnd()) {
                throw InvalidPdfException::unterminatedDictionary();
            }

            if ($cursor->peek(1) !== '/') {
                if ($cursor->matchHere('(?=(?:endobj|endstream|stream)(?![A-Za-z0-9]))') !== null) {
                    $this->warnings[] = sprintf(
                        'Dictionary at byte %d does not close with ">>": closed where the next keyword starts,'
                        . ' as the reference does.',
                        $cursor->position()
                    );

                    break;
                }

                $garbageAt = $cursor->position();
                $discarded = $this->parse($cursor);

                $this->warnings[] = sprintf(
                    'Dictionary at byte %d: a value of type %s stood where a key name was expected; discarded,'
                    . ' as the reference does.',
                    $garbageAt,
                    self::describe($discarded)
                );

                continue;
            }

            $key = $this->readName($cursor)->value();

            if ($key !== 'Contents') {
                $value = $this->parse($cursor);
            } else {
                $cursor->skipWhitespace();

                if ($cursor->peek(1) === '<' && $cursor->peek(2) !== '<<') {
                    $value = $this->readHexString($cursor, true);

                    if ($illegible === null && $value->isIllegible()) {
                        $illegible = $value;
                    }
                } else {
                    $value = $this->parse($cursor);
                }
            }

            if (array_key_exists($key, $entries)) {
                $repeated[$key] = ($repeated[$key] ?? 1) + 1;
            }

            $entries[$key] = $value;
        }

        if ($illegible !== null) {
            $standing = $entries['Contents'] ?? null;

            if (!$standing instanceof PdfString || !$standing->isIllegible() || !self::isSignature($entries)) {
                throw InvalidPdfException::nonHexadecimalDigit(
                    (int) $illegible->illegibleAt(),
                    (string) $illegible->illegibleCharacter()
                );
            }

            $this->warnings[] = sprintf(
                "Old signature unreadable: the hexadecimal string starting at byte %d has the non-hexadecimal"
                . " character '%s'. It is kept as it is, neither read nor checked, and the document goes on, as"
                . ' the reference and the other measured readers do.',
                (int) $illegible->illegibleAt(),
                (string) $illegible->illegibleCharacter()
            );
        }

        if ($repeated === []) {
            return PdfDictionary::of($entries);
        }

        return PdfDictionary::withRepeatedKeys($entries, $repeated);
    }

    private function readArray(Cursor $cursor): PdfArray
    {
        $cursor->advance(1);

        $items = [];

        while (true) {
            $cursor->skipWhitespace();

            if ($cursor->consumeIf(']')) {
                break;
            }

            if ($cursor->atEnd()) {
                throw InvalidPdfException::unterminatedArray();
            }

            $items[] = $this->parse($cursor);
        }

        return PdfArray::of($items);
    }

    private function readNumberOrReference(Cursor $cursor): ?PdfValue
    {
        $whitespace = Cursor::WHITESPACE;

        $match = $cursor->matchHere('(\d+)' . $whitespace . '+(\d+)' . $whitespace . '+R(?![A-Za-z])');

        if ($match !== null) {
            return PdfReference::to((int) $match[1], (int) $match[2]);
        }

        $match = $cursor->matchHere('[-+]?(?:\d+\.\d*|\.\d+|\d+)(?:[eE][-+]?\d+)?');

        if ($match !== null) {
            $text = $match[0];

            return strpbrk($text, '.eE') !== false
                ? PdfReal::of((float) $text)
                : PdfInteger::of((int) $text);
        }

        return null;
    }

    private function readKeyword(Cursor $cursor): ?PdfValue
    {
        $match = $cursor->matchHere('(true|false|null)(?![A-Za-z])');

        if ($match === null) {
            return null;
        }

        if ($match[1] === 'null') {
            return PdfNull::instance();
        }

        return PdfBoolean::of($match[1] === 'true');
    }

    private function readLiteralString(Cursor $cursor): PdfString
    {
        $startsAt = $cursor->position();

        $cursor->advance(1);

        $buffer = '';
        $depth = 1;

        while (true) {
            if ($cursor->atEnd()) {
                throw InvalidPdfException::unterminatedLiteralString($startsAt, $depth);
            }

            $character = $cursor->peek(1);
            $cursor->advance(1);

            if ($character === '\\') {
                if ($cursor->atEnd()) {
                    throw InvalidPdfException::danglingEscape($cursor->position() - 1);
                }

                $buffer .= $character . $cursor->peek(1);
                $cursor->advance(1);
                continue;
            }

            if ($character === '(') {
                $depth++;
            }

            if ($character === ')') {
                $depth--;

                if ($depth === 0) {
                    break;
                }
            }

            $buffer .= $character;
        }

        return PdfString::literal($buffer);
    }

    private function readHexString(Cursor $cursor, bool $mayBeIllegible): PdfString
    {
        $startsAt = $cursor->position();
        $end = $cursor->find('>');

        if ($end === null) {
            throw InvalidPdfException::unterminatedHexString($startsAt);
        }

        $digits = $cursor->slice($startsAt + 1, $end - $startsAt - 1);

        $cursor->seek($end + 1);

        $digits = (string) preg_replace('/[\x00\x09\x0A\x0C\x0D\x20]+/', '', $digits);

        if ($digits !== '' && preg_match('/[^0-9A-Fa-f]/', $digits, $bad) === 1) {
            if (!$mayBeIllegible) {
                throw InvalidPdfException::nonHexadecimalDigit($startsAt, $bad[0]);
            }

            return PdfString::illegibleHex($digits, $startsAt, $bad[0]);
        }

        if (strlen($digits) % 2 === 1) {
            $digits .= '0';
        }

        return PdfString::hex($digits);
    }

    /**
     * @param array<array-key, PdfValue> $entries
     */
    private static function isSignature(array $entries): bool
    {
        $type = $entries['Type'] ?? null;

        if ($type instanceof PdfName) {
            $type = $type->value();
        } elseif ($type instanceof PdfString && !$type->isHex()) {
            $type = $type->text();
        }

        if (in_array($type, ['Sig', 'DocTimeStamp'], true)) {
            return true;
        }

        return ($entries['ByteRange'] ?? null) instanceof PdfArray;
    }

    private static function describe(PdfValue $value): string
    {
        $class = get_class($value);
        $short = substr($class, (int) strrpos($class, '\\') + 1);

        return strtolower((string) preg_replace('/^Pdf/', '', $short));
    }
}
