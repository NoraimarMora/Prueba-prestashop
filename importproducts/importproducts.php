<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ImportProducts extends Module {
    public function __construct()
    {
        $this->name = 'importproducts';
        $this->tab = 'others';
        $this->version = '1.0';
        $this->author = 'Noraimar Mora';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->need_instance = 1;
        parent::__construct();
        $this->displayName = $this->l('Import products');
        $this->description = $this->l('');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('importProducts')) {
            if ($this->importProducts()) {
                $output .= $this->displayConfirmation($this->l('Imported products.'));
            } else {
                $output .= $this->displayError($this->l('An error has occurred.'));
            }
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Import Products'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'name' => 'import_btn',
                    'type' => 'button',
                    'label' => $this->l('Import products'),
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&importProducts'
                ),
            )
        );

        $helper                             = new HelperForm();
        $helper->module                     = $this;
        $helper->name_controller            = $this->name;
        $helper->token                      = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex               = AdminController::$currentIndex . '&configure=' . $this->name;
        // $helper->submit_action              = 'submit' . $this->name;
        $helper->default_form_language      = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang   = $this->context->controller->default_form_language;
        $helper->toolbar_scroll             = false;
        $helper->show_toolbar               = false;
        $helper->show_cancel_button         = true;

        return $helper->generateForm($fields_form);
    }

    public function importProducts()
    {
        $file = fopen("https://wiki.webimpacto.net/docs/products.csv", "r");
        $default_language_id = (int) Configuration::get('PS_LANG_DEFAULT');

        if (!$file) {
            return false;
        }

        $line_count = 0;
        $warnings = array();
        $line = fgetcsv($file, 0, ","); // Encabezados del archivo
        for ($current_line = 0; ($line = fgetcsv($file, 0, ",")); ++$current_line) {
            ++$line_count;

            if (count($line) == 1 && $line[0] == null) {
                $warnings[] = $this->l('There is an empty row in the file that won\'t be imported.', [], 'Admin.Advparameters.Notification');

                continue;
            }

            $info = array(
                'name' => $line[0],
                'reference' => $line[1],
                'ean13' => $line[2],
                'cost_price' => $line[3],
                'sale_price' => $line[4],
                'iva' => $line[5],
                'quantity' => $line[6],
                'categories' => $line[7],
                'brand' => $line[8]
            );

            $this->addProduct(
                $info,
                $default_language_id,
                $this->context->language->id
            );
        }

        fclose($file);
        return true;
    }

    public function addProduct($info, $default_lang, $lang_id)
    {
        $id_shop = (int) Context::getContext()->shop->id;
        $id_shop_group = (int) Context::getContext()->shop->id_shop_group;
        $id_shop_list = array();
        array_push($id_shop_list, $id_shop);

        $product = new Product();
        $product->shop = $id_shop;
        $product->id_shop_list = $id_shop_list;
        $product->id_shop_default = (int) Configuration::get('PS_SHOP_DEFAULT');

        // Manufacturer
        if ($manufacturer = Manufacturer::getIdByName($info['brand'])) {
            $product->id_manufacturer = (int) $manufacturer;
        } else {
            $manufacturer = new Manufacturer();
            $manufacturer->name = $info['brand'];
            $manufacturer->active = true;
            $manufacturer->add();

            $product->id_manufacturer = (int) $manufacturer->id;
            $manufacturer->associateTo($product->id_shop_list);
        }

        $product->name = $this->createMultiLangField($info['name']);
        $link_rewrite = Tools::link_rewrite($product->name[$default_lang]);
        $product->link_rewrite = $this->createMultiLangField($link_rewrite);
        $product->price = (float) $info['sale_price'];
        $product->wholesale_price = $info['cost_price'];
        $product->ean13 = $info['ean13'];
        $product->reference = $info['reference'];
        $product->condition = 'new';
        $product->new = true;
        $product->visibility = 'both';
        $product->quantity = (int) $info['quantity'];

        $id_tax_rules_group = $this->findTaxRuleGroupIdByRate($info['iva']);
        if (!$id_tax_rules_group) {
            $id_tax_rules_group = $this->createTaxRule($info['iva']);
        }
        $product->id_tax_rules_group = $id_tax_rules_group;

        $categories = explode(";", $info['categories']);
        $product->id_category = [];
        foreach ($categories as $cat) {
            $category = Category::searchByName($lang_id, trim($cat), true, true);

            if (!$category) {
                $new_category = new Category();
                $new_category->id_shop_default = $id_shop;
                $new_category->name = $this->createMultiLangField(trim($cat));
                $new_category->active = 1;
                $new_category->id_parent = (int) Configuration::get('PS_HOME_CATEGORY');
                $nc_link_rewrite = Tools::link_rewrite($new_category->name[$default_lang]);
                $new_category->link_rewrite = $this->createMultiLangField($nc_link_rewrite);
                $new_category->add();
                $product->id_category[] = (int) $new_category->id;
            } else {
                if (isset($category['id_category']) && $category['id_category']) {
                    $product->id_category[] = (int) $category['id_category'];
                }
            }
        }
        $product->id_category = array_values(array_unique($product->id_category));

        if (isset($product->id_category[0])) {
            $product->id_category_default = (int) $product->id_category[0];
        } else {
            $product->id_category_default = (int) Configuration::get('PS_HOME_CATEGORY');
        }

        // Convert comma into dot for all floating values
        foreach (Product::$definition['fields'] as $key => $array) {
            if ($array['type'] == Product::TYPE_FLOAT) {
                $product->{$key} = str_replace(',', '.', $product->{$key});
            }
        }
        $product->indexed = 0;
        $product->add();

        StockAvailable::setQuantity((int) $product->id, 0, (int) $product->quantity, (int) $this->context->shop->id);

        return true;
    }

    protected static function createMultiLangField($field)
    {
        $res = [];
        foreach (Language::getIDs(false) as $id_lang) {
            $res[$id_lang] = $field;
        }

        return $res;
    }

    public function findTaxRuleGroupIdByRate($rate)
    {
        return Db::getInstance()->getValue(
            'SELECT tr.id_tax_rules_group
             FROM `' . _DB_PREFIX_ . 'tax` t,
                  `' . _DB_PREFIX_ . 'tax_rule` tr
             WHERE t.rate = ' . (float) $rate . '
                AND tr.id_tax = t.id_tax'
        );
    }

    public function createTaxRule($rate)
    {
        $tax = new Tax();
        $tax->name = $this->createMultiLangField('Rate ' . $rate . '%');
        $tax->active = true;
        $tax->rate = (float) $rate;
        $tax->add();

        $tax_rules_group = new TaxRulesGroup();
        $tax_rules_group->name = 'Rate ' . $rate . '%';
        $tax_rules_group->active = true;
        $tax_rules_group->add();

        $tax_rule = new TaxRule();
        $tax_rule->id_tax_rules_group = $tax_rules_group->id;
        $tax_rule->id_country = $this->context->country->id;
        $tax_rule->id_tax = $tax->id;
        $tax_rule->add();

        return $tax_rules_group->id;
    }
}