<?php

namespace Datalane\XBRL;

class Constants
{
    public const XML     = 'xml';
    public const XBRL    = 'xbrl';
    public const XBRLI   = 'xbrli';
    public const LINK    = 'link';
    public const XLINK   = 'xlink';
    public const XBRLDI  = 'xbrldi';
    public const XBRLDT  = 'xbrldt';
    public const SCHEMA  = 'xs';
    public const GENERIC = 'gen';

    /**
     * @var array<string, string> $prefixes
     */
    public static array $prefixes = [
        'xml'    => 'http://www.w3.org/XML/1998/namespace',
        'xbrli'  => 'http://www.xbrl.org/2003/instance',
        'link'   => 'http://www.xbrl.org/2003/linkbase',
        'xlink'  => 'http://www.w3.org/1999/xlink',
        'xbrldi' => 'http://xbrl.org/2006/xbrldi'
    ];

    /**
     * @var array<string, string> $standardNamespaces
     */
    public static $standardNamespaces = [];

    public static function __static(): void
    {
        $prefixes = [
            self::SCHEMA,
            self::XBRLI,
            self::LINK,
            self::XLINK,
            self::GENERIC,
            self::XBRLDT,
            self::XBRLDI
        ];
        self::$standardNamespaces = array_flip(array_intersect_key(self::$prefixes, array_flip($prefixes)));
    }
}
