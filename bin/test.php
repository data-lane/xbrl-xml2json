<?php

require __DIR__ . '/../vendor/autoload.php';

use Datalane\XBRL\Instance;
use Datalane\JSONFile;

libxml_clear_errors();
$x = new SimpleXMLElement(file_get_contents($argv[1]));
$xml_errors = libxml_get_errors();


$inst = new Instance($x);
echo JSONFile::convertInstance($inst);

//print_r($inst->getPrefixes($x));
