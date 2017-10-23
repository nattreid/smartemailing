<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing;

use Exception;
use Throwable;

/**
 * Class ClientException
 *
 * @author Attreid <attreid@gmail.com>
 */
class SmartEmailingClientException extends Exception
{

	public function __construct(Throwable $previous = null)
	{
		parent::__construct('', 0, $previous);
	}
}