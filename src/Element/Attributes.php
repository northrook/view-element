<?php

declare(strict_types=1);

namespace Core\View\Element;

use AllowDynamicProperties;
use Core\Interface\Printable;
use Stringable;
use UnitEnum;
use BackedEnum;
use InvalidArgumentException;
use function Support\{as_array, as_string, slug};

/**
 * @property-read array{id: ?string, class: string[], style: array<string, string>, ...<string, ?string>} $array
 * @property-read Classes                                                                                 $class
 * @property-read Styles                                                                                  $style
 */
#[AllowDynamicProperties]
final class Attributes implements Stringable
{
    private ?string $id = null;

    /** @var string[] */
    private array $classes = [];

    /** @var array<string,string> */
    private array $styles = [];

    /**
     * @param mixed ...$attributes
     */
    public function __construct( mixed ...$attributes )
    {
        $this->merge( ...$attributes );
    }

    /**
     * @param mixed ...$attributes
     *
     * @return $this
     */
    public function merge( mixed ...$attributes ) : self
    {
        foreach ( $this->parse( $attributes ) as $attribute => $value ) {
            $this->set( $attribute, $value );
        }

        return $this;
    }

    /**
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function has( string $attribute, mixed $value = null ) : bool
    {
        // Get attribute by name, or false if unset
        $attribute = $this->{$this->name( $attribute )} ?? false;

        // Check against value if requested
        if ( $value ) {
            return $attribute === $value;
        }

        // If the attribute is anything but false, consider it set
        return $attribute !== false;
    }

    /**
     * @param mixed ...$attribute
     *
     * @return $this
     */
    public function add( mixed ...$attribute ) : self
    {
        foreach ( $attribute as $key => $value ) {
            $this->set( $key, $value );
        }
        return $this;
    }

    /**
     * @param int|string $attribute
     * @param mixed      $value
     *
     * @return void
     */
    public function set(
        int|string $attribute,
        mixed      $value,
    ) : void {
        /** @var string $attribute */
        $attribute = $this->name( $attribute );

        if ( $value === null ) {
            dump( [__METHOD__ => "{$attribute} is ".\gettype( $value )] );
            return;
        }

        if ( \is_bool( $value ) ) {
            $this->{$attribute} = $value;
            return;
        }

        if ( $attribute === 'classes' ) {
            // @phpstan-ignore-next-line
            $this->class( ...as_array( $value ) );
            return;
        }

        if ( $attribute === 'styles' ) {
            // @phpstan-ignore-next-line
            $this->style( ...as_array( $value ) );
            return;
        }

        if ( $attribute === 'id' ) {
            // @phpstan-ignore-next-line
            $this->id( $value );
            return;
        }

        $this->{$attribute} = $value;
    }

    /**
     * @param null|BackedEnum|string|Stringable|UnitEnum $set
     *
     * @return $this
     */
    public function id( null|string|BackedEnum|UnitEnum|Stringable $set ) : self
    {
        $this->id = $set ? ( slug( as_string( $set ) ) ?: null ) : null;
        return $this;
    }

    /**
     * @param null|bool|float|int|string|Stringable|UnitEnum ...$add
     *
     * @return self
     */
    public function class( bool|float|int|string|Stringable|UnitEnum|null ...$add ) : self
    {
        return ( new Classes( $this->classes, $this ) )->add( $add );
    }

    /**
     * @param null|string|string[] ...$add
     *
     * @return self
     */
    public function style( null|string|array ...$add ) : self
    {
        $styles = [];

        foreach ( $add as $key => $value ) {
            if ( \is_array( $value ) ) {
                $styles = [...$styles, ...$value];
            }
            else {
                $styles[$key] = $value;
            }
        }

        return ( new Styles( $this->styles, $this ) )->add( $styles );
    }

    /**
     * @param string $name
     *
     * @return array<string, null|array<array-key, string>|bool|int|string>|Classes|Styles
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
        $attribute = $this->name( $attribute );

        return match ( $attribute ) {
            'id'      => $this->id,
            'classes' => Classes::resolve( $this->classes ),
            'styles'  => Styles::resolve( $this->styles ),
            default   => $this->{$attribute} ?? null,
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

            \assert(
                \is_string( $value ) || $value === true,
                __METHOD__.' $value should be a string|true at this point, '.\gettype( $value ).' provided.',
            );

            if ( $raw ) {
                $attributes[$attribute] = $attribute;

                continue;
            }

            $attributes[$attribute] = $value === true
                    ? $attribute
                    : "{$attribute}=\"{$value}\"";
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
                $value = ( $attr[2] ?? false ) ?: true;

                if ( ! $name ) {
                    continue;
                }

                $attributes[$name] = $value;
            }
        }

        return new self( $attributes );
    }

    /**
     * @param array<array-key, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function parse( array $arguments ) : array
    {
        $attributes = [];

        foreach ( $arguments as $key => $attribute ) {
            $attribute = $attribute instanceof Attributes
                    ? $attribute->attributeArray()
                    : $attribute;

            if ( \is_int( $key ) && \is_array( $attribute ) ) {
                $attributes = [...$attributes, ...$this->parse( $attribute )];

                continue;
            }

            if ( $attribute === null || $attribute === [] ) {
                continue;
            }

            $attributes[$this->name( $key )] = $attribute;
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
     * Return a normalized, but unprocessed version of {@see self::$attributes}.
     *
     * @return array<string, null|array<array-key, string>|bool|int|string>
     */
    private function attributeArray() : array
    {
        $attributes = [];

        // @phpstan-ignore-next-line
        foreach ( $this as $attribute => $value ) {
            /** @noinspection PhpDuplicateMatchArmBodyInspection */
            $value = match ( true ) {
                $value === null              => $value,
                $value === []                => $value,
                \is_string( $value )         => $value,
                \is_bool( $value )           => $value,
                \is_numeric( $value )        => (string) $value,
                $value instanceof Printable  => $value->toString(),
                $value instanceof Stringable => (string) $value,
                $value instanceof BackedEnum => $value->value,
                $value instanceof UnitEnum   => $value->name,
                \is_array( $value ) && ( function() use ( $value ) {
                    foreach ( $value as $string ) {
                        if ( ! \is_string( $string ) ) {
                            return false;
                        }
                    }
                    return true;
                } )()   => $value,
                default => throw new InvalidArgumentException(
                    $this::class.' does not accept a value of type '.\gettype( $value ).'.',
                ),
            };

            /** @var null|array<string>|bool|int|string $value */
            $attributes[$attribute] = $value;
        }

        return $attributes;
    }
}
