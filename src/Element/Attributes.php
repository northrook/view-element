<?php

declare(strict_types=1);

namespace Core\View\Element;

use Core\View\Element\Attributes\{ClassAttribute, StyleAttribute};
use Stringable, InvalidArgumentException, LogicException;
use function Support\slug;

/**
 * @property-read ClassAttribute                                                                          $class
 * @property-read StyleAttribute                                                                          $style
 * @property-read array{id: ?string, class: string[], style: array<string, string>, ...<string, ?string>} $array
 */
final class Attributes implements Stringable
{
    // /** @var array{id: ?string, class: string[], style: array<string, string>, ...} */
    /** @var array<string, null|array<array-key, string>|bool|string> */
    private array $attributes = [
        'id'    => null,
        'class' => [],
        'style' => [],
    ];

    /**
     * @param array<string, null|array<array-key, string>|bool|int|string> $attributes
     */
    public function __construct( array $attributes = [] )
    {
        $this->assign( $attributes );
    }

    /**
     * @param string $name
     *
     * @return array<string, array<array-key, string>|bool|string>|ClassAttribute|StyleAttribute
     */
    public function __get( string $name ) : ClassAttribute|StyleAttribute|array
    {
        return match ( $name ) {
            'class' => $this->handleClasses(),
            'style' => $this->handleStyles(),
            'array' => $this->attributeArray(),
            default => throw new InvalidArgumentException(
                'Warning: Undefined property: '.$this::class."::\${$name}",
            ),
        };
    }

    public function __set( string $name, mixed $value ) : void
    {
        throw new LogicException( $this::class."::\${$name} cannot be dynamically set." );
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

        return new self( $attributes );
    }

    /**
     * @param array<string, null|array<array-key, string>|bool|string>|self $attributes
     *
     * @return self
     */
    public static function from( array|self $attributes ) : self
    {
        return $attributes instanceof Attributes ? $attributes : new Attributes( $attributes );
    }

    /**
     * Assign one or more attributes, clearing any existing attributes.
     *
     * @param array<string, null|array<array-key, string>|bool|int|string> $attributes
     *
     * @return $this
     */
    public function assign( array $attributes ) : self
    {
        $this->setAttributes( $attributes, true );
        return $this;
    }

    /**
     * Add new attributes.
     *
     * - Will not override existing attributes.
     * - Boolean `$value` set as `true|false`.
     * - Only `class` and `style` accept `array` values.
     *
     * @param array<array-key, null|array<array-key, string>|bool|int|string>|string $attribute
     * @param null|array<array-key, string>|bool|int|string                          $value
     *
     * @return $this
     */
    public function add(
        string|array               $attribute,
        int|string|array|bool|null $value = null,
    ) : self {
        if ( \is_string( $attribute ) ) {
            $attribute = [$attribute => $value];
        }

        $this->setAttributes( $attribute );

        return $this;
    }

    /**
     * Set attributes.
     *
     * - Overrides existing attributes.
     * - Boolean `$value` set as `true|false`.
     * - Only `class` and `style` accept `array` values.
     *
     * @param array<string, null|array<array-key, string>|bool|int|string>|string $attribute
     * @param null|array<array-key, string>|bool|int|string                       $value
     *
     * @return $this
     */
    public function set(
        string|array               $attribute,
        int|string|array|bool|null $value = null,
    ) : self {
        if ( \is_string( $attribute ) ) {
            $attribute = [$attribute => $value];
        }

        $this->setAttributes( $attribute, true );

        return $this;
    }

    /**
     * @param 'class'|'id'|'style'|string $name
     *
     * @return null|string
     */
    public function get( string $name ) : ?string
    {
        $value = $this->attributes[$name] ?? null;

        if ( \is_array( $value ) ) {
            // Attribute value formatting
            return match ( $name ) {
                'class' => ClassAttribute::resolve( $value ),
                'style' => StyleAttribute::resolve( $value ),
                default => \implode( ' ', \array_filter( $value ) ),
            };
        }

        // Convert types to string
        return match ( \gettype( $value ) ) {
            'boolean' => $value ? 'true' : 'false',
            'string'  => $value ?: null,
            default   => null,
        };
    }

