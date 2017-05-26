<?php
	$affils = array(
		'any'       => 'any',
		'faculty'   => 'faculty',
		'staff'     => 'staff',
		'student'   => 'student',
		'applicant' => 'applicant',
	);
?>
<?php echo \Form::open('impersonate') ?>
<h1>User Impersonation Search</h1>
<p>
    <b>Search String:</b><br />
    <?php echo \Form::input('search_string',NULL,array('autocorrect' => 'off', 'autocomplete' => 'off')) ?>
</p>

<p>
	<b>Primary Affiliation:</b><br />
	<?php echo \Form::select('affiliation', $affils) ?>
</p>

<p>
    <?php echo \Form::submit('btn_submit', 'Search') ?>
    <?php echo \Form::submit('btn_submit', 'Cancel') ?>
</p>
<?php echo \Form::close() ?>
<div id="alert">
	<div>
		This will attempt to take you back to the page you were on before
		accessing the impersonate function. Be sure that the user you want
		to impersonate has access to that page, or errors may result.
	</div>
</div>