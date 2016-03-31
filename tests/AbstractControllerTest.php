<?php
namespace Starlit\App;

use Symfony\Component\HttpFoundation\Response;

class AbstractControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestController
     */
    protected $testController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockApp;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRequest;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockView;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResponse;

    protected function setUp()
    {
        $this->mockApp = $this->getMockBuilder('\Starlit\App\BaseApp')->disableOriginalConstructor()->getMock();
        $this->mockRequest = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $this->mockRequest->server = $this->getMock('\Symfony\Component\HttpFoundation\ServerBag');

        $this->mockView = $this->getMock('\Starlit\App\View');
        $this->mockApp->expects($this->any())
            ->method('getNew')
            ->with('view')
            ->will($this->returnValue($this->mockView));

        $this->mockResponse = new \Symfony\Component\HttpFoundation\Response();
        $this->mockApp->expects($this->any())
            ->method('get')
            ->with('response')
            ->will($this->returnValue($this->mockResponse));

        $this->testController = new TestController($this->mockApp, $this->mockRequest);
    }

    public function testConstruct()
    {
         // check module
        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('module');
        $prop->setAccessible(true);
        $this->assertEquals('Starlit', $prop->getValue($this->testController));

         // check controller
        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('controller');
        $prop->setAccessible(true);
        $this->assertEquals('test', $prop->getValue($this->testController));

        // check view
        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('view');
        $prop->setAccessible(true);
        $this->assertInstanceOf('\Starlit\App\View', $prop->getValue($this->testController));
    }

    public function testSetAutoRenderView()
    {
        $this->testController->setAutoRenderView(false);

        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('autoRenderView');
        $prop->setAccessible(true);

        $this->assertFalse($prop->getValue($this->testController));
    }

    public function testSetAutoRenderViewScript()
    {
        $this->testController->setAutoRenderViewScript('someScript');

        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('autoRenderViewScript');
        $prop->setAccessible(true);

        $this->assertEquals('someScript', $prop->getValue($this->testController));
    }

    public function testDispatch()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('index')
            ->will($this->returnValue('indexAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        // mock View::render return
        $this->mockView->expects($this->any())
            ->method('render')
            ->with('starlit/test/index')
            ->will($this->returnValue('yes'));


        $response = $this->testController->dispatch('index');
        $this->assertEquals('yes', $response->getContent());
    }

    public function testDispatchPreDispatch()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('pre-test')
            ->will($this->returnValue('preTestAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $response = $this->testController->dispatch('pre-test');
        $this->assertEquals('preOk', $response->getContent());
    }

    public function testDispatchSpecifiedWithResponseAndParamAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('some-other')
            ->will($this->returnValue('someOtherAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        // mock request-attributes has
        $this->mockRequest->attributes = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->attributes->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnCallback(function ($paramName) {
                return ($paramName == 'someParam');
            }));
        $this->mockRequest->attributes->expects($this->once())
            ->method('get')
            ->with('someParam')
            ->will($this->returnValue('ooh'));



        $response = $this->testController->dispatch('some-other', ['otherParam' => 'aaa']);
        $this->assertEquals('ooh aaa wow', $response->getContent());

    }

    public function testDispatchWithoutReqParam()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('some-other')
            ->will($this->returnValue('someOtherAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        // mock request-attributes has
        $this->mockRequest->attributes = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->attributes->expects($this->once())
            ->method('has')
            ->will($this->returnValue(false));

        $this->setExpectedException('\LogicException');
        $this->testController->dispatch('some-other');
    }

    public function testDispatchNonExistingAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('none')
            ->will($this->returnValue('noneAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $this->setExpectedException('\Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $this->testController->dispatch('none');
    }

    public function testDispatchInvalidAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('invalid')
            ->will($this->returnValue('invalidAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $this->setExpectedException('\Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $this->testController->dispatch('invalid');
    }

    public function testDispatchNoAutoAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('no-auto')
            ->will($this->returnValue('noAutoAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $response = $this->testController->dispatch('no-auto');
        $this->assertEquals('', $response->getContent());
    }

    public function testDispatchStringReturn()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('string-return')
            ->will($this->returnValue('stringReturnAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $response = $this->testController->dispatch('string-return');
        $this->assertEquals('a string', $response->getContent());
    }

    public function testForwardInternal()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));
        $this->mockApp->expects($this->once())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));


        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testForward()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->getMockBuilder('\Starlit\App\Router')->disableOriginalConstructor()->getMock();
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));
        $mockRouter->expects($this->once())
            ->method('getControllerClass')
            ->with('sw', 'mock')
            ->will($this->returnValue('Starlit\App\TestController'));
        $this->mockApp->expects($this->any())
            ->method('getRouter')
            ->will($this->returnValue($mockRouter));


        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end', 'mock', 'sw']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testGetUrlNoUrl()
    {
        $this->mockRequest->expects($this->any())
            ->method('getSchemeAndHttpHost')
            ->will($this->returnValue('http://www.example.org'));

        $this->mockRequest->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('/hej/hopp'));

        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('getUrl');
        $method->setAccessible(true);

        $this->assertEquals('http://www.example.org/hej/hopp', $method->invokeArgs($this->testController, []));
    }

    public function testGetUrl()
    {
        $this->mockRequest->expects($this->any())
            ->method('getSchemeAndHttpHost')
            ->will($this->returnValue('http://www.example.org'));

        $this->mockRequest->query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->query->expects($this->exactly(1))
            ->method('all')
            ->will($this->returnValue(['a' => 1]));

        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('getUrl');
        $method->setAccessible(true);

        $this->assertEquals('http://www.example.org/hej/hopp?a=1&b=2', $method->invokeArgs($this->testController, ['/hej/hopp', ['b' => '2']]));
    }

    public function testGet()
    {
        $get = ['a' => 1, 'b' => 2];
        $this->mockRequest->query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->query->expects($this->exactly(1))
            ->method('all')
            ->will($this->returnValue($get));
        $this->mockRequest->query->expects($this->exactly(1))
            ->method('get')
            ->will($this->returnValue($get['a']));


        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('get');
        $method->setAccessible(true);

        $this->assertEquals($get, $method->invokeArgs($this->testController, []));
        $this->assertEquals($get['a'], $method->invokeArgs($this->testController, ['a']));
    }

    public function testPost()
    {
        $get = ['a' => 1, 'b' => 2];
        $this->mockRequest->request = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->request->expects($this->exactly(1))
            ->method('all')
            ->will($this->returnValue($get));
        $this->mockRequest->request->expects($this->exactly(1))
            ->method('get')
            ->will($this->returnValue($get['a']));


        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('post');
        $method->setAccessible(true);

        $this->assertEquals($get, $method->invokeArgs($this->testController, []));
        $this->assertEquals($get['a'], $method->invokeArgs($this->testController, ['a']));
    }
}

class TestController extends AbstractController
{

    public function indexAction()
    {
    }

    public function someOtherAction($someParam, $otherParam, $paramWithDefault = 'wow')
    {
        return new Response($someParam . ' ' . $otherParam . ' ' . $paramWithDefault);
    }

    protected function invalidAction()
    {
    }

    public function noAutoAction()
    {
        $this->setAutoRenderView(false);
    }

    public function forwardEndAction()
    {
        return new Response('eeend');
    }

    public function preTestAction() { }

    protected function preDispatch($action)
    {
        parent::preDispatch($action); // For code coverage...

        if ($action === 'pre-test') {
            return new Response('preOk');
        }
    }

    public function stringReturnAction()
    {
        return 'a string';
    }

}
