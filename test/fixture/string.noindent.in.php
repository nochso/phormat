<?php
function x() {
	$pattern = '@^
(                         # Either..
	[/\\\\]               # absolute start
|   [a-z]:[/\\\\]         # or Windows drive path
|   [a-z][a-z0-9\.+-]+:// # or URI scheme:// - see http://tools.ietf.org/html/rfc3986#section-3.1
)@ix';
	$pattern = "@^
(                         # Either..
	[/\\\\]               # absolute start
|   [a-z]:[/\\\\]         # or Windows drive path
|   [a-z][a-z0-9\\.+-]+:// # or URI scheme:// - see http://tools.ietf.org/html/rfc3986#section-3.1
)@ix";
}
