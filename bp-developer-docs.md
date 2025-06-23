# BuddyPress Group Name Suggester - Developer Documentation

## Available Hooks and Filters

### 1. Adding Custom Topics

```php
// Add a new topic with custom vocabulary
function my_custom_topics($topics) {
    $topics['cryptocurrency'] = array(
        'icon' => '₿',
        'label' => __('Cryptocurrency & Blockchain', 'my-textdomain')
    );
    return $topics;
}
add_filter('bp_gns_available_topics', 'my_custom_topics');

// Add vocabulary for the custom topic
function my_custom_vocabulary($vocab) {
    $vocab['cryptocurrency'] = array(
        'prefixes' => array('Crypto', 'Blockchain', 'Decentralized', 'Digital'),
        'keywords' => array('Bitcoin', 'Ethereum', 'DeFi', 'NFT', 'Trading'),
        'nouns' => array('Traders', 'Investors', 'Holders', 'Miners', 'Developers'),
        'activities' => array('trading strategies', 'blockchain development', 'DeFi exploration'),
        'purposes' => array('navigate the crypto space', 'share trading insights', 'learn blockchain technology')
    );
    return $vocab;
}
add_filter('bp_gns_topic_vocabulary', 'my_custom_vocabulary');
```

### 2. Integrating AI Suggestions

```php
// Register an AI provider (e.g., OpenAI)
function register_openai_provider() {
    bp_gns_register_ai_provider('openai', 'OpenAI GPT', 'my_openai_suggestion_handler');
}
add_action('bp_gns_init', 'register_openai_provider');

// AI suggestion handler
function my_openai_suggestion_handler($topic, $count) {
    // Your OpenAI API integration
    $api_key = get_option('my_openai_api_key');
    
    $suggestions = array();
    
    // Make API call to OpenAI
    $response = wp_remote_post('https://api.openai.com/v1/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'model' => 'gpt-3.5-turbo',
            'prompt' => "Generate {$count} creative group names for a {$topic} community...",
            'max_tokens' => 150
        ))
    ));
    
    // Process response and return suggestions
    return $suggestions;
}

// Hook into suggestion generation
add_filter('bp_gns_generate_suggestions', function($suggestions, $topic, $count, $provider) {
    if ($provider === 'openai') {
        return my_openai_suggestion_handler($topic, $count);
    }
    return $suggestions;
}, 10, 4);
```

### 3. Custom AJAX Endpoints

```php
// Add custom AJAX handler
add_action('bp_gns_custom_ajax_handler', function($data) {
    if (isset($data['action_type']) && $data['action_type'] === 'ai_suggestions') {
        // Handle AI-specific requests
        $topic = sanitize_text_field($data['topic']);
        $prompt = sanitize_text_field($data['prompt']);
        
        // Your custom logic here
        $suggestions = generate_ai_suggestions($topic, $prompt);
        
        wp_send_json_success($suggestions);
    }
});
```

### 4. Modifying Suggestion Output

```php
// Add metadata to suggestions
add_filter('bp_gns_suggestion_metadata', function($metadata, $topic) {
    $metadata['source'] = 'ai_generated';
    $metadata['confidence'] = rand(85, 99) / 100;
    $metadata['timestamp'] = current_time('timestamp');
    return $metadata;
}, 10, 2);

// Modify final suggestions before output
add_filter('bp_gns_suggestions_output', function($suggestions, $topic) {
    foreach ($suggestions as &$suggestion) {
        // Add emoji based on topic
        $suggestion['emoji'] = get_topic_emoji($topic);
        
        // Add tags
        $suggestion['tags'] = generate_suggestion_tags($suggestion['name']);
    }
    return $suggestions;
}, 10, 2);
```

### 5. Custom Name and Description Patterns

```php
// Add custom name patterns
add_filter('bp_gns_name_patterns', function($patterns, $prefix, $keyword, $noun) {
    // Add new patterns
    $patterns[] = "Team {$keyword}";
    $patterns[] = "{$keyword} {$noun} Alliance";
    $patterns[] = "{$prefix} {$keyword} Hub";
    
    return $patterns;
}, 10, 4);

// Add custom description templates
add_filter('bp_gns_description_templates', function($templates, $keyword, $activity, $purpose) {
    $templates[] = "Discover the world of {$keyword} with us. We're all about {$activity} and helping each other {$purpose}.";
    $templates[] = "Your home for everything {$keyword}. Join us as we {$purpose} through {$activity} and community support.";
    
    return $templates;
}, 10, 4);
```

### 6. Settings and Configuration

