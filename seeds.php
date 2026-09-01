<?php

function bookPlaceholderSvg(string $title, string $startColor = '#5e2a2a', string $endColor = '#1f3d2c'): string
{
    $safeTitle = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $title);
    $safeTitle = trim($safeTitle);
    if ($safeTitle === '') {
        $safeTitle = 'Book';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="840" viewBox="0 0 600 840">'
        . '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0%" stop-color="'.$startColor.'"/><stop offset="100%" stop-color="'.$endColor.'"/></linearGradient></defs>'
        . '<rect width="600" height="840" fill="url(#g)" rx="28"/>'
        . '<circle cx="300" cy="280" r="130" fill="rgba(255,255,255,0.12)"/>'
        . '<path d="M170 610c28-104 84-150 130-150s102 46 130 150" fill="rgba(255,255,255,0.18)"/>'
        . '<text x="50%" y="62%" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="34" font-weight="700">'.htmlspecialchars($safeTitle, ENT_QUOTES, 'UTF-8').'</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

$books = [
    [
        'id' => 1,
        'title' => 'Palpasa Café',
        'author' => 'Narayan Wagle',
        'price' => 450,
        'img' => bookPlaceholderSvg('Palpasa Café', '#5e2a2a', '#1f3d2c'),
        'description' => 'A moving portrait of life, migration, and longing set against the backdrop of Nepal’s changing social landscape.',
    ],
    [
        'id' => 2,
        'title' => 'Summer Love',
        'author' => 'Subin Bhattarai',
        'price' => 380,
        'img' => bookPlaceholderSvg('Summer Love', '#7a5743', '#2d4b3d'),
        'description' => 'A heartfelt story about youthful emotion, memory, and the bittersweet beauty of first love.',
    ],
    [
        'id' => 3,
        'title' => 'Seto Dharti',
        'author' => 'Amar Neupane',
        'price' => 500,
        'img' => bookPlaceholderSvg('Seto Dharti', '#4d4d72', '#1f3d2c'),
        'description' => 'A literary journey through identity, loss, and resilience, rooted in a deeply human narrative.',
    ],
    [
        'id' => 4,
        'title' => 'Karnali Blues',
        'author' => 'Buddhisagar',
        'price' => 420,
        'img' => bookPlaceholderSvg('Karnali Blues', '#6a4b2f', '#244734'),
        'description' => 'An evocative read exploring the beauty and hardship of the Karnali region and the people who call it home.',
    ],
    [
        'id' => 5,
        'title' => 'The Alchemist',
        'author' => 'Paulo Coelho',
        'price' => 600,
        'img' => bookPlaceholderSvg('The Alchemist', '#7a3f3f', '#2f4d47'),
        'description' => 'A timeless tale of purpose, destiny, and courage, following a shepherd on a life-changing journey.',
    ],
    [
        'id' => 6,
        'title' => 'Atomic Habits',
        'author' => 'James Clear',
        'price' => 950,
        'img' => bookPlaceholderSvg('Atomic Habits', '#5c4f2f', '#233d31'),
        'description' => 'A practical guide to forming habits that stick and creating systems that support lasting personal growth.',
    ],
    [
        'id' => 7,
        'title' => 'The Kite Runner',
        'author' => 'Khaled Hosseini',
        'price' => 820,
        'img' => bookPlaceholderSvg('The Kite Runner', '#815033', '#173b2d'),
        'description' => 'A powerful story of friendship, guilt, and redemption set against the changing political landscape of Afghanistan.',
    ],
    [
        'id' => 8,
        'title' => 'Who Will Cry When You Die?',
        'author' => 'Robin Sharma',
        'price' => 560,
        'img' => bookPlaceholderSvg('Who Will Cry When You Die?', '#5d3b50', '#1c413b'),
        'description' => 'A motivating collection of life lessons that encourage self-improvement, purpose, and inner peace.',
    ],
    [
        'id' => 9,
        'title' => 'Sapiens',
        'author' => 'Yuval Noah Harari',
        'price' => 1100,
        'img' => bookPlaceholderSvg('Sapiens', '#4a4a4a', '#254f43'),
        'description' => 'An expansive and fascinating account of human history, evolution, and the forces that shaped civilisation.',
    ],
    [
        'id' => 10,
        'title' => 'The Pragmatic Programmer',
        'author' => 'Andrew Hunt',
        'price' => 1300,
        'img' => bookPlaceholderSvg('The Pragmatic Programmer', '#2e466a', '#1e3d32'),
        'description' => 'A practical guide for developers who want to improve their craft, thinking, and delivery in real-world projects.',
    ],
];

$adminUser = [
    'id' => 1,
    'name' => 'Admin',
    'email' => 'admin@mini-pasal.local',
    'username' => 'admin',
    'password' => 'admin123',
    'role' => 'admin',
];

return [
    'books' => $books,
    'admin' => $adminUser,
];
