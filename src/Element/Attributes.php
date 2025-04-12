<?php

namespace Core\View\Element;

use LogicException;
use Stringable;
use InvalidArgumentException;
use UnitEnum;
use function Support\{slug};
use AllowDynamicProperties;
use BackedEnum;

/**
 * @property-read Classes                                                                                 $class
 * @property-read Styles                                                                                  $style
 * @property-read array{id: ?string, class: string[], style: array<string, string>, ...<string, ?string>} $array
 */
#[AllowDynamicProperties]
final class Attributes implements Stringable
{
    private ?string $id = null;

    /** @var string[] */
    private array $classes = [];

    /** @var array<string,string> */
    private array $styles = [];

    // /** @var array<string, null|scalar|Stringable|UnitEnum> */
    // private array $attributes = [];

    public function __construct(
        Attributes|null|bool|float|int|string|Stringable|UnitEnum ...$attributes,
    ) {
        foreach ( $attributes as $attribute => $value ) {
            if ( $value instanceof Attributes ) {
                $this->merge( $value );
                unset( $attributes[$attribute] );
            }
        }

        $this->merge( $attributes );
    }

    public function merge( Attributes|array $attributes ) : self
    {
        if ( $attributes instanceof Attributes ) {
            $attributes = $attributes->attributeArray();
        }

        foreach ( $attributes as $attribute => $value ) {
            $attribute = $this->name( $attribute );
            if ( $attribute === 'id' ) {
                $this->id( $value );
            }
            elseif ( $attribute === 'classes' ) {
                $value = \is_array( $value ) ? $value : [$value];

                $this->class( ...$value );
            }
            elseif ( $attribute === 'styles' ) {
                $value = \is_array( $value ) ? $value : [$value];
                $this->style( ...$value );
            }
            else {
                $this->{$attribute} = $value ?? true;
            }
        }

        return $this;
    }

    /**
     * @param string                                         $attribute
     * @param null|bool|float|int|string|Stringable|UnitEnum $value
     *
     * @return $this
     */
    public function set(
        string                                         $attribute,
        int|float|string|bool|null|UnitEnum|Stringable $value = null,
    ) : self {
        $attribute = $this->name( $attribute );

        if ( $attribute === 'id' ) {
            $this->id( $value );
        }
        elseif ( $attribute === 'classes' ) {
            $this->class( $value );
        }
        elseif ( $attribute === 'styles' ) {
            $this->style( $value );
        }
        else {
            $this->{$attribute} = $value ?? true;
        }
        return $this;
    }

    public function id( ?string $set ) : self
    {
        $this->id = slug( $set );
        return $this;
    }

    public function class( ?string ...$add ) : self
    {
        return ( new Classes( $this->classes, $this ) )->add( $add );
    }

    /**
     * @param array<string,string>|string ...$add
     *
     * @return self
     */
    public function style( null|string|array ...$add ) : self
    {
        return ( new Styles( $this->styles, $this ) )->add( $add );
    }

    // public function __set( string $name, mixed $value ) : void
    // {
    //     throw new LogicException( $this::class."::\${$name} cannot be dynamically set." );
    // }

    /**
     * @param string $name
     *
     * @return array<string, array<array-key, string>|bool|string>|Classes|Styles
     */
    public function __get( string $name ) : Classes|Styles|array
    {
        return match ( $this->name( $name ) ) {
            'classes' => new Classes( $this->classes, $this ),
            'styles'  => new Styles( $this->styles, $this ),
            'array'   => $this->attributeArray(),
            default   => throw new InvalidArgumentException(
                'Warning: Undefined property: '.$this::class."::\${$name}",
            ),
        };
    }

    /**
     * @param 'class'|'id'|'style'|string $attribute
     *
     * @return null|string
     */
    public function get( string $attribute ) : ?string
    {
        $get = $this->name( $attribute );

        return match ( $get ) {
            'id'      => $this->id,
            'classes' => Classes::resolve( $this->classes ),
            'styles'  => Styles::resolve( $this->styles ),
            default   => $this->{$get} ?? null,
        };
    }

    public function pull( string $attribute ) : ?string
    {
        $attribute = $this->name( $attribute );
        $value     = $this->get( $attribute );

        if ( \is_array( $this->{$attribute} ) ) {
            $this->{$attribute} = [];
        }
        else {
            unset( $this->{$attribute} );
        }
        dump( $this );

        return $value;
    }

