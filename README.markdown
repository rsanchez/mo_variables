# Mo' Variables #

Adds many useful early-parsed global variables and conditionals to use in your templates.

## Installation

***Compatibility***

* ExpressionEngine 2.9+
* ExpressionEngine 3.0+
* ExpressionEngine 4.0+

For earlier versions of EE, use [version 1.1.7](https://github.com/rsanchez/mo_variables/tree/v1.1.7).

***Installation for ExpressionEngine 3.0+***
* Copy the /system/expressionengine/third_party/mo_variables/ folder to your /system/user/addons/ folder
* Activate extensions and enable the Mo' Variables extension
* Go to extension settings and enable the Mo' Variables that you want to use

***Installation for _legacy_ ExpressionEngine 2.x***
* Copy the /system/expressionengine/third_party/mo_variables/ folder to your /system/expressionengine/third_party/ folder
* Activate extensions and enable the Mo' Variables extension
* Go to extension settings and enable the Mo' Variables that you want to use

## Upgrading from 1.3.0 and below

All input variables, like `{get:your_key}`, `{get_post:your_key}`, `{post:your_key}`, and `{cookie:your_key}`, are now escaped using `htmlentities`.

## Upgrading from 1.1.7 and below

The syntax for [Early-parsed Member Group Conditionals](#early-parsed-member-group-conditionals) and [Early-parsed Member ID Conditionals](#early-parsed-member-id-conditionals) has changed in 1.2. Be sure to update your templates accordingly.

## Usage

#### Ajax Detect Conditional

```
{if ajax}{/if}

{if not_ajax}{/if}
```

#### Secure SSL/HTTPS Conditional and Variable

```
{if secure}{/if}

{if not_secure}{/if}

{secure_site_url}

{insecure_site_url}
```

#### GET

```
{get:your_key}
```

#### GET and POST

```
{get_post:your_key}
```

#### POST

```
{post:your_key}
```

#### Cookies

```
{cookie:your_key}
```

#### Session Flashdata

```
{flashdata:your_key}
```

#### Page Tracker

```
{!-- a full url, e.g. http://yoursite.com/site/foo --}
{last_page_visited}

{!-- just the URI portion of the url, e.g. site/foo --}
{last_uri_visited}

{one_page_ago}

{one_uri_ago}

{two_pages_ago}

{two_uris_ago}

{three_pages_ago}

{three_uris_ago}

{four_pages_ago}

{four_uris_ago}

{five_pages_ago}

{five_uris_ago}
```

#### Reverse Segments

```
{rev_segment_1}

{rev_segment_2}

etc.
```

#### Segments Starting From X

```
{segments_from_1}

{segments_from_2}

etc.
```

#### Pagination Detect Conditional, Page Offset and Pagination Base URL/URI

```
{if paginated}{/if}

{page_offset}

{pagination_base_url}

{pagination_base_uri}
```

#### Archive Detect Conditional (detects presence of year, month, date in URI)

```
{if archive}{/if}

{if yearly_archive}{/if}

{if monthly_archive}{/if}

{if daily_archive}{/if}

{if not_archive}{/if}

{if not_yearly_archive}{/if}

{if not_monthly_archive}{/if}

{if not_daily_archive}{/if}
```

#### Category Detect Conditional (detects presence of reserved category word in URI)

```
{if categorized}{/if}
```

#### Reserved Category Word

```
{reserved_category_word}
```

#### Current Page URL

```
{current_url}

{current_url_encoded}

{uri_string}

{uri_string_encoded}

{query_string}

{hostname}

{http_host}

{server_name}
```

#### Early-parsed Member Variables (for use as tag parameters)

```
{logged_in_member_id}

{logged_in_group_id}

{logged_in_username}

{logged_in_screen_name}

{logged_in_email}
```

#### Early-parsed Member Group Conditionals

```
{if in_group_1_2_3}{/if}

{if not_in_group_1_2_3}{/if}
```

#### Early-parsed Member ID Conditionals

```
{if has_member_id_1_2_3}{/if}

{if not_has_member_id_1_2_3}{/if}
```

#### Early-parsed Mobile Detection Conditionals

```
{if is_mobile}

{if is_not_mobile}

{if is_tablet}

{if is_not_tablet}

{if is_phone}

{if is_not_phone}
```

## Change Log

#### v1.3.0

-	Added ExpressionEngine 3 compatibility

#### v1.2.2

-	Updated Mobile_Detect.php to version 2.8.17
-	Added {http_host} and {server_name} to Current URL set of variables

#### v1.2.1

-	Added {hostname} to Current URL set of variables
-	Added uri variables to Page Tracker variables

#### v1.2.0

-	EE 2.9 compatibility
-	Changed Member Group and Member ID conditionals syntax

#### v1.1.6

-	  added {if has_member_id(1|2|3)} and {if not_has_member_id(1|2|3)} conditionals
-	  added {if is_mobile} {if is_not_mobile} {if is_phone} {if is_not_phone} {if is_tablet} {if is_not_tablet} conditionals

#### v1.1.5

-   added {current_url_encoded} and {uri_string_encoded} variables

#### v1.1.4

-   added {query_string} variable

#### v1.1.3

-   added {flashdata:your_key} variables

#### v1.1.2

-   added ability to set default values for get/get_post/post/cookie variables

#### v1.1.1

-   added {pagination_base_uri} variable
-   added {if categorized} conditional (thanks @timkelty)
-   added {reserved_category_word} variable (thanks @timkelty)
-   added {insecure_site_url} variable (thanks @eedfwChris)

#### v1.1.0

-    added {pagination_base_url} variable

#### v1.0.9

-	added {if in_group(1|2|3)} and {if not_in_group(1|2|3)} conditionals

#### v1.0.8

-   changed hooks used from sessions_end to template_fetch_template, thereby changing EE version requirement to 2.4+
-   added opposites to all conditional variables: {if not_paginated}, {if not_ajax}, {if not_archive}, {if not_daily_archive}, {if not_monthy_archive}, {if not_yearly_archive}, {if not_secure}
-   fully deprecated and remoted {theme_folder_url}, which is native in EE 2.4+
-   added early-parsed member variables, ie. {logged_in_member_id}

#### v1.0.7

-   fixed bug where {if paginated} did not work with Structure

#### v1.0.6

-   removed {current_page} variable in Page Tracker, conflicts with the variable of the same name in paginate tag pair (sorry to anyone who used {current_page})

#### v1.0.5

-   fixed bug where if your DB settings were manually altered, this could throw fatal errors

#### v1.0.4

-   added {uri_string} variable
-   fixed bug when using {if paginated} and no url segments (like on homepage for instance)
-   added {secure_site_url} variable
-   added Page Tracker variables

#### v1.0.3

-   added {if secure} conditional

#### v1.0.2

-   added {current_url} variable
-   removed {theme_folder_url} in EE >= 2.1.5

#### v1.0.1

-   added {page_offset} and {theme_folder_url}

#### v1.0.0

-   initial release

## Examples

###Ajax Pagination with graceful degradation using {ajax}

	{if not_ajax}
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
	
	{if not_ajax}
		{embed="_globals/footer"}
	{/if}
