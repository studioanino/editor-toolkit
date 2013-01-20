# Editor Toolkit
A helper class for WordPress which enables entering content in the TinyMCE editor as discrete blocks. On the frontend, blocks can be manipulated extensively before being rendered. For example, HTML elements with classes and ids can be wrapped around a block.


## Usage
This class needs to be integrated with a theme, ideally using the `after_setup_theme` hook:

	function foo_theme_setup() {
		// Required: Include the main file
		require_once('editor-toolkit/editor-toolkit.php');
		// Recommended: Make the instance variable global
		global $edtoolkit;
		// Required: Instantiate the class
		// If needed, specify path to class and post types
		$edtoolkit = new Editor_Toolkit( array('directory' => 'editor-toolkit', 'post_types' => array('page') ) );
		
		// Optional: Create shorter aliases to the render methods
		// Otherwise, use `$edtoolkit->get_block()` and `$edtoolkit->the_block()` directly
		function get_block($request, $post = NULL) {
			global $edtoolkit;
			return $edtoolkit->get_block($request, $post);
		}
		function the_block($request, $post = NULL) {
			global $edtoolkit;
			echo $edtoolkit->the_block($request, $post);
		}
	}
	add_action('after_setup_theme', 'foo_theme_setup');

In a template, reference and output a block by number:

	if ($post->post_blocks) {
		<!-- Output the contents of block #1 -->
		<h1><?php the_block('1'); ?></h1>

		<!-- Output the contents of block #2 > child-block #1 -->
		<h2><?php the_block('2,1'); ?></h2>

		<!-- Loop through block #3, use child-block index, remove HTML tags from and output the contents of each child-block -->
		<ul>
			<?php foreach( get_block('3') as $block as $index => $block ) { ?>
				<li class="item-<?php echo ($index + 1); ?>>
					<?php echo strip_tags($block);  // Or: strip_tags( get_the_block('3,$index') ); ?>
				</li>
			<?php } ?>
		</ul>
	}


## Known Issues
* Serialized content not saved if there are empty blocks
* Unsaved edits do not show up in preview of published posts
* Serialized content saved to the `post_content_filtered` field, potentially preventing any other use of that field
* Content editor too narrow in full-screen mode -- a limitation of WordPress itself


## To-dos
* If prepping XML throws any errors, add a notification to the edit screen
* Block-level and inline element insertion via toolbar


## Changelog
### 1.0 - January 19, 2013
* Original version from 2012 cleaned up and prepped for public release
* Added readme.txt


## Credits
Developed and maintained by [John B. Fakorede](http://studioanino.com "John B. Fakorede")


## License
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to:

Free Software Foundation, Inc.
51 Franklin Street, Fifth Floor,
Boston, MA
02110-1301, USA.
