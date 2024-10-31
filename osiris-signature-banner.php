<?php
/*
Plugin Name: Osiris Signature Banner
Plugin URI: http://www.osirisguitar.com/osiris-signature-banner/
Description: Creates an image banner from the latest post headline
Version: 0.5
Author: Anders Bornholm
Author URI: http://www.osirisguitar.com/about-me
License: A "Slug" license  name e.g. GPL2
*/
class osiris_signature
{
	var $prepend_text = 'Latest post:  ';
	var $font_size = '10';
	var $font_name = 'georgia.ttf';
	var $left = '-1';
	var $top = '-1';
	var $template_name = 'template.png';
	var $color = '#ffffff';
	var $generated_image_name = 'signature.png';

	function osiris_signature()
	{
		$this->constructor();
	}
	
	function __construct()
	{
		add_action('publish_post', array(&$this, 'create_image'));
		add_action('admin_menu', array(&$this, 'os_plugin_menu'));
		$this->get_options();
	}
	
	function create_image($post_id)
	{
		$latest_posts = get_posts("numberposts=1");
		
		if ($post_id == $latest_posts[0]->ID)
		{
			$string = $this->prepend_text . $latest_posts[0]->post_title;
			$image = @imagecreatefrompng(dirname(__FILE__).DIRECTORY_SEPARATOR. $this->template_name);
			
			$font = dirname(__FILE__) . DIRECTORY_SEPARATOR . $this->font_name;
			$font_size = $this->font_size;
			$bounding_box = imagettfbbox($font_size, 0, $font, $string);
			$width = $bounding_box[2] - $bounding_box[0];
			$height = $bounding_box[6] - $bounding_box[0];
			list($image_width, $image_height) = getimagesize(dirname(__FILE__).DIRECTORY_SEPARATOR. $this->template_name); 

			// If user has chosen -1, set top to center the text vertically
			$top = $this->top;
			if ($top == -1)
				$top = $image_height/2 - $height/2;

			// If user has chosen -1, set left to center the text horizontally (including prepend)
			$left = $this->left;
			if ($left == -1)
				$left = $image_width/2 - $width/2; 
	 
			$color_string = ltrim($this->color, "#");
			$red = substr($color_string, 0, 2);
			$green = substr($color_string, 2, 2);
			$blue = substr($color_string, 4, 2);
			$color = imagecolorallocate($image, hexdec("0x" . $red), hexdec("0x" . $green), hexdec("0x" . $blue));
	 
			imagettftext($image,$font_size,0,$left,$top,$color,$font,$string);
			imagepng($image, dirname(__FILE__).DIRECTORY_SEPARATOR. $this->generated_image_name);
			imagedestroy($image);
		}
	}
	
	function get_options()
	{
		$options = get_option('osiris_signature');
		if($options !== false)
		{
			$this->prepend_text = $options['prepend_text'];
			$this->font_size = $options['font_size'];
			$this->font_name = $options['font_name'];
			$this->left = $options['left'];
			$this->top = $options['top'];
			$this->template_name = $options['template_name'];
			$this->color = $options['color'];
			$this->generated_image_name = $options['generated_image_name'];
		}
		else
		{
			// If no options are present, store the defaults.
			$this->set_options();
		}

	}
	
	function set_options()
	{
		$options = array(
			'prepend_text' => $this->prepend_text,
			'font_size' => $this->font_size,
			'font_name' => $this->font_name,
			'left' => $this->left,
			'top' => $this->top,
			'template_name' => $this->template_name,
			'color' => $this->color,
			'generated_image_name' => $this->generated_image_name
		);
		
		update_option('osiris_signature', $options);
	}
	
	function os_plugin_menu() {
		add_options_page('Osiris Signature Options', 'Osiris Signature', 'manage_options', 'osiris-signature', array(&$this, 'os_plugin_options'));
	}

