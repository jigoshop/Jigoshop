<?php
// Load the plugin
require_once('jigoshop.php');
require_once('admin/jigoshop-install.php');

class WP_Test_Jigoshop extends WP_UnitTestCase
{
	public function setUp() {

		parent::setUp();
		install_jigoshop();
	}

	public function test_enqueue() {
		unset($GLOBALS['wp_scripts']); // Ensure scripts are fresh

		$this->go_to( get_permalink(3) ); // On a jigoshop page
		$this->assertFalse( wp_script_is( 'jigoshop_script' ) );
    do_action( 'template_redirect' );
    $this->assertTrue( wp_script_is( 'jigoshop_script' ) );
	}

	public function test_enqueue_off() {
		unset($GLOBALS['wp_scripts']); // Ensure scripts are fresh

		$this->go_to( get_permalink(2) ); // Not a jigo page so no script
		$this->assertFalse( wp_script_is( 'jigoshop_script' ) );
    do_action( 'template_redirect' );
    // @rob: After redirect, we have script now
	$this->assertTrue( wp_script_is( 'jigoshop_script' ) );
	}

	public function test_image_sizes() {

		jigoshop_set_image_sizes();

		$sizes = get_intermediate_image_sizes();

		$this->assertContains('shop_tiny', $sizes);
		$this->assertContains('shop_thumbnail', $sizes);
		$this->assertContains('shop_small', $sizes);
		$this->assertContains('shop_large', $sizes);
	}

	public function test_page_id() {
		$id = jigoshop_get_page_id('cart');
		$this->assertLessThan($id, 0);

		$id = jigoshop_get_page_id('fake');
		$this->assertGreaterThan($id, 0);
	}

	/**
	 * Test returns the correct plugin Url
	 *
	 * Pre-conditions:
	 * Case A: Get the Plugin Url for a HTTP connection
	 * Case A: Get the Plugin Url for a HTTPS connection
	 *
	 * Post-conditions:
	 * Case A: Returned url should be unchanged and contain jigoshop root folder
	 * Case B: Returned url should be rewritten with https:// and contain jigoshop root folder
	 */
	public function test_plugin_url()
	{
		// Case A:
		$this->assertContains('http://', jigoshop::plugin_url());

		// Case B:
		$_SERVER['HTTPS'] = TRUE;
		jigoshop::$plugin_url = NULL;
		$this->assertContains('https://', jigoshop::plugin_url());
	}

	/**
	 * Test Plugin Path
	 *
	 * Pre-conditions:
	 * Plugin root defined to plugin root
	 *
	 * Post-conditions:
	 * Ensure plugin_path() matches $plugin_root
	 */
	public function test_plugin_path()
	{
		$plugin_root = dirname(dirname(dirname(__FILE__)));
		$this->assertEquals( $plugin_root, jigoshop::plugin_path() );
	}

	/**
	 * Test Get Var
	 *
	 * Pre-conditions:
	 * Send all available vars to get_var()
	 *
	 * Post-conditions:
	 * All vars should return a value
	 */
	public function test_get_var()
	{
		$vars = array(
			'shop_small_w'     => '150',
			'shop_small_h'     => '150',
			'shop_tiny_w'      => '36',
			'shop_tiny_h'      => '36',
			'shop_thumbnail_w' => '90',
			'shop_thumbnail_h' => '90',
			'shop_large_w'     => '300',
			'shop_large_h'     => '300',
		);

		foreach ( $vars as $key => $value ) {
			$this->assertEquals( $value, jigoshop::get_var( $key ) );
		}
	}

	/**
	 * Test Forcing SSL on assets
	 *
	 * Pre-conditions:
	 * Case A: Page delivered through HTTPS connection
	 * Case B: Page delivered through HTTP connection
	 *
	 * Post-conditions:
	 * Case A: Returned url should be rewritten with https://...
	 * Case B: Returned url should be unchanged
	 */
	public function test_force_ssl()
	{
		$unsecure_url = 'http://google.com';

		// Case A:
		$_SERVER['HTTPS'] = TRUE;
		$this->assertEquals( 'https://google.com', jigoshop::force_ssl($unsecure_url) );

		// Case B:
		$_SERVER['HTTPS'] = FALSE;
		$this->assertEquals( 'http://google.com', jigoshop::force_ssl($unsecure_url) );
	}



