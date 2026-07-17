<?php

namespace App\Support;

class EatenSynonyms
{
    private const WORDS = [
        'eaten',
        'munched',
        'consumed',
        'devoured',
        'nibbled',
        'sampled',
        'tasted',
        'scoffed',
        'gobbled',
        'savored',
        'crunched',
        'polished off',
    ];

    public static function pick(): string
    {
        return self::WORDS[array_rand(self::WORDS)];
    }

    /**
     * Pick several distinct synonyms at once, so a page doesn't repeat the same word everywhere.
     *
     * @return array<int, string>
     */
    public static function pickMany(int $count): array
    {
        $words = self::WORDS;
        shuffle($words);

        return array_slice($words, 0, $count);
    }
}
