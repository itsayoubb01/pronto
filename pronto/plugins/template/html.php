<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Template plugin for common HTML elements.
 *
 **/
class tpHtml extends Plugin
{
	/**
	 * Constructor
	 */
	function tpHtml() {
		$this->Plugin();
	}

	/**
	 * Insert some HTML into the HEAD of the rendered layout/template.
	 *
	 * @param string $key A unique key for this code or HTML
	 * @param string $val The code/HTML itself
	 */
	function head($key, $val) {
		$this->web->queue_html_head($key, $val);
	}

	/**
	 * Set some JavaScript to be run by the next rendered template.  This
	 * method is AJAX-friendly, i.e., it will work with both Page::render()
	 * and Page::ajax_render().
	 *
	 * @param string $key A unique key for this JavaScript snippet. If blank, one will
	 *                    be auto-generated.
	 * @param string $code The JavaScript code itself
	 * @param boolean $overwrite If true, then this code will overwrite an existing
	 *                           snippet if they share the same key
	 */
	function js_run($key, $code, $overwrite=true) {
		$this->web->queue_js_run($key, $code, $overwrite);
	}

	/**
	 * Load an external JavaScript file through the next rendered template.  This
	 * method is AJAX-friendly, i.e., it will work with both Page::render()
	 * and Page::ajax_render().
	 *
	 * @param string $key A unique key for this JavaScript file.  If blank, one will
	 *                    be auto-generated.
	 * @param string The path to the JavaScript file.
	 */
	function js_load($key, $path='') {
		if($path == '') $path = $key;
		// try to figure out what sort of path/URL we were passed
		if(strpos($path, '://') === false && $path{0} != '/') {
			$path = $this->url('/js/'.$path.'.js', true);
		}
		$this->web->queue_js_load($key, $path);
	}

	/**
	 * Load an external CSS file through the next rendered template.  This
	 * method is AJAX-friendly, i.e., it will work with both Page::render()
	 * and Page::ajax_render().
	 *
	 * @param string $key A unique key for this CSS file.  If blank, one will
	 *                    be auto-generated.
	 * @param string The path to the CSS file.
	 */
	function css_load($key, $path='') {
		if($path == '') $path = $key;
		// try to figure out what sort of path/URL we were passed
		if(strpos($path, '://') === false && $path{0} != '/') {
			$path = $this->url('/css.php?c='.$path, true);
		}
		$this->web->queue_css_load($key, $path);
	}

	/**
	 * Include a CSS file via a normal "link rel=stylesheet" tag.
	 *
	 * @param string $path Path to CSS file, either absolute (http://...) or
	 *                     relative to base web root.
	 * @return string
	 */
	function css($path)
	{
		// try to figure out what sort of path/URL we were passed
		if(strpos($path, '://') === false && $path{0} != '/') {
			$path = $this->url('/css.php?c='.$path, true);
		}
		return '<link rel="stylesheet" type="text/css" href="'.$path.'" />'."\n";
	}

	/**
	 * Include a JavaScript file via a "script src=" tag.
	 *
	 * @param string $path Path to JS file, either absolute (http://...) or
	 *                     relative to base web root.
	 * @return string
	 */
	function js($path)
	{
		// try to figure out what sort of path/URL we were passed
		if(strpos($path, '://') === false && $path{0} != '/') {
			$path = $this->url('/js/'.$path.'.js', true);
		}
		return '<script type="text/javascript" src="'.$path.'"></script>'."\n";
	}

	/**
	 * Include a favicon graphic via a "link rel=shortcut" tag.
	 *
	 * @param string $path Path to favicon, either absolute (http://...) or
	 *                     relative to base web root.
	 * @return string
	 */
	function favicon($path)
	{
		// try to figure out what sort of path/URL we were passed
		if(strpos($path, '://') === false && $path{0} != '/') {
			$path = $this->url('/img/'.$path.'.ico', true);
		}
		return '<link rel="shortcut icon" href="'.$path.'" type="image/x-icon" />'."\n";
	}

