# XBRL file converter from XML to JSON format

Convert XBRL-based financial/business document from XML to JSON format, according to XBRL Consortium recommendations ([xbrl-json-REC-2021-10-13](https://www.xbrl.org/Specification/xbrl-json/REC-2021-10-13/xbrl-json-REC-2021-10-13.html)).

## Quickstart example

Here is an simple command line tool to convert xbrl file to json

```php
use Datalane\XBRL\Instance;
use Datalane\JSONFile;

require __DIR__ . '/../vendor/autoload.php';

$x = new SimpleXMLElement(file_get_contents($argv[1]));


$inst = new Instance($x);
echo JSONFile::convertInstance($inst);
```