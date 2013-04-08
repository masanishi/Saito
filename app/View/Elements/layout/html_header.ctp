<?= $this->Html->docType('html5'); ?>
<html>
	<head>
    <title><?= $title_for_layout ?></title>
		<link rel="icon" type="image/vnd.microsoft.icon" href="/favicon.ico" />
		<?php
			echo $this->Html->charset();
			echo $this->fetch('meta');
			echo $this->fetch('css');

			$stylesheets =
					[
						'stylesheets/static.css',
						'stylesheets/styles.css'
					];
			if (Configure::read('debug') > 0) {
				$stylesheets[] = 'stylesheets/cake.css';
			}
			echo $this->Html->css($stylesheets);

			if (isset($CurrentUser) && $CurrentUser->isLoggedIn()) :
				echo $this->UserH->generateCss($CurrentUser->getSettings());
			endif;

			echo $this->element('layout/script_tags');
		?>
		<?php
			/*
			 * fixing safari mobile fubar;
			 * see: http://stackoverflow.com/questions/6448465/jquery-mobile-device-scaling
			 */
			?>
		<meta name="viewport" content="height=device-height,width=device-width" />
		<script type="text/javascript">
			//<![CDATA[
    if (navigator.userAgent.match(/iPad/i)) {
        var $viewport = $('head').children('meta[name="viewport"]');
        $(window).bind('orientationchange', function() {
            if (window.orientation == 90 || window.orientation == -90 || window.orientation == 270) {
                $viewport.attr('content', 'height=device-width,width=device-height,initial-scale=1.0');
            } else {
                $viewport.attr('content', 'height=device-height,width=device-width,initial-scale=1.0');
            }
        }).trigger('orientationchange');
    }
			//]]>
		</script>
