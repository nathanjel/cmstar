<?php

class cms_payu_transaction {
	public $pay_type;
	public $session_id;
	public $amount_gr;
	public $desc;
	public $order_id;
	public $desc2;
	public $trsDesc;
	public $first_name;
	public $last_name;
	public $street;
	public $street_hn;
	public $streen_an;
	public $city;
	public $post_code;
	public $country;
	public $email;
	public $phone;
	public $client_ip;

	public function setAmount($f) {
		$this->amount_gr = floor($f*100);
	}
}