	/**
	 * Generate an auto-discoverable RSS feed tag.
	 *
	 * @param string $url URL to RSS feed
	 * @param string $title Title of RSS feed
	 * @return string
	 */
	function rss_feed($url, $title)
	{
		// try to figure out what sort of path/URL we were passed
		if(strpos($path, '://') === false && $path{0} != '/') {
			$url = $this->full_url($url, true);
		}
		return '<link rel="alternate" type="application/rss+xml" title="'.$title.'" href="'.$url.'" />'."\n";
	}

	/**
	 * Generate an img tag.
	 *
	 * @param string $path Path to image, either absolute (http://...) or
	 *                     relative to base web root.
	 * @param string $alt Text for ALT tag
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function image($path, $alt='', $attribs=array())
	{
		// backwards compatibility with the old function prototype (path, attribs)
		if(is_array($alt)) {
			$attribs = $alt;
			$alt = '';
		}
		// try to figure out what sort of path/URL we were passed
		if(strpos($path, '://') === false && $path{0} != '/') {
			$path = $this->url('/img/'.$path, true);
		}
		$ret  = '<img src="'.$path.'" alt="'.$alt.'" border="0"';
		$ret .= $this->_attribs($attribs);
		return $ret.' />';
	}

	/**
	 * Convert a URL to an absolute form, path only (no http:// or FQDN)
	 *
	 * @param string $url
	 * @param bool $static If true, ignore DISPATCH_URL even if it's set.
	 *                     This is needed for building URLs to static resources
	 *                     such as images, JS, CSS, things that shouldn't be
	 *                     routed through the DISPATCH_URL.
	 * @return string
	 */
	function url($url, $static=false)
	{
		return url($url, $static);
	}

	/**
	 * Convert a URL to an absolute form, including http(s):// and FQDN
	 *
	 * @param string $url
	 * @param bool $static If true, ignore DISPATCH_URL even if it's set.
	 *                     This is needed for building URLs to static resources
	 *                     such as images, JS, CSS, things that shouldn't be
	 *                     routed through the DISPATCH_URL.
	 * @return string
	 */
	function full_url($url, $static=false)
	{
		return absolute_url($url, $static);
	}

	/**
	 * Create a new URL by adding or substituting in new query arguments
	 *
	 * @param string $base_url The URL to start with before adding/modifying the
	 *                         query arguments (defaults to the current URL if left blnak)
	 * @param array $args An associative array of name-value pairs of query arguments
	 *                    to add to $base_url
	 * @return string The final URL
	 */
	function composite_url($base_url, $args)
	{
		if(empty($base_url)) {
			if(defined('CURRENT_URL')) {
				$base_url = url(CURRENT_URL);
			} else {
				$base_url = url('/');
			}
		}

		// disassemble the current url and map out the query args
		$parts = parse_url($base_url);
		$query = array();
		foreach(explode('&', $parts['query']) as $k=>$v) if($v) $query[$k] = $v;

		// now add (or sub) in the new ones
		foreach($args as $k=>$v) $query[$k] = $v;

		// and rebuild
		$final = $parts['path'].'?';
		foreach($query as $k=>$v) $final .= "$k=$v&";
		// trim off the final char, works if $final is empty as well
		$final = substr($final, 0, -1);

		return $final;
	}

