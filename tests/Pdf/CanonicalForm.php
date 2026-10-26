<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf;

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
use LogicException;

final class CanonicalForm
{
    public static function of(PdfValue $value): string
    {
        if ($value instanceof PdfNull) {
            return 'null';
        }

        if ($value instanceof PdfBoolean) {
            return $value->value() ? 'true' : 'false';
        }

        if ($value instanceof PdfInteger) {
            return 'i:' . $value->value();
        }

        if ($value instanceof PdfReal) {
            return 'f:' . sprintf('%.15g', $value->value());
        }

        if ($value instanceof PdfName) {
            return 'n:' . bin2hex($value->value());
        }

        if ($value instanceof PdfString) {
            if (!$value->isHex()) {
                return 's:' . bin2hex(self::literalBytes($value->text()));
            }

            return $value->isIllegible() ? 'x?:' . $value->text() : 'x:' . strtolower($value->text());
        }

        if ($value instanceof PdfReference) {
            return 'r:' . $value->number() . ' ' . $value->generation();
        }

        if ($value instanceof PdfArray) {
            return '[' . implode(',', array_map([self::class, 'of'], $value->items())) . ']';
        }

        if ($value instanceof PdfDictionary) {
            return self::dictionary($value);
        }

        throw new LogicException('Unknown PDF value class: ' . get_class($value) . '.');
    }

    private static function dictionary(PdfDictionary $dictionary): string
    {
        $keys = $dictionary->keys();
        usort($keys, 'strcmp');

        $parts = [];

        foreach ($keys as $key) {
            $entry = $dictionary->get($key);

            if ($entry === null) {
                throw new LogicException('A listed key has no value.');
            }

            $parts[] = 'n:' . bin2hex($key) . '=' . self::of($entry);
        }

        $repeated = $dictionary->repeatedKeys();
        $suffix = '';

        if ($repeated !== []) {
            ksort($repeated, SORT_STRING);
            $pieces = [];

            foreach ($repeated as $key => $times) {
                $pieces[] = bin2hex((string) $key) . '*' . $times;
            }

            $suffix = '#rep:' . implode(',', $pieces);
        }

        return '{' . implode(',', $parts) . '}' . $suffix;
    }

    private static function literalBytes(string $raw): string
    {
        $output = '';
        $length = strlen($raw);
        $escapes = ['n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\x0C",
            '(' => '(', ')' => ')', '\\' => '\\'];

        for ($i = 0; $i < $length; $i++) {
            $character = $raw[$i];

            if ($character === "\r") {
                $output .= "\n";

                if ($i + 1 < $length && $raw[$i + 1] === "\n") {
                    $i++;
                }

                continue;
            }

            if ($character !== '\\' || $i + 1 >= $length) {
                $output .= $character;
                continue;
            }

            $next = $raw[++$i];

            if (isset($escapes[$next])) {
                $output .= $escapes[$next];
            } elseif ($next === "\r" || $next === "\n") {
                if ($next === "\r" && $i + 1 < $length && $raw[$i + 1] === "\n") {
                    $i++;
                }
            } elseif ($next >= '0' && $next <= '7') {
                $octal = $next;

                while (strlen($octal) < 3 && $i + 1 < $length && $raw[$i + 1] >= '0' && $raw[$i + 1] <= '7') {
                    $octal .= $raw[++$i];
                }

                $output .= chr(((int) octdec($octal)) & 0xFF);
            } else {
                $output .= $next;
            }
        }

        return $output;
    }
}