```php
// Modify plugin settings
add_filter('bp_gns_settings', function($settings) {
    $settings['enable_ai'] = true;
    $settings['ai_provider'] = 'openai';
    $settings['suggestions_per_load'] = 30;
    $settings['cache_duration'] = 7200; // 2 hours
    
    return $settings;
});

// Add custom settings to localized data
add_filter('bp_gns_localize_data', function($data) {
    $data['custom_endpoints'] = array(
        'ai_suggest' => admin_url('admin-ajax.php?action=bp_gns_custom_suggestions'),
        'trending' => admin_url('admin-ajax.php?action=bp_gns_trending_names')
    );
    
    return $data;
});
```

### 7. Template Customization

```php
// Override the suggester template
add_filter('bp_gns_template_path', function($template) {
    // Use custom template from theme
    $theme_template = get_stylesheet_directory() . '/buddypress/group-name-suggester.php';
    
    if (file_exists($theme_template)) {
        return $theme_template;
    }
    
    return $template;
});

// Add content before/after suggester
add_action('bp_gns_before_suggester', function() {
    echo '<div class="ai-powered-notice">Powered by AI</div>';
});

add_action('bp_gns_after_suggester', function() {
    echo '<div class="suggestion-tips">Tips: Be creative with your group name!</div>';
});
```

### 8. JavaScript Integration

```javascript
// In your custom JavaScript file
jQuery(document).ready(function($) {
    // Listen for custom events
    $(document).on('bp_gns_suggestion_selected', function(e, data) {
        console.log('Suggestion selected:', data);
        
        // Track analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'group_name_selected', {
                'event_category': 'engagement',
                'event_label': data.topic,
                'value': data.name
            });
        }
    });
    
    // Add AI button
    if (bpGroupNameSuggester.settings.enable_ai) {
        $('.topic-buttons').after(
            '<button class="ai-suggest-btn">Get AI Suggestions</button>'
        );
    }
});
```

## Complete Example: AI Integration

```php
/**
 * Plugin Name: BuddyPress AI Name Suggestions
 * Description: Adds AI-powered suggestions to BuddyPress Group Name Suggester
 */

class BP_AI_Suggestions {
    
    public function __construct() {
        add_action('bp_gns_init', array($this, 'init'));
    }
    
    public function init() {
        // Register AI provider
        bp_gns_register_ai_provider('chatgpt', 'ChatGPT AI', array($this, 'generate_ai_suggestions'));
        
        // Add AI topic
        add_filter('bp_gns_available_topics', array($this, 'add_ai_topic'));
        
        // Hook into generation
        add_filter('bp_gns_generate_suggestions', array($this, 'maybe_use_ai'), 10, 4);
    }
    
    public function add_ai_topic($topics) {
        $topics['ai_powered'] = array(
            'icon' => '🤖',
            'label' => __('AI Generated', 'bp-ai')
        );
        return $topics;
    }
    
    public function maybe_use_ai($suggestions, $topic, $count, $provider) {
        if ($topic === 'ai_powered' || $provider === 'chatgpt') {
            return $this->generate_ai_suggestions($topic, $count);
        }
        return $suggestions;
    }
    
    public function generate_ai_suggestions($topic, $count) {
        $api_key = get_option('bp_ai_api_key');
        
        if (!$api_key) {
            return array();
        }
        
        // Call AI API
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a creative group name generator.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => "Generate {$count} unique group names for a {$topic} community. 
                                     Format: JSON array with 'name' and 'description' for each."
                    )
                ),
                'temperature' => 0.8
            ))
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Parse and return suggestions
        return $this->parse_ai_response($body);
    }
    
    private function parse_ai_response($response) {
        // Parse AI response and format for plugin
        $suggestions = array();
        
        // ... parsing logic ...
        
        return $suggestions;
    }
}

new BP_AI_Suggestions();
```

## Available Actions

- `bp_gns_init` - Fires when plugin initializes
- `bp_gns_before_suggester` - Before suggester HTML
- `bp_gns_after_topic_selector` - After topic buttons
- `bp_gns_after_suggester` - After suggester HTML
- `bp_gns_custom_ajax_handler` - Handle custom AJAX requests

## Available Filters

- `bp_gns_available_topics` - Modify available topics
- `bp_gns_topic_vocabulary` - Modify topic vocabularies
- `bp_gns_suggestion_providers` - Register suggestion providers
- `bp_gns_generate_suggestions` - Override suggestion generation
- `bp_gns_suggestions_output` - Modify final suggestions
- `bp_gns_topic_data` - Modify topic data
- `bp_gns_generate_name` - Modify generated names
- `bp_gns_generate_description` - Modify descriptions
- `bp_gns_name_patterns` - Add name patterns
- `bp_gns_description_templates` - Add description templates
- `bp_gns_settings` - Modify plugin settings
- `bp_gns_localize_data` - Modify JS localized data
- `bp_gns_template_path` - Override template path
- `bp_gns_heading_text` - Modify heading text