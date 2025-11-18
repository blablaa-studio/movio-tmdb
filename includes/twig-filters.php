<?php
namespace MovioTmdb;

/**
 * Ajouter les filtres et fonctions Twig personnalisés pour TMDB
 */

// Ajouter les fonctions Twig
add_filter('breakdance_element_actions_filter', function($actions) {
    // Fonction pour récupérer les données TMDB
    $actions[] = [
        'onPropertyChange' => [
            'comparisons' => [
                [
                    'path' => 'content.api_config.api_key',
                    'operand' => 'is set',
                    'value' => ''
                ]
            ],
            'actions' => [
                [
                    'action' => 'custom',
                    'callback' => 'tmdb_refresh_data'
                ]
            ]
        ]
    ];
    return $actions;
}, 100, 1);

/*
 * NOTE: This approach doesn't work with Breakdance's Twig implementation.
 * Breakdance\Render\Twig class doesn't expose addFunction() or addFilter() methods.
 * Custom Twig functions need to be registered through Breakdance's Plugin API.
 * This code is disabled but kept for reference.
 */

/*
// This code is disabled - addFunction() and addFilter() don't exist on Breakdance\Render\Twig
add_action('init', function() {
    if (!class_exists('\Breakdance\Render\Twig')) {
        return;
    }

    $twig = \Breakdance\Render\Twig::getInstance();

        // Fonction pour récupérer les données TMDB
        $twig->addFunction(
            new \Twig\TwigFunction('tmdb_get_content', function($config) {
                $api_key = $config['api_config']['api_key'] ?? '';
                if (empty($api_key)) {
                    return null;
                }

                require_once BDTMDB_PATH . 'includes/tmdb-api.php';
                $api = new TMDB_API($api_key);
                return $api->get_content($config);
            })
        );

        // Fonction pour formater les résultats
        $twig->addFunction(
            new \Twig\TwigFunction('tmdb_format_results', function($data, $config) {
                $api_key = $config['api_config']['api_key'] ?? '';
                if (empty($api_key) || !$data) {
                    return [];
                }

                require_once BDTMDB_PATH . 'includes/tmdb-api.php';
                $api = new TMDB_API($api_key);
                return $api->format_results($data, $config);
            })
        );

        // Filtre pour rendre une carte de film/série
        $twig->addFilter(
            new \Twig\TwigFilter('tmdb_render_card', function($item, $config, $variant = 'grid') {
                $display = $config['display_options'] ?? [];

                $show_poster = !empty($display['show_poster']);
                $show_title = !empty($display['show_title']);
                $show_rating = !empty($display['show_rating']);
                $show_overview = !empty($display['show_overview']);
                $show_release_date = !empty($display['show_release_date']);
                $show_genres = !empty($display['show_genres']);
                $link_to_tmdb = !empty($display['link_to_tmdb']);

                ob_start();
                ?>
                <div class="tmdb-card tmdb-card--<?php echo esc_attr($variant); ?>" data-tmdb-id="<?php echo esc_attr($item['id']); ?>">
                    <?php if ($link_to_tmdb): ?>
                        <a href="<?php echo esc_url($item['tmdb_url']); ?>" target="_blank" rel="noopener noreferrer" class="tmdb-card__link">
                    <?php endif; ?>

                    <?php if ($show_poster && !empty($item['poster_path'])): ?>
                        <div class="tmdb-card__poster">
                            <img src="<?php echo esc_url($item['poster_path']); ?>"
                                 alt="<?php echo esc_attr($item['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="tmdb-card__content">
                        <?php if ($show_title): ?>
                            <h3 class="tmdb-card__title"><?php echo esc_html($item['title']); ?></h3>
                        <?php endif; ?>

                        <?php if ($show_rating && $item['vote_average'] > 0): ?>
                            <div class="tmdb-card__rating">
                                <span class="tmdb-card__rating-stars">
                                    <?php
                                    $rating = $item['vote_average'];
                                    $stars = round($rating / 2, 1);
                                    $full_stars = floor($stars);
                                    $half_star = ($stars - $full_stars) >= 0.5;

                                    for ($i = 0; $i < $full_stars; $i++): ?>
                                        <span class="star star--full">★</span>
                                    <?php endfor;

                                    if ($half_star): ?>
                                        <span class="star star--half">★</span>
                                    <?php endif;

                                    $empty_stars = 5 - ceil($stars);
                                    for ($i = 0; $i < $empty_stars; $i++): ?>
                                        <span class="star star--empty">☆</span>
                                    <?php endfor; ?>
                                </span>
                                <span class="tmdb-card__rating-number"><?php echo esc_html($rating); ?>/10</span>
                                <span class="tmdb-card__rating-count">(<?php echo number_format_i18n($item['vote_count']); ?> votes)</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_genres && !empty($item['genres'])): ?>
                            <div class="tmdb-card__genres">
                                <?php foreach ($item['genres'] as $genre): ?>
                                    <span class="tmdb-card__genre"><?php echo esc_html($genre); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_release_date && !empty($item['release_date'])): ?>
                            <div class="tmdb-card__release-date">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item['release_date']))); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_overview && !empty($item['overview'])): ?>
                            <div class="tmdb-card__overview">
                                <?php echo esc_html($item['overview']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($link_to_tmdb): ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php
                return ob_get_clean();
            }, ['is_safe' => ['html']])
        );

        // Filtre pour rendre un item personnalisé (Post Loop Builder)
        $twig->addFilter(
            new \Twig\TwigFilter('tmdb_render_item', function($item, $config) {
                // Dans le mode custom, on expose simplement les données
                // L'utilisateur peut utiliser les Global Blocks pour construire son layout

                // Stocker les données dans un contexte global pour les Global Blocks
                global $tmdb_current_item;
                $tmdb_current_item = $item;

                return '<!-- TMDB Item: ' . esc_html($item['title']) . ' -->';
            }, ['is_safe' => ['html']])
        );

        // Fonction pour obtenir l'item TMDB courant (utilisé dans les Global Blocks)
        $twig->addFunction(
            new \Twig\TwigFunction('tmdb_get_current_item', function() {
                global $tmdb_current_item;
                return $tmdb_current_item ?? null;
            })
        );

        // Filtre pour obtenir une propriété de l'item courant
        $twig->addFilter(
            new \Twig\TwigFilter('tmdb_get', function($property) {
                global $tmdb_current_item;
                if (!$tmdb_current_item) {
                    return '';
                }
                return $tmdb_current_item[$property] ?? '';
            })
        );

        // Filtre pour formater la note en étoiles
        $twig->addFilter(
            new \Twig\TwigFilter('tmdb_rating_stars', function($rating) {
                $stars = round($rating / 2, 1);
                $full_stars = floor($stars);
                $half_star = ($stars - $full_stars) >= 0.5;

                $output = '';
                for ($i = 0; $i < $full_stars; $i++) {
                    $output .= '<span class="star star--full">★</span>';
                }
                if ($half_star) {
                    $output .= '<span class="star star--half">★</span>';
                }
                $empty_stars = 5 - ceil($stars);
                for ($i = 0; $i < $empty_stars; $i++) {
                    $output .= '<span class="star star--empty">☆</span>';
                }

                return $output;
            }, ['is_safe' => ['html']])
        );
}, 100);
*/

