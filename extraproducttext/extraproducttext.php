<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ExtraProductText extends Module {
    public function __construct()
    {
        $this->name = 'extraproducttext';
        $this->tab = 'others';
        $this->version = '1.0';
        $this->author = 'Noraimar Mora';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->need_instance = 1;
        parent::__construct();
        $this->displayName = $this->l('Extra Product Text');
        $this->description = $this->l('Add additional text on the product sheet');
    }

    public function install()
    {
        return parent::install() && $this->alterTable() &&
            $this->registerHook('displayAdminProductsMainStepLeftColumnMiddle') &&
            $this->registerHook('actionProductUpdate') && 
            $this->registerHook('displayProductPriceBlock');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->dropColumn();
    }

    public function alterTable()
    {
        $query = "ALTER TABLE `" . _DB_PREFIX_ . "product_lang` ADD `aditional_text` TEXT NULL";

        if(!Db::getInstance()->execute($query))
            return false;
        return true;
    }

    public function dropColumn()
    {
        $query = "ALTER TABLE `" . _DB_PREFIX_ . "product_lang` DROP COLUMN `aditional_text`";

        if(!Db::getInstance()->execute($query))
            return false;
        return true;
    }

    public function hookDisplayAdminProductsMainStepLeftColumnMiddle($params)
    {
        $product = new Product($params['id_product']);
        $languages = $this->context->controller->getLanguages();

        $this->context->smarty->assign(
            array(
                'languages' => $languages,
                'aditional_text' => $product->aditional_text,
                'default_language' => $this->context->language->id,
            )
        );

        echo "Hola";

        return $this->display(__FILE__, 'views/templates/hook/form.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        $id_product = (int)Tools::getValue('id_product');    
        $languages = $this->context->controller->getLanguages();

        foreach ($languages as $language) {
            if (!Db::getInstance()->update('product_lang', array('aditional_text' => pSQL(Tools::getValue('aditional_text_'.$language['id_lang']))) ,'id_lang = ' . $language['id_lang'] .' AND id_product = ' . $id_product ))
                $this->context->controller->_errors[] = Tools::displayError('Error: ').mysql_error();
        }
    }

    public function hookDisplayProductPriceBlock($params)
    {
        $controller = $this->context->controller->php_self;
        if (isset($params['product']) && $params['type'] == 'after_price' && $controller = 'product') {
            $lang_id = $this->context->language->id;
            $product = $params['product'];

            $this->context->smarty->assign(
                array(
                    'product' => $product
                )
            );

            return $this->display(__FILE__, 'views/templates/hook/extraproducttext.tpl');
        }
    }
}