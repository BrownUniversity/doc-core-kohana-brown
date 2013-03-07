<?php
	if( !isset( $pager_id )) {
		$pager_id = 'pager' ;
	}
	if( !isset( $show_all_size )) {
		$show_all_size = NULL ;
	}
	if( !isset( $page_sizes )) {
		$page_sizes = array(
			'25' => '25',
			'50*' => '50',
			'100' => '100',
			'250' => '250'
		) ;
		if( !empty( $show_all_size ) && $show_all_size > max($page_sizes)) {
			$page_sizes[$show_all_size] = 'Show All' ;
		}
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
			<?php
				foreach( $page_sizes as $page_size => $page_size_display ) {
					$real_size = intval($page_size) ;
					print('<option value="'.$real_size.'"' ) ;
					print(strval($page_size) == strval($real_size) ? '' : ' selected="selected"' );
					print('>'.$page_size_display.'</option>' );
				}
			?>
		</select>
	</form>
</div>