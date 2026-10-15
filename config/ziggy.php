<?php

return [
    /*
     * Rutele expuse clientului.
     *
     * Implicit, directiva @routes serializează ÎNTREAGA hartă a aplicației în
     * HTML-ul fiecărei pagini — inclusiv /admin/items/{record}/edit, /horizon/api/*,
     * /pulse și ruta PUT de upload în storage. Sunt ~25 KB pe fiecare încărcare
     * și, mai important, o listă de ținte oferită oricui deschide sursa paginii
     * sau oricărui crawler.
     *
     * Lista albă de mai jos conține exact rutele apelate din Vue. Orice rută nouă
     * folosită în frontend trebuie adăugată aici explicit.
     */
    'only' => [
        'public.home',
        'login',
        'register',
        'logout',
        'google.redirect',
        'auth.username.store',
        'settings.destroy.code',
        'dashboard',
        'settings.edit',
        'settings.update',
        'report.store',
        'settings.avatar',
        'settings.avatar.destroy',
        'settings.password',
        'settings.destroy',
        'subject.show',
        'gaps.index',
        'gaps.store',
        'gaps.update',
        'gaps.destroy',
        'course.index',
        'course.show',
        'course.complete',
        'formulas.index',
        'formulas.progress',
        'essays.index',
        'essays.show',
        'essays.store',
        'essays.attempt',
        'subjects.edit',
        'subjects.update',
        'practice.start',
        'practice.play',
        'practice.hint',
        'practice.answer',
        'mock.start',
        'mock.submit',
    ],
];