    /**
     * @param bool $raw
     *
     * @return array<string, string>
     */
    public function resolveAttributes( bool $raw = false ) : array
    {
        $attributes = [];

        foreach ( $this->attributeArray() as $attribute => $value ) {
            // Skip empty classes and styles
            if ( $value === false || ( \is_array( $value ) && empty( \array_filter( $value ) ) ) ) {
                continue;
            }

            if ( $attribute === 'classes' ) {
                \assert( \is_array( $value ) );
                $value = Classes::resolve( $value );
            }
            if ( $attribute === 'styles' ) {
                \assert( \is_array( $value ) );
                $value = Styles::resolve( $value );
            }

            if ( $raw ) {
                $attributes[$attribute] = $value;

                continue;
            }

            if ( $value === true ) {
                $attributes[$attribute] = $attribute;
            }
            else {
                $attributes[$attribute] = "{$attribute}=\"{$value}\"";
            }
        }

        return $attributes;
    }

    /**
     * Return a string of fully resolved attributes.
     *
     * Will be prefixed with a single whitespace unless empty.
     *
     * @return string
     */
    public function __toString() : string
    {
        $attributes = \implode( ' ', $this->resolveAttributes() );
        return $attributes ? " {$attributes}" : '';
    }

    /**
     * Return a normalized, but unprocessed version of {@see self::$attributes}.
     *
     * @return array<string, array<array-key, string>|bool|string>
     */
    private function attributeArray() : array
    {
        $attributes = [];

        /** @var iterable<string, mixed> $this */
        foreach ( $this as $attribute => $value ) {
            if ( $value instanceof BackedEnum ) {
                $value = $value->value;
            }

            if ( $value instanceof UnitEnum ) {
                $value = $value->name;
            }

            if ( $value instanceof Stringable || \is_numeric( $value ) ) {
                $value = (string) $value;
            }

            \assert(
                \is_array( $value ) || \is_string( $value ) || \is_null( $value ) || \is_bool( $value ),
                "Attribute '{$attribute}' can only be null|array|string|bool. ".\gettype( $value ).' provided.',
            );

            $attributes[$attribute] = $value;
        }

        return $attributes;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function name( int|string $string ) : string
    {
        \assert(
            \is_string( $string ),
            'Attribute names must be strings, '.\gettype( $string ).' provided.',
        );

        $string = \strtolower( \trim( $string ) );

        $string = \trim( (string) \preg_replace( '/[^a-z0-9-]+/i', '-', $string ), '-' );

        return match ( $string ) {
            'class' => 'classes',
            'style' => 'styles',
            default => $string,
        };
    }

    /**
     * Extracts attributes from an HTML string.
     *
     * - Parses only the first element
     *
     * @param string $html
     * @param bool   $unwrap
     *
     * @return Attributes
     */
    public static function extract( string &$html, bool $unwrap = false ) : Attributes
    {
        if ( ! \preg_match( '/^<(\w+)([^>]*)>/', $html, $matches ) ) {
            return new self();
        }

        [$elementSubstring, $tagName, $attributesString] = $matches;

        if ( $unwrap ) {
            $html = \mb_substr( $html, \mb_strlen( $elementSubstring ) );

            if ( \str_ends_with( $html, "</{$tagName}>" ) ) {
                $html = \mb_substr( $html, 0, -\mb_strlen( "</{$tagName}>" ) );
            }

            $html = \trim( $html );
        }

        $attributes = [];

        if ( ! $attributesString ) {
            return new self();
        }

        if ( \preg_match_all(
            '#([\w_-]+?)\s*=\s*["\'`](.*?\s*)["\'`]|(\w+)#',
            // '/(\w+)(?:\s*=\s*"([^"]*)"|\s*=\s*\'([^\']*)\'|\s*=\s*([^\s>]+))?/',
            $attributesString,
            $attrMatches,
            PREG_SET_ORDER,
        ) ) {
            foreach ( $attrMatches as $attr ) {
                $name  = ( $attr[1] ?? false ) ?: $attr[3] ?? null;
                $value = ( $attr[2] ?? false ) ?: $name;

                if ( ! $name ) {
                    continue;
                }

                $attributes[$name] = $value;
            }
        }

        return new self( ...$attributes );
    }
}
