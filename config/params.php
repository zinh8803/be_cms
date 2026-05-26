<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.accessTokenExpire' => 900, // 15 minutes
    'user.refreshTokenExpire' => 2592000, // 30 days
    'jwt.secret' =>$_ENV['JWT_SECRET'], // Fallback JWT secret key
];
