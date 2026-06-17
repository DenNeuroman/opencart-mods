<?php
class ControllerInformationContact extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('information/contact');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			
			// Письмо менеджеру
			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            // Письмо на основной ящик
            $mail->setTo($this->config->get('config_email'));
            $mail->setFrom($this->config->get('config_mail_smtp_username'));
            $mail->setReplyTo($this->request->post['email']);
            $mail->setSender(html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));
            $mail->setSubject('[ASSECURO.COM.UA] Нове звернення від ' . html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));
            $mail->setText(
                '========================================' . "\n" .
                '  НОВЕ ЗВЕРНЕННЯ З САЙТУ ASSECURO.COM.UA' . "\n" .
                '========================================' . "\n\n" .
                'Ім\'я:     ' . html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8') . "\n" .
                'E-Mail:   ' . $this->request->post['email'] . "\n" .
                'Сторінка: https://assecuro.com.ua/kontakti' . "\n\n" .
                'Повідомлення:' . "\n" .
                '------------------------------------------' . "\n" .
                $this->request->post['enquiry'] . "\n" .
                '------------------------------------------' . "\n\n" .
                'Відповісти клієнту: натисніть "Відповісти" — лист піде на ' . $this->request->post['email']
            );
            $mail->send();
            
        // Письма на дополнительные адреса
        $alert_emails_raw = $this->config->get('config_mail_alert_email');
        if (!empty($alert_emails_raw)) {
            // OcStore может вернуть строку или массив
            if (is_array($alert_emails_raw)) {
                $alert_emails = $alert_emails_raw;
            } else {
                $alert_emails = array_map('trim', explode(',', $alert_emails_raw));
            }
            
            foreach ($alert_emails as $alert_email) {
                if (filter_var(trim($alert_email), FILTER_VALIDATE_EMAIL)) {
                    $mail_alert = new Mail($this->config->get('config_mail_engine'));
                    $mail_alert->parameter = $this->config->get('config_mail_parameter');
                    $mail_alert->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                    $mail_alert->smtp_username = $this->config->get('config_mail_smtp_username');
                    $mail_alert->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                    $mail_alert->smtp_port = $this->config->get('config_mail_smtp_port');
                    $mail_alert->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        
                    $mail_alert->setTo(trim($alert_email));
                    $mail_alert->setFrom($this->config->get('config_mail_smtp_username'));
                    $mail_alert->setReplyTo($this->request->post['email']);
                    $mail_alert->setSender(html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));
                    $mail_alert->setSubject('[ASSECURO.COM.UA] Нове звернення від ' . html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));
                    $mail_alert->setText(
                        '========================================' . "\n" .
                        '  НОВЕ ЗВЕРНЕННЯ З САЙТУ ASSECURO.COM.UA' . "\n" .
                        '========================================' . "\n\n" .
                        'Ім\'я:     ' . html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8') . "\n" .
                        'E-Mail:   ' . $this->request->post['email'] . "\n" .
                        'Сторінка: https://assecuro.com.ua/kontakti' . "\n\n" .
                        'Повідомлення:' . "\n" .
                        '------------------------------------------' . "\n" .
                        $this->request->post['enquiry'] . "\n" .
                        '------------------------------------------' . "\n\n" .
                        'Відповісти клієнту: натисніть "Відповісти" — лист піде на ' . $this->request->post['email']
                    );
                    $mail_alert->send();
                }
            }
        }

			// Автоответ посетителю
			$mail_reply = new Mail($this->config->get('config_mail_engine'));
			$mail_reply->parameter = $this->config->get('config_mail_parameter');
			$mail_reply->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail_reply->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail_reply->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail_reply->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail_reply->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail_reply->setTo($this->request->post['email']);
			$mail_reply->setFrom($this->config->get('config_mail_smtp_username'));
			$mail_reply->setReplyTo($this->config->get('config_email'));
			$mail_reply->setSender($this->config->get('config_name'));
			$mail_reply->setSubject('Ваше повідомлення отримано — ASSECURO');
			$mail_reply->setText(
				'Дякуємо, ' . html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8') . '!' . "\n\n" .
				'Ми отримали ваше повідомлення і зв\'яжемося з вами найближчим часом.' . "\n\n" .
				'Ваше повідомлення:' . "\n" .
				'--------------------------------------------------' . "\n" .
				$this->request->post['enquiry'] . "\n" .
				'--------------------------------------------------' . "\n\n" .
				'З повагою,' . "\n" .
				'Команда ASSECURO' . "\n" .
				'https://assecuro.com.ua' . "\n" .
				'Тел: +38 (073) 102-63-50'
			);
			$mail_reply->send();

			$this->response->redirect($this->url->link('information/contact/success'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['enquiry'])) {
			$data['error_enquiry'] = $this->error['enquiry'];
		} else {
			$data['error_enquiry'] = '';
		}

		$data['button_submit'] = $this->language->get('button_submit');

		$data['action'] = $this->url->link('information/contact', '', true);

		$this->load->model('tool/image');

		if ($this->config->get('config_image')) {
			$data['image'] = $this->model_tool_image->resize($this->config->get('config_image'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
		} else {
			$data['image'] = false;
		}

		$data['store'] = $this->config->get('config_name');
		$data['address'] = nl2br($this->config->get('config_address'));
		$data['geocode'] = $this->config->get('config_geocode');
		$data['geocode_hl'] = $this->config->get('config_language');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['fax'] = $this->config->get('config_fax');
		$data['open'] = nl2br($this->config->get('config_open'));
		$data['comment'] = $this->config->get('config_comment');

		$data['locations'] = array();

		$this->load->model('localisation/location');

		foreach((array)$this->config->get('config_location') as $location_id) {
			$location_info = $this->model_localisation_location->getLocation($location_id);

			if ($location_info) {
				if ($location_info['image']) {
					$image = $this->model_tool_image->resize($location_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
				} else {
					$image = false;
				}

				$data['locations'][] = array(
					'location_id' => $location_info['location_id'],
					'name'        => $location_info['name'],
					'address'     => nl2br($location_info['address']),
					'geocode'     => $location_info['geocode'],
					'telephone'   => $location_info['telephone'],
					'fax'         => $location_info['fax'],
					'image'       => $image,
					'open'        => nl2br($location_info['open']),
					'comment'     => $location_info['comment']
				);
			}
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} else {
			$data['name'] = $this->customer->getFirstName();
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}

		if (isset($this->request->post['enquiry'])) {
			$data['enquiry'] = $this->request->post['enquiry'];
		} else {
			$data['enquiry'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/contact', $data));
	}

	protected function validate() {
		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen($this->request->post['enquiry']) < 10) || (utf8_strlen($this->request->post['enquiry']) > 3000)) {
			$this->error['enquiry'] = $this->language->get('error_enquiry');
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		return !$this->error;
	}

	public function success() {
		$this->load->language('information/contact');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

		$data['text_message'] = $this->language->get('text_message');

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}