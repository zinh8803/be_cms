<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.accessTokenExpire' => 900, // 15 minutes
    'user.refreshTokenExpire' => 2592000, // 30 days
    'jwt.secret' => getenv('JWT_SECRET') ?: 'g5LzR9k4W2s8X6q1M3P0n7B9yA4c7D1e', // Fallback JWT secret key
];
