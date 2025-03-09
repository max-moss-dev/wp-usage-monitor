# Block Usage

A WordPress plugin that provides an overview of all registered Gutenberg blocks and their usage across your site.

## Features

- **Complete Block Inventory**: Lists all registered Gutenberg blocks from WordPress core, themes, and plugins
- **Usage Tracking**: Tracks how many times each block is used across your site
- **Detailed Usage Statistics**: Shows exactly where each block is used (posts, pages, templates)
- **Easy Filtering**: Filter by used/unused blocks to identify opportunities for cleanup
- **Template & Site Editor Support**: Includes blocks used in FSE templates and template parts

## Installation

1. Download the plugin zip file
2. Go to Plugins → Add New in your WordPress admin
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin
5. Go to Tools → Block Usage to see your block statistics

## Usage

### Viewing Block Usage

1. Navigate to Tools → Block Usage in your WordPress admin
2. View the complete list of blocks available on your site
3. See usage counts for each block
4. Filter by "Used" or "Unused" to focus on specific blocks

### Scanning Blocks

If you see a notice that content has been updated or if you've just installed the plugin:

1. Click the "Scan usage statistics" button
2. Wait for the scan to complete (a progress bar will show status)
3. View the updated block usage statistics

### Finding Where a Block is Used

1. Click on any block name in the list
2. A sidebar will appear showing all posts/pages/templates using this block
3. Click any listed item to edit that content

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher

## Development

### Local Development Setup

1. Clone this repository
2. Run `npm install` to install dependencies
3. Make your changes
4. Test thoroughly before deployment

## License

This project is licensed under the GPL v2 or later. 