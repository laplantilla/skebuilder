<?php
/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-04-05 at 11:23:30.
 */
class TemplateManagerTest extends PHPUnit_Framework_TestCase
{
	public function testSingleton()
	{
		$tpl_manager = TemplateManager::getInstance(SKEBUILDER_UNITTEST_TEMPLATE_DIR);
		$template = $tpl_manager->getTemplate('phpfox_block_default_class');
		$this->assertEquals($template->getName(), 'phpfox_block_default_class');
	}
}
	