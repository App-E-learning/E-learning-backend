<?php

// Config CORS explicite — absente du projet jusqu'ici, ce qui bloque
// silencieusement les appels cross-origin (ex: Expo web sur localhost:8081
// vers l'API sur localhost:8000). Sans effet sur l'app mobile native
// (iOS/Android), qui n'est pas soumise à CORS, mais nécessaire dès qu'on
// teste depuis un navigateur (Expo web) ou un simulateur qui envoie un
// header Origin.

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // À restreindre à l'URL exacte de l'app en production
    // (ex: 'https://app.mon-elearning.com') — '*' convient pour le
    // développement local uniquement.
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 'supports_credentials' doit rester false tant que 'allowed_origins'
    // vaut '*' (les deux sont incompatibles côté navigateur). L'app mobile
    // utilise un Bearer token (Sanctum en mode "personal access token"),
    // pas les cookies de session — donc pas besoin de credentials ici.
    'supports_credentials' => false,

];