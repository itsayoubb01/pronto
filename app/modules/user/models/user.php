<?php

class mUser extends Model
{
	var $default_sort = 'last_name ASC';
	var $enable_cache = true;

	function validate($data, $is_update)
	{
		$errors = array();
		$this->validator->required($errors, array('first_name','last_name'));
		$this->validator->validate($errors, 'email', VALID_EMAIL, __('Valid email address is required'));
		if(!$is_update && !isset($_SESSION['openid_identity'])) {
			$this->validator->required($errors, array('password'));
		}

		// check for a duplicate email address
		if($is_update) {
			if($this->db->get_item("SELECT * FROM users WHERE email='%s' AND id!=%i", array($data['email'],$data['id']))) {
				$errors['email'] = __('This email address is already in use. Please try another one.');
			}
		} else {
			if($this->db->get_item("SELECT * FROM users WHERE email='%s'", array($data['email']))) {
				$errors['email'] = __('This email address is already in use. Please try another one.');
			}
		}

		if($data['password']) {
			if(mb_strlen($data['password']) < 6) {
				$errors['password'] = __('Password must be at least 6 characters in length');
			}
			if($data['password'] != $data['password2']) {
				$errors['password2'] = __('Passwords do not match');
			}
		}
		return $errors;
	}

	function create() {
		return array(
			'access_keys' => 'User'
		);
	}

	function get_record($id) {
		$data = parent::get_record($id);
		if(!$data) return false;

		// do extra data massaging here...
		$data['name'] = $data['first_name'].' '.$data['last_name'];

		return $data;
	}

	function insert($data)
	{
		$data['created_on'] = date('Y-m-d');
		$data['last_login'] = '0000-00-00';

		if(isset($_SESSION['openid_identity'])) {
			// an empty password ensures that this user cannot login with
			// the normal email/password method.
			$data['password'] = '';
			$data['openid'] = $_SESSION['openid_identity'];
		}
		// encrypt password
		$data['password'] = sha1($data['password']);

		if(!a('ADMIN')) {
			$data['access_keys']   = 'User';
			$data['status']        = 'pending';
			$data['confirm_token'] = $this->generate_token();
			$data['confirm_sent']  = date('Y-m-d');
		}

		return parent::insert($data);
	}

	function update($data)
	{
		if(!a('ADMIN')) {
			unset($data['status'], $data['access_keys']);
			unset($data['created_on'], $data['last_login']);
			unset($data['confirm_token'], $data['confirm_sent']);
			unset($data['openid']);
		}

		$old = $this->get($data['id']);
		// encrypt password, if exists
		if(!$old['openid'] && $data['password']) {
			$data['password'] = sha1($data['password']);
		} else {
			unset($data['password']);
		}

		return parent::update($data);
	}


	function delete($id)
	{
		$this->db->execute("UPDATE {$this->table} SET status='deleted' WHERE id=%i", array($id));
		$this->invalidate($id);
	}

	function list_params()
	{
		return array(
			'from'       => $this->table,
			'exprs'      => array(),
			'select'     => '*',
			'where'      => array(),
			'where_args' => array(),
			'order'      => $this->default_sort,
			'limit'      => $this->per_page
		);
	}

	/**
	 * New user confirmation/activation routines
	 */
	function get_by_token($token)
	{
		$id = $this->db->get_value("SELECT id FROM {$this->table} WHERE confirm_token='%s' LIMIT 1", array($token));
		if($id) return $this->get($id);
		return false;
	}

	function generate_token($length=20)
	{
		$pool  = "abcdefghijklmnopqrstuvwxyz0123456789";
		$found = true;
		while($found) {
			$token = '';
			for($i = 0; $i < $length; $i++) {
				$token .= substr($pool, rand() % (strlen($pool)), 1);
			}
			$found = $this->db->get_value("SELECT id FROM {$this->table} WHERE confirm_token='%s'", array($token));
		}
		return $token;
	}

	function activate($id)
	{
		$this->db->execute("UPDATE {$this->table} SET status='active',confirm_token='' WHERE id=%i", array($id));
		$this->invalidate($id);
	}

	/**
	 * Authentication routines
	 */
	function authenticate($user)
	{
		if($user['status'] != 'active') return __('This account is not active');

		$access =& Registry::get('pronto:access');
		// clear out any old auth id/keys first
		$access->clear_authentication();

		$access->set_id($user['id']);
		$access->set_keys($user['access_keys']);

		$this->db->execute("UPDATE {$this->table} SET last_login='%s' WHERE id=%i", array(date('Y-m-d'),$user['id']));

		$_SESSION['USER'] = $this->get($user['id']);
		return true;
	}
	function authenticate_password($email, $password)
	{
		$user = $this->get_by('email', $email);
		if(!$user)                               return __('Invalid email/password');
		if(empty($password))                     return __('Invalid email/password');
		if($user['password'] != sha1($password)) return __('Invalid email/password');

		return $this->authenticate($user);
	}
	function authenticate_openid($openid_url)
	{
		$user = $this->get_by('openid', $openid_url);
		if(!$user) {
			// No user found, I guess we're creating a new one.
			return false;
		}

		return $this->authenticate($user);
	}
	function clear_authentication()
	{
		$access =& Registry::get('pronto:access');
		$access->clear_authentication();
		unset($_SESSION['USER']);
	}

	/**
	 * Password routines
	 */
	function set_password($id, $password)
	{
		$this->db->execute("UPDATE {$this->table} SET password=SHA1('%s') WHERE id=%i", array($password,$id));
		$this->invalidate($id);
	}

	/**
	 * A mnemonic password generator.
	 * Source:	http://www.alixaxel.com/wordpress/2007/06/14/php-friendly-password-generator/
	 */
	function generate_password($letters=6, $digits=2)
	{
		$charset = array (
			0 => array('b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','y','z'),
			1 => array('a','e','i','o','u')
		);

		$result = null;
		for($i = 0; $i < $letters; $i++) {
			$result .= $charset[$i % 2][array_rand($charset[$i % 2])];
		}

		$dirty_words = array('bix','bob','con','cum','fod','fuc','fud','fuk',
			'gal','gat','gay','mal','mam','mar','mec','pat','peg','per','pic',
			'pil','pit','put','rab','sex','tar','tes','tet','tol','vac','xup');
		foreach($dirty_words as $dirty_word) {
			if(strpos($result, $dirty_word) !== false) {
				return $this->generate_password($letters, $digits);
			}
		}

		if($digits > 0) {
			for($i = 0; $i < $digits; $i++) {
				$result .= mt_rand(0, 9);
			}
		} else if($digits < 0) {
			$digits = abs($digits);
			for($i = 0; $i < $digits; $i++) {
				$result = mt_rand(0, 9) . $result;
			}
		}

		return $result;
	}
}

?>