<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Bootstrap are nevoie de componentele RGB separate ale culorilor de temă
 * (ex: --bs-primary-rgb) pentru regulile care folosesc rgba(var(...), alpha).
 * Culorile de temă vin ca hex din .env.local, deci le convertim aici.
 */
final class ColorExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('hex2rgb', $this->hexToRgb(...)),
        ];
    }

    public function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (3 === \strlen($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (6 !== \strlen($hex) || !ctype_xdigit($hex)) {
            return '0, 0, 0';
        }

        return implode(', ', array_map(
            static fn (string $part): int => hexdec($part),
            str_split($hex, 2),
        ));
    }
}
