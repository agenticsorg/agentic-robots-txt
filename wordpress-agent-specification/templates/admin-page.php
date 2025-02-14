<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="agentics-admin-tabs" role="tabpanel">
        <nav class="nav-tab-wrapper" role="tablist">
            <a href="#specifications" 
               class="nav-tab nav-tab-active" 
               role="tab" 
               aria-selected="true" 
               aria-controls="specifications">
                <?php esc_html_e('Specifications', 'agentics-agent-spec'); ?>
            </a>
            <a href="#preview" 
               class="nav-tab" 
               role="tab" 
               aria-selected="false" 
               aria-controls="preview">
                <?php esc_html_e('Preview', 'agentics-agent-spec'); ?>
            </a>
            <a href="#status" 
               class="nav-tab" 
               role="tab" 
               aria-selected="false" 
               aria-controls="status">
                <?php esc_html_e('Status', 'agentics-agent-spec'); ?>
            </a>
        </nav>

        <div class="tab-content">
            <div id="specifications" 
                 class="tab-pane active" 
                 role="tabpanel" 
                 aria-labelledby="tab-specifications">
                <div class="agentics-specs-list">
                    <?php foreach ($this->specs->get_available_specs() as $name): 
                        $spec = $this->specs->get_spec($name);
                        if (!$spec) continue;
                    ?>
                        <div class="spec-item" data-spec="<?php echo esc_attr($name); ?>">
                            <h3><?php echo esc_html(ucfirst(str_replace('-', ' ', $name))); ?></h3>
                            <div class="spec-content">
                                <textarea class="spec-editor" 
                                          data-spec="<?php echo esc_attr($name); ?>"
                                          aria-label="<?php echo esc_attr(sprintf(__('%s specification content', 'agentics-agent-spec'), ucfirst($name))); ?>"
                                          rows="10"><?php echo esc_textarea($spec['content']); ?></textarea>
                            </div>
                            <div class="spec-actions">
                                <button class="button save-spec" 
                                        data-spec="<?php echo esc_attr($name); ?>"
                                        type="button">
                                    <?php esc_html_e('Save Changes', 'agentics-agent-spec'); ?>
                                </button>
                                <button class="button reset-spec" 
                                        data-spec="<?php echo esc_attr($name); ?>"
                                        type="button">
                                    <?php esc_html_e('Reset to Default', 'agentics-agent-spec'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="preview" 
                 class="tab-pane" 
                 role="tabpanel" 
                 aria-labelledby="tab-preview">
                <div class="agentics-preview">
                    <h3><?php esc_html_e('Specification URLs', 'agentics-agent-spec'); ?></h3>
                    <table class="widefat" role="table">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Specification', 'agentics-agent-spec'); ?></th>
                                <th scope="col"><?php esc_html_e('URL', 'agentics-agent-spec'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'agentics-agent-spec'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->specs->get_available_specs() as $name): 
                                $file = $this->specs->get_spec_file($name);
                                $url = site_url('/.well-known/' . $file);
                            ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst(str_replace('-', ' ', $name))); ?></td>
                                    <td><code><?php echo esc_html($url); ?></code></td>
                                    <td>
                                        <a href="<?php echo esc_url($url); ?>" 
                                           class="button view-spec" 
                                           target="_blank" 
                                           rel="noopener noreferrer">
                                            <?php esc_html_e('View', 'agentics-agent-spec'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="status" 
                 class="tab-pane" 
                 role="tabpanel" 
                 aria-labelledby="tab-status">
                <div class="agentics-status">
                    <h3><?php esc_html_e('System Status', 'agentics-agent-spec'); ?></h3>
                    <table class="widefat" role="table">
                        <tbody>
                            <tr>
                                <td><?php esc_html_e('Plugin Version', 'agentics-agent-spec'); ?></td>
                                <td class="status-value" data-key="version">
                                    <?php echo esc_html(AGENTICS_VERSION); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('WordPress Version', 'agentics-agent-spec'); ?></td>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Deployment Role', 'agentics-agent-spec'); ?></td>
                                <td class="status-value" data-key="role">
                                    <?php echo esc_html(get_option('agentics_deployment_role', 'wordpress')); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Federation Status', 'agentics-agent-spec'); ?></td>
                                <td class="status-value" data-key="federation">
                                    <?php echo get_option('agentics_federation_enabled') ? 
                                        esc_html__('Enabled', 'agentics-agent-spec') : 
                                        esc_html__('Disabled', 'agentics-agent-spec'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Last Updated', 'agentics-agent-spec'); ?></td>
                                <td class="status-timestamp">
                                    <?php echo esc_html(current_time('mysql')); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>