<?php

return [
    'deadline' => [
        'min_business_day' => 2,
        'max_business_day' => 16,
        'default_business_day' => 2,

        // Decreto 1990 de 2016: últimos 2 dígitos del documento → ordinal del día hábil (2..16)
        // Se mantiene en config (no en código) para evitar "magic numbers" y permitir ajuste controlado.
        'last_two_digits_to_business_day' => [
            ['min' => 0, 'max' => 7, 'day' => 2],
            ['min' => 8, 'max' => 14, 'day' => 3],
            ['min' => 15, 'max' => 21, 'day' => 4],
            ['min' => 22, 'max' => 28, 'day' => 5],
            ['min' => 29, 'max' => 35, 'day' => 6],
            ['min' => 36, 'max' => 42, 'day' => 7],
            ['min' => 43, 'max' => 49, 'day' => 8],
            ['min' => 50, 'max' => 56, 'day' => 9],
            ['min' => 57, 'max' => 63, 'day' => 10],
            ['min' => 64, 'max' => 69, 'day' => 11],
            ['min' => 70, 'max' => 75, 'day' => 12],
            ['min' => 76, 'max' => 81, 'day' => 13],
            ['min' => 82, 'max' => 87, 'day' => 14],
            ['min' => 88, 'max' => 93, 'day' => 15],
            ['min' => 94, 'max' => 99, 'day' => 16],
        ],
    ],
];

