<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class WeatherInfo extends Module implements WidgetInterface {
    public function __construct()
    {
        $this->name = 'weatherinfo';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        $this->author = 'Noraimar Mora';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->need_instance = 1;
        parent::__construct();
        $this->displayName = $this->l('Weather Info');
        $this->description = $this->l('Displays weather information on the home page');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayNav') &&
            $this->registerHook('displayNav1');
    }

    public function uninstall()
    {
        return parent::uninstall();
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
        Configuration::updateValue("WI_API_KEY", Tools::getValue("WI_API_KEY"));
        
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
                    'name' => 'WI_API_KEY',
                    'type' => 'text',
                    'label' => $this->l('API Key'),
                    'desc' => $this->l('API key for weatherapi.com'),
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

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

        return $helper->generateForm($fields_form);
    }

    public function hookHeader($params)
    {
        if (!$this->active) {
            return;
        }

        $this->context->controller->addCSS($this->getLocalPath() . 'views/css/hook.css');
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->active) {
            return;
        }

        if ($hookName == null && isset($configuration['hook'])) {
            $hookName = $configuration['hook'];
        }

        if (preg_match('/^displayNav\d*$/', $hookName)) {
            $weather = $this->getWidgetVariables($hookName, $configuration);

            if (!is_array($weather)) {
                return;
            } 

            $this->context->smarty->assign(
                array(
                    'weather' => $weather
                )
            );

            return $this->display(__FILE__, 'weatherinfo.tpl');
        }
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $customer_ip = $this->getCustomerIp();
        $api_key = Configuration::get("WI_API_KEY");
        $lang_iso = $this->context->language->iso_code;

        $url = "http://api.weatherapi.com/v1/current.json?key=$api_key&q=$customer_ip&lang=$lang_iso";
        try {
            $curl = curl_init($url);
            $options = array(
                CURLOPT_RETURNTRANSFER   => true,
                CURLOPT_HEADER           => false,  // don't return header
                CURLOPT_MAXREDIRS        => 10,
                CURLOPT_ENCODING         => "",     // handle compressed
                CURLOPT_CONNECTTIMEOUT   => 120,    // time-out on connect
                CURLOPT_TIMEOUT          => 120,    // time-out on response
                CURLOPT_HTTPHEADER       => array(
                    "content-type: application/json",
                ),
            );

            curl_setopt_array($curl, $options);
            $response = curl_exec($curl);
            $error = curl_error($curl);
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($error || ($responseCode < 200 || $responseCode > 299)) {
                return null;
            }

            $result = json_decode($response);
            $data = array(
                'temp_c' => (float)$result->current->temp_c,
                'condition' => $result->current->condition->text,
                'condition_icon' => $result->current->condition->icon,
                'feelslike_c' => (float)$result->current->feelslike_c,
                'city' => $result->location->name,
                'country' => $result->location->country,
                'last_updated' => $result->current->last_updated,
                'humidity' => $result->current->humidity,
                'wind_kph' => $result->current->wind_kph
            );

            return $data;
        } catch (Exception $err) {
            return null;
        }
    }

    public function getCustomerIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}