<?php
if (!defined('ABSPATH')) {
    exit;
}

class Agentics_Specs {
    private $specs = array();
    private $default_specs = array();

    public function __construct() {
        $this->init_default_specs();
    }

    private function init_default_specs() {
        $this->default_specs = array(
            'manifest' => array(
                'file' => 'agentics-manifest.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_manifest()
            ),
            'guidance' => array(
                'file' => 'agentic-guidance.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_guidance()
            ),
            'openapi' => array(
                'file' => 'openapi.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_openapi()
            ),
            'asyncapi' => array(
                'file' => 'asyncapi.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_asyncapi()
            ),
            'peers' => array(
                'file' => 'peers.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_peers()
            ),
            'health' => array(
                'file' => 'health.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_health()
            ),
            'models' => array(
                'file' => 'models.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_models()
            ),
            'version-control' => array(
                'file' => 'version-control.json',
                'content_type' => 'application/json',
                'content' => $this->get_default_version_control()
            ),
            'agent-guide' => array(
                'file' => 'agent-guide.md',
                'content_type' => 'text/markdown',
                'content' => $this->get_default_agent_guide()
            )
        );
    }

    public function init_specs() {
        foreach ($this->default_specs as $key => $spec) {
            $this->specs[$key] = get_option('agentics_spec_' . $key, $spec['content']);
        }
    }

    public function get_spec($name) {
        if (isset($this->specs[$name])) {
            return array(
                'content' => $this->specs[$name],
                'content_type' => $this->default_specs[$name]['content_type']
            );
        }
        return false;
    }

    public function update_spec($name, $content) {
        if (isset($this->default_specs[$name])) {
            $this->specs[$name] = $content;
            update_option('agentics_spec_' . $name, $content);
            return true;
        }
        return false;
    }

    public function get_robots_txt_content() {
        $site_url = get_site_url();
        return "# Agentics Web Application - Autonomous Agent Guidance System
# Version: 1.0.0
# Last Updated: " . current_time('Y-m-d') . "

# Quick Start Guide for Autonomous Agents:
# 1. Read the manifest at /.well-known/agentics-manifest.json for complete capabilities
# 2. See /.well-known/agent-guide.md for detailed examples and best practices
# 3. Authenticate via /auth endpoint using JWT
# 4. Access real-time updates via WebSocket
# 5. Join federation network if distributed operations needed

# Structured Specifications:
# - Main Manifest:     /.well-known/agentics-manifest.json    # Complete system overview
# - Agent Guide:       /.well-known/agent-guide.md           # Detailed examples
# - Agent Guidance:    /.well-known/agentic-guidance.json     # Core specifications
# - API Docs:          /.well-known/openapi.json              # REST API
# - WebSocket:         /.well-known/asyncapi.json             # Real-time features
# - Federation:        /.well-known/peers.json                # Deployment coordination
# - Health Status:     /.well-known/health.json              # System monitoring
# - Model Specs:       /.well-known/models.json              # AI capabilities

# For detailed specifications and capabilities, see:
# {$site_url}/.well-known/agentics-manifest.json";
    }

    private function get_default_manifest() {
        $site_url = get_site_url();
        return json_encode([
            'version' => '1.0.0',
            'description' => 'Agentics guidance specification for ' . get_bloginfo('name'),
            'deployment' => [
                'id' => sanitize_title(get_bloginfo('name')),
                'url' => $site_url,
                'role' => 'wordpress'
            ],
            'specifications' => [
                'agent_guidance' => [
                    'path' => '/.well-known/agentic-guidance.json',
                    'required' => true
                ],
                'api' => [
                    'path' => '/.well-known/openapi.json',
                    'required' => true
                ],
                'realtime' => [
                    'path' => '/.well-known/asyncapi.json',
                    'required' => true
                ]
            ]
        ], JSON_PRETTY_PRINT);
    }

    // Additional default spec getters would go here...
    // get_default_guidance()
    // get_default_openapi()
    // etc.

    public function get_available_specs() {
        return array_keys($this->default_specs);
    }

    public function get_spec_file($name) {
        return isset($this->default_specs[$name]) ? $this->default_specs[$name]['file'] : false;
    }

    public function get_spec_content_type($name) {
        return isset($this->default_specs[$name]) ? $this->default_specs[$name]['content_type'] : false;
    }
}