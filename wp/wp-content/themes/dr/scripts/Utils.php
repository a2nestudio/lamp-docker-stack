<?php

/**
 * $media es el campo ACF
 * 
 * En el caso de que sea distinto a imagen, devuelve la url
 * $media = get_field('campo, $id);
 */
function createSrc($media) {
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
