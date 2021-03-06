<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Page\Admin\Order\HistoryPageInterface;
use Sylius\Behat\Page\Admin\Order\IndexPageInterface;
use Sylius\Behat\Page\Admin\Order\ShowPageInterface;
use Sylius\Behat\Page\Admin\Order\UpdatePageInterface;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Behat\Service\SharedSecurityServiceInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

/**
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 * @author Grzegorz Sadowski <grzegorz.sadowski@lakion.com>
 */
final class ManagingOrdersContext implements Context
{
    /**
     * @var SharedStorageInterface
     */
    private $sharedStorage;

    /**
     * @var IndexPageInterface
     */
    private $indexPage;

    /**
     * @var ShowPageInterface
     */
    private $showPage;

    /**
     * @var UpdatePageInterface
     */
    private $updatePage;

    /**
     * @var HistoryPageInterface
     */
    private $historyPage;

    /**
     * @var NotificationCheckerInterface
     */
    private $notificationChecker;

    /**
     * @var SharedSecurityServiceInterface
     */
    private $sharedSecurityService;

    /**
     * @param SharedStorageInterface $sharedStorage
     * @param IndexPageInterface $indexPage
     * @param ShowPageInterface $showPage
     * @param UpdatePageInterface $updatePage
     * @param HistoryPageInterface $historyPage
     * @param NotificationCheckerInterface $notificationChecker
     * @param SharedSecurityServiceInterface $sharedSecurityService
     */
    public function __construct(
        SharedStorageInterface $sharedStorage,
        IndexPageInterface $indexPage,
        ShowPageInterface $showPage,
        UpdatePageInterface $updatePage,
        HistoryPageInterface $historyPage,
        NotificationCheckerInterface $notificationChecker,
        SharedSecurityServiceInterface $sharedSecurityService
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->indexPage = $indexPage;
        $this->showPage = $showPage;
        $this->updatePage = $updatePage;
        $this->historyPage = $historyPage;
        $this->notificationChecker = $notificationChecker;
        $this->sharedSecurityService = $sharedSecurityService;
    }

    /**
     * @Given I am browsing orders
     * @When I browse orders
     */
    public function iBrowseOrders()
    {
        $this->indexPage->open();
    }

    /**
     * @When I browse order's :order history
     */
    public function iBrowseOrderHistory(OrderInterface $order)
    {
        $this->historyPage->open(['id' => $order->getId()]);
    }

    /**
     * @Given /^I am viewing the summary of (this order)$/
     * @When I view the summary of the order :order
     */
    public function iSeeTheOrder(OrderInterface $order)
    {
        $this->showPage->open(['id' => $order->getId()]);
    }

    /**
     * @When /^I mark (this order) as paid$/
     */
    public function iMarkThisOrderAsAPaid(OrderInterface $order)
    {
        $this->showPage->completeOrderLastPayment($order);
    }

    /**
     * @When /^I mark (this order)'s payment as refunded$/
     */
    public function iMarkThisOrderSPaymentAsRefunded(OrderInterface $order)
    {
        $this->showPage->refundOrderLastPayment($order);
    }

    /**
     * @When specify its tracking code as :trackingCode
     */
    public function specifyItsTrackingCodeAs($trackingCode)
    {
        $this->showPage->specifyTrackingCode($trackingCode);
        $this->sharedStorage->set('tracking_code', $trackingCode);
    }

    /**
     * @When /^I ship (this order)$/
     */
    public function iShipThisOrder(OrderInterface $order)
    {
        $this->showPage->shipOrder($order);
    }

    /**
     * @When I switch the way orders are sorted by :fieldName
     */
    public function iSwitchSortingBy($fieldName)
    {
        $this->indexPage->sortBy($fieldName);
    }

    /**
     * @When I specify filter date from as :dateTime
     */
    public function iSpecifyFilterDateFromAs($dateTime)
    {
        $this->indexPage->specifyFilterDateFrom(new \DateTime($dateTime));
    }

