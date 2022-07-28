<?php

//http://www.xbrl.org/Specification/XBRL-2.1/REC-2003-12-31/XBRL-2.1-REC-2003-12-31+corrected-errata-2013-02-20.html
namespace Datalane\XBRL;

use SimpleXMLElement;

class Instance
{
    /**
     * @var \SimpleXMLElement $xml
     */
    private $xml;

    /**
     * @var array<string, string> $namespaces
     */
    private $namespaces;

    /**
     * @var array<string, array> $facts
     */
    private $facts;

    /**
     * @var array<string, array> $contexts
     */
    private $contexts;

    /**
     * @var array<string, array> $units
     */
    private $units;
//    private $footnotes;

    /**
     * @var array<int, string> $taxonomyFiles
     */
    private $taxonomyFiles;

    public function __construct(SimpleXMLElement $xml)
    {
        $this->xml = $xml;
        if ('xbrl' === $xml->getName()) {
            $this->xml->registerXPathNamespace(Constants::XBRLI, Constants::$prefixes[Constants::XBRLI]);

            $linkRef = $this->xml->children(Constants::$prefixes[Constants::LINK]);
            if (!property_exists($linkRef, 'schemaRef')) {
                throw new \Exception('4.2 Every XBRL Instance MUST contain at least one <schemaRef> element.');
            }

            $this->namespaces = $this->xml->getDocNamespaces(true);

            $this->taxonomyFiles = [];
            foreach ($linkRef->schemaRef as $eKey => $schemaRef) {
                $xlinkAttributes = $schemaRef->attributes(Constants::$prefixes[Constants::XLINK]);
                if (!isset($xlinkAttributes['href'])) {
                    throw new \Exception('4.2.2 A <schemaRef> element MUST have an @xlink:href attribute.');
                }
                $taxonomyFile = (string) $xlinkAttributes['href'];
/*
                $attributes = $schemaRef->attributes(Constants::$prefixes[Constants::XML]);
                $base = isset($attributes['base']) ? ((string) $attributes['base']) : '';

                if (!empty($base) && '/' !== substr($base, -1)) {
                    $base .= '/';
                }
*/
                $this->taxonomyFiles[] = $taxonomyFile;
            }
        }
        $this->processElements($this->xml);
    }

    /**
     * @return array<string, array<int, array<string, array<string, array<int, string|false>>|int|string>>>
     */
    protected function processScenarioElement(\SimpleXMLElement $nodes): array
    {
        $component = [];
        $ordinal = 0;

        $domNode = dom_import_simplexml($nodes);
        if ($domNode !== false) {
            foreach ($domNode->childNodes as $childNode) {
                if (XML_ELEMENT_NODE != $childNode->nodeType) {
                    continue;
                }

                $memberKey = $childNode->localName;
                $member = simplexml_import_dom($childNode);

                if (
                    ($childNode->namespaceURI == Constants::$prefixes[Constants::XBRLDI]) &&
                    ('explicitMember' == $memberKey || 'typedMember' == $memberKey)
                ) {
                    $memberAttributes = $member->attributes();
                    if ('explicitMember' == $memberKey) {
                        $component[$member->getName()][] = [
                            'dimension' => (string) $memberAttributes['dimension'],
                            'member' => (string) $member,
                            'ordinal' => $ordinal,
                        ];
                    } else {
                        $members = [];
                        $namespaces = [];

                        foreach ($member->getNamespaces(true) as $prefix => $namespace) {
                            if (isset($namespaces[$namespace])) {
                                continue;
                            }
                            $namespaces[$namespace] = $prefix;

                            $namespaceMembers = $member->children($namespace);
                            if (!count($namespaceMembers)) {
                                continue;
                            }

                            $prefixMap = [];
                            $localNamespaces = $namespaceMembers->getDocNamespaces(true, false);
                            $globalNamespaces = array_flip($namespaceMembers->getDocNamespaces(false, true));
                            foreach ($localNamespaces as $localPrefix => $localNamespace) {
                                if (!isset($globalNamespaces[$localNamespace])) {
                                    continue;
                                }
                                $globalPrefix = $globalNamespaces[$localNamespace];
                                $prefixMap[$localPrefix] = $globalPrefix;
                            }

                            if (empty($prefix)) {
                                $name = $namespaceMembers->getName();
                            } else {
                                // Lookup the prefix for the namespace
                                if (isset($prefixMap[$prefix])) {
                                    $prefix = $prefixMap[$prefix];
                                }
                                $name = $prefix . ':' . $namespaceMembers->getName();
                            }

                            $members[$name] = [];
                            foreach ($namespaceMembers as $namespaceMember) {
                                /** @var \SimpleXMLElement $namespaceMember */
                                $xml = $namespaceMember->asXML();
                                if ($xml !== false) {
                                    foreach ($prefixMap as $localPrefix => $globalPrefix) {
                                        $xml = str_replace("$localPrefix:", "$globalPrefix:", $xml);
                                    }
                                }

                                $members[$name][] = $xml;
                            }
                        }
                        $component[$member->getName()][] = [
                            'dimension' => (string) $memberAttributes['dimension'],
                            'member' => $members,
                            'ordinal' => $ordinal,
                        ];
                    }
                } else {
                    throw new \Exception('Read taxonomy');
                }
            }
        } else {
            //TODO: what if domnode is false
        }
        return $component;
    }

