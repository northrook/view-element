<?php

declare(strict_types=1);

namespace Core\View\Element\Attributes;

use Core\View\Element\Attributes;
use ValueError;

/**
 * @internal
 */
final class StyleAttribute
{
    /**
     * @param array<string, string> $style
     * @param Attributes            $return
     */
    private function __construct(
        private array &               $style,
        private readonly Attributes $return,
    ) {}

    /**
     * @param array<string, string> $data
     * @param Attributes            $return
     *
     * @return self
     */
    public static function byReference( mixed &$data, Attributes $return ) : self
    {
        return new self( $data, $return );
    }

    /**
     * @param array<string, string> $array
     *
     * @return string
     */
    public static function resolve( array $array ) : string
    {
        foreach ( $array as $style => $value ) {
            $array[$style] = "{$style}: {$value}";
        }
        return \implode( '; ', $array );
    }

    /**
     * @param array<array-key,?string>|string $style
     * @param array<string, string>           $styles [optional] merge `$styles` into this array
     *
     * @return array<string, string>
     */
    public static function toArray( string|array $style, array $styles = [] ) : array
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
            //
            // Normalize
            $name  = \trim( $name, " \n\r\t\v\0,:" );
            $value = \trim( $value, " \n\r\t\v\0,;" );
            //
            // Validate
            if ( \ctype_digit( $name[0] ) ) {
                throw new ValueError( 'CSS style names cannot start with a number.' );
            }

            $styles[$name] = $value;
        }

        return \array_filter( $styles );
    }

    /**
     * @param null|array<array-key,?string>|string $style
     * @param bool                                 $prepend
     * @param bool                                 $append
     *
     * @return Attributes
     */
    public function add( null|string|array $style, bool $prepend = false, bool $append = false ) : Attributes
    {
        if ( ! $style ) {
            return $this->return;
        }

        // Cast and filter to array of values
        foreach ( $this::toArray( $style ) as $name => $value ) {
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

        return $this->return;
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

    public function get( string $class ) : ?string
    {
        return $this->style[$class] ?? null;
    }

    /**
     * @return string[]
     */
    public function getAll() : array
    {
        return $this->parse()->style;
    }

    public function __toString() : string
    {
        return \implode( ' ', $this->parse()->style );
    }

    public function clear() : self
    {
        $this->style = [];
        return $this;
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

    private function parse() : self
    {
        // TODO : Order of classes
        return $this;
    }
}