	/**
	 * Generate an anchor/href tag
	 *
	 * @param string $text Link text
	 * @param string $url URL
	 * @param string $confirm Popup confirmation before going to link
	 * @param mixed $popup Make link appear in a popup window.  If true, use
	 *                     default dimensions.  If a string, use the specified
	 *                     dimensions (eg, "800x600").
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function link($text, $url, $confirm='', $popup=false, $attribs=array())
	{
		if($popup) {
			$ret = '<a href="'.$this->_popup_href($popup, $url).'"';
		} else {
			$ret = '<a href="'.$url.'"';
		}
		if($confirm) {
			$confirm = str_replace("'", "\\'", $confirm);
			$ret .= ' onClick="if(!confirm(\''.$confirm.'\')){event.cancelBubble=true;return false;};"';
		}
		$ret .= $this->_attribs($attribs);
		$ret .= '>'.$text.'</a>';
		return $ret;
	}

	/**
	 * Generate a rounded-corner link with optional icon
	 *
	 * @param string $text Link text
	 * @param string $url URL
	 * @param string $icon Icon image URL (optional)
	 * @param string $confirm Popup confirmation before going to link
	 * @param mixed $popup Make link appear in a popup window.  If true, use
	 *                     default dimensions.  If a string, use the specified
	 *                     dimensions (eg, "800x600").
	 * @param array $attribs Additional HTML attributes (for parent <div>)
	 * @return string
	 */
	function link_button($text, $url, $icon='', $confirm='', $popup=false, $attribs=array())
	{
		if($popup) {
			$lnk = '<a href="'.$this->_popup_href($popup, $url).'"';
		} else {
			$lnk = '<a href="'.$url.'"';
		}
		if($confirm) {
			$lnk .= ' onClick="return confirm(\''.$confirm.'\')"';
		}
		$lnk .= '>';
		$ret  = '<div class="link_button"';
		$ret .= $this->_attribs($attribs);
		$ret .= ">$lnk";
		if($icon) {
			$ret .= $this->image($icon, array('align'=>'bottom'));
			$ret .= "<span>$text</span></a></div>";
		} else {
			$ret .= "<span style=\"width:100%\">$text</span></a></div>";
		}
		return $ret;
	}

	/**
	 * Generate a button input tag
	 *
	 * @param string $text Link text
	 * @param string $url URL
	 * @param string $confirm Popup confirmation before going to link
	 * @param bool $popup Make link appear in a popup window
	 * @param string $formanme If set, override the action of this form to point
	 *                         to the URL of this button, and submit the form.
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function button($text, $url='', $confirm='', $popup=false, $formname='', $attribs=array())
	{
		$onclick = '';
		if($confirm) {
			$onclick .= 'if(confirm(\''.$confirm.'\')) {';
		}
		if($popup) {
			$onclick .= "a=window.open('".$url."','newWin',";
			$onclick .= "'toolbar=no,location=no,directories=no,status=yes,menubar=no,";
			$onclick .= "resizable=yes,copyhistory=no,scrollbars=yes,width=640,height=480');";
			$onclick .= "a.focus();";
		} else if($url) {
			if($formname) {
				$onclick .= "document.forms.$formname.action='".$url."';document.forms.$formname.submit();";
			} else {
				$onclick .= "location.href='".$url."';";
			}
		}
		if($confirm) {
			$onclick .= "}";
		}

		$ret  = "<input type=\"button\" class=\"submit\" value=\"$text\"";
		if(!empty($onclick)) $ret .= " onClick=\"$onclick\"";
		$ret .= $this->_attribs($attribs);
		$ret .= " />";
		return $ret;
	}

	/*
	 * Generate the necessary javascript to open a link in a new popup window.
	 */
	function _popup_href($popup, $url)
	{
		if($popup === true) {
			$x = 640;
			$y = 480;
		} else {
			$dims = explode('x', $popup);
			$x = $dims[0];
			$y = $dims[1];
		}
		$lnk  = "javascript:a=window.open('".$url."','newWin',";
		$lnk .= "'toolbar=no,location=no,directories=no,status=yes,menubar=no,";
		$lnk .= "resizable=yes,copyhistory=no,scrollbars=yes,width=$x,height=$y');";
		$lnk .= "a.focus();";
		return $lnk;
	}

	function _attribs($attribs)
	{
		$ret = '';
		foreach($attribs as $k=>$v) {
			$ret .= " $k=\"$v\"";
		}
		return $ret;
	}

}

?>