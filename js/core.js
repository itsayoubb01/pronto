/**
 * The ubiquitous Pronto class.  Can be referenced from templates
 * as "pronto".
 */
Pronto = function(web_root) {
	this.CSS_LOADED  = new Array();
	this.JS_LOADED   = new Array();

	this.DIR_WS_BASE = web_root;

	/**
	 * Build a URL relative to the base web root
	 */
	this.url = function(relurl) {
		return this.DIR_WS_BASE + relurl;
	}

	/**
	 * Load a CSS file.
	 * @param string filename Filename, relative to the app's /css directory.
	 *                        Do not include the .css extension.
	 */
	this.load_css = function(filename) {
		if(jQuery.inArray(filename, this.CSS_LOADED) == -1) {
			var f = document.createElement("link");
			f.setAttribute("rel", "stylesheet");
			f.setAttribute("type", "text/css");
			f.setAttribute("href", this.url("/css.php?c="+filename));
			document.getElementsByTagName("head").item(0).appendChild(f);

			this.CSS_LOADED.push(filename);
		}
	}

	/**
	 * Load a JavaScript file.
	 * @param string filename Filename, relative to the app's /js directory.
	 *                        Do not include the .js extension.
	 * @param function func Optional function to execute once script is loaded.
	 *                      If the JavaScript file has already been loaded,
	 *                      this function will be executed immediately.
	 */
	this.load_js = function(filename, func) {
		var func = func || function(){};
		if(jQuery.inArray(filename, this.JS_LOADED) == -1) {
			jQuery.getScript(this.url("/js/"+filename+".js"), func);
			this.JS_LOADED.push(filename);
		} else {
			func();
		}
	}

	/**
	 * Retrieve the browser's timezone offset (in seconds) and store it in
	 * a cookie.
	 */
	this.tz_offset = function(cookie_name) {
		var cn = cookie_name || "tz_offset";
		var d = new Date();
		var tzoff = d.getTimezoneOffset() * 60 * -1;

		d.setDate(d.getDate() + 1); // expires in 1 day
		document.cookie = cn + "="  + escape(tzoff) + ";expires=" + d.toGMTString();
	}
};