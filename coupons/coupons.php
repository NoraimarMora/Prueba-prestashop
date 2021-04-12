<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\MailTemplate\Layout\Layout;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCatalogInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCollectionInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeInterface;

class Coupons extends Module {
    public function __construct()
    {
        $this->name = 'coupons';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0';
        $this->author = 'Noraimar Mora';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->need_instance = 1;
        parent::__construct();
        $this->displayName = $this->l('Coupons');
        $this->description = $this->l('Create a coupon when the customer exceeds an amount among all their purchases');
    }

    public function install()
    {
        $languages = $this->context->controller->getLanguages();
        $coupon_name = array();
        foreach ($languages as $language) {
            $coupon_name[$language['id_lang']] = "Loyalty discount";
        }

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('displayOrderConfirmation2') &&
            $this->registerHook(ThemeCatalogInterface::LIST_MAIL_THEMES_HOOK) &&
            Configuration::updateValue("C_MAX_AMOUNT", 100) &&
            Configuration::updateValue("C_COUPON_NAME", $coupon_name) &&
            Configuration::updateValue("C_COUPON_DISCOUNT", 20);
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Configuration::deleteByName("C_MAX_AMOUNT") &&
            Configuration::deleteByName("C_COUPON_NAME") &&
            Configuration::deleteByName("C_COUPON_DISCOUNT");
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm() . $this->renderList();
    }

    public function postProcess()
    {
        $languages = $this->context->controller->getLanguages();
        $coupon_name = array();
        foreach ($languages as $language) {
            $coupon_name[$language['id_lang']] = Tools::getValue("C_COUPON_NAME" . $language['id_lang']);

        }

        Configuration::updateValue("C_MAX_AMOUNT", Tools::getValue("C_MAX_AMOUNT"));
        Configuration::updateValue("C_COUPON_NAME", $coupon_name);
        Configuration::updateValue("C_COUPON_DISCOUNT", Tools::getValue("C_COUPON_DISCOUNT"));
        
        return $this->displayConfirmation($this->l("Configuration updated."));
    }

