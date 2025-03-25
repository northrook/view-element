<?php

declare(strict_types=1);

namespace Core\View\Element;

use InvalidArgumentException;

use Stringable, UnitEnum, BadMethodCallException;
use Support\PropertyAccessor;
use function Support\{is_stringable};
use const Support\{
    TAG_CONTENT,
    TAG_HEADING,
    TAG_INLINE,
    TAG_SELF_CLOSING,
    TAG_STRUCTURE,
};

/**
 * @property-read  string $name
 * @property-read  bool   $isValidTag
 * @property-read  bool   $isContent
 * @property-read  bool   $isHeading
 * @property-read  bool   $isInline
 * @property-read  bool   $isSelfClosing
 */
final class Tag implements Stringable
{
    use PropertyAccessor;

    public const array TAGS = [
        ...self::STRUCTURE,
        ...self::CONTENT,
        ...self::HEADING,
        ...self::INLINE,
    ];

    public const array STRUCTURE = TAG_STRUCTURE;

    public const array CONTENT = TAG_CONTENT;

    public const array HEADING = TAG_HEADING;

    public const array INLINE = TAG_INLINE;

    public const array SELF_CLOSING = TAG_SELF_CLOSING;

    /** @var non-empty-lowercase-string */
    protected string $tag;

    private function __construct( string $name )
    {
        $this->set( $name );
    }

    public static function from(
        mixed        $value,
        false|string $fallback = 'div',
    ) : self {
        if ( $value instanceof self ) {
            return $value;
        }

        if ( ! is_stringable( $value ) ) {
            $type    = \gettype( $value );
            $message = "must be null or stringable. '{$type}' provided.";
            \trigger_error(
                message     : __METHOD__.'::from( $value ) '.$message,
                error_level : E_USER_WARNING,
            );

            $value = $fallback;
        }

        $string = (string) $value;

        if ( \ctype_alpha( $string ) ) {
            return new self( $string );
        }

        $openTag = \mb_strpos( $string, '<' );

        if ( $openTag !== false ) {
            $result   = '';
            $distence = \min( \mb_strlen( $string ), $openTag + 25 );

            for ( $i = $openTag; $i < $distence; $i++ ) {
                $character = \mb_substr( $string, $i, 1 );
                if ( \strpbrk( $character, ':- >' ) ) {
                    break;
                }
                $result .= $character;
            }
            $string = \trim( $result, ' <' );
        }

        if ( ! $string && $fallback ) {
            $string = $fallback;
        }

        return new self( $string );
    }

    /**
     * Retrieve the tag `name`.
     *
     * @return non-empty-lowercase-string
     */
    public function __toString() : string
    {
        return $this->tag;
    }

    /**
     * @param string $set
     *
     * @return self
     */
    public function __invoke( string $set ) : self
    {
        return $this->set( $set );
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function __get( string $property ) : bool|string
    {
        return match ( $property ) {
            'name'          => $this->tag,
            'isValidTag'    => $this::isValidTag( $this->tag ),
            'isContent'     => $this::isContent( $this->tag ),
            'isHeading'     => $this::isHeading( $this->tag ),
            'isInline'      => $this::isInline( $this->tag ),
            'isSelfClosing' => $this::isSelfClosing( $this->tag ),
            default         => throw new BadMethodCallException(
                'Warning: Undefined method: '.$this::class."::\${$property}",
            ),
        };
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function is( string $tag ) : bool
    {
        return $this->tag === $tag;
    }

    /**
     * @param string $tag
     *
     * @return self
     */
    public function set( string $tag ) : Tag
    {
        $tag = \strtolower( $tag );

        if ( empty( $tag ) || ! \ctype_alnum( $tag ) ) {
            $message = "Invalid '\$tag' string provided: '{$tag}'. Only ASCII letters allowed.";
            throw new InvalidArgumentException( $message );
        }

        $this->tag = $tag;

        return $this;
    }

    public function getTagName() : string
    {
        return $this->tag;
    }

    /**
     * @param null|array<int|string, null|array<array-key, ?string>|Attributes|scalar|UnitEnum>|Attributes $attributes
     *
     * @return string
     */
    public function getOpeningTag(
        null|array|Attributes $attributes = null,
    ) : string {
        if ( \is_array( $attributes ) ) {
            $attributes = new Attributes( ...$attributes );
        }

        return "<{$this->tag}{$attributes}>";
    }

    /**
     * @return null|string
     */
    public function getClosingTag() : ?string
    {
        return \in_array( $this->tag, $this::SELF_CLOSING ) ? null : "</{$this->tag}>";
    }

    /**
     * Check if the provided tag is a valid HTML tag.
     *
     * - Only checks native HTML tags.
     * - Instanced calls checks `$this->name`.
     *
     * @param null|self|string $name
     *
     * @return bool
     */
    public static function isValidTag(
        null|string|Tag $name = null,
    ) : bool {
        if ( ! $name ) {
            return false;
        }

        return \in_array( \strtolower( (string) $name ), [...Tag::TAGS, ...Tag::SELF_CLOSING], true );
    }

    /**
     * Instanced calls checks `$this->name`.
     *
     * @param null|self|string $name
     *
     * @return bool
     */
    public static function isContent(
        null|string|self $name = null,
    ) : bool {
        if ( ! $name ) {
            return false;
        }
        return \in_array( \strtolower( (string) $name ), [...Tag::HEADING, ...Tag::INLINE, 'p'], true );
    }

    /**
     * Instanced calls checks `$this->name`.
     *
     * @param null|self|string $name
     *
     * @return bool
     */
    public static function isHeading(
        null|string|self $name = null,
    ) : bool {
        if ( ! $name ) {
            return false;
        }
        return \in_array( \strtolower( (string) $name ), Tag::HEADING );
    }

    /**
     * Instanced calls checks `$this->name`.
     *
     * @param null|self|string $name
     *
     * @return bool
     */
    public static function isInline(
        null|string|self $name = null,
    ) : bool {
        if ( ! $name ) {
            return false;
        }
        return \in_array( \strtolower( (string) $name ), Tag::INLINE, true );
    }

    /**
     * Instanced calls checks `$this->name`.
     *
     * @param null|self|string $name
     *
     * @return bool
     */
    public static function isSelfClosing(
        null|string|self $name = null,
    ) : bool {
        if ( ! $name ) {
            return false;
        }
        return \in_array( \strtolower( (string) $name ), Tag::SELF_CLOSING, true );
    }
}