    /**
     * @return void
     */
    protected function processContextElement(SimpleXMLElement $element): void
    {
        $attributes = $element->attributes();

        if (!isset($attributes['id'])) {
            throw new \Exception('4.7.1 Every <context> element MUST include the @id attribute');
        } else {
            $id = (string) $attributes['id'];
        }
        $context = [];

        if (property_exists($element, 'entity')) {
            $entityElement = $element->entity;
            $entity = [];

            $identifierElement = $entityElement->children(Constants::$prefixes[Constants::XBRLI])->identifier;
            if ($identifierElement) {
                $identifierAttributes = $identifierElement->attributes();

                $identifier = [
                    'scheme' => (string) $identifierAttributes['scheme'],
                    'value' => trim((string) $identifierElement),
                ];
                $entity['identifier'] = $identifier;

                switch ((string)$identifierAttributes['scheme']) {
                    case 'http://www.sec.gov/CIK':
                        $schemePrefix = 'cik';
                        break;
                    case 'http://standard.iso.org/iso/17442':
                        $schemePrefix = 'lei';
                        break;
                    default:
                        $schemePrefix = 'scheme';
                }
                $this->namespaces[$schemePrefix] = (string) $identifierAttributes['scheme'];
            }

            $context['entity'] = $entity;
        } else {
            throw new \Exception('4.7 Element <context> must contain entity element');
        }

        // TODO: Process segment

        // Process scenario
        if (property_exists($element, 'scenario')) {
            $component = $this->processScenarioElement($element->scenario);
            if ($component) {
                $context['scenario'] = $component;
            }
        }

        if (property_exists($element, 'period')) {
            $periodElement = $element->period;
            $period = ['startDate' => '', 'endDate' => '', 'type' => 'duration'];

            foreach ($periodElement->children(Constants::$prefixes[Constants::XBRLI]) as $periodChildKey => $periodChild) {
                $date = (string) $periodChild;
                switch ($periodChild->getName()) {
                    case 'endDate':
                        $period['endDate'] = $date;
                        break;
                    case 'startDate':
                        $period['startDate'] = $date;
                        break;
                    case 'instant':
                        $period['type'] = 'instant';
                        $period['startDate'] = $date;
                        $period['endDate'] = $date;
                        break;
                    case 'forever':
                        $period['startDate'] = date('Y-m-d', 0);
                        $period['endDate'] = date('Y-m-d', PHP_INT_MAX);
                        break;
                }
            }

            //An end date or instant with no time component is interpreted as
            //24:00:00 on that day (or, equivalently, as 00:00:00 on the following day)
            $tmp = new \DateTime($period['startDate']);
            $context['period'] = $tmp->modify('+1 day')->format('Y-m-d\T00:00:00');
            if ($period['type'] == 'duration') {
                $tmp = new \DateTime($period['endDate']);
                $period['endDate'] = $tmp->modify('+1 day')->format('Y-m-d\T00:00:00');
                $context['period'] .= '/' . $period['endDate'];
            }
        } else {
            throw new \Exception('4.7 Element <context> must contain period element');
        }

        $this->contexts[$id] = $context;
    }

