<?php

namespace Eccube\Tests\Fixture;

use Eccube\Common\Constant;
use Eccube\Entity\PaymentOption;
use Eccube\Tests\EccubeTestCase;

/**
 * Fixture Generator
 *
 * @author Kentaro Ohkouchi
 */
class GeneratorTest extends EccubeTestCase
{
    protected $numberOfProducts = 100;
    protected $numberOfCustomer = 100;
    protected $numberOfOrder = 10;
    protected $numberOfDeliveries = 5;
    protected $numberOfPayments = 3;
    protected $Customers;
    protected $Products;
    protected $Deliveries;
    protected $Orders;

    public function setUp()
    {
        // parent::setUp();
        $this->app = $this->createApplication();
        $this->Customers = array();
        $this->Products = array();
        $this->Deliveries = array();
        $this->Orders = array();
    }

    public function tearDown()
    {
        // parent::tearDown();
    }

    public function testDeliveryGenerator()
    {
        $Payments = $this->app['eccube.repository.payment']->findAll();
        for ($i = 0; $i < $this->numberOfDeliveries; $i++) {
            $Delivery = $this->app['eccube.fixture.generator']->createDelivery();
            foreach ($Payments as $Payment) {
                $PaymentOption = new PaymentOption();
                $PaymentOption
                    ->setDeliveryId($Delivery->getId())
                    ->setPaymentId($Payment->getId())
                    ->setDelivery($Delivery)
                    ->setPayment($Payment);
                $Payment->addPaymentOption($PaymentOption);
                $this->app['orm.em']->persist($PaymentOption);
                $this->app['orm.em']->flush($PaymentOption);
            }
            $this->app['orm.em']->flush($Payment);
            $this->Deliveries[] = $Delivery;
        }
        $this->assertEquals($this->numberOfDeliveries, count($this->Deliveries));
    }

    public function testProductGenerator()
    {
        for ($i = 0; $i < $this->numberOfProducts; $i++) {
            $this->Products[] = $this->app['eccube.fixture.generator']->createProduct();
        }
        $this->assertEquals($this->numberOfProducts, count($this->Products));
    }
    public function testCustomerGenerator()
    {
        for ($i = 0; $i < $this->numberOfCustomer; $i++) {
            $this->Customers[] = $this->app['eccube.fixture.generator']->createCustomer();
        }
        $this->assertEquals($this->numberOfCustomer, count($this->Customers));
    }

    public function testOrderGenerator()
    {
        $faker = $this->getFaker();
        $Customers = $this->app['eccube.repository.customer']->findAll();
        $Deliveries = $this->app['eccube.repository.delivery']->findAll();
        $Products = $this->app['eccube.repository.product']->findAll();
        $Status = $this->app['eccube.repository.order_status']->find($this->app['config']['order_new']);
        foreach ($Customers as $Customer) {
            $Delivery = $Deliveries[$faker->numberBetween(0, $this->numberOfDeliveries)];
            $Product = $Products[$faker->numberBetween(0, $this->numberOfProducts)];
            $charge = $faker->randomNumber(4);
            $discount = $faker->randomNumber(4);
            for ($i = 0; $i < $this->numberOfOrder; $i++) {
                $Order = $this->app['eccube.fixture.generator']->createOrder($Customer, $Product->getProductClasses()->toArray(), $Delivery, $charge, $discount);
                $Order->setOrderStatus($Status);
                $Order->setOrderDate($faker->dateTimeThisYear());
                $this->app['orm.em']->flush($Order);
                $this->Orders[] = $Order;
            }
        }
        $this->assertEquals(count($Customers) * $this->numberOfOrder, count($this->Orders));
    }
}
