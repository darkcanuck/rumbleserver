<?php

require_once 'classes/common.php';


// get contributor data
/*$qry = "SELECT user, MAX(timestamp) as last, COUNT(version) as count
		FROM battle_results
		WHERE state IN ('" . STATE_NEW . "', '" . STATE_OK . "', '" . STATE_RATED . "')
		GROUP BY user
		ORDER BY count DESC";
$db->query($qry);
$allrows = $db->all();
*/
$users = new UploadUsers($db);
$userlist = $users->getContributors();

//output header
echo "<h2>CONTRIBUTORS</h2>
<table border=1>
<tr>
	<td><b>User</b></td>
	<td><b># Battles</b></td>
	<td><b>Last Battle</b></td>
</tr>";


// output data
$total = 0;
foreach ($userlist as $rs) {
	echo "<tr>";
	echo "<td>{$rs['username']}</td>";
	echo "<td>" . $rs['battles'] . "</td>";
	echo "<td>{$rs['updated']}</td>";
	$total += $rs['battles'];
}

//output footer
echo "</table>
<p><b>Total battles = $total</b></p>";
			
?>