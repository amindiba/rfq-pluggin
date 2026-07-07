<?php
/**
 * REST API - Registers the base REST namespace.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_REST_API
 *
 * Registers the REST API namespace and base endpoint.
 *
 * @since 1.0.0
 */
class B2B_Procurement_REST_API {

    /**
     * REST API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'b2b-procurement/v1';

    /**
     * Initialize REST API.
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    /**
     * Register REST routes.
     */
    public static function register_routes() {
        // Status
        register_rest_route(self::NAMESPACE, '/status', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_status'),
            'permission_callback' => '__return_true',
        ));

        // Categories
        $cat_ctrl = new B2B_Category_Controller();
        register_rest_route(self::NAMESPACE, '/categories', array(
            array('methods' => 'GET', 'callback' => array($cat_ctrl, 'index'), 'permission_callback' => '__return_true'),
            array('methods' => 'POST', 'callback' => array($cat_ctrl, 'store'), 'permission_callback' => array(__CLASS__, 'check_permission')),
        ));
        register_rest_route(self::NAMESPACE, '/categories/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array($cat_ctrl, 'show'), 'permission_callback' => '__return_true'),
            array('methods' => 'PUT', 'callback' => array($cat_ctrl, 'update'), 'permission_callback' => array(__CLASS__, 'check_permission')),
            array('methods' => 'DELETE', 'callback' => array($cat_ctrl, 'destroy'), 'permission_callback' => array(__CLASS__, 'check_permission')),
        ));

        // Products
        $prod_ctrl = new B2B_Product_Controller();
        register_rest_route(self::NAMESPACE, '/products', array(
            array('methods' => 'GET', 'callback' => array($prod_ctrl, 'index'), 'permission_callback' => '__return_true'),
            array('methods' => 'POST', 'callback' => array($prod_ctrl, 'store'), 'permission_callback' => array(__CLASS__, 'check_permission')),
        ));
        register_rest_route(self::NAMESPACE, '/products/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array($prod_ctrl, 'show'), 'permission_callback' => '__return_true'),
            array('methods' => 'PUT', 'callback' => array($prod_ctrl, 'update'), 'permission_callback' => array(__CLASS__, 'check_permission')),
            array('methods' => 'DELETE', 'callback' => array($prod_ctrl, 'destroy'), 'permission_callback' => array(__CLASS__, 'check_permission')),
        ));
        register_rest_route(self::NAMESPACE, '/products/(?P<id>\d+)/attributes', array(
            array('methods' => 'GET', 'callback' => array($prod_ctrl, 'get_attributes'), 'permission_callback' => '__return_true'),
            array('methods' => 'PUT', 'callback' => array($prod_ctrl, 'set_attributes'), 'permission_callback' => array(__CLASS__, 'check_permission')),
        ));

        // Attributes
        $attr_ctrl = new B2B_Attribute_Controller();
        register_rest_route(self::NAMESPACE, '/attributes', array(
            array('methods' => 'GET', 'callback' => array($attr_ctrl, 'index'), 'permission_callback' => '__return_true'),
            array('methods' => 'POST', 'callback' => array($attr_ctrl, 'store'), 'permission_callback' => array(__CLASS__, 'check_permission')),
        ));
        register_rest_route(self::NAMESPACE, '/attributes/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array($attr_ctrl, 'show'), 'permission_callback' => '__return_true'),
            array('methods' => 'PUT', 'callback' => array($attr_ctrl, 'update'), 'permission_callback' => array(__CLASS__, 'check_permission')),
            array('methods' => 'DELETE', 'callback' => array($attr_ctrl, 'destroy'), 'permission_callback' => array(__CLASS__, 'check_permission')),
        ));
    }

    public static function check_permission() {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Handle the status endpoint.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response Response object.
     */
    public static function get_status($request) {
        return new WP_REST_Response(array(
            'plugin'   => 'سیستم مدیریت خرید B2B',
            'version'  => B2B_PROCUREMENT_VERSION,
            'status'   => 'فعال',
            'timestamp' => current_time('c'),
        ), 200);
    }
}
