<?php
require('../../../wp-load.php');
//require('fsThickboxAnnouncement.php');

$fsTbA = new fsThickboxAnnouncement();

$post_id = $fsTbA->getAnnouncementPostId();

$post = get_post($post_id);
echo $post->post_content;

$preview = isset($_GET['admin_preview']) == 'true';
		
$onClickHandler = 'tb_remove()';

if ($preview) {
	$close = $_GET['close_type'];
}
else {
	$close = $fsTbA->getAnnouncementCloseType();
}
if ($close > 1) {
	echo '<p style="text-align:center;" id="fsTBA_Close">';
	if ($close == 2) { // Button
		echo '<input type="button" onClick="'.$onClickHandler.';" value="'.$fsTbA->getAnnouncementCloseLabel().'" />';
	} elseif ($close == 3) { // Link
		echo '<a href="javascript:void(0)" onClick="'.$onClickHandler.';">'.$fsTbA->getAnnouncementCloseLabel().'</a>';
	}
	echo '</p>';
}
exit;
?>