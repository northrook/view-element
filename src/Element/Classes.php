<?php

declare(strict_types=1);

namespace Core\View\Element;

use UnitEnum;
use ValueError;
use Stringable;
use BackedEnum;
use function Support\as_string;

/**
 * @internal
 */
final class Classes implements Stringable
{
    /**
     * @param string[]   $class
     * @param Attributes $attributes
     */
    public function __construct(
        protected array &             $class,
        private readonly Attributes $attributes,
    ) {}

    public function __toString() : string
    {
        return $this::resolve( $this->class );
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function has( string $class ) : bool
    {
        return \array_key_exists( $class, $this->class );
    }

    /**
     * @param BackedEnum[]|bool[]|float[]|int[]|null[]|string[]|Stringable[]|UnitEnum[] $class
     * @param bool                                                                      $prepend
     * @param bool                                                                      $append
     *
     * @return Attributes
     */
    public function add(
        mixed $class,
        bool  $prepend = false,
        bool  $append = false,
    ) : Attributes {
        // Cast and filter to array of values
        foreach ( \array_filter( (array) $class ) as $value ) {
            //
            // Normalize, and skip if empty
            if ( ! $selector = \strtolower( \trim( as_string( $value ) ) ) ) {
                continue;
            }

            if ( \ctype_digit( $selector[0] ) ) {
                throw new ValueError( 'CSS class selectors cannot start with a number.' );
            }

            // Parse strings with multiple classes
            if ( \str_contains( $selector, ' ' ) ) {
                $selector = \explode( ' ', \str_replace( ',', ' ', $selector ) );
                $this->add( $selector, $prepend, $append );

                continue;
            }

            // Append by default
            if ( ! $prepend && ! $append ) {
                $this->class[$selector] = $selector;

                continue;
            }

            // Remove the relevant $selector if we are prepended or appending
            unset( $this->class[$selector] );

            // Place the $selector at the start
            if ( $prepend ) {
                $this->class = [...[$selector => $selector], ...$this->class];
            }
            // Append at the end, even if it was set previously
            else {
                $this->class[$selector] = $selector;
            }
        }

        return $this->attributes;
    }

    public function get( string $class ) : ?string
    {
        return $this->class[$class] ?? null;
    }

    /**
     * @return string[]
     */
    public function getAll() : array
    {
        return $this->class;
    }

    public function remove( string ...$class ) : Attributes
    {
        foreach ( $class as $value ) {
            $value = \strtolower( \trim( $value ) );
            unset( $this->class[$value] );
        }
        return $this->attributes;
    }

    public function clear() : Attributes
    {
        $this->class = [];
        return $this->attributes;
    }

    /**
     * @param string[] $array
     *
     * @return string
     */
    public static function resolve( array $array ) : string
    {
        $classes = \array_filter( $array );

        \ksort( $classes );

        return \implode( ' ', $classes );
    }
}