	function os_plugin_options() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		if (!empty($_POST['save']))
		{
			$this->save_posted_values();
		}
		else if (!empty($_POST['regenerate']))
		{
			$posts = get_posts("numberposts=1");
			$this->create_image($posts[0]->ID);
		}
		?>
		<style type="text/css">
		.label { width:150px;float:left;clear:left; }
		.field { float:left;width:250px; }
		.field input { width:250px;}
		.helpText { float:left;width:400px;padding:3px 0px 10px 10px; }
		</style>
		<div class="wrap">
			<h2>Settings</h2>
			<form method="post" action="">
				<p>
					<div class="label">Template image:</div><div class="field"><input type="text" name="template_name" value="<?= $this->template_name ?>"></div><div class="helpText"><span class="description">Full path to template image</span></div>
					<div class="label">Prepend text:</div><div class="field"><input type="text" name="prepend_text" value="<?= $this->prepend_text ?>"></div><div class="helpText"><span class="description">Text that is added before the title of your latest post</span></div>
					<div class="label">Font file:</div>
						<div class="field">
							<select name="font_name">
							<?
								$dir = dirname(__FILE__);
								$files = scandir($dir);
								
								foreach($files as $file)
								{
									if (strrpos($file, ".ttf"))
									{
										?>
											<option value="<?= $file ?>" <?= strcmp($this->font_name, $file) == 0 ? 'selected' : '' ?>><?= $file ?></option>
										<?
									}
								}
							?>
							</select>
						</div>
						<div class="helpText"><span class="description">Name (including .ttf) of the font file, which needs to be placed in the same directory as the plugin</span></div>
					<div class="label">Font size:</div><div class="field"><input type="text" name="font_size" value="<?= $this->font_size ?>"></div><div class="helpText"><span class="description">Size in pt of the text</span></div>
					<div class="label">Text color:</div><div class="field"><input type="text" name="color" value="<?= $this->color ?>"></div><div class="helpText"><span class="description">Color of the text entered as a HTML color (#FF00FF)</span></div>
					<div class="label">Left position:</div><div class="field"><input type="text" name="left" value="<?= $this->left ?>"></div><div class="helpText"><span class="description">Text placement in pixels from left edge (-1 to center horizontally)</span></div>
					<div class="label">Right position:</div><div class="field"><input type="text" name="top" value="<?= $this->top ?>"></div><div class="helpText"><span class="description">Text placement in pixels from top edge (-1 to center vertically)</span></div>
					<div class="label">Generated image:</div><div class="field"><input type="text" name="generated_image_name" value="<?= $this->generated_image_name ?>"></div><div class="helpText"><span class="description">Name of the generated image (will be placed in the plugin directory)</span></div>
					<div style="clear:left;"><input type="submit" name="save" value="Save settings"></div>
				</p>
				<h3>bbCode</h3>
				<p>
					[url=<?= home_url() ?>][img]<?= plugins_url($this->generated_image_name, __FILE__) ?>[/img][/url]
				</p>
				<h3>HTML</h3>
				<p>
					&lt;a href="<?= home_url() ?>"&gt;&lt;img src="<?= plugins_url($this->generated_image_name, __FILE__) ?>" border="0"&gt;&lt/a&gt;
				</p>
				<h3>Sample</h3>
				<img src="<?= plugins_url($this->generated_image_name, __FILE__) ?>" border="0"><br>
				<br>
				<input type="submit" Name="regenerate" value="Regenerate image"><br>
				(Press CTRL + refresh after regeneration to get the latest image)
			</form>
		</div>
		<?
	}
	
	function save_posted_values()
	{
		if (isset($_POST['template_name']))
			$this->template_name = $_POST['template_name'];
		if (isset($_POST['prepend_text']))
			$this->prepend_text = $_POST['prepend_text'];
		if (isset($_POST['font_name']))
			$this->font_name = $_POST['font_name'];
		if (isset($_POST['font_name']))
			$this->font_size = $_POST['font_size'];
		if (isset($_POST['font_size']))
			$this->font_name = $_POST['font_name'];
		if (isset($_POST['color']))
			$this->color = $_POST['color'];
		if (isset($_POST['left']))
			$this->left = $_POST['left'];
		if (isset($_POST['top']))
			$this->top = $_POST['top'];
		if (isset($_POST['generated_image_name']))
			$this->generated_image_name = $_POST['generated_image_name'];
			
		$this->set_options();
	}
}

$osirissignature = new osiris_signature();
?>