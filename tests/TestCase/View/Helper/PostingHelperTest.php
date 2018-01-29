<?php

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\PostingHelper;
use Cake\View\View;
use Saito\App\Registry;
use Saito\Cache\ItemCache;
use Saito\Posting\Posting;
use Saito\Test\SaitoTestCase;

class PostingHelperTest extends SaitoTestCase
{

    public $Helper;

    public function setUp()
    {
        parent::setUp();

        $View = new View();
        $View->set('LineCache', new ItemCache('test'));
        $this->Helper = new PostingHelper($View);
    }

    public function tearDown()
    {
        unset($this->Helper);
        parent::tearDown();
    }

    public function testGetFastLink()
    {
        $this->Helper->request->webroot = 'localhost/';

        //= simple test
        $data = [
            'id' => 3,
            'tid' => 1,
            'pid' => 1,
            'subject' => 'Subject',
            'text' => 'Text'
        ];
        $posting = Registry::newInstance(
            '\Saito\Posting\Posting',
            ['rawData' => $data]
        );
        $expected = '<a href="localhost/entries/view/3" class="">Subject</a>';
        $result = $this->Helper->getFastLink($posting);
        $this->assertEquals($expected, $result);

        //=  test 'class' input
        $class = 'my_test_class foo';
        $expected = '<a href="localhost/entries/view/3" class="my_test_class foo">Subject</a>';
        $result = $this->Helper->getFastLink(
            $posting,
            ['class' => $class]
        );
        $this->assertEquals($expected, $result);

        //* test n/t posting
        $data['text'] = '';
        $posting = Registry::newInstance(
            '\Saito\Posting\Posting',
            ['rawData' => $data]
        );
        $expected = '<a href="localhost/entries/view/3" class="">Subject n/t</a>';
        $result = $this->Helper->getFastLink($posting);
        $this->assertEquals($expected, $result);
    }
}