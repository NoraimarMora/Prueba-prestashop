<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class HomeMessages extends Module {
    public function __construct()
    {
        $this->name = 'homemessages';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        $this->author = 'Noraimar Mora';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->need_instance = 1;
        parent::__construct();
        $this->displayName = $this->l('Home Messages');
        $this->description = $this->l('Display messages on the home page');
    }

    public function install()
    {
        $languages = $this->context->controller->getLanguages();
        $top_message = array();
        $bottom_message = array();
        foreach ($languages as $language) {
            $top_message[$language['id_lang']] = "Welcome!";
            $bottom_message[$language['id_lang']] = "Welcome!";
        }

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('displayFooterBefore') &&
            Configuration::updateValue("HM_TOP_MESSAGE", $top_message) &&
            Configuration::updateValue("HM_BOTTOM_MESSAGE", $bottom_message);
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Configuration::deleteByName("HM_TOP_MESSAGE") &&
            Configuration::deleteByName("HM_BOTTOM_MESSAGE");
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    public function postProcess()
    {
        $languages = $this->context->controller->getLanguages();
        $top_message = array();
        $bottom_message = array();
        foreach ($languages as $language) {
            $top_message[$language['id_lang']] = Tools::getValue("HM_TOP_MESSAGE_" . $language['id_lang']);
            $bottom_message[$language['id_lang']] = Tools::getValue("HM_BOTTOM_MESSAGE_" . $language['id_lang']);
        }

        Configuration::updateValue("HM_TOP_MESSAGE", $top_message);
        Configuration::updateValue("HM_BOTTOM_MESSAGE", $bottom_message);
        
        return $this->displayConfirmation($this->l("Configuration updated."));
    }

    protected function renderForm()
    {
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Configuration'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'name' => 'HM_TOP_MESSAGE',
                    'type' => 'text',
                    'label' => $this->l('Top Message'),
                    'required' => true,
                    'lang' => true
                ),
                array(
                    'name' => 'HM_BOTTOM_MESSAGE',
                    'type' => 'text',
                    'label' => $this->l('Bottom Message'),
                    'required' => true,
                    'lang' => true
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $languages = $this->context->controller->getLanguages();
        foreach ($languages as $language) {
            $values['HM_TOP_MESSAGE'][$language['id_lang']] = Configuration::get('HM_TOP_MESSAGE', $language['id_lang']);
            $values['HM_BOTTOM_MESSAGE'][$language['id_lang']] = Configuration::get('HM_BOTTOM_MESSAGE', $language['id_lang']);
        }

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

    public function hookHeader($params)
    {
        if (!$this->active) {
            return;
        }

        $this->context->controller->addCSS($this->getLocalPath() . 'views/css/hook.css');
    }

    public function hookDisplayHome($params)
    {
        if (!$this->active) {
            return;
        }

        $lang_id = (int) $this->context->language->id;

        $this->context->smarty->assign(
            array(
                'message' => Configuration::get('HM_TOP_MESSAGE', $lang_id)
            )
        );

        return $this->display(__FILE__, 'views/templates/hook/homemessages.tpl');
    }

    public function hookDisplayFooterBefore($params)
    {
        if (!$this->active) {
            return;
        }

        $lang_id = (int) $this->context->language->id;

        $this->context->smarty->assign(
            array(
                'message' => Configuration::get('HM_BOTTOM_MESSAGE', $lang_id)
            )
        );

        return $this->display(__FILE__, 'views/templates/hook/homemessages.tpl');
    }
}