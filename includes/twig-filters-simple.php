<?php
namespace MovioTmdb;

/**
 * Fonctions Twig simplifiées pour l'élément TMDB Movies
 *
 * NOTE: This approach doesn't work with Breakdance's Twig implementation.
 * Breakdance\Render\Twig class doesn't expose addFunction() method.
 * Custom Twig functions need to be registered through Breakdance's Plugin API.
 * This file is kept for reference but the code is disabled.
 */

/*
// This code is disabled - addFunction() doesn't exist on Breakdance\Render\Twig
add_action('init', function() {
    if (!class_exists('\Breakdance\Render\Twig')) {
        return;
    }

    $twig = \Breakdance\Render\Twig::getInstance();

    // Fonction pour récupérer les données TMDB
    $twig->addFunction(
        new \Twig\TwigFunction('tmdb_get_content', function($config) {
            $api_key = $config['api_key'] ?? '';
            if (empty($api_key)) {
                return null;
            }

            $endpoint = $config['endpoint'] ?? 'popular';
            $limit = $config['limit'] ?? 10;
            $content_type = $config['content_type'] ?? 'movie';

            // Construire l'URL de l'API
            $url = sprintf(
                'https://api.themoviedb.org/3/%s/%s?api_key=%s&language=fr-FR&page=1',
                $content_type,
                $endpoint,
                $api_key
            );

            // Vérifier le cache
            $cache_key = 'tmdb_' . md5($url);
            $cached = get_transient($cache_key);

            if ($cached !== false) {
                return $cached;
            }

            // Appel API
            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if (is_wp_error($response)) {
                return null;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!$data || !isset($data['results'])) {
                return null;
            }

            // Mettre en cache pour 1 heure
            set_transient($cache_key, $data, HOUR_IN_SECONDS);

            return $data;
        })
    );
}, 100);
*/
