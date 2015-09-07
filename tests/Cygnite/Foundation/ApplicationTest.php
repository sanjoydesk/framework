<?php
use Cygnite\Foundation\Application;
use Cygnite\Foundation\Autoloader;
use Mockery as m;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    public function testApplicationInstance()
    {
        $application = Application::instance();

        $this->assertInstanceOf('Cygnite\Foundation\Application', $application);
    }

    public function testSetValueToContainer()
    {
        $app = Application::instance();
        $app->set('greet', 'Hello Application');

        $this->assertEquals($app['greet'], 'Hello Application');
    }

    public function testDependencyInjection()
    {
        $app = Application::instance();

        $router = new \Cygnite\Base\Router\Router();
        $url = new \Cygnite\Common\UrlManager\Url($router);
        $madeUrl = $app->make('\Cygnite\Common\UrlManager\Url');

        $this->assertEquals($url, $madeUrl);
    }

    public function testServiceCreation()
    {
        $app = Application::instance();
        $app->service(function($app)
            {
                $app->registerServiceProvider(['FooBarServiceProvider']);

                $app->setServiceController('bar.controller', 'BarController');
            });

        $this->assertInstanceOf('\FooBar', $app['foo.bar']());
        $this->assertNotNull($app['foo.bar']()->greet());
        $this->assertEquals("Hello FooBar!", $app['foo.bar']()->greet());

        $app['greet.bar.controller'] = 'Hello BarController!';
        $this->assertEquals("Hello BarController!", $app['bar.controller']()->indexAction());
    }

    public function testComposeMethod()
    {
        $app = Application::instance();
        $bazBar = $app->compose('BazBar', ['greet' => 'Hello!']);

        $this->assertArrayHasKey('greet', $app);
        $this->assertEquals("Hello!", $bazBar->greet());
    }

    public function tearDown()
    {
        m::close();
    }
}

class FooBarServiceProvider
{
    protected $app;

    public function register(Application $app)
    {
        $app['foo.bar'] = $app->share(function ($c) {
                return new FooBar();
            });
    }
}

class FooBar
{
    public function greet()
    {
        return 'Hello FooBar!';
    }
}

class BarController
{
    private $app;

    private $serviceController;

    public function __construct($serviceController, \Cygnite\Foundation\ApplicationInterface $app)
    {
        $this->app = $app;
    }

    public function indexAction()
    {
        return $this->app['greet.bar.controller'];
    }
}

class BazBar
{
    private $arguments= [];

    public function __construct($arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function greet()
    {
        return $this->arguments['greet'];
    }
}