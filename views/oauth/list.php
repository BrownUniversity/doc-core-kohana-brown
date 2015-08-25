<?php

$columns = array(
	array(
		'type' => DOC_Helper_Table::TYPE_DATA,
		'property' => 'auth_code',
		'heading' => 'Auth Code',
		'format' => array(
			'type' => DOC_Helper_Table::FORMAT_CALLBACK,
			'method' => 'auth_code',
		)
	),
	array(
		'type' => DOC_Helper_Table::TYPE_DATA,
		'property' => 'token_expires',
		'heading' => 'Token Expiry',
		'format' => array(
			'type' => DOC_Helper_Table::FORMAT_CALLBACK,
			'method' => 'expiry',
		),
	),
);

if (count($oauths) > 0) {
	$table = View::factory('tablesorter/table');
	$table->data = $oauths;
	$table->column_specs = $columns;
	$table->table_attributes = array(
		'class' => 'tablesorter',
		'id' => 'sortableTable'
	);
	echo $table;
} else {
	$new_object_link_view = View::factory( 'newobjectlink' ) ;
	$new_object_link_view->new_object_link = array(
		'url_fragment' => 'admin/oauth/authcode/-1',
		'name' => 'New Auth Code',
		'class' => 'newObjectLink'
	) ;
	print( $new_object_link_view->render() ) ;
}