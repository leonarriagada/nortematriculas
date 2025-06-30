<?php
require_once 'config/config.php';

define('DB_HOST', 'localhost');
define('DB_NAME', 'php_mariculas');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_URL', 'http://localhost/matriculas');
define('ITEMS_PER_PAGE', 10);

// Clave secreta para tokens JWT
define('JWT_SECRET', 'S6uQV<`&+Q+pxh-*,GwS;Pr2rd9.,£M3|5b"GGgTvqHWwf9/HM');