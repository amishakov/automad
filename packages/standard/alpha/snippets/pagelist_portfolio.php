<?php defined('AUTOMAD') or die('Direct access not permitted!'); ?>

	<ul class="masonry grid-margin uk-grid uk-grid-width-small-1-2 uk-grid-width-medium-1-3">
		<@ foreach in pagelist @>
			<li class="masonry-item">
				<a 
				href="@{ url }" 
				class="uk-panel uk-panel-box uk-panel-box-hover
				">
					<@ with @{ imageTeaser | def('*.jpg, *.jpeg, *.png, *.gif') } { 
						width: 430 
					} @>
						<img 
						class="uk-margin-small-bottom"
						src="@{ :fileResized }" 
						alt="@{ :basename }"
						width="@{ :widthResized }" 
						height="@{ :heightResized }" 
						/>		
					<@ end @>
					<div class="uk-panel-title">
						@{ title }
					</div>
					<span class="uk-text-muted">
						<@ ../../snippets/date.php @>
					</span>
				</a>
			</li>
		<@ end @>
	</ul>	