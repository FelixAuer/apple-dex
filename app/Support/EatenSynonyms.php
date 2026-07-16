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
}
