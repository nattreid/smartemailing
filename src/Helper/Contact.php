<?php

declare(strict_types=1);

namespace NAttreid\SmartEmailing\Helper;

use Nette\SmartObject;

/**
 * Class Contact
 *
 * @author Attreid <attreid@gmail.com>
 */
class Contact
{
	use SmartObject;

	/**
	 * E-mail address of imported contact. This is the only required field
	 * @var string|string[]
	 */
	public $emailaddress;

	/**
	 * First name
	 * @var string
	 */
	public $name;

	/**
	 * Last name
	 * @var string
	 */
	public $surname;

	/**
	 * Titles before name
	 * @var string
	 */
	public $titlesbefore;

	/**
	 * Titles after name
	 * @var string
	 */
	public $titlesafter;

	/**
	 * Company
	 * @var string
	 */
	public $company;

	/**
	 * Street
	 * @var string
	 */
	public $street;

	/**
	 * Town
	 * @var string
	 */
	public $town;

	/**
	 * Postal/ZIP code
	 * @var string
	 */
	public $postalcode;

	/**
	 * Country
	 * @var string
	 */
	public $country;

	/**
	 * Cellphone number
	 * @var string
	 */
	public $cellphone;

	/**
	 * Phone number
	 * @var string
	 */
	public $phone;

	/**
	 * Language in POSIX format, eg. 'cz_CZ'
	 * @var string
	 */
	public $language;

	/**
	 * Custom notes
	 * @var string
	 */
	public $notes;

	/**
	 * Gender
	 * Allowed values: 'M', 'F', null
	 * @var string
	 */
	public $gender;
}