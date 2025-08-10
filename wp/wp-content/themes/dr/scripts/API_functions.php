<?php

/**
 * API Functions for WordPress Theme
 * 
 * This file contains all the REST API endpoints and functions for the theme.
 * It includes security measures, utilities and custom API routes.
 * 
 * Available Routes:
 * - GET /wp-json/api/v1/pages/{slug} - Retrieve page data by slug
 * - GET /wp-json/api/v1/eventos/{slug} - Retrieve event data by slug
 * 
 * Security Features:
 * - Blocks WordPress default API endpoints (/wp-json/wp/v2/*)
 * - Blocks index.php API access routes
 * - Only allows specific custom endpoints
 * 
 * @package WordPress
 * @subpackage Custom_API
 * @since 1.0.0
 */

// Utils
include('Utils.php');

/**
 * Block access to default WordPress REST API endpoints
 * 
 * This hook intercepts requests to the root API endpoints and returns a 404 status
 * to hide the default WordPress API routes for security purposes.
 * Allows specific custom endpoints to function while blocking base routes.
 * 
 * @param mixed           $result  Response to replace the requested version with
 * @param WP_REST_Server  $server  Server instance
 * @param WP_REST_Request $request Request used to generate the response
 * @return WP_REST_Response
 */
add_action('rest_pre_dispatch', function ($result, $server, $request) {
    $route = $request->get_route();

    // Lista de rutas base que queremos bloquear
    $blocked_routes = [
        '/',
        '/wp-json',
        '/wp-json/wp/v2',
        '/wp-json/wp/v2/',
        '/api/v1',
        '/api/v2'
    ];

    // Lista de patrones de rutas permitidas (usando regex)
    $allowed_patterns = [
        '/^\/api\/v1\/pages\/[A-Za-z0-9\-\s]+$/',
        '/^\/api\/v1\/posts\/[A-Za-z0-9\-\s,]+$/',
        '/^\/api\/v1\/evento\/[A-Za-z0-9\-\s,]+$/'
    ];

    // Verificar si la ruta está en la lista de bloqueadas
    if (in_array($route, $blocked_routes)) {
        $result = new WP_REST_Response();
        $result->set_status(404);
        return $result;
    }

    // Bloquear todas las rutas de WordPress por defecto excepto las permitidas
    if (strpos($route, '/wp-json/wp/') === 0 || strpos($route, '/wp/v2') !== false) {
        // Verificar si la ruta coincide con algún patrón permitido
        $is_allowed = false;
        foreach ($allowed_patterns as $pattern) {
            if (preg_match($pattern, $route)) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            $result = new WP_REST_Response();
            $result->set_status(404);
            return $result;
        }
    }

    return $result;
}, 10, 3);

/**
 * Additional security layer - Block WordPress default API via authentication errors
 * 
 * This provides an extra layer of protection by blocking access at the authentication level
 * for WordPress default API routes, even when accessed via index.php
 */
add_filter('rest_authentication_errors', function ($result) {
    // Si ya hay un error, no hacer nada
    if (is_wp_error($result)) {
        return $result;
    }

    // Obtener la URL solicitada
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    // Patrones de URLs a bloquear
    $blocked_patterns = [
        '/wp-json/wp/v2',
        '/index.php/wp-json/wp/v2',
        '/?rest_route=/wp/v2'
    ];

    // Verificar si algún patrón coincide
    foreach ($blocked_patterns as $pattern) {
        if (strpos($request_uri, $pattern) !== false) {
            return new WP_Error(
                'rest_forbidden',
                'API access denied',
                array('status' => 404)
            );
        }
    }

    return $result;
});

//////////////////////////////
/**
 * Get page data by slug
 * 
 * Retrieves a single page's data from WordPress pages by slug.
 * Returns basic page information including ID, content, title, etc.
 * 
 * @param WP_REST_Request $req Request object containing the page slug
 * @return WP_REST_Response Response object with page data or 404 if not found
 * 
 * @since 1.0.0
 * @example GET /wp-json/api/v1/pages/home
 * @example GET /wp-json/api/v1/pages/about-us
 */
function api_page_by_slug(WP_REST_Request $req)
{
    $params = [];
    if (isset($req)) {
        $params = $req->get_params();
    }
    // Buscar la página por slug
    $post = get_posts(array(
        'name' => $params['slug'],
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 1,
        'no_found_rows' => true
    ));

    // Validar que se proporcionó un slug
    if (empty($params['slug'])) {
        return new WP_REST_Response('Sin datos', 404, array('X-Powered-By' => '|=|'));
    }

    // Validar que la página existe
    if (empty($post)) {
        return new WP_REST_Response('Sin datos', 404, array('X-Powered-By' => '|=|'));
    }

    // Preparar datos de respuesta básicos
    $fposts = array(
        'ID' => $post[0]->ID,
        'title' => $post[0]->post_title,
        'content' => $post[0]->post_content,
        'slug' => $post[0]->post_name,
        'date' => $post[0]->post_date,
        'status' => $post[0]->post_status
    );

    // TODO: Agregar campos ACF específicos de páginas cuando estén configurados
    // 'titulo' => get_field('titulo', $post[0]->ID),
    // 'sub_titulo' => get_field('sub_titulo', $post[0]->ID),
    // 'instagram' => get_field('instagram', $post[0]->ID),
    // 'email' => get_field('email', $post[0]->ID),

    $res = new WP_REST_Response();
    $res->set_data($fposts);
    $res->set_headers(array('Cache-Control' => 'max-age=3600,public', 'X-Powered-By' => '|=|'));
    return $res;
}