    /**
     * @param string  $name
     * @param ?string $value
     *
     * @return bool
     */
    public function has( string $name, ?string $value = null ) : bool
    {
        // Get attribute by $name, or false if unset
        $attribute = $this->attributes[$name] ?? false;

        // Check against value if requested
        if ( $value ) {
            return $attribute === $value;
        }

        // If the attribute is anything but false, consider it set
        return $attribute !== false;
    }

    /**
     * Merges one or more attributes.
     *
     * @param array<string, null|array<array-key, string>|bool|int|string>|Attributes $attributes
     *
     * @return $this
     */
    public function merge( Attributes|array $attributes ) : self
    {
        $attributes = $attributes instanceof Attributes ? $attributes->attributes : $attributes;
        return $this->assign( $attributes );
    }

    /**
     * Remove all attributes.
     *
     * @return $this
     */
    public function clear() : self
    {
        $this->attributes = [
            'id'    => null,
            'class' => [],
            'style' => [],
        ];
        return $this;
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
     * @param bool $associative
     *
     * @return array<string, string>
     */
    public function resolveAttributes( bool $associative = false ) : array
    {
        $attributes = [];

        foreach ( $this->attributes as $attribute => $value ) {
            if ( \is_array( $value ) ) {
                if ( ! \array_filter( $value ) ) {
                    continue;
                }
            }

            $value = $this->get( $attribute );

            if ( \is_null( $value ) ) {
                continue;
            }

            if ( $associative ) {
                $attributes[$attribute] = $value;

                continue;
            }

            if ( $value === $attribute ) {
                $attributes[$attribute] = $attribute;
            }
            else {
                $attributes[$attribute] = "{$attribute}=\"{$value}\"";
            }
        }

        dump( $attributes );

        return $attributes;
    }

    /**
     * @param array<string, null|array<array-key, string>|bool|int|string> $attributes
     * @param bool                                                         $override
     */
    private function setAttributes( array $attributes, bool $override = false ) : void
    {
        foreach ( $attributes as $name => $value ) {
            $name = $this->name( $name );

            if ( $name === 'id' ) {
                \assert(
                    \is_string( $value ),
                    "Attribute '{$name}' can only be string. ".\gettype( $value ).' provided.',
                );
                $this->attributes[$name] = slug( $value );

                continue;
            }

            if ( $name == 'class' || $name == 'classes' ) {
                if ( ! $value ) {
                    continue;
                }

                \assert(
                    \is_array( $value ) || \is_string( $value ),
                    "Attribute '{$name}' can only be string|string[]. ".\gettype( $value ).' provided.',
                );
                if ( $override ) {
                    $this->handleClasses()->clear();
                }
                $this->handleClasses()->add( $value );

                continue;
            }

            if ( $name == 'style' || $name == 'styles' ) {
                if ( ! $value ) {
                    continue;
                }

                \assert(
                    \is_array( $value ) || \is_string( $value ),
                    "Attribute '{$name}' can only be string|array<string,string>. ".\gettype(
                        $value,
                    ).' provided.',
                );
                if ( $override ) {
                    $this->handleStyles()->clear();
                }
                $this->handleStyles()->add( $value );

                continue;
            }

            if ( $override === false && $this->has( $name ) ) {
                continue;
            }

            if ( \is_int( $value ) ) {
                $value = (string) $value;
            }

            \assert(
                \is_string( $value ) || \is_null( $value ) || \is_bool( $value ),
                "Attribute '{$name}' can only be null|string|bool. ".\gettype( $value ).' provided.',
            );

            $this->attributes[$name] = $value;
        }
    }

    /**
     * Return a normalized, but unprocessed version of {@see self::$attributes}.
     *
     * @return array<string, array<array-key, string>|bool|string>
     */
    private function attributeArray() : array
    {
        $attributes = \array_filter( $this->attributes );
        if ( isset( $attributes['class'] ) && \is_array( $attributes['class'] ) ) {
            $attributes['class'] = \array_values( $attributes['class'] );
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

        $string = (string) \preg_replace( '/[^a-z0-9-]+/i', '-', $string );

        return \trim( $string, '-' );
    }

    private function handleClasses() : ClassAttribute
    {
        \assert( \is_array( $this->attributes['class'] ) );
        return ClassAttribute::byReference( $this->attributes['class'], $this );
    }

    private function handleStyles() : StyleAttribute
    {
        \assert( \is_array( $this->attributes['style'] ) );
        return StyleAttribute::byReference( $this->attributes['style'], $this );
    }
}
