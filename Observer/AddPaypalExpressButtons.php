<?php

namespace Fatchip\Nexi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Paypal\Block\Express\Shortcut;

/**
 * Event class to add the PayPal Express buttons to the frontend
 */
class AddPaypalExpressButtons implements ObserverInterface
{
    /**
     * Add PayPal shortcut buttons
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        if (in_array(
            $shortcutButtons->getNameInLayout(),
            ['addtocart.shortcut.buttons', 'addtocart.shortcut.buttons.additional', 'map.shortcut.buttons']
        )) {
            return;
        }

        # checkout.cart.shortcut.buttons = BUTTON AUF WARENKORB SEITE
        # shortcutbuttons_0 = MINI WARENKORB

        /** @var Shortcut $shortcut */
        $shortcut = $shortcutButtons->getLayout()->createBlock(
            'Fatchip\Nexi\Block\Paypal\ExpressButton',
            '',
            []
        );
        $shortcut->setName($shortcutButtons->getNameInLayout());

        $shortcutButtons->addShortcut($shortcut);
    }
}
