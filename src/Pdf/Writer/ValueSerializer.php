<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Writer;

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
use InvalidArgumentException;
use LogicException;

final class ValueSerializer
{
    private const DELIMITERS = '()<>[]{}/%';

    private const MAXIMUM_DECIMAL_PLACES = 40;

    public function serialize(PdfValue $value): string
    {
        if ($value instanceof PdfName) {
            return $this->name($value->value());
        }

        if ($value instanceof PdfInteger) {
            return (string) $value->value();
        }

        if ($value instanceof PdfReal) {
            return $this->real($value->value());
        }

        if ($value instanceof PdfReference) {
            return $value->number() . ' ' . $value->generation() . ' R';
        }

        if ($value instanceof PdfArray) {
            return $this->array($value);
        }

        if ($value instanceof PdfDictionary) {
            return $this->dictionary($value);
        }

        if ($value instanceof PdfString) {
            return $this->string($value);
        }

        if ($value instanceof PdfBoolean) {
            return $value->value() ? 'true' : 'false';
        }

        if ($value instanceof PdfNull) {
            return 'null';
        }

        throw new LogicException('Unknown PDF value class: ' . get_class($value) . '.');
    }

    private function name(string $name): string
    {
        $output = '/';
        $length = strlen($name);

        for ($i = 0; $i < $length; $i++) {
            $byte = $name[$i];
            $code = ord($byte);

            $mustEscape = $code < 0x21
                || $code > 0x7E
                || $byte === '#'
                || strpos(self::DELIMITERS, $byte) !== false;

            $output .= $mustEscape ? '#' . strtoupper(str_pad(dechex($code), 2, '0', STR_PAD_LEFT)) : $byte;
        }

        return $output;
    }

    private function real(float $value): string
    {
        if (is_nan($value) || is_infinite($value)) {
            throw new InvalidArgumentException(
                sprintf('A real has no PDF representation: %s.', var_export($value, true))
            );
        }

        // The fewest places that read back as the same double, always with %F: %f follows the
        // locale (595,276 under pt_BR) and a plain cast may write an exponent, which PDF lacks.
        $text = null;

        for ($places = 0; $places <= self::MAXIMUM_DECIMAL_PLACES; $places++) {
            $attempt = sprintf('%.' . $places . 'F', $value);

            if ((float) $attempt === $value) {
                $text = $attempt;
                break;
            }
        }

        if ($text === null) {
            throw new InvalidArgumentException(sprintf(
                'A real cannot be written positionally in %d places: %s.',
                self::MAXIMUM_DECIMAL_PLACES,
                var_export($value, true)
            ));
        }

        if ((string) $value === '-0') {
            $text = '-' . $text;
        }

        if (strpos($text, '.') === false) {
            $text .= '.0';
        }

        return $text;
    }

    private function array(PdfArray $array): string
    {
        $pieces = [];

        foreach ($array->items() as $item) {
            $pieces[] = $this->serialize($item);
        }

        return '[' . implode(' ', $pieces) . ']';
    }

    private function dictionary(PdfDictionary $dictionary): string
    {
        $output = '<<';

        foreach ($dictionary->keys() as $key) {
            $entry = $dictionary->get($key);

            if ($entry === null) {
                throw new LogicException(sprintf('Dictionary key "%s" has no value.', $key));
            }

            $output .= $this->name($key) . ' ' . $this->serialize($entry) . ' ';
        }

        return rtrim($output, ' ') . '>>';
    }

    private function string(PdfString $string): string
    {
        if ($string->isIllegible()) {
            throw new InvalidArgumentException(
                'An illegible hexadecimal string (an old signature\'s /Contents) is never written back.'
            );
        }

        return $string->isHex() ? '<' . $string->text() . '>' : '(' . $string->text() . ')';
    }
}
