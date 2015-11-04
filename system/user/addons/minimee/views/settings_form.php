<div class="box mb">
	<?php if ($inlines = ee('CP/Alert')->getAllInlines()) : ?>
		<?php
			/*
				As of EE3.1.0 DP2, any inline alerts have negative top left & right margins, and a positive bottom margin,
				presumably to help stack them or something, but it results in a potentially muddled UI.
				So for now, I'm not allowing any inline alerts to be dismissed (assuming this bug gets fixed: https://ellislab.com/forums/viewthread/248346/),
				and establishing a container negative margin, equal to that of the alert's bottom margin.

				Oh also, we have to wrap our alerts in .tbl-ctrls because that element has the necessary padding
				that the alert negative margins are supposed to slot into.

				This seems like a lot of back-bending, but I'm *trying* to adopt the new CP UI.

				*sigh*
			*/
		?>
		<div class="tbl-ctrls" style="margin-bottom: -15px;">
			<?=$inlines?>
		</div>
	<?php endif ?>

	<?php $this->embed('ee:_shared/form')?>
</div>
