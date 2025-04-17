<?php

declare(strict_types=1);

namespace Core\View\Element;

use Core\View\Element;
use InvalidArgumentException;
use function Support\escape_url;

trait StaticElements
{
    /**
     * @param string $href
     * @param mixed  ...$attributes
     *
     * @return Element
     */
    public static function link(
        string   $href,
        mixed ...$attributes,
    ) : Element {
        $attributes['href'] = escape_url( $href );
        return new Element( 'link', ...$attributes );
    }

    /**
     * @param ?string $src
     * @param ?string $inline
     * @param mixed   ...$attributes
     *
     * @return Element
     */
    public static function script(
        ?string  $src = null,
        ?string  $inline = null,
        mixed ...$attributes,
    ) : Element {
        if ( $src && ! $inline ) {
            $attributes['src'] = escape_url( $src );
        }
        else {
            unset( $attributes['src'] );
        }

        return new Element( 'script', $inline, ...$attributes );
    }

    /**
     * @param ?string $href
     * @param ?string $inline
     * @param mixed   ...$attributes
     *
     * @return Element
     */
    public static function style(
        ?string  $href = null,
        ?string  $inline = null,
        mixed ...$attributes,
    ) : Element {
        if ( $href && ! $inline ) {
            $attributes['href'] = escape_url( $href );
            $attributes['rel']  = 'stylesheet';
            return new Element( 'link', ...$attributes );
        }

        if ( $inline ) {
            unset( $attributes['href'] );
            return new Element( 'style', $inline, ...$attributes );
        }

        throw new InvalidArgumentException();
    }

    /**
     * @param string               $src
     * @param string               $alt
     * @param null|string|string[] $srcset
     * @param null|string|string[] $sizes
     * @param mixed                ...$attributes
     *
     * @return Element
     */
    public static function img(
        string            $src,
        string            $alt = '',
        null|string|array $srcset = null,
        null|string|array $sizes = null,
        mixed          ...$attributes,
    ) : Element {
        $attributes['src'] = escape_url( $src );
        $attributes['alt'] = $alt;

        if ( $srcset ) {
            $attributes['srcset'] = \is_array( $srcset ) ? \implode( ', ', $srcset ) : $srcset;
        }

        if ( $sizes ) {
            $attributes['sizes'] = \is_array( $sizes ) ? \implode( ', ', $sizes ) : $sizes;
        }

        return new Element( 'img', ...$attributes );
    }

    /**
     * @param null|string          $src
     * @param null|string          $srcset
     * @param null|string          $media
     * @param null|string          $type
     * @param null|string|string[] $sizes
     * @param mixed                ...$attributes
     *
     * @return Element
     */
    public static function source(
        ?string           $src = null,
        ?string           $srcset = null,
        ?string           $media = null,
        ?string           $type = null,
        null|string|array $sizes = null,
        mixed          ...$attributes,
    ) : Element {
        if ( $media ) {
            $attributes['media'] = $media;
        }

        if ( $src ) {
            $attributes['src'] = escape_url( $src );
        }

        if ( $srcset ) {
            $attributes['srcset'] = escape_url( $srcset );
        }

        if ( $type ) {
            $attributes['type'] = $type;
        }

        if ( $sizes ) {
            $attributes['sizes'] = \is_array( $sizes ) ? \implode( ', ', $sizes ) : $sizes;
        }

        return new Element( 'source', ...$attributes );
    }
}
