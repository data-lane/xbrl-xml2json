<?php

namespace Datalane;

use Datalane\XBRL\Constants;
use Datalane\XBRL\Instance;

class JSONFile
{
    public const VERSION = '2021-02-03';
    public const DOCUMENT_TYPE = 'https://xbrl.org/CR/' . self::VERSION . '/xbrl-json';

    public static function convertInstance(Instance $inst)
    {

        $ns = $inst->getNamespaces();
        unset($ns[Constants::XBRLDI]);
        unset($ns[Constants::XBRLI]);
        unset($ns[Constants::LINK]);
        unset($ns[Constants::XLINK]);
        $ns[Constants::XBRL] = 'https://xbrl.org/CR/' . self::VERSION;
        $json = ['documentInfo' => [
            'documentType' => self::DOCUMENT_TYPE,
                'features' => [
                    'xbrl:canonicalValues' => false
                ],
            'namespaces' => $ns,
            'taxonomy' => $inst->getTaxonomy()
            ],
            'facts' => $inst->getFacts()
        ];
        return json_encode($json, JSON_UNESCAPED_SLASHES);
    }
}
