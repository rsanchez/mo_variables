# Mo' Variables #

Add more early-parsed, global variables to your EE installation.

## Installation

* Copy the /system/expressionengine/third_party/mo_variables/ folder to your /system/expressionengine/third_party/ folder
* Activate extensions and enable the Mo' Variables extension
* Go to extension settings and enable the Mo' Variables that you want to use

## Usage

* Ajax Detect Conditional: {if ajax}
* Secure SSL/HTTPS Conditional and Variable: {if secure} {secure_site_url}
* GET: {get:your_key} or {embed:get:your_key}
* GET and POST: {get_post:your_key} or {embed:get_post:your_key}
* POST: {post:your_key} or {embed:post:your_key}
* Cookies: {cookie:your_key} or {embed:cookie:your_key}
* Reverse Segments: {rev_segment_1}, {rev_segment_2}, etc.
* Segments Starting From X: {segments_from_1}, {segments_from_2}, etc.
* Pagination Detect Conditional and Page Offset: {if paginated}, {page_offset}
* Archive Detect Conditional (detects presence of year, month, date in URI): {if archive} {if yearly_archive} {if monthly_archive} {if daily_archive}
* Theme Folder URL: {theme_folder_url}
* Current Page URL: {current_url}

For the get, post, get_post, and cookie variables, you can use the {embed:xxx:your_key} syntax, which will prevent unparsed tags when there is no key matching "your_key".

## Examples

###Ajax Pagination with graceful degradation using {ajax}

	{if !ajax}
		{embed="_globals/header"}
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$("p#pagination a").live("click", function(event){
				event.preventDefault();
				$("body").load($(this).attr("href"));
				return false;
			});
		});
		</script>
		</head>
		<body>
	{/if}
	
	{exp:channel:entries channel="products"}
	
		{paginate}
		
		<p id="pagination">Page {current_page} of {total_pages} pages {pagination_links}</p>
		
		{/paginate}
	
	{/exp:channel:entries}
	
	{if !ajax}
		{embed="_globals/footer"}
	{/if}

###HTTPS Redirection using {secure}