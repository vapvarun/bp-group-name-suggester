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

### 2. Replacing the Generator

Return an array of suggestions from `bp_gns_generate_suggestions` to replace the built-in generator (an AI service, a list of your own). Return `null` to keep the default. The fourth argument lists the names the member already sees, so "Get More" never repeats one. Returning fewer than `$count` suggestions tells the panel the topic has run out, and it hides "Get More".

```php
add_filter('bp_gns_generate_suggestions', function($suggestions, $topic, $count, $exclude) {
    if ($topic !== 'crypto') {
        return $suggestions; // null: use the default generator.
    }

    $names = array_diff(my_crypto_group_names(), $exclude);

    return array_map(function($name) use ($topic) {
        return array(
            'name'        => $name,
            'description' => sprintf('A community for %s fans.', $name),
            'topic'       => $topic,
            'metadata'    => array(),
        );
    }, array_slice(array_values($names), 0, $count));
}, 10, 4);
```

### 3. Modifying Suggestion Output

```php
// Add metadata to suggestions
add_filter('bp_gns_suggestion_metadata', function($metadata, $topic) {
    $metadata['source'] = 'my_plugin';
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

### 4. Custom Name and Description Patterns

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

### 5. Settings and Configuration

```php
// Modify plugin settings
add_filter('bp_gns_settings', function($settings) {
    $settings['suggestions_per_load']   = 30; // first load
    $settings['more_suggestions_count'] = 15; // each "Get More"
    
    return $settings;
});

// Add custom settings to localized data
add_filter('bp_gns_localize_data', function($data) {
    $data['i18n']['exhausted'] = __('No more names for this topic.', 'my-plugin');
    
    return $data;
});
```

### 6. Template Customization

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
    echo '<div class="my-suggester-notice">Pick a name you like.</div>';
});

add_action('bp_gns_after_suggester', function() {
    echo '<div class="suggestion-tips">Tips: Be creative with your group name!</div>';
});
```

### 7. JavaScript Integration

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
});
```

## Available Actions

- `bp_gns_init` - Fires when plugin initializes
- `bp_gns_before_suggester` - Before suggester HTML
- `bp_gns_after_topic_selector` - After topic buttons
- `bp_gns_after_suggester` - After suggester HTML

## Available Filters

- `bp_gns_available_topics` - Modify available topics
- `bp_gns_topic_vocabulary` - Modify topic vocabularies
- `bp_gns_generate_suggestions` - Replace suggestion generation (args: null, topic, count, names already shown)
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