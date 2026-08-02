<?php

namespace App\ValueObject;

final readonly class ThemeColors
{
    public function __construct(
        public string $bg,
        public string $surface,
        public string $text,
        public string $textMuted,
        public string $accent,
        public string $accentStrong,
    ) {
    }
}
