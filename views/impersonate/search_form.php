<?php
	$affils = array(
		'any'       => 'any',
		'faculty'   => 'faculty',
		'staff'     => 'staff',
		'student'   => 'student',
		'applicant' => 'applicant',
	);
?>
<?php echo form::open('impersonate') ?>
<h1>User Impersonation Search</h1>
<p>
    <b>Search String:</b><br />
    <?php echo form::input('search_string') ?>
</p>

<p>
	<b>Primary Affiliation:</b><br />
	<?php echo form::select('affiliation', $affils) ?>
</p>

<p>
    <?php echo form::submit('btn_submit', 'Search') ?>
    <?php echo form::submit('btn_submit', 'Cancel') ?>
</p>
<?php echo form::close() ?>
<div id="alert">
	<div>
		This will attempt to take you back to the page you were on before
		accessing the impersonate function. Be sure that the user you want
		to impersonate has access to that page, or errors may result.
	</div>
</div>