<?php
/**
 * Plugin Name: Agentics Agent Specification
 * Plugin URI: https://agentics.org
 * Description: Implements the Agentics Web Application - Autonomous Agent Guidance System for WordPress websites
 * Version: 1.0.0
 * Author: Agentics
 * Author URI: https://agentics.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: agentics-agent-spec
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AGENTICS_VERSION', '1.0.0');
define('AGENTICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AGENTICS_PLUGIN_URL', plugin_dir_url(__FILE__));

class Agentics_Agent_Spec {
    private static $instance = null;
    private $admin;
    private $specs;
    private $router;
    private $api;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // Core classes
        require_once AGENTICS_PLUGIN_DIR . 'includes/class-admin.php';
        require_once AGENTICS_PLUGIN_DIR . 'includes/class-specs.php';
        require_once AGENTICS_PLUGIN_DIR . 'includes/class-router.php';
        require_once AGENTICS_PLUGIN_DIR . 'includes/class-api.php';

        // Initialize components
        $this->specs = new Agentics_Specs();
        $this->router = new Agentics_Router($this->specs);
        $this->api = new Agentics_API($this->specs);
        $this->admin = new Agentics_Admin($this->specs);
    }

    private function init_hooks() {
        // Activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize components
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this->api, 'register_routes'));

        // Filter robots.txt
        add_filter('robots_txt', array($this, 'modify_robots_txt'), 10, 2);
    }

    public function activate() {
        // Create necessary directories
        $upload_dir = wp_upload_dir();
        $well_known_dir = $upload_dir['basedir'] . '/.well-known';
        
        if (!file_exists($well_known_dir)) {
            wp_mkdir_p($well_known_dir);
        }

        // Initialize specifications
        $this->specs->init_specs();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        // Add rewrite rules for .well-known directory
        add_rewrite_rule(
            '^\.well-known/([^/]+)/?$',
            'index.php?agentics_spec=$matches[1]',
            'top'
        );

        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'agentics_spec';
            return $vars;
        });

        // Handle requests
        add_action('template_redirect', array($this->router, 'handle_request'));
    }

    public function modify_robots_txt($output, $public) {
        if (!$public) {
            return $output;
        }

        $specs = $this->specs->get_robots_txt_content();
        return $specs . "\n\n" . $output;
    }

    public static function plugin_url() {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    public static function template_path() {
        return apply_filters('agentics_template_path', 'agentics/');
    }

    public function get_specs() {
        return $this->specs;
    }

    public function get_router() {
        return $this->router;
    }

    public function get_api() {
        return $this->api;
    }
}

function Agentics() {
    return Agentics_Agent_Spec::get_instance();
}

// Initialize the plugin
Agentics();