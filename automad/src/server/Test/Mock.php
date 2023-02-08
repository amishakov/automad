<?php
/*
 *                    ....
 *                  .:   '':.
 *                  ::::     ':..
 *                  ::.         ''..
 *       .:'.. ..':.:::'    . :.   '':.
 *      :.   ''     ''     '. ::::.. ..:
 *      ::::.        ..':.. .''':::::  .
 *      :::::::..    '..::::  :. ::::  :
 *      ::'':::::::.    ':::.'':.::::  :
 *      :..   ''::::::....':     ''::  :
 *      :::::.    ':::::   :     .. '' .
 *   .''::::::::... ':::.''   ..''  :.''''.
 *   :..:::'':::::  :::::...:''        :..:
 *   ::::::. '::::  ::::::::  ..::        .
 *   ::::::::.::::  ::::::::  :'':.::   .''
 *   ::: '::::::::.' '':::::  :.' '':  :
 *   :::   :::::::::..' ::::  ::...'   .
 *   :::  .::::::::::   ::::  ::::  .:'
 *    '::'  '':::::::   ::::  : ::  :
 *              '::::   ::::  :''  .:
 *               ::::   ::::    ..''
 *               :::: ..:::: .:''
 *                 ''''  '''''
 *
 *
 * AUTOMAD
 *
 * Copyright (c) 2021 by Marc Anton Dahmen
 * https://marcdahmen.de
 *
 * Licensed under the MIT license.
 * https://automad.org/license
 */

namespace Automad\Test;

use Automad\Core\Automad;
use Automad\Core\Parse;
use Automad\Models\Context;
use Automad\Models\Page;
use Automad\Models\Shared;
use PHPUnit\Framework\TestCase;

defined('AUTOMAD') or die('Direct access not permitted!');

/**
 * The test mock class.
 *
 * @author Marc Anton Dahmen
 * @copyright Copyright (c) 2021 Marc Anton Dahmen - https://marcdahmen.de
 * @license MIT license - https://automad.org/license
 */
class Mock extends TestCase {
	/**
	 * Create a mock of the Automad object with a single page.
	 * A template can be passed optionally to the page.
	 *
	 * @param string $template
	 * @return Automad
	 */
	public function createAutomad(string $template = ''): object {
		$Shared = new Shared();
		$Shared->data['shared'] = 'Shared default text content';
		$collection = $this->createCollection($Shared, $template);
		$Automad = new Automad($collection, $Shared);
		$Automad->Context = new Context($collection[AM_REQUEST]);

		return $Automad;
	}

	/**
	 * Create a collection of test pages.
	 *
	 * @param Shared $Shared
	 * @param string $template
	 * @return array the collection
	 */
	private function createCollection(Shared $Shared, string $template): array {
		$theme = 'templates';
		$testsDir = AM_BASE_DIR . '/automad/tests';

		return array(
			'/' => new Page(
				array(
					'title' => 'Home',
					'url' => '/',
					':path' => '/',
					':origUrl' => '/',
					'theme' => $theme,
					'template' => $template,
					':level' => 0,
					':index' => '1',
					'tags' => 'test'
				),
				$Shared
			),
			'/page' => new Page(
				array_merge(
					array(
						'url' => '/page',
						':path' => '/page-slug/',
						':origUrl' => '/page',
						':parent' => '/',
						'theme' => $theme,
						'template' => $template,
						':level' => 1,
						':index' => '1.1',
						'tags' => 'test'
					),
					Parse::dataFile($testsDir . '/data/page.txt'),
					Parse::dataFile($testsDir . '/data/inheritance.txt')
				),
				$Shared
			),
			'/page/subpage' => new Page(
				array(
					'title' => 'Subpage',
					'url' => '/page/subpage',
					':path' => '/page-slug/subpage/',
					':origUrl' => '/page/subpage',
					':parent' => '/page',
					'theme' => $theme,
					'template' => $template,
					':level' => 2,
					':index' => '1.1.1',
					'tags' => 'test'
				),
				$Shared
			),
			'/text' => new Page(
				array_merge(
					array(
						'url' => '/text',
						':path' => '/text/',
						':origUrl' => '/text',
						':parent' => '/',
						'theme' => $theme,
						'template' => $template,
						':level' => 1,
						':index' => '1.2'
					),
					Parse::dataFile($testsDir . '/data/text.txt')
				),
				$Shared
			),
			'/blocks' => new Page(
				array_merge(
					array(
						'url' => '/blocks',
						':path' => '/blocks-slug/',
						':origUrl' => '/blocks',
						':parent' => '/',
						'theme' => $theme,
						'template' => $template,
						':level' => 1,
						':index' => '1.3'
					),
					Parse::dataFile($testsDir . '/data/blocks.txt')
				),
				$Shared
			)
		);
	}
}
