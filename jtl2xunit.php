<?php

$inputFile = $_SERVER['argv'][1];
$outputFile = $_SERVER['argv'][2];

$dom = new DOMDocument();
$dom->load($inputFile);

$xpath = new DOMXPath($dom);
$httpSampleNodeList = $xpath->query("/testResults/httpSample");

$xUnitDom = new DOMDocument('1.0', 'utf-8');
$xml_testsuites = $xUnitDom->createElement('testsuites');
$xUnitDom->appendChild($xml_testsuites);
$xml_testsuite = $xUnitDom->createElement('testsuite');
$num_failed = 0;

foreach ($httpSampleNodeList as $httpSampleDomElement)
{
  $assertionResults = $httpSampleDomElement->getElementsByTagName('assertionResult');

  if (count($assertionResults) > 0)
  {
    $assertionResult = $assertionResults->item(0);
    if ($assertionResult->getElementsByTagName('failure')->item(0)->nodeValue == "true")
    {
      $num_failed++;
      $xml_testcase = $xUnitDom->createElement('testcase');
      $url = $httpSampleDomElement->getElementsByTagName('java.net.URL')->item(0)->nodeValue;
      $xml_testcase->setAttribute('file', $url);

      $xml_testcase->setAttribute('name', $assertionResult->getElementsByTagName('name')->item(0)->nodeValue . "|" . $url);

      $xml_failure = $xUnitDom->createElement('failure');
      $xml_failure->setAttribute('message', $assertionResult->getElementsByTagName('failureMessage')->item(0)->nodeValue);
      $xml_testcase->appendChild($xml_failure);
      $xml_testsuite->appendChild($xml_testcase);
    }
  }
}

$xml_testsuite->setAttribute('name', 'JMeter');
$xml_testsuite->setAttribute('errors', 0);
$xml_testsuite->setAttribute('failures', $num_failed);
$xml_testsuite->setAttribute('tests', $httpSampleNodeList->length);
$xml_testsuite->setAttribute('timestamp', strftime("%Y-%m-%dT%H:%M:%S"));
$xml_testsuites->appendChild($xml_testsuite);
$xUnitDom->formatOutput = true;

file_put_contents($outputFile, $xUnitDom->saveXML());

if ($num_failed > 0)
{
  exit(1);
}
