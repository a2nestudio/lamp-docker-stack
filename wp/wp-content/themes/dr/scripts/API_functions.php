<?php
/**
 * API Functions for Piña Estudio WordPress Theme
 * 
 * This file contains all the REST API endpoints and functions for the theme.
 * It includes utilities and custom API routes for pages and posts.
 */

// Utils
include('Utils.php');

/**
 * Block access to default WordPress REST API endpoints
 * 
 * This hook intercepts requests to the root API endpoints and returns a 404 status
 * to hide the default WordPress API routes for security purposes.
 * 
 * @param mixed           $result  Response to replace the requested version with
 * @param WP_REST_Server  $server  Server instance
 * @param WP_REST_Request $request Request used to generate the response
 * @return WP_REST_Response
 */
add_action( 'rest_pre_dispatch', function( $result, $server, $request ) {
    if($request->get_route() == '/' || $request->get_route() == '/wp-json'){ 
        $result = new WP_REST_Response();
        $result->set_status(404);
    }
    if($request->get_route() == '/api/v1'){   
        $result = new WP_REST_Response();
        $result->set_status(404);
    }
    return $result;
}, 10, 3 );
//////////////////////////////
/**
 * Get page data by slug
 * 
 * Retrieves a single page's data including ACF fields, media URLs and contact information
 * based on the provided slug.
 * 
 * @param WP_REST_Request $req Request object containing the page slug
 * @return WP_REST_Response Response object with page data or 404 if not found
 */
function api_page_by_slug(WP_REST_Request $req) {
    $params = [];
    if (isset($req)) {
        $params = $req->get_params();
    }
    $post = get_posts(array(
        'name' => $params['slug'],
        'post_type' => 'page',
    ));
    if (empty($params['slug'])) {
        return new WP_REST_Response('Sin datos', 404, array('X-Powered-By' => '|=|'));
    }

    if (empty($post)) {
        return new WP_REST_Response('Sin datos', 404, array('X-Powered-By' => '|=|'));
    } // ----------------------------
    // Solo devolver ciertos campos
    // ACF data
    $tipo = get_field('tipo', $post[0]->ID);
    $media = get_field($tipo, $post[0]->ID);
    $mediav = get_field($tipo . '_vertical', $post[0]->ID);
    // Construccion srcset
    $url = createSrc($media);
    $urlv = createSrc($mediav);
    // Contactos
    // ---------
    $fposts = array(
        'title' => get_field('titulo', $post[0]->ID),
        'sub-title' => get_field('sub_titulo', $post[0]->ID),
        'ID' => $post[0]->ID,
        'content' => $post[0]->post_content,
        'tipo' => $tipo,
        'url' => $url['url'],
        'urlv' => $urlv['url'],
        'instagram' => get_field('instagram', $post[0]->ID),
        'email' => get_field('email', $post[0]->ID),
        'favicon' => get_field('favicon', $post[0]->ID),
        'descripcion' => get_field('descripcion', $post[0]->ID),
    );
    // -----------------------------
    //     // ACF Gallery plugin
    //     $posts[$i]->acf_gallery = acf_photo_gallery('fotos', $posts[$i]->ID);
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
        // 1
        $media1 = get_field('media_1', $id);
        $media1_tipo = $media1['tipo'];
        $media1_media = $media1[$media1_tipo];
        $media1_url = createSrc($media1_media);
        $media1_media_v = $media1[$media1_tipo . '_vertical'];
        $media1_url_v = createSrc($media1_media_v);
        // 2
        $media2 = get_field('media_2', $id);
        $media2_tipo = $media2['tipo'];
        $media2_media = $media2[$media2_tipo];
        $media2_url = createSrc($media2_media);
        $media2_media_v = $media2[$media2_tipo . '_vertical'];
        $media2_url_v = createSrc($media2_media_v);
        // 3
        $media3 = get_field('media_3', $id);
        $media3_tipo = $media3['tipo'];
        $media3_media = $media3[$media3_tipo];
        $media3_url = createSrc($media3_media);
        $media3_media_v = $media3[$media3_tipo . '_vertical'];
        $media3_url_v = createSrc($media3_media_v);
        // 4
        $media4 = get_field('media_4', $id);
        $media4_tipo = $media4['tipo'];
        $media4_media = $media4[$media4_tipo];
        $media4_url = createSrc($media4_media);
        $media4_media_v = $media4[$media4_tipo . '_vertical'];
        $media4_url_v = createSrc($media4_media_v);
        //$media1_tipo = $media1[]
        // $media2 = get_field('media_1', $id);
        // $media3 = get_field('media_1', $id);
        // $media4 = get_field('media_1', $id);
        //$tipo = get_field('tipo', $id);
        //$media = get_field($tipo, $id);
        // Construccion srcset
        //$url = createSrc($media);
        // ---------
        $fposts['posts'][] = array(
            'title' => get_post_field('post_title', $id),
            'ID' => $id,
            // 'todos' => array(
            //     $media1_url['url'],
            //     $media1_url_v['url'],
            //     $media2_url['url'],
            //     $media2_url_v['url'],
            //     $media3_url['url'],
            //     $media3_url_v['url'],
            //     $media4_url['url'],
            //     $media4_url_v['url'],
            // ),
            'media' => array(
                array(
                    'tipo' => $media1_tipo,
                    //'urls' => array(
                    'url' => $media1_url['url'],
                    'urlv' => $media1_url_v['url']
                    //   'srcset' => $media1_url['srcset']
                    //)
                ),
                array(
                    'tipo' => $media2_tipo,
                    // 'urls' => array(
                    'url' => $media2_url['url'],
                    'urlv' => $media2_url_v['url']
                    //    'srcset' => $media2_url['srcset']
                    // )
                ),
                array(
                    'tipo' => $media3_tipo,
                    //'urls' => array(
                    'url' => $media3_url['url'],
                    'urlv' => $media3_url_v['url']
                    //   'srcset' => $media3_url['srcset']
                    // )
                ),
                array(
                    'tipo' => $media4_tipo,
                    // 'urls' => array(
                    'url' => $media4_url['url'],
                    'urlv' => $media4_url_v['url']
                    //   'srcset' => $media4_url['srcset']
                    // )
                )
            ),
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
 * Sets up custom endpoints for the theme's API:
 * - GET /api/v1/pages/{slug} - Get single page by slug
 * - GET /api/v1/posts/{category} - Get posts by category with optional pagination
 * 
 * All endpoints return cached responses (1 hour) and include custom headers.
 */
add_action('rest_api_init', function () {

    // API V1 ------------------------------------------------
    // (?P<arg>regexp) -> si no coincide devuelve 404
    register_rest_route('api/v1', 'pages/(?P<slug>[A-Za-z0-9 \-]+)', array(
        'methods' => 'GET',
        'callback' => 'api_page_by_slug',
    ));
    // si no hay datos devuelve 404
    register_rest_route('api/v1', '/posts/(?P<category>[A-Za-z0-9 \-]+)', array(
        'methods' => 'GET',
        'callback' => 'api_posts_by_category',
        'args' => array(
            'category' => array(
                'validate_callback' => function ($param, $request, $key) {
                    // verifca si existen las categorias
                    $categories = get_categories(array('fields' => 'slugs'));
                    $cats = explode(",", $param);
                    return count(array_intersect($cats, $categories)) > 0;
                }
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