	/**
	 * Test add error
	 *
	 * Pre-conditions:
	 * Add an error to the $errors array
	 *
	 * Post-conditions:
	 * Error should be contained in the array & count should be 1
	 */
	public function test_add_error()
	{
		$this->assertFalse(jigoshop::has_errors());

		// perform the change
		jigoshop::add_error('Hello World');

		$this->assertContains('Hello World', jigoshop::$errors);
		$this->assertTrue(jigoshop::has_errors());

	}

	/**
	 * Test add message
	 *
	 * Pre-conditions:
	 * Add an message to the $messages array
	 *
	 * Post-conditions:
	 * Message should be contained in the array & count should be 1
	 */
	public function test_add_message()
	{
		$this->assertFalse(jigoshop::has_messages());

		jigoshop::add_message('Hello World');

		$this->assertContains('Hello World', jigoshop::$messages);
		$this->assertTrue(jigoshop::has_messages());

	}

	/**
	 * Test Message & Error Clearing
	 *
	 * Pre-conditions:
	 * Set an error & message to populate the class
	 *
	 * Post-conditions:
	 * Both $errors & $messages should return empty
	 */
	public function test_clear_messages()
	{
		jigoshop::add_error('Hello World');

		jigoshop::clear_messages();

		$this->assertEmpty(jigoshop::$errors, '$errors is not empty');
	}

	/**
	 * Test Show Messages
	 *
	 * Pre-conditions:
	 * Case A: Set error 'Hello World' and ensure it is output
	 * Case B: Set message 'Foo Bar' and ensure it is output
	 *
	 * Post-conditions:
	 * Case A: Ouput contains div with class of jigoshop_error
	 * Case B: Ouput contains div with class of jigoshop_message
	 */
	public function test_show_messages()
	{
		// Case A:
		jigoshop::add_error( 'Hello World' );

		ob_start();
		jigoshop::show_messages();
		$this->assertEquals('<div class="jigoshop_error">Hello World</div>', ob_get_clean());

		// Case B:
		jigoshop::add_message( 'Foo Bar' );

		ob_start();
		jigoshop::show_messages();
		$this->assertEquals('<div class="jigoshop_message">Foo Bar</div>', ob_get_clean());
	}

	/**
	 * Test Nonce field creation
	 *
	 * Pre-conditions:
	 * Sets up a nonce field
	 *
	 * Post-conditions:
	 * Returns a hidden input element with nonce hash for a value
	 */
	public function test_nonce_field()
	{
		ob_start();
		jigoshop::nonce_field('nonce_me');
		$this->assertContains('input', ob_get_clean());
	}

	/**
	 * Test Nonce Url
	 *
	 * Pre-conditions:
	 * Adds a nonce query arguement to a url
	 *
	 * Post-conditions:
	 * Returns query argument with nonce hash
	 */
	public function test_nonce_url()
	{
		$this->assertContains('?_n=', jigoshop::nonce_url('nonce_me'));
	}

	/**
	 * Test Verification of nonces
	 *
	 * Pre-conditions:
	 * Case A: Send nonce ticket via GET
	 * Case B: Send nonce ticket via POST
	 *
	 * Post-conditions:
	 * Verification should return true in both instances
	 */
	public function test_verify_nonce()
	{
		// Case A:
		$action = 'nonce_get';
		$nonce_hash = wp_create_nonce( 'jigoshop-'.$action );
		$_GET['_n'] = $nonce_hash;

		$this->assertTrue(jigoshop::verify_nonce($action));

		// Case B:
		$action = 'nonce_post';
		$nonce_hash = wp_create_nonce( 'jigoshop-'.$action );
		$_POST['_n'] = $nonce_hash;

		$this->assertTrue(jigoshop::verify_nonce($action));
	}
}