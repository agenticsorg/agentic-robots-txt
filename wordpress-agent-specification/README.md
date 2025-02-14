# Agentics Agent Specification for WordPress

This WordPress plugin implements the Agentics Web Application - Autonomous Agent Guidance System, enabling WordPress websites to provide structured specifications and guidance for autonomous agents.

## Features

- Implements the complete Agentics specification system
- Provides structured guidance for autonomous agents
- Manages robots.txt enhancements
- Serves .well-known directory specifications
- Supports federation between deployments
- Includes comprehensive admin interface
- REST API endpoints for programmatic access
- Real-time status monitoring

## Specifications

The plugin implements the following specifications:

- `/.well-known/agentics-manifest.json` - Primary discovery endpoint
- `/.well-known/agentic-guidance.json` - Core interaction specifications
- `/.well-known/openapi.json` - REST API documentation
- `/.well-known/asyncapi.json` - WebSocket/real-time features
- `/.well-known/peers.json` - Federation coordination
- `/.well-known/health.json` - System monitoring
- `/.well-known/models.json` - AI capabilities
- `/.well-known/version-control.json` - Change management
- `/.well-known/agent-guide.md` - Usage documentation

## Installation

1. Download the plugin zip file
2. Upload to your WordPress site through the plugins page
3. Activate the plugin
4. Go to Settings > Agentics to configure

## Configuration

### Basic Setup

1. Navigate to the Agentics settings page in WordPress admin
2. Configure your deployment role (primary/secondary/peer)
3. Enable/disable federation capabilities
4. Save changes

### Advanced Configuration

The plugin provides several filters and actions for customization:

```php
// Customize manifest content
add_filter('agentics_manifest', function($manifest) {
    $manifest['deployment']['role'] = 'primary';
    return $manifest;
});

// Add custom endpoints
add_filter('agentics_api_endpoints', function($endpoints) {
    $endpoints[] = [
        'path' => '/custom',
        'callback' => 'handle_custom_endpoint'
    ];
    return $endpoints;
});

// Modify robots.txt content
add_filter('agentics_robots_txt', function($content) {
    return $content . "\n# Custom directives\n";
});
```

## Usage

### For Administrators

1. Access the Agentics admin panel from WordPress dashboard
2. Edit specifications through the user interface
3. Monitor system status
4. Configure federation settings

### For Developers

#### REST API Endpoints

```
GET /wp-json/agentics/v1/specs
GET /wp-json/agentics/v1/specs/{name}
POST /wp-json/agentics/v1/specs/{name}
GET /wp-json/agentics/v1/health
```

#### WebSocket Support

```javascript
const socket = new WebSocket('wss://your-site.com/agentics/realtime');
socket.onmessage = (event) => {
    const data = JSON.parse(event.data);
    // Handle real-time updates
};
```

### For Autonomous Agents

1. Read robots.txt to discover specifications
2. Access /.well-known/agentics-manifest.json for capabilities
3. Follow specification guidelines for interaction
4. Use appropriate authentication methods
5. Respect rate limits and quotas

## Federation

The plugin supports federation between different Agentics deployments:

1. Enable federation in settings
2. Configure trust verification methods
3. Set resource sharing policies
4. Monitor federation status

## Security

- All API endpoints require authentication
- Rate limiting enforced
- Federation requires trust verification
- Secure WebSocket connections
- Access control for admin features

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- HTTPS enabled
- Write permissions for .well-known directory

## Support

- [Documentation](https://agentics.org/docs)
- [GitHub Issues](https://github.com/agentics/wordpress-agent-specification/issues)
- [Support Forum](https://wordpress.org/support/plugin/agentics-agent-spec)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

GPL v2 or later

## Credits

Developed by Agentics - Enabling autonomous agent interactions for WordPress websites.