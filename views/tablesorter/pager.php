<?php
	if( !isset( $pager_id )) {
		$pager_id = 'pager' ;
	}
?>

<div id="<?php print( $pager_id ) ; ?>" class="pager">
	<form>
		<a class="first">|&lt;</a>
		<a class="prev">&lt;</a>
		<span class="pagedisplay"></span>
		<a class="next">&gt;</a>
		<a class="last">&gt;|</a>
		<select class="pagesize">
			<option value="25">25</option>
			<option value="50" selected="selected">50</option>
			<option value="100">100</option>
			<option value="250">250</option>
		</select>
	</form>
</div>