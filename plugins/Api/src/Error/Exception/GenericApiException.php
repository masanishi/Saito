<?php

declare(strict_types = 1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers 2015
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace Api\Error\Exception;

use Cake\Http\Exception\BadRequestException;

class GenericApiException extends BadRequestException
{

    /**
     * {@inheritdoc}
     *
     * @param string $message exception message
     * @return void
     */
    public function __construct($message = '')
    {
        if (empty($message)) {
            $message = 'Api Error. Check URL, request type and headers.';
        }
        parent::__construct($message);
    }
}