<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
/**
 * Class QRDataModeAbstract
 *
 * @created      19.11.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace Webkernel\QrCode\Data;

use Webkernel\QrCode\Common\Mode;

/**
 * abstract methods for the several data modes
 */
abstract class QRDataModeAbstract implements QRDataModeInterface{

	/**
	 * The data to write
	 */
	protected string $data;

	/**
	 * QRDataModeAbstract constructor.
	 *
	 * @throws \Webkernel\QrCode\Data\QRCodeDataException
	 */
	public function __construct(string $data){
		$data = $this::convertEncoding($data);

		if(!$this::validateString($data)){
			throw new QRCodeDataException('invalid data');
		}

		$this->data = $data;
	}

	/**
	 * returns the character count of the $data string
	 */
	protected function getCharCount():int{
		return strlen($this->data);
	}

	/**
	 * @inheritDoc
	 */
	public static function convertEncoding(string $string):string{
		return $string;
	}

	/**
	 * shortcut
	 */
	protected static function getLengthBits(int $versionNumber):int{
		return Mode::getLengthBitsForVersion(static::DATAMODE, $versionNumber);
	}

}