    protected function renderForm()
    {
        $default_lang = $this->context->language->id;

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Configuration'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'name' => 'C_MAX_AMOUNT',
                    'type' => 'text',
                    'required' => true,
                    'validate' => 'isFloat',
                    'label' => $this->l('Maximum amount'),
                    'desc' => $this->l('Amount of money to be spent to generate the coupon'),
                ),
                array(
                    'name' => 'C_COUPON_NAME',
                    'type' => 'text',
                    'required' => true,
                    'label' => $this->l('Coupon Name'),
                    'lang' => true
                ),
                array(
                    'name' => 'C_COUPON_DISCOUNT',
                    'type' => 'text',
                    'required' => true,
                    'validate' => 'isFloat',
                    'label' => $this->l('Coupon Discount')
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $languages = $this->context->controller->getLanguages();
        foreach ($languages as $language) {
            $values['C_COUPON_NAME'][$language['id_lang']] = Configuration::get('C_COUPON_NAME', $language['id_lang']);
        }

        $values['C_MAX_AMOUNT'] = Configuration::get('C_MAX_AMOUNT');
        $values['C_COUPON_DISCOUNT'] = Configuration::get('C_COUPON_DISCOUNT');

        $helper                             = new HelperForm();
        $helper->module                     = $this;
        $helper->name_controller            = $this->name;
        $helper->token                      = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex               = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action              = 'submit' . $this->name;
        $helper->default_form_language      = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang   = $this->context->controller->default_form_language;
        $helper->toolbar_scroll             = false;
        $helper->show_toolbar               = false;
        $helper->show_cancel_button         = true;
        $helper->fields_value               = $values;

        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );
        }

        return $helper->generateForm($fields_form);
    }

    protected function renderList()
    {
        $info = $this->getList();

        $fields_list = array(
            'id_customer' => array(
                'title' => $this->l('Customer ID'),
                'align' => 'center',
                'type' => 'int',
                'class' => 'fixed-width-xs'
            ),
            'firstname' => array(
                'title' => $this->l('Name'),
                'type' => 'text'
            ),
            'lastname' => array(
                'title' => $this->l('Lastname'),
                'type' => 'text'
            ),
            'email' => array(
                'title' => $this->l('Email'),
                'type' => 'text'
            ),
            'code' => array(
                'title' => $this->l('Coupon code'),
                'type' => 'text'
            ),
            'date_add' => array(
                'title' => $this->l('Creation date'),
                'type' => 'text'
            ),
        );

        $helper = new HelperList();
        $helper->bulk_actions = false; 
        $helper->shopLinkType = '';
        $helper->no_link = false;       // Content line is clickable if false
        $helper->simple_header = true;  // false = search header, true = not header. No filters, no paginations and no sorting.
        // $helper->actions = array('edit', 'delete');
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->identifier = 'id_customer';
        $helper->title = $this->l('Coupon list');
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        
        return $helper->generateList($info, $fields_list);
    }

    public function hookHeader($params)
    {
        if (!$this->active) {
            return;
        }

        $this->context->controller->addCSS($this->getLocalPath() . 'views/css/coupons.css');
        $this->context->controller->addJS($this->getLocalPath() . 'views/js/coupons.js');
    }

    /**
     * @param array $hookParams
     */
    public function hookActionListMailThemes(array $hookParams)
    {
        if (!isset($hookParams['mailThemes'])) {
            return;
        }

        /** @var ThemeCollectionInterface $themes */
        $themes = $hookParams['mailThemes'];

        /** @var ThemeInterface $theme */
        foreach ($themes as $theme) {
            if (!in_array($theme->getName(), ['classic', 'modern'])) {
                continue;
            }

            // Add a layout to each theme (don't forget to specify the module name)
            $theme->getLayouts()->add(new Layout(
                'coupon',
                __DIR__ . '/mails/layouts/coupon.html.twig',
                '',
                $this->name
            ));

            $theme->getLayouts()->add(new Layout(
                'total_spent',
                __DIR__ . '/mails/layouts/total_spent.html.twig',
                '',
                $this->name
            ));
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $order = new Order($params['id_order']);
        $customer = new Customer((int) $order->id_customer);

        $total_spent = (float) $this->getTotalSpentByCustomer($customer->id);
        $max_amount = Configuration::get("C_MAX_AMOUNT");

        // Enviar correo
        $templateVars = array(
            '{lastname}' => $customer->lastname,
            '{firstname}' => $customer->firstname,
            '{total_spent}' => Tools::displayPrice($total_spent)
        );

        Mail::send(
            (int) $order->id_lang,
            'total_spent',
            $this->l('Total Spent'),
            $templateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'coupons/mails',
            null,
            (int) $order->id_shop
        );

        if ($total_spent >= $max_amount) {
            $coupon = $this->createCoupon($customer->id);
        
            if ($coupon != null) {
                // Enviar coupon
                $templateVars = array(
                    '{coupon_code}' => $coupon->code,
                    '{coupon_amount}' => Tools::displayPrice($coupon->reduction_amount)
                );
        
                Mail::send(
                    (int) $order->id_lang,
                    'coupon',
                    $this->l('You have earned a coupon'),
                    $templateVars,
                    $customer->email,
                    $customer->firstname . ' ' . $customer->lastname,
                    null,
                    null,
                    null,
                    null,
                    _PS_MODULE_DIR_ . 'coupons/mails',
                    null,
                    (int) $order->id_shop
                );
            }
        }
    }

    public function hookDisplayOrderConfirmation2($params)
    {
        $coupon = $this->getLastCoupon((int) $this->context->customer->id);

        if (is_array($coupon) && count($coupon) > 0) {
            $this->context->smarty->assign(
                array(
                    'coupon_code' => $coupon[0]['code'],
                    'coupon_amount' => Tools::displayPrice($coupon[0]['reduction_amount'])
                )
            );

            return $this->display(__FILE__, 'views/templates/hook/coupons.tpl');
        }
    }

    public function getTotalSpentByCustomer($customer_id)
    {
        return Db::getInstance()->getValue(
            'SELECT SUM(total_paid_real) AS total_spent
             FROM `' . _DB_PREFIX_ . 'orders`
             WHERE `id_customer` = ' . $customer_id . '
                AND `valid` = 1'
        );
    }

    public function createCoupon($customer_id)
    {
        $lang_id = (int) $this->context->language->id;
        $customer_coupons = CartRule::getCustomerCartRules($lang_id, $customer_id);

        if (count($customer_coupons) > 0) {
            return null;
        }

        $languages = $this->context->controller->getLanguages();
        foreach ($languages as $language) {
            $values[$language['id_lang']] = Configuration::get('C_COUPON_NAME', $language['id_lang']);
        }

        $coupon = new CartRule();
        $coupon->name = $values;
        $coupon->date_from = date('Y-m-d H:i:s');
        $coupon->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));
        $coupon->highlight = true;
        $coupon->code = Tools::passwdGen(5);
        $coupon->id_customer = $customer_id;
        $coupon->reduction_amount = Configuration::get("C_COUPON_DISCOUNT");
        $coupon->reduction_currency = ContextCore::getContext()->currency->id;

        return $coupon->add() ? $coupon : null;
    }

    public function getList()
    {
        return Db::getInstance()->executeS(
            'SELECT c.id_customer, c.firstname, c.lastname, c.email, cr.code, cr.date_add
             FROM `' . _DB_PREFIX_ . 'customer` c, 
                  `' . _DB_PREFIX_ . 'cart_rule` cr
             WHERE c.id_customer = cr.id_customer'
        );
    }

    public function getLastCoupon($customer_id)
    {
        return Db::getInstance()->executeS(
            'SELECT code, reduction_amount
             FROM `' . _DB_PREFIX_ . 'cart_rule` 
             WHERE id_customer = ' . $customer_id . ' AND
                   active = 1
             ORDER BY date_add DESC
             LIMIT 1'
        );
    }
}