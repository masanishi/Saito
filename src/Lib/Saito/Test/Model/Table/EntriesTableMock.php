<?php

namespace Saito\Test\Model\Table;

use App\Model\Table\EntriesTable;

class EntriesTableMock extends EntriesTable
{

    public $_CurrentUser;

    public $_editPeriod;

    protected $_table = 'entries';

    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        $this->entityClass('Entry');
        parent::initialize($config);
    }

    /**
     * prepare markup
     *
     * @param string $string string
     * @return mixed
     */
    public function prepareMarkup($string)
    {
        return $string;
    }

    /**
     * setting
     *
     * @return mixed
     */
    public function setting()
    {
        return $this->_setting('subject_maxlength');
    }
}
