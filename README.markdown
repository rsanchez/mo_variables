# Mo' Variables #

Add more early-parsed, global variables to your EE installation.

## Installation

* Copy the /system/expressionengine/third_party/mo_variables/ folder to your /system/expressionengine/third_party/ folder
* Activate extensions and enable the Mo' Variables extension
* Go to extension settings and enable the Mo' Variables that you want to use

## Usage

* Ajax Detect Conditional: {if ajax}
* GET: {get:your_key} or {embed:get:your_key}
* GET and POST: {get_post:your_key} or {embed:get_post:your_key}
* POST: {post:your_key} or {embed:post:your_key}
* Cookies: {cookie:your_key} or {embed:cookie:your_key}
* Reverse Segments: {rev_segment_1}, {rev_segment_2}, etc.
* Segments Starting From X: {segments_from_1}, {segments_from_2}, etc.
* Pagination Detect Conditional: {if paginated}
* Archive Detect Conditional (detects presence of year, month, date in URI): {if archive} {if yearly_archive} {if monthly_archive} {if daily_archive}

For the get, post, get_post, and cookie variables, you can use the {embed:xxx:your_key} syntax, which will prevent unparsed tags when there is no key matching "your_key".