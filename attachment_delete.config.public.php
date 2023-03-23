<?php
require_once BASEDIR . '/includes/class.AttachmentFilter.php';

/**
 * Criteria for attachment removal.
 * 
 * The order matters. Attachments that qualify for the first rules will not be reviewed further.
 * 
 * @see sched_attach_delete.php
 * @see class AttachmentRemoval
 */
$re_agreement = "\\.(pdf|doc|jpeg|jpg|png)";
return array(
	// Conversions data => remove after short term (30 days).
	array(
		'short_name' => 'conv.; non-agg.',
		'task_type' => 'Help desk',
		'product_category' => 'Conversions',
		'name_not_re' => $re_agreement,
		'min_closed_days' => 30,
	),
	// j/w agreements => remove after 5 years
	array(
		'short_name' => 'conv.; agreement',
		'task_type' => 'Help desk',
		'product_category' => 'Conversions',
		'name_re' => $re_agreement,
		'min_closed_days' => 5 * 365,
	),
	// size
	AttachmentConf::size('50M', 3 * 365, 'very large files'),
	AttachmentConf::size('20M', 5 * 365, 'large files'),
	AttachmentConf::size('10M', 10 * 365, 'medium files'),
);
