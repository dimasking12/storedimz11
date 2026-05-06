<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * @var string 'cookie' (default, stateless) atau 'session'.
     * Cookie cocok karena legacy juga share session yang sama.
     */
    public string $csrfProtection = 'cookie';

    public bool $tokenRandomize = true;

    public string $tokenName = 'csrf_test_name';

    public string $headerName = 'X-CSRF-TOKEN';

    public string $cookieName = 'csrf_cookie_name';

    public int $expires = 7200;

    public bool $regenerate = true;

    public bool $redirect = true;

    /**
     * SameSite cookie attribute. Lax = aman utk request normal,
     * tetap blok cross-site form yang berbahaya.
     */
    public string $samesite = 'Lax';

    public array $redirectException = [];
}
