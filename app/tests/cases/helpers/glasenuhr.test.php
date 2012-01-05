<?php

App::import('Helper', 'Glasenuhr');

class GlasenuhrHelperTestCase extends CakeTestCase {

	function startTest($message) {
		echo "<h3>Starting ".get_class($this)." - $message()</h3>\n";
		$this->Glasenuhr =& new GlasenuhrHelper();
	}

	function endTest() {
		unset($this->Glasenuhr);
		ClassRegistry::flush();
	}

	function testFtime() {

		$watches = array (
				0 => __('Nacht', true),
				1 => __('Morgen', true),
				2 => __('Vormittag', true),
				3 => __('Nachmittag', true),
				4 => __('Freiwache', true),
				5 => __('Abend', true),
		);

		$this->Glasenuhr->setup(array('watches' => $watches));

		$input 		= mktime(0, 0);
		$expected = '8 Gl. Abend';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result);

		$input 		= mktime(0, 29);
		$expected = '8 Gl. Abend';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(0, 30, 0);
		$expected = '1 Gl. Nacht';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(0, 31, 0);
		$expected = '1 Gl. Nacht';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(4, 59, 0);
		$expected = '1 Gl. Morgen';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(9, 0, 0);
		$expected = '2 Gl. Vormittag';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(13, 31);
		$expected = '3 Gl. Nachmittag';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(18, 06);
		$expected = '4 Gl. Freiwache';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(22, 44);
		$expected = '5 Gl. Abend';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(15, 17);
		$expected = '6 Gl. Nachmittag';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

		$input 		= mktime(23, 50);
		$expected = '7 Gl. Abend';
		$result 	=	$this->Glasenuhr->ftime($input);
		$this->assertIdentical($expected, $result, "%s (" . strftime('%H:%M', $input). ')');

	}

}
?>
