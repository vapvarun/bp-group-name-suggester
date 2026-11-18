# BuddyPress Group Name Suggester

Advanced randomizer generator names for BuddyPress groups. Generate creative, topic-based group names instantly with our intelligent suggestion system.

## 🚀 Features

- **Topic-Based Suggestions**: 14 different categories for targeted name generation
- **Instant Generation**: Get 20+ creative suggestions immediately
- **Smart Algorithm**: Contextually relevant names with matching descriptions
- **User-Friendly**: One-click to use any suggestion
- **Unlimited Suggestions**: Load more options anytime
- **Responsive Design**: Works on all devices
- **Zero Configuration**: Works out of the box

## 📋 Requirements

- WordPress 5.0+
- BuddyPress 5.0+
- PHP 8.1+

## 🔧 Installation

### From WordPress Dashboard

1. Navigate to **Plugins > Add New**
2. Search for "BuddyPress Group Name Suggester"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the latest release from [GitHub Releases](https://github.com/wbcomdesigns/bp-group-name-suggester/releases)
2. Upload to `/wp-content/plugins/` directory
3. Activate through the WordPress Plugins menu

### Via Composer

```bash
composer require wbcomdesigns/bp-group-name-suggester
```

## 💡 Usage

1. Navigate to BuddyPress group creation page
2. Select your desired topic category
3. Browse through generated suggestions
4. Click "Use This Name" to auto-fill the form
5. Load more suggestions if needed

### File Structure

```
bp-group-name-suggester/
├── bp-group-name-suggester.php    # Main plugin file
├── assets/
│   ├── css/
│   │   └── style.css              # Plugin styles
│   ├── js/
│   │   └── script.js              # Plugin JavaScript
│   └── screenshots/               # Screenshots for WordPress.org
├── languages/                     # Translation files
├── readme.txt                     # WordPress.org readme
└── README.md                      # This file
```

### Coding Standards

This plugin follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/):

```bash
# Check PHP coding standards
phpcs --standard=WordPress bp-group-name-suggester.php

# Fix automatically fixable issues
phpcbf --standard=WordPress bp-group-name-suggester.php
```

## 🌍 Internationalization

The plugin is translation-ready. To create translations:

1. Use POEdit or similar tool
2. Load `/languages/bp-group-name-suggester.pot`
3. Create your translation
4. Save as `bp-group-name-suggester-{locale}.po`

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Reporting Issues

Please use the [GitHub Issues](https://github.com/wbcomdesigns/bp-group-name-suggester/issues) to report bugs or request features.

## 📝 Changelog

### Version 1.0.0

- Initial release
- 14 topic categories
- Intelligent name generation algorithm
- One-click suggestion usage
- Load more functionality
- Mobile responsive design

## 🙏 Credits

- Developed by [Wbcom Designs](https://wbcomdesigns.com/)
- Built for the [BuddyPress](https://buddypress.org/) community

## 📄 License

This project is licensed under the GPL v2.0 or later - see the [LICENSE](LICENSE) file for details.

## 💬 Support

- **Documentation**: [Plugin Documentation](https://wbcomdesigns.com/docs/bp-group-name-suggester/)
- **Support Forum**: [Wbcom Designs Support](https://wbcomdesigns.com/support/)
- **Email**: support@wbcomdesigns.com

## 🔗 Links

- [WordPress Plugin Directory](https://wordpress.org/plugins/bp-group-name-suggester/)
- [Wbcom Designs](https://wbcomdesigns.com/)
- [Other BuddyPress Plugins](https://profiles.wordpress.org/wbcomdesigns/#content-plugins)

---

Made with ❤️ by [Wbcom Designs](https://wbcomdesigns.com/) for the BuddyPress Community
