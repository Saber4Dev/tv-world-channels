# TV World Channels

A powerful WordPress plugin that displays TV channels from around the world using the [iptv-org API](https://iptv-org.github.io/api/). Create beautiful channel sliders and interactive tables with customizable styling options.

## Features

- **Channel Sliders**: Smooth, animated horizontal sliders with customizable speed, logo size, and styling
- **Interactive Tables**: DataTables-powered channel directory with country filtering and search
- **Category Filtering**: Filter channels by category (sports, news, entertainment, etc.)
- **Country Support**: Display channels from any country with automatic country name resolution
- **SEO Optimized**: Built-in h3 headings for better search engine visibility
- **Customizable Styling**: Full control over colors, fonts, shadows, and button styles via admin settings
- **Performance Optimized**: Multi-level caching, lazy loading, and cache plugin compatibility
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin
5. Go to Settings → TV World Channels to configure

## Quick Start

### Basic Slider
```
[tv country="france" logos="on" names="show"]
```

### Slider with Category Filter
```
[tv country="france" category="sports" logos="on" names="hide" sort="popular"]
```

### Channel Table
```
[tv_table]
```

### Table with Filters
```
[tv_table country="france" category="sports" sort="popular"]
```

## Shortcode Parameters

### `[tv]` Slider Shortcode

- `country` - Country code (e.g., "FR", "US") or name (e.g., "France", "United States")
- `category` - Filter by category (e.g., "sports", "news", "entertainment")
- `logos` - Show logos: "on" or "off" (default: "on")
- `names` - Show channel names: "show" or "hide" (default: "show")
- `sort` - Sort order: "popular", "name", or "default" (default: "default")

### `[tv_table]` Table Shortcode

- `country` - Filter by country code or name
- `category` - Filter by category
- `sort` - Sort order: "popular", "name", or "default"
- `logos` - Show logos: "on" or "off"

## Settings

Access all settings via **Dashboard → Settings → TV World Channels**:

### Slider Settings
- Slider speed (milliseconds)
- Logo width, padding, margin, and gap
- Background color for logos
- Autoplay, pause on hover, loop options
- Navigation controls (arrows and dots)

### Table Settings
- Show/hide logos
- Rows per page
- Enable country flags
- Full styling customization (colors, fonts, shadows, borders)

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher

## Automatic Updates

The plugin checks for updates from GitHub automatically. When you create a new release tag on GitHub, users will be notified of the update in their WordPress admin.

## Support

- **GitHub Repository**: [https://github.com/Saber4Dev/tv-world-channels](https://github.com/Saber4Dev/tv-world-channels)
- **Issues**: Report bugs or request features on GitHub Issues

## Changelog

See the [GitHub Releases](https://github.com/Saber4Dev/tv-world-channels/releases) page for detailed changelog.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- TV channel data provided by [iptv-org](https://iptv-org.github.io/api/)
- Built with WordPress best practices and performance optimization in mind

