<?php

	App::uses('BbcodeProcessor', 'Lib/Bbcode/Processors');

	/**
	 * Class BbcodeImageUploadLegacyPreprocessor
	 *
	 * @todo should be permanently done in Model and saved there
	 */
	class BbcodeImageUploadLegacyPreprocessor extends BbcodeProcessor {

		public function process($string) {
			return preg_replace('/\[img#\](.*?)\[\/img\]/', "[upload]\\1[/upload]", $string);
		}

	}