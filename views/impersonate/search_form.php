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