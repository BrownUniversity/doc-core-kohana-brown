<?php
switch ($output_type)
{
	case 'xml' :
		$xml = new SimpleXMLElement( "<?xml version='1.0' standalone='yes'?>\n<response />" ) ;
		$xml->addAttribute('status', 'fail');
		$error = $xml->addChild('error', $message);
		$error->addAttribute('code', $code);
		if ($code == 405)
		{
			$types = $xml->addChild('allowedTypes');
			foreach ($this->supported_methods as $sm)
				$type = $types->addChild('allowedType', strtoupper($sm));
		}
		echo $xml->asXML();
		break;
	case 'json' :
		$json = new stdClass() ;
		$json->response = new stdClass() ;
		$json->response->code = $code;
		$json->response->message = $message;
		echo json_encode($json);
		break;
	case 'html' :
		$message = <<< HTML
<html>
<head>
<title>Error: {$code}</title>
</head>
<body>
Error: {$code} &mdash; {$message}
</body>
</html>
HTML;
		echo $message;
		break;
}

?>