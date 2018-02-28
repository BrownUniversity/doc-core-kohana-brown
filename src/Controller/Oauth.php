<?php
namespace BrownUniversity\DOC\Controller ;
use BrownUniversity\DOC\View ;
use BrownUniversity\DOC\ORM ;
use BrownUniversity\DOC\Encrypt ;
use BrownUniversity\DOC\Util\Banner\Ords ;
use Kohana\Controller\Template;

/**
 * @package Kohana 3.x Modules
 * @author Sumner Warren <Sumner_Warren@brown.edu>
 */
defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Controller for providing OAuth management
 * @todo refactor as appropriate for use in a namespaced context, without hard-coded references to things like URLs.
 */
class Oauth extends Template {

	/**
	 * Main template file
	 * 
	 * @var string
	 */
	public $template = 'template';

	/**
	 * Logic to execute before this controller
	 */
	public function before() {
		parent::before();
		$this->template->view_fragment = View::factory('oauth/list');
		if ($this->session == NULL) {
			$this->session = \Session::instance('database');
		}
	}
	
	public function action_index() {
		$this->redirect('admin/oauth/list');
	}
	
	public function action_list() {
		$this->template->view_fragment->oauths = ORM::factory(\Kohana::$config->load('bannerintegration.ords.model'))->find_all();
	}
	
	public function action_authcode() {
		$id = $this->request->param('id');
		$config = \Kohana::$config->load('bannerintegration.ords');
		if ($id == NULL) {
			$this->session->set('errors', array('No OAuth id provided.'));
			$this->redirect('oauth/list');
		} else if ($id < 0) {
			$oauth = ORM::factory($config['model']);
		} else {
			$oauth = ORM::factory($config['model'], $id);
			if (!$oauth->loaded()) {
				$this->session->set('errors', array("Could not locate OAuth settings with id: $id."));
				$this->redirect('admin/oauth/list');
			}
		}
		
		$state = Encrypt::instance()->encode_url_safe($id);
		$this->session->set('oauth_state', $state);
		$base_url = $config['base-url'] . 'oauth2/auth?';
		$this->redirect($base_url . "response_type=code&client_id={$config['client-id']}&client_secret={$config['client-secret']}&state=$state&_auth_=force");
	}
	
	public function action_redirect() {
		$state = $this->session->get_once('oauth_state');
		if ($state !== $this->request->query('state')) {
			$this->session->set('errors', array('Error obtaining auth code: returned state parameter did not match original value.'));
			$this->redirect('admin/oauth/list');
		}
		
		$config = \Kohana::$config->load('bannerintegration.ords');
		$id = Encrypt::instance()->decode_url_safe($state);
		if ($id == NULL) {
			$this->session->set('errors', array('No OAuth id provided.'));
			$this->redirect('admin/oauth/list');
		} else if ($id < 0) {
			$oauth = ORM::factory($config['model']);
		} else {
			$oauth = ORM::factory($config['model'], $id);
			if (!$oauth->loaded()) {
				$this->session->set('errors', array("Could not locate OAuth settings with id: $id."));
				$this->redirect('admin/oauth/list');
			}
		}
		
		\Database::instance()->begin();
		$auth_code = $this->request->query('code');
		try {
			if ($oauth->loaded()) {
				$oauth->delete();
			}
			Ords::instance($config['base-url'], $config['client-id'], $config['client-secret'], $config['model'], $auth_code);
			$this->session->set('messages', array('Auth code successfully obtained.'));
			\Database::instance()->commit();
		} catch (ErrorException $e) {
			\Database::instance()->rollback();
			$this->session->set('errors', array('Error obtaining auth code: ' . $e->getMessage()));
		}
		$this->redirect('admin/oauth/list');
	}
	
	public function action_refresh() {
		$id = $this->request->param('id');
		$config = Kohana::$config->load('bannerintegration.ords');
		if ($id == NULL || $id < 0) {
			$this->session->set('errors', array('No OAuth id provided.'));
			$this->redirect('admin/oauth/list');
		} else {
			$oauth = ORM::factory($config['model'], $id);
			if (!$oauth->loaded()) {
				$this->session->set('errors', array("Could not locate OAuth settings with id: $id."));
				$this->redirect('admin/oauth/list');
			}
		}
		
		try {
			Ords::instance($config['base-url'], $oauth->client_id, $oauth->client_secret, $config['model']);
			$this->session->set('messages', array('Access code successfully refreshed.'));
		} catch (ErrorException $e) {
			$this->session->set('errors', array('Unable to refresh access token. You probably need to obtain a new auth code.'));
		}
		$this->redirect('admin/oauth/list');
	}

} // End OAuth Controller