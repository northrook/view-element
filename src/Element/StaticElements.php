<?php

declare(strict_types=1);

namespace Core\View\Element;

use Core\View\Element;
use InvalidArgumentException;
use function Support\escape_url;

trait StaticElements
{
    /**
     * @param string                                    $href
     * @param null|array<array-key, string>|bool|string ...$attributes
     *
     * @return string
     */
    public static function link(
        string                    $href,
        string|bool|array|null ...$attributes,
    ) : string {
        $attributes['href'] = escape_url( $href );
        return Tag::from( 'link' )->getOpeningTag( $attributes );
    }

    /**
     * @param ?string                                   $src
     * @param ?string                                   $inline
     * @param null|array<array-key, string>|bool|string ...$attributes
     *
     * @return string
     */
    public static function script(
        ?string                   $src = null,
        ?string                   $inline = null,
        string|bool|array|null ...$attributes,
    ) : string {
        if ( $src && ! $inline ) {
            $attributes['src'] = escape_url( $src );
        }
        else {
            unset( $attributes['src'] );
        }

        return (string) new Element( 'script', $inline, ...$attributes );
    }

    /**
     * @param ?string                                   $href
     * @param ?string                                   $inline
     * @param null|array<array-key, string>|bool|string ...$attributes
     *
     * @return string
     */
    public static function style(
        ?string                   $href = null,
        ?string                   $inline = null,
        string|bool|array|null ...$attributes,
    ) : string {
        if ( $href && ! $inline ) {
            $attributes['href'] = escape_url( $href );
            $attributes['rel']  = 'stylesheet';
            return Tag::from( 'link' )->getOpeningTag( $attributes );
        }

        if ( $inline ) {
            unset( $attributes['href'] );
            return (string) new Element( 'style', $inline, ...$attributes );
        }

        throw new InvalidArgumentException();
    }

    public static function img(
        string                    $src,
        string                    $alt = '',
        null|string|array         $srcset = null,
        null|string|array         $sizes = null,
        string|bool|array|null ...$attributes,
    ) : string {
        $attributes['src'] = escape_url( $src );
        $attributes['alt'] = $alt;

        if ( $srcset ) {
            $attributes['srcset'] = \is_array( $srcset ) ? \implode( ', ', $srcset ) : $srcset;
        }

        if ( $sizes ) {
            $attributes['sizes'] = \is_array( $sizes ) ? \implode( ', ', $sizes ) : $sizes;
        }

        return Tag::from( 'img' )->getOpeningTag( $attributes );
    }

    public static function source(
        ?string                   $src = null,
        ?string                   $srcset = null,
        ?string                   $media = null,
        ?string                   $type = null,
        null|string|array         $sizes = null,
        null|string|array|bool ...$attributes,
    ) : string {
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

        return Tag::from( 'source' )->getOpeningTag( $attributes );
    }
}
