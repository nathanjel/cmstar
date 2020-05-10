<?php



class cms_payu_pos {

	private $posid;

	private $posauthkey;

	private $key1;

	private $key2;

	private $lang;

	

	private $url_form;

	private $url_get;

	private $url_accept;

	private $url_cancel;



	public function verifyInbound($pos_id, $sess_id, $ts, $sig) {

		$t1 = $pos_id == $this->posid;

		$t2 = md5($pos_id . $sess_id . $ts . $this->key2) == $sig;

		return ($t1 && $t2);

	}



	public function sigCall($sess_id, $ts) {

		return md5($this->posid . $sess_id . $ts . $this->key1);

	}



	public function readTransactionState($sess_id) {

		$ts = $this->makeTS();

		$params = array( 	'pos_id' => $this->posid,

					'session_id' => $sess_id,

					'ts' => $ts,

					'sig' => $this->sigCall($sess_id, $ts));

		$param = http_build_query($params);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->url_get);

		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec ($ch);

		curl_close ($ch);

		$lines = explode("\n", $server_output);

		$res = array();

		foreach($lines as $line) {

			$p = strpos($line, ':');

			$n = substr($line, 0, $p);

			$v = trim(substr($line, $p+1));

			$res[$n] = $v;

		}

		if ($res['status'] == 'OK') {

			// sig check

			$msig = md5( $this->posid .

					$res['trans_session_id'] .

					$res['trans_order_id'] .

					$res['trans_status'] .

					$res['trans_amount'] .

					$res['trans_desc'] .

					$res['trans_ts'] .

					$this->key2);

			if ($res['trans_sig'] == $msig) {

				return $res;

			}

		}

		return false;

	}



	public function __construct($pid, $pak, $k1, $k2, $uf, $ug, $ua, $uc, $lang = 'pl') {

		$this->posid = $pid;

		$this->posauthkey = $pak;

		$this->key1 = $k1;

		$this->key2 = $k2;

		$this->lang = $lang; 

		$this->url_form = $uf;

		$this->url_get = $ug;

		$this->url_accept = $ua;

		$this->url_cancel = $uc;

	}



	public function makeTS() {

		$ret = mt_rand(1000,9999) . time();

		return $ret;

	}



	public function outputFormHead($addp='') {

		return '<form '.$addp.' method="POST" action="'.$this->url_form.'">';

	}



	public function outputFormData($t, $ts, $js, $xhtml = false) {

		$a1 = array(  'pos_id' => $this->posid,

				'pos_auth_key' => $this->posauthkey,

				'pay_type' => $t->pay_type,

				'session_id' => $t->session_id,

				'amount' => $t->amount_gr,

				'desc' => $t->desc,

				'order_id' => $t->order_id,

				'desc2' => $t->desc2,

				'trsDesc' => $t->trsDesc,

				'first_name' => $t->first_name,

				'last_name' => $t->last_name,

				'street' => $t->street,

				'street_hn' => $t->street_hn,

				'street_an' => $t->street_an,

				'city' => $t->city,

				'post_code' => $t->post_code,

				'country' => $t->country,

				'email' => $t->email,

				'phone' => $t->phone,

				'language' => $this->lang,

				'client_ip' => $t->client_ip,

				'js' => ($js?1:0),

				'ts' => $ts,

				'sig' => $this->sigTransaction($t, $ts) 

			);

		$o = '';

		foreach($a1 as $k=>$v) {

			if (strlen($v)>0)

				$o .= '<input type="hidden" name="'.

					$k.'" value ="'.

					htmlentities($v, ENT_COMPAT | ($xhtml ? ENT_XHTML : ENT_HTML5), 'UTF-8').

					'" '. ($xhtml ? '/>' : '>') . "\n";

		}

		return $o;

	}



	public function sigTransaction($t, $ts) {

		/*sig = md5 ( pos_id + pay_type + session_id + pos_auth_key + amount + desc + 

			desc2 + trsDesc + order_id + first_name + last_name + payback_login + 

			street + street_hn + street_an + city + post_code + country + email + 

			phone + language + client_ip + ts + key1 ) */

		$mid = $this->posid . $t->pay_type . $t->session_id . $this->posauthkey . $t->amount_gr . $t->desc .

			$t->desc2 . $t->trsDesc . $t->order_id . $t->first_name . $t->last_name . '' /* payback login */ .

			$t->street . $t->street_hn . $t->street_an . $t->city . $t->post_code . $t->country . $t->email .

			$t->phone . $this->lang . $t->client_ip . $ts . $this->key1;

		return md5($mid);

	}

	

}

