<?php
if (!defined('ABSPATH')) {
    exit;
}

class Agentics_Router {
    private $specs;

    public function __construct($specs) {
        $this->specs = $specs;
    }

    public function handle_request() {
        $spec_name = get_query_var('agentics_spec');
        
        if (!$spec_name) {
            return;
        }

        // Remove file extension if present
        $spec_name = preg_replace('/\.(json|md)$/', '', $spec_name);

        // Get specification content
        $spec = $this->specs->get_spec($spec_name);
        if (!$spec) {
            return;
        }

        // Set headers
        $this->set_headers($spec_name, $spec['content_type']);

        // Output content
        echo $spec['content'];
        exit;
    }

    private function set_headers($spec_name, $content_type) {
        // Set content type
        header('Content-Type: ' . $content_type . '; charset=UTF-8');

        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Set caching headers
        $cache_time = $this->get_cache_time($spec_name);
        header('Cache-Control: public, max-age=' . $cache_time);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');

        // Set security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // Enable compression if available
        if (extension_loaded('zlib')) {
            ini_set('zlib.output_compression', 'On');
        }
    }

    private function get_cache_time($spec_name) {
        // Different cache times for different specs
        $cache_times = array(
            'health' => 60,              // 1 minute
            'version-control' => 300,     // 5 minutes
            'peers' => 300,              // 5 minutes
            'manifest' => 3600,          // 1 hour
            'guidance' => 3600,          // 1 hour
            'openapi' => 3600,           // 1 hour
            'asyncapi' => 3600,          // 1 hour
            'models' => 3600,            // 1 hour
            'agent-guide' => 3600        // 1 hour
        );

        return isset($cache_times[$spec_name]) ? $cache_times[$spec_name] : 3600;
    }

    public function add_rewrite_rules() {
        // Add rewrite rules for each specification
        foreach ($this->specs->get_available_specs() as $spec_name) {
            $file = $this->specs->get_spec_file($spec_name);
            add_rewrite_rule(
                '^\.well-known/' . $file . '/?$',
                'index.php?agentics_spec=' . $spec_name,
                'top'
            );
        }
    }

    public function add_query_vars($vars) {
        $vars[] = 'agentics_spec';
        return $vars;
    }

    public function handle_options_request() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->set_headers('', 'text/plain');
            exit;
        }
    }

    public function check_well_known_directory() {
        $upload_dir = wp_upload_dir();
        $well_known_dir = $upload_dir['basedir'] . '/.well-known';
        
        if (!file_exists($well_known_dir)) {
            wp_mkdir_p($well_known_dir);
        }

        // Create .htaccess to ensure proper handling
        $htaccess_file = $well_known_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^(.*)$ /index.php?agentics_spec=$1 [QSA,L]
</IfModule>

<IfModule mod_headers.c>
Header set Access-Control-Allow-Origin \"*\"
Header set Access-Control-Allow-Methods \"GET, OPTIONS\"
Header set Access-Control-Allow-Headers \"Content-Type\"
</IfModule>
";
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
}