<pre>
<strong>INFO:</strong>&nbsp;&nbsp;&nbsp;Basic messages logged at each signficant point in process<br />
<strong>DEBUG:</strong>&nbsp;&nbsp;Possible problem or event that may explain unexpected behaviour</br />
<strong>ERROR:</strong>&nbsp;&nbsp;Minimee has failed, and this is why.
</pre>
<hr />

<ol>

<?php
	foreach($logs as $log) :
		$class = ($log[0] == 'ERROR') ? 'flash' : '';
		echo '<li class="' . $class . '">[' . $log[0] . ']<br />' . $log[1] . '</li>';
	endforeach; 
?>
</ol>