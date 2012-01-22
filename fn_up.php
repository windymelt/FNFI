<?php
date_default_timezone_set('Asia/Tokyo');
function readtoeof ($fp, $string){
	fputs ($fp, $string);
	$temp = "";
	$return = "";
	while (1){
		$temp = fgets ($fp, 128);
		//echo $temp;
		$return = $return . $temp;
		if (preg_match('/^EndMessage/', $temp)){break;}
	}
	//echo $return;
	return $return;
}

function makeXML ($name, $data, $data_length, $desc, $datakey){
	$imp = new DOMImplementation();
	$dtd = $imp->createDocumentType('FNFI', 'Freenet File Index 0.1', 'http://localhost:8888/CHK@3tKHs~gb-PgmhXnfuJ00PD5RuIAykBzisNM9x542nqc,36eqIi3A7a-kWqPg2lVtdKCsi5J51K~sUniGkiXf154,AAIC--8/FNFI01.dtd');
	$dom = $imp->createDocument('','', $dtd);

	//add root 'files'
	$filesnode = $dom->appendChild($dom->createElement('FNFI'));
		$filenode = $filesnode->appendChild($dom->createElement('file'));
		$keynode = $filenode->setAttribute('key', $datakey);
			$namenode = $filenode->appendChild($dom->createElement('name'));
			$namenode->appendChild($dom->createTextNode($name));
			$datenode = $filenode->appendChild($dom->createElement('date'));
			$datenode->appendChild($dom->createTextNode(date("r")));
			$descnode = $filenode->appendChild($dom->createElement('desc'));
			$descnode->appendChild($dom->createTextNode($desc));
			$sizenode = $filenode->appendChild($dom->createElement('size'));
			$sizenode->appendChild($dom->createTextNode($data_length));
			$hashnode = $filenode->appendChild($dom->createElement('hash'));
			$hashnode->appendChild($dom->createTextNode(hash ('sha256', $data)));
			$hashnode->setAttribute('hashtype', 'sha256');

	$dom->formatOutput = true;
	return $dom->saveXML();
}

$fp = fsockopen ("localhost", 9481, $errno, $errstr, 30);
if (!$fp) {
	echo "Unable to connect\n";
} else {
	/*Read file from given param, calculate size of it, make string to put*/
	$data = file_get_contents ($argv[1]);
	$data_length = strlen($data);
	$putstr = "ClientPut\nURI=CHK@\nIdentifier=$argv[1]\nDataLength=$data_length\nData\n$data";
	//echo $putstr;
	

	/*Send message to Freenet via FCP, Receive, Get Key of the sent file*/
	readtoeof ($fp, "ClientHello\nName=IndexTan\nExpectedVersion=2.0\nEndMessage\n");
	$return = readtoeof ($fp, $putstr);
	preg_match ('/^URI=(.*?)$/m', $return, $filekey);
	echo $filekey[1] . "\n";
	

	/*Make XML*/
	$XML_data = makeXML ($argv[1], $data, $data_length, $argv[2], $filekey[1]);

	$XML_length = strlen ($XML_data);
	$putstr = "ClientPut\nURI=KSK@FN_INDEX.XML\nIdentifier=Freenet Index XML\nDataLength=$XML_length\nData\n$XML_data";
	$return = readtoeof ($fp, $putstr);
	preg_match ('/^URI=(.*?)$/m', $return, $filekey);
	echo $filekey[1] . "\n";

	readtoeof($fp, 'Disconnect EndMessage\n');
	socket_set_timeout($fp, 2);

	$stat = socket_get_status ($fp);
	if ($stat["timed_out"]) {echo "timeout\n";}
	fclose ($fp);
	}
?>
