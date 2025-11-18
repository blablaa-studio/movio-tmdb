<?php
namespace MovioTmdb;

/**
 * Classe pour gérer les appels à l'API TMDB
 */
class TMDB_API {

    private $api_key;
    private $base_url = 'https://api.themoviedb.org/3';
    private $image_base_url = 'https://image.tmdb.org/t/p/';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Effectue une requête à l'API TMDB
     */
    private function request($endpoint, $params = []) {
        $params['api_key'] = $this->api_key;

        $url = $this->base_url . $endpoint . '?' . http_build_query($params);

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('TMDB API Error: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }

    /**
     * Récupère les données selon le mode configuré
     */
    public function get_content($config) {
        $cache_key = $this->get_cache_key($config);

        // Vérifier le cache si activé
        if (!empty($config['cache_settings']['enable_cache'])) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $results = null;
        $mode = $config['query_mode']['mode'] ?? 'endpoint';
        $content_type = $config['api_config']['content_type'] ?? 'movie';

        switch ($mode) {
            case 'endpoint':
                $results = $this->get_by_endpoint($config);
                break;
            case 'search':
                $results = $this->search($config);
                break;
            case 'genre':
                $results = $this->get_by_genre($config);
                break;
            case 'discover':
                $results = $this->discover($config);
                break;
        }

        if ($results && !empty($config['cache_settings']['enable_cache'])) {
            $cache_duration = intval($config['cache_settings']['cache_duration'] ?? 12) * HOUR_IN_SECONDS;
            set_transient($cache_key, $results, $cache_duration);
        }

        return $results;
    }

    /**
     * Récupère les données via un endpoint prédéfini
     */
    private function get_by_endpoint($config) {
        $endpoint_type = $config['query_mode']['endpoint'] ?? 'popular';
        $content_type = $config['api_config']['content_type'] ?? 'movie';
        $language = $config['api_config']['language'] ?? 'fr-FR';
        $region = $config['api_config']['region'] ?? '';

        $params = [
            'language' => $language,
        ];

        if ($region) {
            $params['region'] = $region;
        }

        // Construire l'endpoint
        $endpoint = '';
        switch ($endpoint_type) {
            case 'trending_day':
                $endpoint = "/trending/{$content_type}/day";
                break;
            case 'trending_week':
                $endpoint = "/trending/{$content_type}/week";
                break;
            case 'now_playing':
            case 'upcoming':
            case 'top_rated':
            case 'popular':
                $endpoint = "/{$content_type}/{$endpoint_type}";
                break;
            case 'airing_today':
            case 'on_the_air':
                $endpoint = "/tv/{$endpoint_type}";
                break;
        }

        return $this->request($endpoint, $params);
    }

    /**
     * Recherche de contenu
     */
    private function search($config) {
        $query = $config['query_mode']['search_query'] ?? '';
        if (empty($query)) {
            return null;
        }

        $content_type = $config['api_config']['content_type'] ?? 'movie';
        $language = $config['api_config']['language'] ?? 'fr-FR';
        $include_adult = !empty($config['query_mode']['include_adult']);

        $params = [
            'query' => $query,
            'language' => $language,
            'include_adult' => $include_adult ? 'true' : 'false',
        ];

        return $this->request("/search/{$content_type}", $params);
    }

    /**
     * Récupère les données par genre
     */
    private function get_by_genre($config) {
        $genre_id = $config['query_mode']['genre_id'] ?? '';
        if (empty($genre_id)) {
            return null;
        }

        $content_type = $config['api_config']['content_type'] ?? 'movie';
        $language = $config['api_config']['language'] ?? 'fr-FR';
        $sort_by = $config['query_mode']['sort_by'] ?? 'popularity.desc';

        $params = [
            'language' => $language,
            'sort_by' => $sort_by,
            'with_genres' => $genre_id,
        ];

        return $this->request("/discover/{$content_type}", $params);
    }

    /**
     * Découverte avec filtres avancés
     */
    private function discover($config) {
        $content_type = $config['api_config']['content_type'] ?? 'movie';
        $language = $config['api_config']['language'] ?? 'fr-FR';
        $sort_by = $config['query_mode']['sort_by'] ?? 'popularity.desc';

        $params = [
            'language' => $language,
            'sort_by' => $sort_by,
        ];

        // Ajouter les filtres de découverte
        $filters = $config['query_mode']['discover_filters'] ?? [];

        if (!empty($filters['with_genres'])) {
            $params['with_genres'] = $filters['with_genres'];
        }
        if (!empty($filters['without_genres'])) {
            $params['without_genres'] = $filters['without_genres'];
        }
        if (!empty($filters['year'])) {
            if ($content_type === 'movie') {
                $params['year'] = $filters['year'];
            } else {
                $params['first_air_date_year'] = $filters['year'];
            }
        }
        if (isset($filters['vote_average_gte']) && $filters['vote_average_gte'] !== '') {
            $params['vote_average.gte'] = $filters['vote_average_gte'];
        }
        if (isset($filters['vote_count_gte']) && $filters['vote_count_gte'] !== '') {
            $params['vote_count.gte'] = $filters['vote_count_gte'];
        }
        if (!empty($filters['with_runtime_gte'])) {
            $params['with_runtime.gte'] = $filters['with_runtime_gte'];
        }
        if (!empty($filters['with_runtime_lte'])) {
            $params['with_runtime.lte'] = $filters['with_runtime_lte'];
        }

        return $this->request("/discover/{$content_type}", $params);
    }

    /**
     * Récupère les détails d'un film/série
     */
    public function get_details($id, $content_type = 'movie', $language = 'fr-FR') {
        $params = [
            'language' => $language,
            'append_to_response' => 'credits,videos,images,keywords',
        ];

        return $this->request("/{$content_type}/{$id}", $params);
    }

    /**
     * Récupère la liste des genres
     */
    public function get_genres($content_type = 'movie', $language = 'fr-FR') {
        $params = [
            'language' => $language,
        ];

        return $this->request("/genre/{$content_type}/list", $params);
    }

    /**
     * Génère une URL d'image
     */
    public function get_image_url($path, $size = 'w500') {
        if (empty($path)) {
            return '';
        }

        return $this->image_base_url . $size . $path;
    }

    /**
     * Génère une clé de cache unique
     */
    private function get_cache_key($config) {
        return 'tmdb_' . md5(serialize($config));
    }

    /**
     * Vide le cache pour une configuration donnée
     */
    public function clear_cache($config) {
        $cache_key = $this->get_cache_key($config);
        delete_transient($cache_key);
    }

    /**
     * Formate les résultats pour l'utilisation dans le template
     */
    public function format_results($data, $config) {
        if (empty($data['results'])) {
            return [];
        }

        $limit = intval($config['query_mode']['limit'] ?? 12);
        $results = array_slice($data['results'], 0, $limit);

        $content_type = $config['api_config']['content_type'] ?? 'movie';
        $poster_size = $config['display_options']['poster_size'] ?? 'w342';

        // Charger les genres si nécessaire
        $genres_map = [];
        if (!empty($config['display_options']['show_genres'])) {
            $genres_data = $this->get_genres($content_type, $config['api_config']['language'] ?? 'fr-FR');
            if (!empty($genres_data['genres'])) {
                foreach ($genres_data['genres'] as $genre) {
                    $genres_map[$genre['id']] = $genre['name'];
                }
            }
        }

        $formatted = [];
        foreach ($results as $item) {
            $formatted_item = [
                'id' => $item['id'],
                'title' => $content_type === 'movie' ? ($item['title'] ?? '') : ($item['name'] ?? ''),
                'original_title' => $content_type === 'movie' ? ($item['original_title'] ?? '') : ($item['original_name'] ?? ''),
                'overview' => $item['overview'] ?? '',
                'poster_path' => !empty($item['poster_path']) ? $this->get_image_url($item['poster_path'], $poster_size) : '',
                'backdrop_path' => !empty($item['backdrop_path']) ? $this->get_image_url($item['backdrop_path'], 'w1280') : '',
                'vote_average' => round($item['vote_average'] ?? 0, 1),
                'vote_count' => $item['vote_count'] ?? 0,
                'popularity' => $item['popularity'] ?? 0,
                'release_date' => $content_type === 'movie' ? ($item['release_date'] ?? '') : ($item['first_air_date'] ?? ''),
                'genre_ids' => $item['genre_ids'] ?? [],
                'genres' => [],
                'tmdb_url' => "https://www.themoviedb.org/{$content_type}/{$item['id']}",
            ];

            // Ajouter les noms de genres
            if (!empty($formatted_item['genre_ids']) && !empty($genres_map)) {
                foreach ($formatted_item['genre_ids'] as $genre_id) {
                    if (isset($genres_map[$genre_id])) {
                        $formatted_item['genres'][] = $genres_map[$genre_id];
                    }
                }
            }

            // Tronquer le résumé si nécessaire
            if (!empty($config['display_options']['overview_length'])) {
                $max_length = intval($config['display_options']['overview_length']);
                if (mb_strlen($formatted_item['overview']) > $max_length) {
                    $formatted_item['overview'] = mb_substr($formatted_item['overview'], 0, $max_length) . '...';
                }
            }

            $formatted[] = $formatted_item;
        }

        return $formatted;
    }
}