    /**
     * @When I specify filter date to as :dateTime
     */
    public function iSpecifyFilterDateToAs($dateTime)
    {
        $this->indexPage->specifyFilterDateTo(new \DateTime($dateTime));
    }

    /**
     * @When I choose :channelName as a channel filter
     */
    public function iChooseChannelAsAChannelFilter($channelName)
    {
        $this->indexPage->chooseChannelFilter($channelName);
    }

    /**
     * @When I choose :currencyName as the filter currency
     */
    public function iChooseCurrencyAsTheFilterCurrency($currencyName)
    {
        $this->indexPage->chooseCurrencyFilter($currencyName);
    }

    /**
     * @When I specify filter total being greater than :total
     */
    public function iSpecifyFilterTotalBeingGreaterThan($total)
    {
        $this->indexPage->specifyFilterTotalGreaterThan($total);
    }

    /**
     * @When I specify filter total being less than :total
     */
    public function iSpecifyFilterTotalBeingLessThan($total)
    {
        $this->indexPage->specifyFilterTotalLessThan($total);
    }

    /**
     * @When I filter
     */
    public function iFilter()
    {
        $this->indexPage->filter();
    }

    /**
     * @Then I should see a single order from customer :customer
     */
    public function iShouldSeeASingleOrderFromCustomer(CustomerInterface $customer)
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['customer' => $customer->getEmail()]),
            sprintf('Cannot find order for customer "%s" in the list.', $customer->getEmail())
        );
    }

    /**
     * @Then it should have been placed by the customer :customerEmail
     */
    public function itShouldBePlacedByCustomer($customerEmail)
    {
        Assert::true(
            $this->showPage->hasCustomer($customerEmail),
            sprintf('Cannot find customer "%s".', $customerEmail)
        );
    }

    /**
     * @Then it should be shipped to :customerName, :street, :postcode, :city, :countryName
     * @Then /^(this order) should (?:|still )be shipped to "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)"$/
     */
    public function itShouldBeShippedTo(
        OrderInterface $order = null,
        $customerName,
        $street,
        $postcode,
        $city,
        $countryName
    ) {
        if (null !== $order) {
            $this->iSeeTheOrder($order);
        }

        Assert::true(
            $this->showPage->hasShippingAddress($customerName, $street, $postcode, $city, $countryName),
            sprintf('Cannot find shipping address "%s, %s %s, %s".', $street, $postcode, $city, $countryName)
        );
    }

    /**
     * @Then it should be billed to :customerName, :street, :postcode, :city, :countryName
     * @Then the order should be billed to :customerName, :street, :postcode, :city, :countryName
     * @Then /^(this order) bill should (?:|still )be shipped to "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)"$/
     */
    public function itShouldBeBilledTo(
        OrderInterface $order = null,
        $customerName,
        $street,
        $postcode,
        $city,
        $countryName
    ) {
        if (null !== $order) {
            $this->iSeeTheOrder($order);
        }

        Assert::true(
            $this->showPage->hasBillingAddress($customerName, $street, $postcode, $city, $countryName),
            sprintf('Cannot find shipping address "%s, %s %s, %s".', $street, $postcode, $city, $countryName)
        );
    }

    /**
     * @Then it should be shipped via the :shippingMethodName shipping method
     */
    public function itShouldBeShippedViaShippingMethod($shippingMethodName)
    {
        Assert::true(
            $this->showPage->hasShipment($shippingMethodName),
            sprintf('Cannot find shipment "%s".', $shippingMethodName)
        );
    }

    /**
     * @Then it should be paid with :paymentMethodName
     */
    public function itShouldBePaidWith($paymentMethodName)
    {
        Assert::true(
            $this->showPage->hasPayment($paymentMethodName),
            sprintf('Cannot find payment "%s".', $paymentMethodName)
        );
    }

    /**
     * @Then /^it should have (\d+) items$/
     * @Then I should see :amount orders in the list
     * @Then I should see a single order in the list
     */
    public function itShouldHaveAmountOfItems($amount = 1)
    {
        $itemsCount = $this->showPage->countItems();

        Assert::same(
            (int)$amount,
            $itemsCount,
            sprintf('There should be %d items, but get %d.', $amount, $itemsCount)
        );
    }

    /**
     * @Then the product named :productName should be in the items list
     */
    public function theProductShouldBeInTheItemsList($productName)
    {
        Assert::true(
            $this->showPage->isProductInTheList($productName),
            sprintf('Product %s is not in the item list.', $productName)
        );
    }

    /**
     * @Then the order's items total should be :itemsTotal
     */
    public function theOrdersItemsTotalShouldBe($itemsTotal)
    {
        $itemsTotalOnPage = $this->showPage->getItemsTotal();

        Assert::eq(
            $itemsTotalOnPage,
            $itemsTotal,
            'Items total is %s, but should be %s.'
        );
    }

    /**
     * @Then /^the order's total should(?:| still) be "([^"]+)"$/
     */
    public function theOrdersTotalShouldBe($total)
    {
        $totalOnPage = $this->showPage->getTotal();

        Assert::eq(
            $totalOnPage,
            $total,
            'Total is %s, but should be %s.'
        );
    }

    /**
     * @Then there should be a shipping charge :shippingCharge
     */
    public function theOrdersShippingChargesShouldBe($shippingCharge)
    {
        Assert::true(
            $this->showPage->hasShippingCharge($shippingCharge),
            sprintf('Shipping charges is not "%s".', $shippingCharge)
        );
    }

    /**
     * @Then the order's shipping total should be :shippingTotal
     */
    public function theOrdersShippingTotalShouldBe($shippingTotal)
    {
        $shippingTotalOnPage = $this->showPage->getShippingTotal();

        Assert::eq(
            $shippingTotal,
            $shippingTotalOnPage,
            sprintf('Shipping total is "%s", but should be "%s".', $shippingTotalOnPage, $shippingTotal)
        );
    }

    /**
     * @Then the order's payment should (also) be :paymentAmount
     */
    public function theOrdersPaymentShouldBe($paymentAmount)
    {
        $actualPaymentAmount = $this->showPage->getPaymentAmount();

        Assert::eq($paymentAmount, $actualPaymentAmount);
    }

    /**
     * @Then the order should have tax :tax
     */
    public function theOrderShouldHaveTax($tax)
    {
        Assert::true(
            $this->showPage->hasTax($tax),
            sprintf('Order should have tax "%s", but it does not.', $tax)
        );
    }

    /**
     * @Then /^the order's tax total should(?:| still) be "([^"]+)"$/
     */
    public function theOrdersTaxTotalShouldBe($taxTotal)
    {
        $taxTotalOnPage = $this->showPage->getTaxTotal();

        Assert::eq(
            $taxTotal,
            $taxTotalOnPage,
            sprintf('Tax total is "%s", but should be "%s".', $taxTotalOnPage, $taxTotal)
        );
    }

    /**
     * @Then the order's promotion discount should be :promotionDiscount
     */
    public function theOrdersPromotionDiscountShouldBe($promotionDiscount)
    {
        Assert::true(
            $this->showPage->hasPromotionDiscount($promotionDiscount),
            sprintf('Promotion discount is not "%s".', $promotionDiscount)
        );
    }

    /**
     * @Then the order's shipping promotion should be :promotion
     */
    public function theOrdersShippingPromotionDiscountShouldBe($promotionData)
    {
        Assert::same($this->showPage->getShippingPromotionData(), $promotionData);
    }

    /**
     * @Then /^the order's promotion total should(?:| still) be "([^"]+)"$/
     */
    public function theOrdersPromotionTotalShouldBe($promotionTotal)
    {
        $promotionTotalOnPage = $this->showPage->getPromotionTotal();

        Assert::eq(
            $promotionTotalOnPage,
            $promotionTotal,
            'Promotion total is %s, but should be %s.'
        );
    }

    /**
     * @When I check :itemName data
     */
    public function iCheckData($itemName)
    {
        $this->sharedStorage->set('item', $itemName);
    }

    /**
     * @Then /^(its) code should be "([^"]+)"$/
     */
    public function itemCodeShouldBe($itemName, $code)
    {
        $itemCodeOnPage = $this->showPage->getItemCode($itemName);

        Assert::same(
            $itemCodeOnPage,
            $code,
            'Item code is %s, but should be %s.'
        );
    }

    /**
     * @Then /^(its) unit price should be ([^"]+)$/
     */
    public function itemUnitPriceShouldBe($itemName, $unitPrice)
    {
        $itemUnitPriceOnPage = $this->showPage->getItemUnitPrice($itemName);

        Assert::eq(
            $itemUnitPriceOnPage,
            $unitPrice,
            'Item unit price is %s, but should be %s.'
        );
    }

    /**
     * @Then /^(its) discounted unit price should be ([^"]+)$/
     */
    public function itemDiscountedUnitPriceShouldBe($itemName, $discountedUnitPrice)
    {
        $itemUnitPriceOnPage = $this->showPage->getItemDiscountedUnitPrice($itemName);

        Assert::eq(
            $itemUnitPriceOnPage,
            $discountedUnitPrice,
            'Item discounted unit price is %s, but should be %s.'
        );
    }

    /**
     * @Then /^(its) quantity should be ([^"]+)$/
     */
    public function itemQuantityShouldBe($itemName, $quantity)
    {
        $itemQuantityOnPage = $this->showPage->getItemQuantity($itemName);

        Assert::eq(
            $itemQuantityOnPage,
            $quantity,
            'Item quantity is %s, but should be %s.'
        );
    }

    /**
     * @Then /^(its) subtotal should be ([^"]+)$/
     */
    public function itemSubtotalShouldBe($itemName, $subtotal)
    {
        $itemSubtotalOnPage = $this->showPage->getItemSubtotal($itemName);

        Assert::eq(
            $itemSubtotalOnPage,
            $subtotal,
            'Item subtotal is %s, but should be %s.'
        );
    }

    /**
     * @Then /^(its) discount should be ([^"]+)$/
     */
    public function theItemShouldHaveDiscount($itemName, $discount)
    {
        $itemDiscountOnPage = $this->showPage->getItemDiscount($itemName);

        Assert::eq(
            $itemDiscountOnPage,
            $discount,
            'Item discount is %s, but should be %s.'
        );
    }

    /**
     * @Then /^(its) tax should be ([^"]+)$/
     */
    public function itemTaxShouldBe($itemName, $tax)
    {
        $itemTaxOnPage = $this->showPage->getItemTax($itemName);

        Assert::eq(
            $itemTaxOnPage,
            $tax,
            'Item tax is %s, but should be %s.'
        );
    }

    /**
     * @Then /^(its) total should be ([^"]+)$/
     */
    public function itemTotalShouldBe($itemName, $total)
    {
        $itemTotalOnPage = $this->showPage->getItemTotal($itemName);

        Assert::eq(
            $itemTotalOnPage,
            $total,
            'Item total is %s, but should be %s.'
        );
    }

    /**
     * @Then I should be notified that the order's payment has been successfully completed
     */
    public function iShouldBeNotifiedThatTheOrderSPaymentHasBeenSuccessfullyCompleted()
    {
        $this
            ->notificationChecker
            ->checkNotification('Payment has been successfully updated.', NotificationType::success());
    }

    /**
     * @Then I should be notified that the order's payment has been successfully refunded
     */
    public function iShouldBeNotifiedThatTheOrderSPaymentHasBeenSuccessfullyRefunded()
    {
        $this
            ->notificationChecker
            ->checkNotification('Payment has been successfully refunded.', NotificationType::success());
    }

    /**
     * @Then it should have payment state :paymentState
     * @Then it should have payment with state :paymentState
     */
    public function itShouldHavePaymentState($paymentState)
    {
        Assert::true(
            $this->showPage->hasPayment($paymentState),
            sprintf('It should have payment with %s state', $paymentState)
        );
    }

    /**
     * @Then it should have order's payment state :orderPaymentState
     */
    public function itShouldHaveOrderPaymentState($orderPaymentState)
    {
        Assert::same(
            $this->showPage->getPaymentState(),
            $orderPaymentState,
            'Order payment state should be %2$s, but it is %s.'
        );
    }
    
    /**
     * @Then it's payment state should be refunded
     */
    public function orderPaymentStateShouldBeRefunded()
    {
        Assert::same(
            $this->showPage->getPaymentState(),
            'Refunded',
            'Order payment state should be refunded, but it is not.'
        );
    }

    /**
     * @Then /^I should not be able to mark (this order) as paid again$/
     */
    public function iShouldNotBeAbleToFinalizeItsPayment(OrderInterface $order)
    {
        Assert::false(
            $this->showPage->canCompleteOrderLastPayment($order),
            'It should not have complete payment button.'
        );
    }

    /**
     * @Then I should be notified that the order has been successfully shipped
     */
    public function iShouldBeNotifiedThatTheOrderHasBeenSuccessfullyShipped()
    {
        $this->notificationChecker->checkNotification(
            'Shipment has been successfully updated.',
            NotificationType::success()
        );
    }

    /**
     * @Then /^I should not be able to ship (this order)$/
     */
    public function iShouldNotBeAbleToShipThisOrder(OrderInterface $order)
    {
        Assert::false(
            $this->showPage->canShipOrder($order),
            'It should not have ship shipment button.'
        );
    }

    /**
     * @When I cancel this order
     */
    public function iCancelThisOrder()
    {
        $this->showPage->cancelOrder();
    }

    /**
     * @Then I should be notified that it has been successfully updated
     */
    public function iShouldBeNotifiedAboutItHasBeenSuccessfullyCanceled()
    {
        $this->notificationChecker->checkNotification(
            'Order has been successfully updated.',
            NotificationType::success()
        );
    }

    /**
     * @Then I should not be able to cancel this order
     */
    public function iShouldNotBeAbleToCancelThisOrder()
    {
        Assert::false(
            $this->showPage->hasCancelButton(),
            'There should not be a cancel button, but it is.'
        );
    }

    /**
     * @Then this order should have state :state
     * @Then its state should be :state
     */
    public function itsStateShouldBe($state)
    {
        Assert::same(
            $this->showPage->getOrderState(),
            $state,
            'The order state should be %2$s, but it is %s.'
        );
    }

    /**
     * @Then it should( still) have a :state state
     */
    public function itShouldHaveState($state)
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['state' => $state]),
            sprintf('Cannot find order with "%s" state in the list.', $state)
        );
    }

    /**
     * @Then /^(the administrator) should know about (this additional note) for (this order made by "[^"]+")$/
     */
    public function theCustomerServiceShouldKnowAboutThisAdditionalNotes(
        AdminUserInterface $user,
        $note,
        OrderInterface $order
    ) {
        $this->sharedSecurityService->performActionAsAdminUser(
            $user,
            function () use ($note, $order) {
                $this->showPage->open(['id' => $order->getId()]);
                Assert::true($this->showPage->hasNote($note), sprintf('I should see %s note, but I do not see', $note));
            }
        );
    }

    /**
     * @Then I should see an order with :orderNumber number
     */
    public function iShouldSeeOrderWithNumber($orderNumber)
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['number' => $orderNumber]),
            sprintf('Cannot find order with "%s" number in the list.', $orderNumber)
        );
    }

    /**
     * @Then I should not see an order with :orderNumber number
     */
    public function iShouldNotSeeOrderWithNumber($orderNumber)
    {
        Assert::false(
            $this->indexPage->isSingleResourceOnPage(['number' => $orderNumber]),
            sprintf('Order with "%s" number should not be in the list.', $orderNumber)
        );
    }

    /**
     * @Then I should not see any orders with currency :currencyCode
     */
    public function iShouldNotSeeAnyOrderWithCurrency($currencyCode)
    {
        Assert::false(
            $this->indexPage->isSingleResourceOnPage(['currencyCode' => $currencyCode]),
            sprintf('Order with currency "%s" should not be on the list.', $currencyCode)
        );
    }

    /**
     * @Then the first order should have number :number
     */
    public function theFirstOrderShouldHaveNumber($number)
    {
        $actualNumber = $this->indexPage->getColumnFields('number')[0];

        Assert::eq(
            $actualNumber,
            $number,
            sprintf('Expected first order\'s number to be %s, but it is %s.', $number, $actualNumber)
        );
    }

    /**
     * @Then it should have shipment in state :shipmentState
     */
    public function itShouldHaveShipmentState($shipmentState)
    {
        Assert::true(
            $this->showPage->hasShipment($shipmentState),
            sprintf('It should have shipment with %s state', $shipmentState)
        );
    }

    /**
     * @Then order :orderNumber should have shipment state :shippingState
     */
    public function thisOrderShipmentStateShouldBe($shippingState)
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['shippingState' => $shippingState]),
            sprintf('Order should have %s shipping state', $shippingState)
        );
    }

    /**
     * @Then the order :order should have order payment state :orderPaymentState
     * @Then /^(this order) should have order payment state "([^"]+)"$/
     */
    public function theOrderShouldHavePaymentState(OrderInterface $order, $orderPaymentState)
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['paymentState' => $orderPaymentState]),
            sprintf('Cannot find order with "%s" order payment state in the list.', $orderPaymentState)
        );
    }

    /**
     * @Then /^(this order) should have order shipping state "([^"]+)"$/
     */
    public function theOrderShouldHaveShipmentState(OrderInterface $order, $orderShipmentState)
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['shippingState' => $orderShipmentState]),
            sprintf('Cannot find order with "%s" order shipping state on the list.', $orderShipmentState)
        );
    }

    /**
     * @Then /^there should be(?:| only) (\d+) payments?$/
     */
    public function theOrderShouldHaveNumberOfPayments($number)
    {
        $actualNumberOfPayments = $this->showPage->getPaymentsCount();

        Assert::same((int)$number, $actualNumberOfPayments);
    }

    /**
     * @Then I should see the order :orderNumber with total :total
     */
    public function iShouldSeeTheOrderWithTotal($orderNumber, $total)
    {
        Assert::true(
            $this->indexPage->isSingleResourceOnPage(['total' => $total]),
            sprintf('The total of order "%s" is not "%s".', $orderNumber, $total)
        );
    }

    /**
     * @When /^I want to modify a customer's (?:billing|shipping) address of (this order)$/
     */
    public function iWantToModifyACustomerSShippingAddress(OrderInterface $order)
    {
        $this->updatePage->open(['id' => $order->getId()]);
    }

    /**
     * @When I save my changes
     * @When I try to save my changes
     */
    public function iSaveMyChanges()
    {
        $this->updatePage->saveChanges();
    }

    /**
     * @When /^I specify their (?:|new )shipping (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/
     */
    public function iSpecifyTheirShippingAddressAsFor(AddressInterface $address)
    {
        $this->updatePage->specifyShippingAddress($address);
    }

    /**
     * @When /^I specify their (?:|new )billing (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/
     */
    public function iSpecifyTheirBillingAddressAsFor(AddressInterface $address)
    {
        $this->updatePage->specifyBillingAddress($address);
    }

    /**
     * @Then /^I should be notified that the "([^"]+)", the "([^"]+)", the "([^"]+)" and the "([^"]+)" in (shipping|billing) details are required$/
     */
    public function iShouldBeNotifiedThatTheAndTheInShippingDetailsAreRequired($firstElement, $secondElement, $thirdElement, $fourthElement, $type)
    {
        $this->assertElementValidationMessage($type, $firstElement, sprintf('Please enter %s.', $firstElement));
        $this->assertElementValidationMessage($type, $secondElement, sprintf('Please enter %s.', $secondElement));
        $this->assertElementValidationMessage($type, $thirdElement, sprintf('Please enter %s.', $thirdElement));
        $this->assertElementValidationMessage($type, $fourthElement, sprintf('Please enter %s.', $fourthElement));
    }

    /**
     * @Then I should see :provinceName as province in the shipping address
     */
    public function iShouldSeeAsProvinceInTheShippingAddress($provinceName)
    {
        Assert::true(
            $this->showPage->hasShippingProvinceName($provinceName),
            sprintf('Cannot find shipping address with province %s', $provinceName)
        );
    }

    /**
     * @Then I should see :provinceName ad province in the billing address
     */
    public function iShouldSeeAdProvinceInTheBillingAddress($provinceName)
    {
        Assert::true(
            $this->showPage->hasBillingProvinceName($provinceName),
            sprintf('Cannot find shipping address with province %s', $provinceName)
        );
    }

    /**
     * @Then /^(the administrator) should know about IP address of (this order made by "[^"]+")$/
     */
    public function theAdministratorShouldKnowAboutIPAddressOfThisOrderMadeBy(
        AdminUserInterface $user,
        OrderInterface $order
    ) {
        $this->sharedSecurityService->performActionAsAdminUser(
            $user,
            function () use ($order) {
                $this->showPage->open(['id' => $order->getId()]);

                Assert::notSame(
                    $this->showPage->getIpAddressAssigned(),
                    '',
                    'There should be IP address assigned to order, but there is not.'
                );
            }
        );
    }

    /**
     * @When /^I (clear old billing address) information$/
     */
    public function iSpecifyTheBillingAddressAs(AddressInterface $address)
    {
        $this->updatePage->specifyBillingAddress($address);
    }

    /**
     * @When /^I (clear old shipping address) information$/
     */
    public function iSpecifyTheShippingAddressAs(AddressInterface $address)
    {
        $this->updatePage->specifyShippingAddress($address);
    }

    /**
     * @When /^I do not specify new information$/
     */
    public function iDoNotSpecifyNewInformation()
    {
        // Intentionally left blank to fulfill context expectation
    }

    /**
     * @Then /^(the administrator) should see that (order placed by "[^"]+") has "([^"]+)" currency$/
     */
    public function theAdministratorShouldSeeThatThisOrderHasBeenPlacedIn(AdminUserInterface $user, OrderInterface $order, $currency)
    {
        $this->sharedSecurityService->performActionAsAdminUser($user, function () use ($order, $currency) {
            $this->showPage->open(['id' => $order->getId()]);
            Assert::same(
                $this->showPage->getOrderCurrency(),
                $currency,
                'The order has been placed in %s, but it was expected to be placed in %s'
            );
        });
    }

    /**
     * @Then /^(the administrator) should see the order with total "([^"]+)" in order list$/
     */
    public function theAdministratorShouldSeeTheOrderWithTotalInOrderList(AdminUserInterface $user, $total)
    {
        $this->sharedSecurityService->performActionAsAdminUser($user, function () use ($total) {
            $this->indexPage->open();

            Assert::true(
                $this->indexPage->isSingleResourceOnPage(['total' => $total]),
                sprintf('The order with total "%s" has not been found.', $total)
            );
        });
    }

    /**
     * @Then there should be :count changes in the registry
     */
    public function thereShouldBeCountChangesInTheRegistry($count)
    {
        Assert::same(
            $this->historyPage->countShippingAddressChanges(),
            (int) $count
        );
    }

    /**
     * @Then I should not be able to refund this payment
     */
    public function iShouldNotBeAbleToRefundThisPayment()
    {
        Assert::false($this->showPage->hasRefundButton());
    }

    /**
     * @Then I should not see information about payments
     */
    public function iShouldNotSeeInformationAboutPayments()
    {
        Assert::same(
            0,
            $this->showPage->getPaymentsCount(),
            'There should be no payments, but they are.'
        );
    }

    /**
     * @param string $type
     * @param string $element
     * @param string $expectedMessage
     *
     * @throws \InvalidArgumentException
     */
    private function assertElementValidationMessage($type, $element, $expectedMessage)
    {
        $element = sprintf('%s_%s', $type, implode('_', explode(' ', $element)));
        Assert::true(
            $this->updatePage->checkValidationMessageFor($element, $expectedMessage),
            sprintf('The %s should be required.', $element)
        );
    }
}
