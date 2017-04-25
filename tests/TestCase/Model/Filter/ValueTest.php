<?php
namespace Search\Test\TestCase\Model\Filter;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Search\Manager;
use Search\Model\Filter\Value;

class ValueTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Search.Articles'
    ];

    /**
     * @return void
     */
    public function testDeprecatedModeOption()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, ['mode' => 'modeValue']);

        $this->assertEquals('modeValue', $filter->config('mode'));
        $this->assertEquals('modeValue', $filter->config('valueMode'));
        $this->assertEquals('OR', $filter->config('fieldMode'));
    }

    /**
     * @return void
     */
    public function testSkipProcess()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        /* @var $filter \Search\Model\Filter\Value|\PHPUnit_Framework_MockObject_MockObject */
        $filter = $this
            ->getMockBuilder('Search\Model\Filter\Value')
            ->setConstructorArgs(['title', $manager])
            ->setMethods(['skip'])
            ->getMock();
        $filter
            ->expects($this->once())
            ->method('skip')
            ->willReturn(true);
        $filter->args(['title' => 'test']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertEmpty($filter->query()->clause('where'));
    }

    /**
     * @return void
     */
    public function testProcess()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager);
        $filter->args(['title' => 'test']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.title = :c0$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['test'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessSingleValueWithAndValueMode()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, ['valueMode' => 'and']);
        $filter->args(['title' => 'foo']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.title = :c0$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessSingleValueAndMultiFieldWithAndValueMode()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, [
            'field' => ['title', 'other'],
            'valueMode' => 'and'
        ]);
        $filter->args(['title' => 'foo']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE \(Articles\.title = :c0 OR Articles\.other = :c1\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo', 'foo'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessSingleValueAndMultiFieldWithAndFieldMode()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, [
            'field' => ['title', 'other'],
            'fieldMode' => 'and'
        ]);
        $filter->args(['title' => 'foo']);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE \(Articles\.title = :c0 AND Articles\.other = :c1\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo', 'foo'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessMultiValue()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, ['multiValue' => true]);
        $filter->args(['title' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.title IN \(:c0,:c1\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo', 'bar'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessMultiValueWithAndValueMode()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, [
            'multiValue' => true,
            'valueMode' => 'and'
        ]);
        $filter->args(['title' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE \(Articles\.title = :c0 AND Articles\.title = :c1\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo', 'bar'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessMultiValueAndMultiField()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, [
            'multiValue' => true,
            'field' => ['title', 'other']
        ]);
        $filter->args(['title' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE \(Articles\.title IN \(:c0,:c1\) ' .
            'OR Articles\.other IN \(:c2,:c3\)\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo', 'bar', 'foo', 'bar'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessMultiValueAndMultiFieldWithAndFieldMode()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, [
            'multiValue' => true,
            'field' => ['title', 'other'],
            'fieldMode' => 'and'
        ]);
        $filter->args(['title' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE \(Articles\.title IN \(:c0,:c1\) ' .
            'AND Articles\.other IN \(:c2,:c3\)\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo', 'bar', 'foo', 'bar'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessMultiValueWithNonScalarValue()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, ['multiValue' => true]);
        $filter->args(['title' => ['foo' => ['bar']]]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.title IN \(:c0\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            [['bar']],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessEmptyMultiValue()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, ['multiValue' => true]);
        $filter->args(['title' => []]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertEmpty($filter->query()->clause('where'));
        $filter->query()->sql();
        $this->assertEmpty($filter->query()->valueBinder()->bindings());
    }

    /**
     * @return void
     */
    public function testProcessDefaultFallbackForDisallowedMultiValue()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, ['defaultValue' => 'default']);
        $filter->args(['title' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.title = :c0$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['default'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }

    /**
     * @return void
     */
    public function testProcessNoDefaultFallbackForDisallowedMultiValue()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager);
        $filter->args(['title' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertEmpty($filter->query()->clause('where'));
        $filter->query()->sql();
        $this->assertEmpty($filter->query()->valueBinder()->bindings());
    }

    /**
     * @return void
     */
    public function testProcessCaseInsensitiveValueMode()
    {
        $articles = TableRegistry::get('Articles');
        $manager = new Manager($articles);
        $filter = new Value('title', $manager, [
            'multiValue' => true,
            'valueMode' => 'Or'
        ]);
        $filter->args(['title' => ['foo', 'bar']]);
        $filter->query($articles->find());
        $filter->process();

        $this->assertRegExp(
            '/WHERE Articles\.title IN \(:c0,:c1\)$/',
            $filter->query()->sql()
        );
        $this->assertEquals(
            ['foo', 'bar'],
            Hash::extract($filter->query()->valueBinder()->bindings(), '{s}.value')
        );
    }
}
