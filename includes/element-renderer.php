<?php
namespace MovioTmdb;

/**
 * Fonction de rendu TMDB pour Twig
 */
function render_tmdb_movies($api_key, $endpoint = 'popular', $limit = 10) {
    // Détecter si on est dans le builder
    $is_builder = (
        (function_exists('\\Breakdance\\isRequestFromBuilderSsr') && \Breakdance\isRequestFromBuilderSsr()) ||
        (isset($_POST['action']) && $_POST['action'] === 'breakdance_server_side_render')
    );

    // Dans le builder, toujours afficher une preview
    if ($is_builder) {
        if (empty($api_key)) {
            return '<div style="padding: 20px; background: #fff3cd; border: 2px dashed #ffc107; border-radius: 8px; text-align: center;">
                <p style="margin: 0; color: #856404; font-weight: 600;">⚠️ Configuration requise</p>
                <p style="margin: 10px 0 0 0; color: #856404; font-size: 14px;">
                    Veuillez configurer votre clé API TMDB
                </p>
            </div>';
        }

        return '<div style="padding: 20px; background: #e3f2fd; border: 2px dashed #2196f3; border-radius: 8px; text-align: center;">
            <p style="margin: 0; color: #1976d2; font-weight: 600;">📽️ Élément TMDB Movies</p>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                Endpoint: <strong>' . esc_html($endpoint) . '</strong> |
                Limit: <strong>' . esc_html($limit) . '</strong> films<br>
                <em style="font-size: 12px; color: #999;">Les films s\'afficheront sur le front-end</em>
            </p>
        </div>';
    }

    // Front-end: vérifier la clé API
    if (empty($api_key)) {
        return '<p class="tmdb-error">Clé API manquante</p>';
    }

    // Récupérer les données TMDB
    $tmdb_data = \MovioTmdb\TmdbMovies::getTmdbMovies($api_key, $endpoint, $limit);

    if (empty($tmdb_data) || !isset($tmdb_data['results']) || count($tmdb_data['results']) === 0) {
        return '<p class="tmdb-error">Aucun film trouvé</p>';
    }

    // Générer le HTML
    ob_start();
    ?>
    <div class="tmdb-movies-grid">
        <?php
        $movies = array_slice($tmdb_data['results'], 0, $limit);
        foreach ($movies as $movie):
        ?>
            <div class="tmdb-movie-card">
                <?php if (!empty($movie['poster_path'])): ?>
                    <img src="https://image.tmdb.org/t/p/w500<?php echo esc_attr($movie['poster_path']); ?>"
                         alt="<?php echo esc_attr($movie['title']); ?>"
                         class="tmdb-movie-poster">
                <?php endif; ?>
                <h3 class="tmdb-movie-title"><?php echo esc_html($movie['title']); ?></h3>
                <?php if (!empty($movie['vote_average'])): ?>
                    <div class="tmdb-movie-rating">
                        ⭐ <?php echo number_format($movie['vote_average'], 1); ?>/10
                    </div>
                <?php endif; ?>
                <?php if (!empty($movie['overview'])): ?>
                    <p class="tmdb-movie-overview"><?php echo esc_html($movie['overview']); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Enregistrer la fonction Twig dans Breakdance
add_action('breakdance_loaded', function() {
    \Breakdance\PluginsAPI\PluginsController::getInstance()->registerTwigFunction(
        'tmdb_render',
        '\MovioTmdb\render_tmdb_movies',
        '(api_key, endpoint, limit) => ""'
    );
});
