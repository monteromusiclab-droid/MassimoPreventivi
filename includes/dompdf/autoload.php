<?php
/**
 * Autoloader per DOMPDF - MM Preventivi
 * Carica automaticamente le classi DOMPDF e le sue dipendenze
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definisci il path base di DOMPDF
if (!defined('DOMPDF_DIR')) {
    define('DOMPDF_DIR', dirname(__FILE__));
}
if (!defined('DOMPDF_LIB_DIR')) {
    define('DOMPDF_LIB_DIR', DOMPDF_DIR . '/lib');
}

// Autoloader per DOMPDF e dipendenze
spl_autoload_register(function ($class) {
    // Namespace Dompdf
    if (strpos($class, 'Dompdf\\') === 0) {
        $relative_class = substr($class, strlen('Dompdf\\'));
        $file = DOMPDF_DIR . '/src/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    // Namespace FontLib
    if (strpos($class, 'FontLib\\') === 0) {
        $relative_class = substr($class, strlen('FontLib\\'));
        $file = DOMPDF_LIB_DIR . '/php-font-lib/src/FontLib/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    // Namespace Svg
    if (strpos($class, 'Svg\\') === 0) {
        $relative_class = substr($class, strlen('Svg\\'));
        $file = DOMPDF_LIB_DIR . '/php-svg-lib/src/Svg/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    // Namespace Sabberworm CSS Parser
    if (strpos($class, 'Sabberworm\\CSS\\') === 0) {
        $relative_class = substr($class, strlen('Sabberworm\\CSS\\'));
        $file = DOMPDF_LIB_DIR . '/php-css-parser/src/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    // Namespace Masterminds HTML5
    if (strpos($class, 'Masterminds\\') === 0) {
        $relative_class = substr($class, strlen('Masterminds\\'));

        // HTML5 è nella root di src
        if ($relative_class === 'HTML5') {
            $file = DOMPDF_LIB_DIR . '/html5-php/src/HTML5.php';
        } else {
            $file = DOMPDF_LIB_DIR . '/html5-php/src/' . str_replace('\\', '/', $relative_class) . '.php';
        }

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    return false;
});

// Carica Cpdf (backend PDF nativo di DOMPDF)
if (file_exists(DOMPDF_LIB_DIR . '/Cpdf.php')) {
    require_once DOMPDF_LIB_DIR . '/Cpdf.php';
}
