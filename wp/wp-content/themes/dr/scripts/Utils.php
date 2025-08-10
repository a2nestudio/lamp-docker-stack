<?php

/**
 * Crea un conjunto de fuentes (srcset) para un medio dado.
 *
 * @param array $media Datos del medio que contiene las URLs y tamaños.
 * @return array Un array que contiene la URL y el srcset.
 */
function createSrcACF($media) {
    $srcset = '';
    $url = '';
    if ($media['sizes']) {

        $srcset = $media['sizes']['thumbnail'] . ' ' . $media['sizes']['thumbnail-width'] . 'w, ';
        $srcset .= $media['sizes']['medium'] . ' ' . $media['sizes']['medium-width'] . 'w, ';
        $srcset .= $media['sizes']['medium_large'] . ' ' . $media['sizes']['medium_large-width'] . 'w, ';
        $srcset .= $media['sizes']['large'] . ' ' . $media['sizes']['large-width'] . 'w, ';
        $srcset .= $media['sizes']['xq-large'] . ' ' . $media['sizes']['xq-large-width'] . 'w';
        ///
        $url = $media['sizes']['x2q-large'];
    } else {
        $url = $media['url'];
    }
    return array('url' => $url, 'srcset' => $srcset);
}

/**
 * Crea un conjunto de fuentes (srcset) para una imagen adjunta dada.
 *
 * @param int $id ID de la imagen adjunta.
 * @return array Un array que contiene la URL de la imagen completa y el srcset.
 */
function createSrc($id) {
    $srcset = '';
    $full = wp_get_attachment_image_src($id, 'full');
    $thumbnail = wp_get_attachment_image_src($id, 'thumbnail');
    $medium = wp_get_attachment_image_src($id, 'medium');
    $medium_large = wp_get_attachment_image_src($id, 'medium_large');
    $large = wp_get_attachment_image_src($id, 'large');
    $xq_large = wp_get_attachment_image_src($id, 'xq-large');
    $x2q_large = wp_get_attachment_image_src($id, 'x2q-large');

    $srcset = $thumbnail[0] . ' ' . $thumbnail[1] . 'w, ';
    $srcset .= $medium[0] . ' ' . $medium[1] . 'w, ';
    $srcset .= $medium_large[0] . ' ' . $medium_large[1] . 'w, ';
    $srcset .= $large[0] . ' ' . $large[1] . 'w, ';
    $srcset .= $xq_large[0] . ' ' . $xq_large[1] . 'w, ';
    $srcset .= $x2q_large[0] . ' ' . $x2q_large[1] . 'w';

    $url = $x2q_large[0];
    return array('url' => $full[0], 'srcset' => $srcset);
}
