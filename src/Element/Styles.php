<?php

declare(strict_types=1);

namespace Core\View\Element;

use Stringable;
use InvalidArgumentException;
use ValueError;

/**
 * @internal
 */
final class Styles implements Stringable
{
    /**
     * @param array<string, string> $style
     * @param Attributes            $attributes
     */
    public function __construct(
        protected array &             $style,
        private readonly Attributes $attributes,
    ) {}

    public function __toString() : string
    {
        return $this::resolve( $this->style );
    }

    /**
     * @param string  $style
     * @param ?string $value
     *
     * @return bool
     */
    public function has( string $style, ?string $value = null ) : bool
    {
        $style = $this->style[$style] ?? null;

        if ( $value ) {
            return \trim( $value, " \n\r\t\v\0;" ) === $style;
        }

        return (bool) $style;
    }

    /**
     * @param null|array<array-key,?string>|string $style
     * @param bool                                 $prepend
     * @param bool                                 $append
     *
     * @return Attributes
     */
    public function add(
        null|string|array $style,
        bool              $prepend = false,
        bool              $append = false,
    ) : Attributes {
        if ( ! $style ) {
            return $this->attributes;
        }

        // Cast and filter to array of values
        foreach ( $this::arrayFrom( $style ) as $name => $value ) {
            // Append by default
            if ( ! $prepend && ! $append ) {
                $this->style[$name] = $value;

                continue;
            }

            // Remove the relevant style if we are prepended or appending
            unset( $this->style[$name] );

            // Place the style at the start
            if ( $prepend ) {
                $this->style = [...[$name => $value], ...$this->style];
            }
            // Append at the end, even if it was set previously
            else {
                $this->style[$name] = $value;
            }
        }

        return $this->attributes;
    }

    public function get( string $class ) : ?string
    {
        return $this->style[$class] ?? null;
    }

    /**
     * @return string[]
     */
    public function getAll() : array
    {
        return $this->style;
    }

    /**
     * Remove one or more styles.
     *
     * @param string ...$style
     *
     * @return $this
     */
    public function remove( string ...$style ) : self
    {
        foreach ( $style as $value ) {
            $value = \strtolower( \trim( $value ) );
            unset( $this->style[$value] );
        }
        return $this;
    }

    public function clear() : self
    {
        $this->style = [];
        return $this;
    }

    /**
     * @param array<string, string> $styles
     *
     * @return string
     */
    public static function resolve( array $styles ) : string
    {
        foreach ( $styles as $style => $value ) {
            $styles[$style] = "{$style}: {$value}";
        }

        \ksort( $styles );
        return \implode( '; ', $styles );
    }

    /**
     * @param array<array-key,?string>|string $style
     * @param array<string, string>           $styles [optional] merge `$styles` into this array
     *
     * @return array<string, string>
     */
    public static function arrayFrom( string|array $style, array $styles = [] ) : array
    {
        $style = \is_string( $style ) ? \preg_split(
            pattern : '#;\s*(?![^(]*\))#',
            subject : \trim( $style ),
            flags   : PREG_SPLIT_NO_EMPTY,
        ) ?: [$style] : $style;

        foreach ( $style as $name => $value ) {
            //
            // Normalize, and skip if empty
            if ( ! $value = \trim( (string) $value, " \n\r\t\v\0," ) ) {
                continue;
            }
            //
            // Parse inlined name:value
            if ( \is_numeric( $name ) && \str_contains( $value, ':' ) ) {
                [$name, $value] = \explode( ':', $value, 2 );
            }

            if ( ! \is_string( $name ) ) {
                throw new InvalidArgumentException(
                    "Unable to parse style '{$value}'.\nStyle: separator appears to be missing.",
                );
            }

            //
            // Normalize
            $name  = \trim( $name, " \n\r\t\v\0,:" );
            $value = \trim( $value, " \n\r\t\v\0,;" );
            //
            // Validate
            if ( \ctype_digit( $name[0] ) ) {
                throw new ValueError( 'CSS style names cannot start with a number.' );
            }

            $styles[\str_replace( '_', '-', $name )] = $value;
        }

        return \array_filter( $styles );
    }

    // public static function sort( array $styles, ?array $priorityKeys = null ) : array
    // {
    //     $sorted = [];
    //
    //     $priorityKeys ??= [
    //         'not-found',
    //         'error',
    //         'warning',
    //     ];
    //
    //     // Add preferred keys in order
    //     foreach ( $priorityKeys as $key ) {
    //         if ( \array_key_exists( $key, $styles ) ) {
    //             $sorted[$key] = $styles[$key];
    //             unset( $styles[$key] );
    //         }
    //     }
    //
    //     // Sort remaining keys alphabetically
    //     if ( ! empty( $styles ) ) {
    //         \ksort( $styles );
    //         $sorted += $styles;
    //     }
    //
    //     return $sorted;
    // }
}
