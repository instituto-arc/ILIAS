<?php

/* Copyright (c) 2016 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

require_once("libs/composer/vendor/autoload.php");

require_once(__DIR__."/ilIndependentTemplate.php");

use ILIAS\UI\Implementation\Render\TemplateFactory;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Factory;

class ilIndependentTemplateFactory implements TemplateFactory {
	public function getTemplate($path, $purge_unfilled_vars, $purge_unused_blocks) {
		return new ilIndependentTemplate($path, $purge_unfilled_vars, $purge_unused_blocks);
	}
}

class NoUIFactory implements Factory {
	public function counter() {}
	public function glyph() {}
}

class LoggingRegistry implements ResourceRegistry {
	public $resources = array();

	public function register($name) {
		$resources[] = $name;
	}
}

class DefaultRendererTesting extends \ILIAS\UI\Implementation\DefaultRenderer {
	public function getResourceRegistry() {
		return $this->resource_registry;
	}
}

/**
 * Provides common functionality for UI tests.
 */
class ILIAS_UI_TestBase extends PHPUnit_Framework_TestCase {
	public function setUp() {
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_CALLBACK, null);
	}

	public function tearDown() {
		assert_options(ASSERT_WARNING, 1);
		assert_options(ASSERT_CALLBACK, null);
	}

	public function getUIFactory() {
		return new NoUIFactory();
	}

	public function getTemplateFactory() {
		return new ilIndependentTemplateFactory();
	}

	public function getResourceRegistry() {
		return new LoggingRegistry();
	}

	public function getDefaultRenderer() {
		$ui_factory = $this->getUIFactory();
		$tpl_factory = $this->getTemplateFactory();
		$resource_registry = $this->getResourceRegistry();
		return new DefaultRendererTesting(
						$ui_factory, $tpl_factory, $resource_registry);
	}

	public function normalizeHTML($html) {
		return trim(str_replace("\n", "", $html));
	}        
}
