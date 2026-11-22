<?php declare(strict_types=1);

return [
    'view' => 'http_error_pages::errors.show',
    'show_exception_details' => false,
    'statuses' => [
        'default' => [
            'icon' => 'exclamation', 'accent' => 'gray',
            'title' => ['en' => 'Something went wrong', 'fr' => 'Une erreur est survenue', 'es' => 'Algo salió mal', 'ar' => 'حدث خطأ ما'],
            'description' => ['en' => 'An unexpected error occurred. Please try again later.', 'fr' => 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.', 'es' => 'Ocurrió un error inesperado. Inténtalo de nuevo más tarde.', 'ar' => 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى لاحقاً.'],
        ],
        400 => ['icon' => 'exclamation', 'accent' => 'warning', 'title' => ['en' => 'Bad request', 'fr' => 'Requête invalide'], 'description' => ['en' => 'The request could not be understood.', 'fr' => 'La requête n’a pas pu être comprise.']],
        401 => ['icon' => 'lock', 'accent' => 'warning', 'title' => ['en' => 'Sign in required', 'fr' => 'Connexion requise'], 'description' => ['en' => 'Please sign in to continue.', 'fr' => 'Veuillez vous connecter pour continuer.']],
        403 => ['icon' => 'shield', 'accent' => 'warning', 'title' => ['en' => 'Access forbidden', 'fr' => 'Accès interdit'], 'description' => ['en' => 'You do not have permission to access this page.', 'fr' => 'Vous n’avez pas l’autorisation d’accéder à cette page.']],
        404 => ['icon' => 'search', 'accent' => 'gray', 'title' => ['en' => 'Page not found', 'fr' => 'Page introuvable'], 'description' => ['en' => 'We could not find the page you requested.', 'fr' => 'La page demandée est introuvable.']],
        405 => ['icon' => 'exclamation', 'accent' => 'warning', 'title' => ['en' => 'Method not allowed', 'fr' => 'Méthode non autorisée'], 'description' => ['en' => 'This request method is not supported here.', 'fr' => 'Cette méthode de requête n’est pas prise en charge ici.']],
        419 => ['icon' => 'lock', 'accent' => 'warning', 'title' => ['en' => 'Page expired', 'fr' => 'Page expirée'], 'description' => ['en' => 'The security token expired. Refresh the page and try again.', 'fr' => 'Le jeton de sécurité a expiré. Actualisez la page et réessayez.']],
        429 => ['icon' => 'clock', 'accent' => 'warning', 'title' => ['en' => 'Too many requests', 'fr' => 'Trop de requêtes'], 'description' => ['en' => 'Please wait a moment before trying again.', 'fr' => 'Veuillez patienter un instant avant de réessayer.']],
        500 => ['icon' => 'server', 'accent' => 'danger', 'title' => ['en' => 'Server error', 'fr' => 'Erreur du serveur'], 'description' => ['en' => 'Something went wrong on our end. Please try again shortly.', 'fr' => 'Une erreur est survenue de notre côté. Veuillez réessayer dans quelques instants.']],
        503 => ['icon' => 'server', 'accent' => 'danger', 'title' => ['en' => 'Service unavailable', 'fr' => 'Service indisponible'], 'description' => ['en' => 'This service is temporarily unavailable.', 'fr' => 'Ce service est temporairement indisponible.']],
    ],
];