// Ajouter les données dynamiques pour les Global Blocks
add_filter('breakdance_dynamic_data_providers', function($providers) {
    $providers[] = [
        'label' => 'TMDB',
        'callback' => function() {
            global $tmdb_current_item;
            if (!$tmdb_current_item) {
                return [];
            }

            return [
                [
                    'label' => 'Titre',
                    'value' => $tmdb_current_item['title'] ?? '',
                    'returnType' => 'text'
                ],
                [
                    'label' => 'Résumé',
                    'value' => $tmdb_current_item['overview'] ?? '',
                    'returnType' => 'text'
                ],
                [
                    'label' => 'Affiche',
                    'value' => $tmdb_current_item['poster_path'] ?? '',
                    'returnType' => 'image_url'
                ],
                [
                    'label' => 'Fond',
                    'value' => $tmdb_current_item['backdrop_path'] ?? '',
                    'returnType' => 'image_url'
                ],
                [
                    'label' => 'Note',
                    'value' => $tmdb_current_item['vote_average'] ?? 0,
                    'returnType' => 'number'
                ],
                [
                    'label' => 'Nombre de votes',
                    'value' => $tmdb_current_item['vote_count'] ?? 0,
                    'returnType' => 'number'
                ],
                [
                    'label' => 'Date de sortie',
                    'value' => $tmdb_current_item['release_date'] ?? '',
                    'returnType' => 'text'
                ],
                [
                    'label' => 'Lien TMDB',
                    'value' => $tmdb_current_item['tmdb_url'] ?? '',
                    'returnType' => 'url'
                ],
            ];
        }
    ];

    return $providers;
}, 100);
