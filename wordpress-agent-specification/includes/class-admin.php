<?php
if (!defined('ABSPATH')) {
    exit;
}

class Agentics_Admin {
    private $specs;
    private $page_hook;

    public function __construct($specs) {
        $this->specs = $specs;
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu() {
        $this->page_hook = add_menu_page(
            'Agentics Agent Specification',
            'Agentics',
            'manage_options',
            'agentics-agent-spec',
            array($this, 'render_admin_page'),
            'dashicons-rest-api',
            30
        );

        add_submenu_page(
            'agentics-agent-spec',
            'Specifications',
            'Specifications',
            'manage_options',
            'agentics-agent-spec',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'agentics-agent-spec',
            'Settings',
            'Settings',
            'manage_options',
            'agentics-agent-spec-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('agentics_options', 'agentics_deployment_role');
        register_setting('agentics_options', 'agentics_federation_enabled');
        register_setting('agentics_options', 'agentics_websocket_enabled');
        
        add_settings_section(
            'agentics_general_settings',
            'General Settings',
            array($this, 'render_general_settings_section'),
            'agentics-agent-spec-settings'
        );

        add_settings_field(
            'agentics_deployment_role',
            'Deployment Role',
            array($this, 'render_deployment_role_field'),
            'agentics-agent-spec-settings',
            'agentics_general_settings'
        );

        add_settings_field(
            'agentics_federation_enabled',
            'Enable Federation',
            array($this, 'render_federation_field'),
            'agentics-agent-spec-settings',
            'agentics_general_settings'
        );

        add_settings_field(
            'agentics_websocket_enabled',
            'Enable WebSocket',
            array($this, 'render_websocket_field'),
            'agentics-agent-spec-settings',
            'agentics_general_settings'
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_enqueue_style(
            'agentics-admin',
            AGENTICS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AGENTICS_VERSION
        );

        wp_enqueue_script(
            'agentics-admin',
            AGENTICS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api'),
            AGENTICS_VERSION,
            true
        );

        wp_localize_script('agentics-admin', 'agenticsAdmin', array(
            'restUrl' => get_rest_url(null, 'agentics/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'specs' => $this->specs->get_available_specs()
        ));
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="agentics-admin-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#specifications" class="nav-tab nav-tab-active">Specifications</a>
                    <a href="#preview" class="nav-tab">Preview</a>
                    <a href="#status" class="nav-tab">Status</a>
                </nav>

                <div class="tab-content">
                    <div id="specifications" class="tab-pane active">
                        <?php $this->render_specifications_tab(); ?>
                    </div>
                    <div id="preview" class="tab-pane">
                        <?php $this->render_preview_tab(); ?>
                    </div>
                    <div id="status" class="tab-pane">
                        <?php $this->render_status_tab(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_specifications_tab() {
        $specs = $this->specs->get_available_specs();
        ?>
        <div class="agentics-specs-list">
            <?php foreach ($specs as $name): ?>
                <div class="spec-item" data-spec="<?php echo esc_attr($name); ?>">
                    <h3><?php echo esc_html(ucfirst(str_replace('-', ' ', $name))); ?></h3>
                    <div class="spec-content">
                        <textarea class="spec-editor" data-spec="<?php echo esc_attr($name); ?>"
                            rows="10"><?php echo esc_textarea($this->specs->get_spec($name)['content']); ?></textarea>
                    </div>
                    <div class="spec-actions">
                        <button class="button save-spec" data-spec="<?php echo esc_attr($name); ?>">
                            Save Changes
                        </button>
                        <button class="button reset-spec" data-spec="<?php echo esc_attr($name); ?>">
                            Reset to Default
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_preview_tab() {
        $site_url = get_site_url();
        ?>
        <div class="agentics-preview">
            <h3>Specification URLs</h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Specification</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->specs->get_available_specs() as $name): ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst(str_replace('-', ' ', $name))); ?></td>
                            <td>
                                <code><?php echo esc_html($site_url . '/.well-known/' . $this->specs->get_spec_file($name)); ?></code>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($site_url . '/.well-known/' . $this->specs->get_spec_file($name)); ?>"
                                   target="_blank" class="button">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_status_tab() {
        ?>
        <div class="agentics-status">
            <h3>System Status</h3>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td>Plugin Version</td>
                        <td><?php echo esc_html(AGENTICS_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td>WordPress Version</td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td>Deployment Role</td>
                        <td><?php echo esc_html(get_option('agentics_deployment_role', 'wordpress')); ?></td>
                    </tr>
                    <tr>
                        <td>Federation Status</td>
                        <td><?php echo get_option('agentics_federation_enabled') ? 'Enabled' : 'Disabled'; ?></td>
                    </tr>
                    <tr>
                        <td>WebSocket Status</td>
                        <td><?php echo get_option('agentics_websocket_enabled') ? 'Enabled' : 'Disabled'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('agentics_options');
                do_settings_sections('agentics-agent-spec-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_general_settings_section() {
        echo '<p>Configure general settings for the Agentics Agent Specification system.</p>';
    }

    public function render_deployment_role_field() {
        $value = get_option('agentics_deployment_role', 'wordpress');
        ?>
        <select name="agentics_deployment_role" id="agentics_deployment_role">
            <option value="primary" <?php selected($value, 'primary'); ?>>Primary</option>
            <option value="secondary" <?php selected($value, 'secondary'); ?>>Secondary</option>
            <option value="peer" <?php selected($value, 'peer'); ?>>Peer</option>
            <option value="wordpress" <?php selected($value, 'wordpress'); ?>>WordPress</option>
        </select>
        <?php
    }

    public function render_federation_field() {
        $value = get_option('agentics_federation_enabled', false);
        ?>
        <label>
            <input type="checkbox" name="agentics_federation_enabled" value="1" <?php checked($value, true); ?>>
            Enable federation capabilities
        </label>
        <?php
    }

    public function render_websocket_field() {
        $value = get_option('agentics_websocket_enabled', false);
        ?>
        <label>
            <input type="checkbox" name="agentics_websocket_enabled" value="1" <?php checked($value, true); ?>>
            Enable WebSocket support
        </label>
        <?php
    }
}