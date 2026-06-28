<?php
/**
 * VGRB ERP Sync — Admin Controller
 * Реєстрація події OpenCart + сторінка налаштувань модуля
 *
 * @author  Den Neuroman
 * @version 1.0.0
 * @link    https://github.com/DenNeuroman/opencart-mods
 */
class ControllerExtensionModuleVgrbErp extends Controller {

    private $error = [];

    public function index() {
        $this->load->language('extension/module/vgrb_erp');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_vgrb_erp', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            ));
        }

        // Повідомлення
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        // Хлібні крихти
        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/vgrb_erp', 'user_token=' . $this->session->data['user_token'], true),
            ],
        ];

        $data['action'] = $this->url->link('extension/module/vgrb_erp', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        // Мовні рядки
        $data['heading_title']  = $this->language->get('heading_title');
        $data['text_enabled']   = $this->language->get('text_enabled');
        $data['text_disabled']  = $this->language->get('text_disabled');
        $data['text_info']      = $this->language->get('text_info');
        $data['entry_status']   = $this->language->get('entry_status');
        $data['entry_endpoint'] = $this->language->get('entry_endpoint');
        $data['entry_timeout']  = $this->language->get('entry_timeout');
        $data['button_save']    = $this->language->get('button_save');
        $data['button_cancel']  = $this->language->get('button_cancel');

        // Поточні значення налаштувань (або дефолти)
        $data['module_vgrb_erp_status']   = $this->config->get('module_vgrb_erp_status');
        $data['module_vgrb_erp_endpoint'] = $this->config->get('module_vgrb_erp_endpoint')
            ?: 'http://93.190.42.172/api/v1/orders';
        $data['module_vgrb_erp_timeout']  = $this->config->get('module_vgrb_erp_timeout') ?: 10;

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/vgrb_erp', $data));
    }

    /**
     * Викликається при натисканні "Встановити" у списку модулів.
     * Реєструє подію OpenCart.
     */
    public function install() {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent(
            'vgrb_erp',
            'catalog/model/checkout/order/addOrder/after',
            'extension/module/vgrb_erp/eventOrderAdded'
        );
    }

    /**
     * Викликається при натисканні "Видалити".
     * Знімає реєстрацію події.
     */
    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('vgrb_erp');
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/vgrb_erp')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
