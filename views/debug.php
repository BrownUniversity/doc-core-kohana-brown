<!-- I don't think we're using this anywhere. Deprecate? -->
<div id="debug">
	<?php
		$debug_array = array(
			'Debug' => $debug,
			'Kohana Request' => \Kohana\Request::current(),
			'GET' => $_GET,
			'POST' => $_POST,
			'COOKIE' => $_COOKIE,
			'REQUEST' => $_REQUEST
		) ;

		foreach( $debug_array as $key => $val ) {
			print('<div class="debug-section">') ;
			print("<div class='debug-section-head'>{$key}</div>") ;
			print("<div class='debug-section-data'>") ;
			\BrownUniversity\DOC\Util\Debug::dump( $val, FALSE ) ;
			print("</div>") ;
			print("</div>") ;
		}
	?>
</div>