/**
 * Get posts by category with media
 * 
 * Retrieves posts from specified categories with pagination support and media attachments.
 * Each post includes up to 4 media items (horizontal and vertical versions) with their types and URLs.
 * 
 * @param WP_REST_Request $req Request object containing category, pagination and limit parameters
 * @return WP_REST_Response Response object with posts data and pagination info or 404 if not found
 */
function api_posts_by_category(WP_REST_Request $req) {
    $params = [];
    if (isset($req)) {
        $params = $req->get_params();
    }
    $numberposts = -1;
    $posts_per_page = -1;
    $category = '_';
    $offset = 0;
    if (!empty($params['num_posts'])) {
        $numberposts = intval($params['num_posts']);
    }
    if (!empty($params['per_page'])) {
        $posts_per_page = intval($params['per_page']);
    }
    if (!empty($params['offset'])) {
        $offset = intval($params['offset']);
    }
    if (!empty($params['category'])) {
        // if($params['category'])
        $category = $params['category'];
    } else {
        return new WP_REST_Response('Sin datos', 404, array('X-Powered-By' => '|=|'));
    }
    $posts = new WP_Query(array(
        'category_name' => $category,
        'numberposts' => $numberposts,
        'posts_per_page' => $posts_per_page,
        'offset' => $offset,
        'post_type' => 'post',
        'fields' => 'ids'
    ));
    if (count($posts->posts) == 0) {
        return new WP_REST_Response('Sin datos', 404, array('X-Powered-By' => '|=|'));
    }
    // ----------------------------
    // Solo devolver ciertos campos
    $fposts = [];
    // Datos de paginación
    $fposts = array(
        'per_page' => $posts_per_page,
        'offset' => $offset,
        'total' => $posts->found_posts,
        'posts' => []
    );
    // Construccion de la respuesta
    foreach ($posts->posts as $id) {
        // ACF data
        // Obtener datos de ACF
        // ---------
        $fposts['posts'][] = array(
            'title' => get_post_field('post_title', $id),
            'ID' => $id,
            // Resto de campos
        );
    }
    // -----------------------------
    $res = new WP_REST_Response();
    $res->set_data($fposts);
    $res->set_headers(array('Cache-Control' => 'max-age=3600,public', 'X-Powered-By' => '|=|'));
    return $res;
}


/**
 * Register Custom REST API Routes
 * 
 * Sets up custom endpoints for the theme's API with security restrictions.
 * Only these specific routes are allowed, all other WordPress API routes are blocked.
 * 
 * Active Routes:
 * - GET /api/v1/pages/{slug} - Get single page by slug
 * 
 * All endpoints return cached responses (1 hour) and include custom headers.
 * 
 * @since 1.0.0
 */
add_action('rest_api_init', function () {

    /**
     * Page endpoint
     * Returns page data by slug from WordPress pages
     */
    register_rest_route('api/v1', 'pages/(?P<slug>[A-Za-z0-9 \-]+)', array(
        'methods' => 'GET',
        'callback' => 'api_page_by_slug',
        'args' => array(
            'slug' => array(
                'required' => true,
                'description' => 'Page slug to retrieve',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
        ),
    ));

    register_rest_route('api/v1', 'posts/(?P<category>[A-Za-z0-9 \-]+)', array(
        'methods' => 'GET',
        'callback' => 'api_posts_by_category',
        'args' => array(
            'category' => array(
                'required' => true,
                'description' => 'Category slug to retrieve',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
        ),
    ));

    // CPT y Custom taxonomies
    // register_rest_route('api/v1', 'cpt/(?P<cpt>[A-Za-z0-9 \-]+)', array(
    //     'methods' => 'POST',
    //     'callback' => 'api_cpt_by_category',
    //     'args' => array(
    //         'taxonomy' => array(
    //             'validate_callback' => function ($param, $request, $key) {
    //                 // verifca si existen las taxonomias
    //                 return $param != '';
    //             }
    //         ),
    //         'tax_terms' => array(
    //             'validate_callback' => function ($param, $request, $key) {
    //                 // verifca si existen las taxonomias
    //                 $taxonomies = get_terms(['taxonomy' => 'tipo-modulo', 'hide_empty' => false, 'fields' => 'slugs']);
    //                 $tax = explode(",", $param);
    //                 return count(array_intersect($tax, $taxonomies)) > 0;
    //             }
    //         ),
    //     ),
    // ));
});
// **************************************************************
