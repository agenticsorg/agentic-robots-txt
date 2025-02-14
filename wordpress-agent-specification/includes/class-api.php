<?php
if (!defined('ABSPATH')) {
    exit;
}

class Agentics_API {
    private $specs;
    private $namespace = 'agentics/v1';

    public function __construct($specs) {
        $this->specs = $specs;
    }

    public function register_routes() {
        // Get all specifications
        register_rest_route($this->namespace, '/specs', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_specs'),
                'permission_callback' => array($this, 'get_specs_permissions_check')
            )
        ));

        // Get single specification
        register_rest_route($this->namespace, '/specs/(?P<name>[a-zA-Z0-9-]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_spec'),
                'permission_callback' => array($this, 'get_specs_permissions_check'),
                'args' => array(
                    'name' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    )
                )
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_spec'),
                'permission_callback' => array($this, 'update_specs_permissions_check'),
                'args' => array(
                    'name' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'content' => array(
                        'required' => true,
                        'type' => 'string'
                    )
                )
            )
        ));

        // Get health status
        register_rest_route($this->namespace, '/health', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_health'),
                'permission_callback' => '__return_true'
            )
        ));

        // WebSocket connection info
        register_rest_route($this->namespace, '/websocket', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_websocket_info'),
                'permission_callback' => array($this, 'get_specs_permissions_check')
            )
        ));
    }

    public function get_specs($request) {
        $specs = array();
        foreach ($this->specs->get_available_specs() as $name) {
            $spec = $this->specs->get_spec($name);
            if ($spec) {
                $specs[$name] = array(
                    'content' => json_decode($spec['content']),
                    'content_type' => $spec['content_type']
                );
            }
        }
        return rest_ensure_response($specs);
    }

    public function get_spec($request) {
        $name = $request->get_param('name');
        $spec = $this->specs->get_spec($name);
        
        if (!$spec) {
            return new WP_Error(
                'spec_not_found',
                'Specification not found',
                array('status' => 404)
            );
        }

        return rest_ensure_response(array(
            'content' => json_decode($spec['content']),
            'content_type' => $spec['content_type']
        ));
    }

    public function update_spec($request) {
        $name = $request->get_param('name');
        $content = $request->get_param('content');

        // Validate JSON for JSON specs
        if ($this->specs->get_spec_content_type($name) === 'application/json') {
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error(
                    'invalid_json',
                    'Invalid JSON content',
                    array('status' => 400)
                );
            }
        }

        $updated = $this->specs->update_spec($name, $content);
        if (!$updated) {
            return new WP_Error(
                'update_failed',
                'Failed to update specification',
                array('status' => 500)
            );
        }

        return rest_ensure_response(array(
            'message' => 'Specification updated successfully'
        ));
    }

    public function get_health($request) {
        $health = array(
            'status' => 'healthy',
            'timestamp' => current_time('c'),
            'version' => AGENTICS_VERSION,
            'wordpress' => array(
                'version' => get_bloginfo('version'),
                'url' => get_site_url(),
                'name' => get_bloginfo('name')
            ),
            'specs' => array(
                'available' => count($this->specs->get_available_specs()),
                'last_updated' => get_option('agentics_specs_last_updated')
            )
        );

        return rest_ensure_response($health);
    }

    public function get_websocket_info($request) {
        return rest_ensure_response(array(
            'enabled' => apply_filters('agentics_websocket_enabled', false),
            'endpoint' => apply_filters('agentics_websocket_endpoint', null),
            'protocols' => apply_filters('agentics_websocket_protocols', array())
        ));
    }

    public function get_specs_permissions_check($request) {
        return true; // Public read access
    }

    public function update_specs_permissions_check($request) {
        return current_user_can('manage_options');
    }

    // Helper method to validate JSON content
    private function is_valid_json($content) {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }
}