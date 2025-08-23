# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WizChat is a WordPress plugin that provides AI-powered customer service chat functionality using OpenAI's API. It displays a chat bubble in the bottom corner of WordPress sites that expands into a full chat interface with conversation persistence and modern UI design.

## Architecture

### Core Classes

- **WizChat** (`includes/class-wizchat.php`): Main plugin class implementing singleton pattern, handles initialization, REST API registration, and asset loading
- **WizChat_Admin** (`includes/class-wizchat-admin.php`): Handles WordPress admin interface, settings pages, and configuration management
- **WizChat_Public** (`includes/class-wizchat-public.php`): Renders the frontend chat interface with modern glassmorphism design
- **WizChat_API** (`includes/class-wizchat-api.php`): Manages OpenAI API communication with error handling and connection testing

### Key Features

- **REST API Endpoints**: `/wizchat/v1/chat` for conversations, `/wizchat/v1/verify-api` for connection testing
- **Modern UI**: Glassmorphism design with CSS variables for theming, backdrop-filter effects, and responsive layout
- **Conversation Persistence**: Uses localStorage for session management with configurable duration
- **Model Selection**: Supports both predefined OpenAI models and custom model configurations
- **Positioning**: Chat bubble can be positioned in left or right corners

## Development Commands

### WordPress Development

Since this is a WordPress plugin, standard WordPress development practices apply:

- **Plugin Testing**: Activate plugin in WordPress admin and test through the interface
- **Debugging**: Enable `WP_DEBUG` in wp-config.php for debug logging
- **Asset Development**: Edit files in `assets/` directory directly (no build process)

### File Structure

```
wizchat/
├── wizchat.php                 # Main plugin file with activation hooks
├── includes/                   # Core PHP classes
│   ├── class-wizchat.php       # Main plugin class
│   ├── class-wizchat-admin.php # Admin interface
│   ├── class-wizchat-api.php   # OpenAI API communication
│   └── class-wizchat-public.php # Frontend rendering
├── assets/                     # Frontend assets
│   ├── css/wizchat.css         # Main stylesheet with glassmorphism design
│   ├── js/wizchat.js           # Frontend JavaScript
│   └── js/wizchat-admin.js     # Admin interface JavaScript
└── uninstall.php               # Plugin cleanup
```

## Configuration

### Settings Structure

Settings are stored in WordPress options as `wizchat_settings` array:

```php
array(
    'api_key' => '',              // OpenAI API key
    'base_url' => 'https://api.openai.com/v1',  // API base URL
    'model' => 'gpt-4o',          // Selected AI model
    'custom_model' => '',         // Custom model name if 'custom' selected
    'bubble_position' => 'right', // Chat bubble position
    'session_duration' => 24,     // Hours to persist conversations
    'primary_color' => '#4F46E5', // Theme color
    'enable_vector_search' => 'no' // Vector knowledge库 toggle
)
```

### API Integration

The plugin uses OpenAI's chat completions API with:
- System prompt defining the AI as a website assistant
- Conversation history management (limited to 10 recent messages)
- Error handling for network and API issues
- Connection testing functionality

## UI/UX Implementation

### CSS Architecture

- **CSS Variables**: Theme colors defined in `:root` for easy customization
- **Glassmorphism**: Uses `backdrop-filter: blur()` for modern frosted glass effect
- **Responsive Design**: Mobile-first approach with Tailwind CSS utilities
- **Animation**: Smooth transitions for chat window open/close states

### JavaScript Architecture

- **jQuery-based**: Uses WordPress's included jQuery library
- **Event Delegation**: Proper event binding for dynamic content
- **State Management**: Conversation history stored in localStorage
- **API Communication**: RESTful calls to WordPress REST API endpoints

## Security Considerations

- All user input sanitized using WordPress `sanitize_text_field()`
- API keys stored encrypted in WordPress options
- REST API endpoints use proper permission callbacks
- Admin functions require `manage_options` capability
- CSRF protection via WordPress nonces

## Testing Approach

### Manual Testing

1. **Admin Interface**: Test settings page functionality and API connection validation
2. **Frontend Chat**: Verify chat bubble click, message sending, and AI responses
3. **Persistence**: Confirm conversations survive page refreshes
4. **Responsive**: Test on various screen sizes

### Debug Logging

Enable WordPress debug mode to see API communication logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Common Development Tasks

### Adding New Settings

1. Add field to `WizChat_Admin::register_settings()`
2. Create callback method for field rendering
3. Update `sanitize_settings()` method
4. Add JavaScript handling if needed

### Modifying Chat Interface

1. Edit HTML structure in `WizChat_Public::render_chat_interface()`
2. Update CSS in `assets/css/wizchat.css`
3. Modify JavaScript behavior in `assets/js/wizchat.js`

### API Integration Changes

1. Update `WizChat_API` class methods
2. Modify message preparation in `prepare_messages()`
3. Update response processing in `process_response()`

## Browser Compatibility

- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **CSS Features**: Uses `backdrop-filter` with fallbacks
- **JavaScript**: ES5+ compatible via jQuery
- **Mobile**: Responsive design works on mobile devices

## Notes

- No build process required - edit files directly
- Uses WordPress coding standards
- Chinese language interface with localization support
- Glassmorphism UI provides modern aesthetic with good accessibility