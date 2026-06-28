<?php
/**
 * VGRB ERP Sync — Catalog Controller
 * Обробник події: після створення замовлення надсилає дані до VGRB ERP
 *
 * @author  Den Neuroman
 * @version 1.0.0
 * @link    https://github.com/DenNeuroman/opencart-mods
 */
class ControllerExtensionModuleVgrbErp extends Controller {

    public function eventOrderAdded(&$route, &$args, &$output) {
        // Перевіряємо чи модуль увімкнений
        if (!$this->config->get('module_vgrb_erp_status')) {
            return;
        }

        $order_id = $output; // addOrder повертає order_id
        if (!$order_id) {
            return;
        }

        try {
            $this->sendOrderToErp($order_id);
        } catch (Exception $e) {
            $this->log->write('VGRB ERP sync error: ' . $e->getMessage());
        }
    }

    private function sendOrderToErp($order_id) {
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            $this->log->write('VGRB ERP: order_info not found for order #' . $order_id);
            return;
        }

        $order_products = $this->model_checkout_order->getOrderProducts($order_id);

        $products = [];
        foreach ($order_products as $product) {
            $products[] = [
                'name'     => $product['name'],
                'model'    => $product['model'],
                'price'    => (float)$product['price'],
                'quantity' => (int)$product['quantity'],
            ];
        }

        $payload = [
            'order_id'  => (string)$order_id,
            'firstname' => $order_info['firstname'],
            'lastname'  => $order_info['lastname'],
            'telephone' => $order_info['telephone'],
            'email'     => $order_info['email'],
            'total'     => (float)$order_info['total'],
            'products'  => $products,
        ];

        $endpoint = $this->config->get('module_vgrb_erp_endpoint');
        $timeout  = (int)$this->config->get('module_vgrb_erp_timeout') ?: 10;

        if (!$endpoint) {
            $this->log->write('VGRB ERP: endpoint not configured, skipping order #' . $order_id);
            return;
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST,            true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,      json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER,      ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
        curl_setopt($ch, CURLOPT_TIMEOUT,         $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  5);

        $response   = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->log->write('VGRB ERP curl error for order #' . $order_id . ': ' . $curl_error);
            return;
        }

        if ($http_code === 200) {
            $this->log->write('VGRB ERP sync OK — order #' . $order_id . ' | response: ' . $response);
        } else {
            $this->log->write('VGRB ERP HTTP ' . $http_code . ' for order #' . $order_id . ' | response: ' . $response);
        }
    }
}
