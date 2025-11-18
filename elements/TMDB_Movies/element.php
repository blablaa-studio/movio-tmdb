<?php

namespace MovioTmdb;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "MovioTmdb\\TmdbMovies",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class TmdbMovies extends \Breakdance\Elements\Element
{
    static function uiIcon()
    {
        return 'SquareIcon';
    }

    static function tag()
    {
        return 'div';
    }

    static function tagOptions()
    {
        return [];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'TMDB Movies';
    }

    static function className()
    {
        return 'bde-tmdb-movies';
    }

    static function category()
    {
        return 'other';
    }

    static function badge()
    {
        return false;
    }

    static function slug()
    {
        return __CLASS__;
    }

    static function template()
    {
        return file_get_contents(__DIR__ . '/html.twig');
    }

    static function defaultCss()
    {
        return file_get_contents(__DIR__ . '/default.css');
    }

    static function defaultProperties()
    {
        return false;
    }

    static function defaultChildren()
    {
        return false;
    }

    static function cssTemplate()
    {
        $template = file_get_contents(__DIR__ . '/css.twig');
        return $template;
    }

    static function designControls()
    {
        return [];
    }

    static function contentControls()
    {
        return [c(
        "param",
        "Paramètre",
        [c(
        "api_key",
        "API Key TMDB",
        [],
        ['type' => 'text', 'layout' => 'vertical', 'placeholder' => 'Entrez votre API KEY'],
        false,
        false,
        [],
        
      ), c(
        "endpoint",
        "Endpoint",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'popular', 'text' => 'Film populaire'], ['value' => 'top_rated', 'text' => 'Top Rated'], ['value' => 'now_playing', 'text' => 'Films au cinéma']]],
        false,
        false,
        [],
        
      ), c(
        "limit",
        "Nombre de résultats",
        [],
        ['type' => 'number', 'layout' => 'vertical', 'rangeOptions' => ['min' => 1, 'max' => 20, 'step' => 1]],
        false,
        false,
        [],
        
      )],
        ['type' => 'section', 'layout' => 'vertical'],
        false,
        false,
        [],
        
      )];
    }

    static function settingsControls()
    {
        return [];
    }

    static function dependencies()
    {
        return false;
    }

    static function settings()
    {
        return false;
    }

    static function addPanelRules()
    {
        return false;
    }

    static public function actions()
    {
        return false;
    }

    static function nestingRule()
    {
        return ['type' => 'final'];
    }

    static function spacingBars()
    {
        return false;
    }

    static function attributes()
    {
        return false;
    }

    static function experimental()
    {
        return false;
    }

    static function availableIn()
    {
        return ['breakdance'];
    }


    static function order()
    {
        return 0;
    }

    static function dynamicPropertyPaths()
    {
        return false;
    }

    static function additionalClasses()
    {
        return false;
    }

    static function projectManagement()
    {
        return false;
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return false;
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return false;
    }

    /**
     * Rendu SSR pour le builder
     */
    static function ssr($propertiesData, $parentPropertiesData = [], $isBuilder = false, $repeaterItemNodeId = null)
    {
        $api_key = $propertiesData['content']['param']['api_key'] ?? '';
        $endpoint = $propertiesData['content']['param']['endpoint'] ?? 'popular';
        $limit = $propertiesData['content']['param']['limit'] ?? 10;

        // Si on est dans le builder, afficher une preview
        if ($isBuilder) {
            ob_start();
            ?>
            <div style="padding: 20px; background: #e3f2fd; border: 2px dashed #2196f3; border-radius: 8px; text-align: center;">
                <p style="margin: 0; color: #1976d2; font-weight: 600;">📽️ Élément TMDB Movies</p>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                    <?php if (!empty($api_key)): ?>
                        Endpoint: <strong><?php echo esc_html($endpoint); ?></strong> |
                        Limit: <strong><?php echo esc_html($limit); ?></strong> films<br>
                        <em>Les films s'afficheront sur le front-end</em>
                    <?php else: ?>
                        <span style="color: #d32f2f;">⚠️ Veuillez configurer votre clé API TMDB</span>
                    <?php endif; ?>
                </p>
            </div>
            <?php
            return ob_get_clean();
        }

        // Sur le front-end, retourner null pour utiliser le template Twig normal
        return null;
    }

    /**
     * Récupérer les films depuis l'API TMDB
     */
    static function getTmdbMovies($api_key, $endpoint = 'popular', $limit = 10)
    {
        if (empty($api_key)) {
            return null;
        }

        // Construire l'URL de l'API
        $url = sprintf(
            'https://api.themoviedb.org/3/movie/%s?api_key=%s&language=fr-FR&page=1',
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
    }
}
