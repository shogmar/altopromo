<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Price_from_to extends Module
{
    public function __construct()
    {
        $this->name = 'price_from_to';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Firstname Lastname';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('price_from_to');
        $this->description = $this->l('Description of my module.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('hookFooter') ||
            !Configuration::updateValue('PriceFrom', 1)||
            !Configuration::updateValue('PriceTo', 2)
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !Configuration::deleteByName('MYMODULE_NAME')) return FALSE;
        return true;
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            $PriceFrom = intval(Tools::getValue('PriceFrom'));
            $PriceTo = intval(Tools::getValue('PriceTo'));
            if (
                !Validate::isUnsignedInt($PriceFrom) ||
                !Validate::isUnsignedInt($PriceTo) || ($PriceFrom >= $PriceTo)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('PriceFrom', $PriceFrom);
                Configuration::updateValue('PriceTo', $PriceTo);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'html',
                    'label' => $this->l('Price from'),
                    'name' => 'PriceFrom',
                    'required' => true,
                    'html_content' => '<input type="number" min="1" name="PriceFrom" value="'.Configuration::get('PriceFrom').'">'
                ],
                [
                    'type' => 'html',
                    'label' => $this->l('Price to'),
                    'name' => 'PriceTo',
                    'required' => true,
                    'html_content' => '<input type="number" min="1" name="PriceTo"  value="'.Configuration::get('PriceTo').'">'
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value['MYMODULE_NAME'] = Configuration::get('MYMODULE_NAME');

        return $helper->generateForm($fieldsForm);
    }

    public function hookDisplayFooter()
    {
        $result = Db::getInstance()->executeS("SELECT count(price) as count_price FROM "._DB_PREFIX_."product WHERE price >=".(float)Configuration::get('PriceFrom')." and price <=".(float)Configuration::get('PriceTo'));

        $count = !empty($result) ? $result[0]['count_price'] : 0;
        $this->context->smarty->assign([
            'from' => Configuration::get('PriceFrom'),
            'to' => Configuration::get('PriceTo'),
            'count_price' => $count
        ]);

        return $this->display(__FILE__, 'footerhook.tpl');
    }

}