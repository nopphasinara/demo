<?php

use Mockery as m;

class TestModel extends \Illuminate\Database\Eloquent\Model
{

}

class TestFormButtons extends \SleepingOwl\Admin\Form\FormButtons {

}

class FormDefaultTest extends TestCase
{
    /**
     * @var \SleepingOwl\Admin\Form\FormDefault
     */
    private $form;

    public function setUp()
    {
        parent::setUp();

        $this->form = new \SleepingOwl\Admin\Form\FormDefault([
            new \SleepingOwl\Admin\Form\Element\Text('field1', 'Test'),
            new \SleepingOwl\Admin\Form\Element\Upload('field2', 'Test 2')
        ]);

        $this->form->setModelClass(TestModel::class);
    }

    /**
     * @covers SleepingOwl\Admin\Form\FormDefault::__constructor
     */
    public function test_constructor()
    {
        $this->assertEquals($this->form->getElements()->count(), 2);
        $this->assertInstanceOf(\SleepingOwl\Admin\Contracts\FormButtonsInterface::class, $this->form->getButtons());
        $this->assertNull($this->form->getRepository());
    }

    public function test_initialize()
    {
        $this->form->initialize();

        $this->assertInstanceOf(
            TestModel::class,
            $this->form->getModel()
        );

        $this->assertEquals(
            'POST',
            $this->form->getHtmlAttribute('method')
        );

        $this->assertEquals(
            'multipart/form-data',
            $this->form->getHtmlAttribute('enctype')
        );

        $this->assertTrue($this->form->isInitialized());
    }

    public function test_getButtons()
    {
        $this->assertInstanceOf(\SleepingOwl\Admin\Contracts\FormButtonsInterface::class, $this->form->getButtons());
    }

    public function test_setButtons()
    {
        $buttons = $this->getMock(\SleepingOwl\Admin\Form\FormButtons::class);
        $buttons->expects($this->once())->method('setModelConfiguration')->willReturnSelf();
        $this->form->setButtons($buttons);

        $this->form->initialize();

        $buttonsNew = $this->getMock(TestFormButtons::class);
        $buttonsNew->expects($this->once())->method('setModelConfiguration')->willReturnSelf();

        $this->assertNotEquals($buttonsNew, $this->form->getButtons());
        $this->form->setButtons($buttonsNew);

        $this->assertEquals($buttonsNew, $this->form->getButtons());
    }

    public function test_getRepository()
    {
        $this->assertNull($this->form->getRepository());

        $this->form->initialize();

        $this->assertInstanceOf(
            \SleepingOwl\Admin\Contracts\RepositoryInterface::class,
            $this->form->getRepository()
        );
    }

    public function test_getView()
    {
        $this->assertEquals('form.default', $this->form->getView());
    }

    public function test_setView()
    {
        $this->form->setView('form.testDefault');
        $this->assertEquals('form.testDefault', $this->form->getView());
    }

    public function test_setAction()
    {
        $this->form->setAction('test');
        $this->assertEquals('test', $this->form->getHtmlAttribute('action', 'test'));
    }

    public function test_setModelClass()
    {
        $form = new \SleepingOwl\Admin\Form\FormDefault();
        $this->assertNull($form->getModelClass());

        $form->setModelClass(TestModel::class);
        $this->assertEquals(TestModel::class, $form->getModelClass());

        $form->setModelClass('test');
        $this->assertNotEquals('test', $form->getModelClass());
    }

    public function test_setId()
    {
        $form = new \SleepingOwl\Admin\Form\FormDefault();

        \Illuminate\Database\Eloquent\Model::unguard();
        $role = \App\Role::firstOrCreate([
            'name' => 'Test'
        ]);

        $form->setModelClass(\App\Role::class);
        $form->initialize();
        $form->setId($role->id);

        $this->assertEquals($role, $form->getModel());
        $this->assertEquals($role->id, $form->getModel()->id);

        $form->setId(15567567567);
        $this->assertEquals($role->id, $form->getModel()->id);
    }

    public function test_getModelConfiguration()
    {
        $this->assertInstanceOf(
            \SleepingOwl\Admin\Contracts\ModelConfigurationInterface::class,
            $this->form->getModelConfiguration()
        );
    }

    public function test_setOrGetModel()
    {
        $this->assertNull($this->form->getModel());

        $this->form->initialize();

        $this->assertInstanceOf(
            TestModel::class,
            $this->form->getModel()
        );

        $this->form->setModel(new \App\Role());

        $this->assertInstanceOf(
            \App\Role::class,
            $this->form->getModel()
        );
    }

    public function test_saveForm()
    {
        $element1 = $this->getMock(\SleepingOwl\Admin\Form\Element\Text::class, ['save'], ['field1', 'Title']);
        $element1->expects($this->once())->method('save')->willReturnSelf();

        $element2 = $this->getMock(\SleepingOwl\Admin\Form\Element\Textarea::class, ['save'], ['field2', 'Textarea']);
        $element2->expects($this->once())->method('save')->willReturnSelf();

        $form = new \SleepingOwl\Admin\Form\FormDefault([
            $element1, $element2
        ]);

        $relation = m::mock(\App\Role::class)->makePartial();
        $relation->shouldReceive(['save' => 1]);

        $belongsTo = m::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)->makePartial();
        $belongsTo->shouldReceive('associate')->once()->with($relation);

        $hasOneOrMany = m::mock(\Illuminate\Database\Eloquent\Relations\HasOneOrMany::class)->makePartial();
        $hasOneOrMany->shouldReceive('saveMany')->twice();
        $hasOneOrMany->shouldReceive('save')->twice()->with($relation);

        $model = m::mock(TestModel::class)->makePartial();
        $model->shouldReceive('save')->once();
        $model->shouldReceive('field')->andReturn(
            $belongsTo
        )->shouldReceive('field1')->andReturn(
            $belongsTo
        )->shouldReceive('field2')->andReturn(
            $hasOneOrMany
        )->shouldReceive('field3')->andReturn(
            $hasOneOrMany
        )->shouldReceive('field4')->andReturn(
            $hasOneOrMany
        )->shouldReceive('field5')->andReturn(
            $hasOneOrMany
        );

        $model->shouldReceive(['getRelations' => 1])->andReturn([
            'field' => $relation,
            'field1' => null,
            'field3' => $relation,
            'field4' => $relation,
            'field5' => collect([$relation]),
            'field2' => [$relation],
        ]);

        $form->setModel($model);
        $form->saveForm();
    }

    public function tearDown()
    {
        m::close();
    }
}