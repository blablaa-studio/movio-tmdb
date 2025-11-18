<?php

/**
 * Plugin Name: Movio TMDB
 * Plugin URI: https://blablaa.fr
 * Description: Module Breakdance pour TMDB
 * Author: Guillaume
 * Author URI: https://blablaa.fr
 * License: GPLv2
 * Text Domain: Breakdance
 * Domain Path: /languages/
 * Version: 0.0.1
 */

namespace MovioTmdb;

use function Breakdance\Util\getDirectoryPathRelativeToPluginFolder;

add_action('breakdance_loaded', function () {
    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/elements',
        'MovioTmdb',
        'element',
        'Movio Custom Elements',
        false
    );

    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/macros',
        'MovioTmdb',
        'macro',
        'Movio Custom Macros',
        false,
    );

    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/presets',
        'MovioTmdb',
        'preset',
        'Movio Custom Presets',
        false,
    );

    // Charger l'élément TMDB Movies
    $element_path = __DIR__ . '/elements/TMDB_Movies/element.php';
    if (file_exists($element_path)) {
        require_once $element_path;
    }

    // Charger le renderer personnalisé
    $renderer_path = __DIR__ . '/includes/element-renderer.php';
    if (file_exists($renderer_path)) {
        require_once $renderer_path;
    }
},
    // register elements before loading them
    9
);
