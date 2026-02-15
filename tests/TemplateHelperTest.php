<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Rahpt\Ci4ModuleTools\Support\TemplateHelper;

class TemplateHelperTest extends TestCase
{
    public function testFormatArray()
    {
        $data = ['a' => 1];
        $result = TemplateHelper::formatArray($data);
        $this->assertStringContainsString("'a' => 1", $result);
    }

    public function testGetTemplatePath()
    {
        $path = TemplateHelper::getTemplatePath('test.tpl');
        $this->assertStringContainsString('Generator/Templates/test.tpl', str_replace('\\', '/', $path));
    }
}