    /**
     * @return void
     */
    protected function processUnitElement(SimpleXMLElement $element): void
    {
        $attributes = $element->attributes();
        if (!isset($attributes['id'])) {
            throw new \Exception('4.8.1 Every <unit> element MUST include an @id attribute');
        } else {
            $id = (string) $attributes['id'];
        }

        $add = function ($measures) {
            $result = [];
            foreach ($measures as $measure) {
                $result[] = (string) $measure;
            }

            return $result;
        };

        if (property_exists($element, 'measure')) {
            $measures = $element->children(Constants::$prefixes[Constants::XBRLI])->measure;
            $this->units[$id]['measures'] = $add($measures);
        } elseif (property_exists($element, 'divide')) {
            $divide = $element->divide;
            if (property_exists($divide, 'unitNumerator')) {
                $this->units[$id]['divide']['numerator'] = [];

                $unitNumerator = $divide->unitNumerator;
                if (property_exists($unitNumerator, 'measure')) {
                    $this->units[$id]['divide']['numerator'] += $add($unitNumerator->measure);
                }
            }

            if (property_exists($divide, 'unitDenominator')) {
                $this->units[$id]['divide']['denominator'] = [];

                $unitDenominator = $divide->unitDenominator;
                if (property_exists($unitDenominator, 'measure')) {
                    $this->units[$id]['divide']['denominator'] += $add($unitDenominator->measure);
                }
            }
        }
    }

    protected function processElements(SimpleXMLElement $fromRoot): void
    {
        $namespaces = [];
        foreach ($this->namespaces as $prefix => $namespace) {
            if (!isset($namespaces[$namespace])) {
                if ($namespace === Constants::$prefixes[Constants::XBRLI]) {
                    $namespaces[$namespace] = $prefix;

                    //$this->processXBRLIElement();
                    foreach ($fromRoot->children($namespace) as $elementKey => $element) {
                        switch ($elementKey) {
                            case 'context':
                                $this->processContextElement($element);
                                break;
                            case 'unit':
                                $this->processUnitElement($element);
                                break;
                            case 'item':
                            case 'tuple':
                                break;
                            default:
                                throw new \Exception('4.9 Elements other than context,unit,item,tuple are not allowed in the xbrli namespace');
                        }
                    }
                }
            }
        }

        $fidx = 36760;
        foreach ($this->namespaces as $prefix => $namespace) {
            if (!isset($namespaces[$namespace])) {
                $namespaces[$namespace] = $prefix;
                foreach ($fromRoot->children($namespace) as $elementKey => $element) {
                    $attributes = $element->attributes();
                    //It's a fact
                    if (isset($attributes['unitRef']) || isset($attributes['contextRef'])) {
                        $fact = ['value' => (string) $element];
                        if (isset($attributes['decimals'])) {
                            $fact['decimals'] = (int) $attributes['decimals'];
                        }
                        $fact['dimensions'] = [
                            'concept' => $prefix . ':' . $element->getName(),
                            'entity' => 'scheme:' . $this->contexts[(string) $attributes['contextRef']]['entity']['identifier']['value'],
                            'period' => $this->contexts[(string) $attributes['contextRef']]['period'],
//                            'language' => 'et-ee'
                        ];

                        $unitRef = (string) $attributes['unitRef'];
                        if (isset($this->units[$unitRef])) {
                            if (isset($this->units[$unitRef]['measures'])) {
                                $fact['dimensions']['unit'] = $this->units[$unitRef]['measures'][0];
                            } else {
                                //TODO: multiple measures or divide
                            }
                        }

                        if (isset($this->contexts[(string) $attributes['contextRef']]['scenario'])) {
                            $scenario = $this->contexts[(string) $attributes['contextRef']]['scenario'];
                            $fact['dimensions'][$scenario['explicitMember'][0]['dimension']] = $scenario['explicitMember'][0]['member'];
                        }
                        $this->facts['f' . $fidx++] = $fact;
                    }
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function getTaxonomy(): array
    {
        return $this->taxonomyFiles;
    }

    /**
     * @return array<string, string>
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * @return array<string, array>
     */
    public function getFacts(): array
    {
        return $this->facts;
    }
}
