<?php

namespace Datalane;

use Datalane\XBRL\Constants;
use Datalane\XBRL\Instance;

class JSONFile
{
    public const VERSION = '2021';
    public const DOCUMENT_TYPE = 'https://xbrl.org/'.self::VERSION.'/xbrl-json';

    public static function convertInstance(Instance $inst): string
    {
        $ns = $inst->getNamespaces();
        unset($ns[Constants::XBRLDI]);
        unset($ns[Constants::XBRLI]);
        unset($ns[Constants::LINK]);
        unset($ns[Constants::XLINK]);
        $ns[Constants::XBRL] = 'https://xbrl.org/' . self::VERSION;
        if (isset($ns[''])) {
            $ns[Constants::XBRLI] = $ns[''];
        }
        unset($ns['']);
        $json = ['documentInfo' => [
            'documentType' => self::DOCUMENT_TYPE,
                'features' => [
                    'xbrl:canonicalValues' => true
                ],
            'namespaces' => $ns,
            'taxonomy' => $inst->getTaxonomy()
            ],
            'facts' => $inst->getFacts()
        ];
        $ret = json_encode($json, JSON_UNESCAPED_SLASHES);
        if ($ret === false) {
            throw new \Exception('Converting to JSON format failed');
        }
        return $ret;
    }
}
