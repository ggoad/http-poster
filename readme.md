<h1>How to use</h1>
<h2>Using HttpSender</h2>
<p>
	To use the HttpSender, instantiate it like so:<br>
<pre>
use WAASender\HttpSender;

$config=[
	// associateive array of configs
];
$tokens=[
	// associative array of tokens,
	// basic uses 'user' & 'pass'
	// bearer uses 'bearer'
	// oauth1 uses 'consumerToken' 'consumerSecret' 'accessToken' 'accessSecret'
];


$sender= new HttpSender($config, $tokens);

</pre>


</p>
<h3>Configurations and Tokens</h3>
<ul>
	<li><b>Configurations: </b>
		<ul>
			<li>timeout: Wait time for response</li>
			<li>userAgent: UA to send with request </li>
			<li>contentType: encoding of request body</li>
			<li>authorization: type of authorization</li>
			<li>sinatureMethod: for Oauth</li>
			<li>retType: type of expected resonse</li>
			<li>There are others, that are set by the Go method</li>
			<li>You can also set any config in your class extensions and they will be accessible through the Config function.</li>
		</ul>
	</li>
	<li><b>Tokens: </b><ul>
		<li>bearer</li>
		<li>consumerKey (oauth1)</li>
		<li>consumerSecret (oauth1)</li>
		<li>accessToken (oauth1)</li>
		<li>accessSecret (oauth1)</li>
		<li>user (basic)</li>
		<li>pass (basic)</li>
		<li>You can also set any token in your class extensions and they will be accessible through the Token function.</li>
	</ul></li>
</ul>
<p>Then, there are two families of functions. Request functions, and configurations functions.</p>
<h3>Configuration Functions</h3>
<p>
The configurations functions set a configuration, and then return a reference to itself. 
This allows the configuration functions to be chained together, like so:
<code>$sender->Json()->Basic()->RetText();</code>
This would configure the request to have a JSON body, using basic authorizaiton, and returning raw text as a response. 
These configurations could be pre-set, but it's handy to be able to set them on the fly. 
</p>
<h4>A list of configurations functions</h4>
<ul>
	<li><b>Content Types</b><ul>
		<li>Urlencoded</li>
		<li>Json</li>
		<li>MultiPart</li>
	</ul></li>
	<li><b>Response Types</b><ul>
		<li>RetText</li>
		<li>RetJson</li>
	</ul></li>
	<li><b>Authorizations</b><ul>
		<li>None</li>
		<li>Bearer</li>
		<li>Basic</li>
		<li>Oauth1</li>
	</ul></li>
</ul>
<h3>Request Functions</h3>
<p>
The request functions are functions that dispatch the HTTP requests. 
They return a response. The most important members of the response are:
<ul>
	<li>success: bool</li>
	<li>rawResp: raw text response</li>
	<li>resp: the parsed response</li>
</ul>
</p>
<h4>A List of Request Functions</h4>
<p>
Some accept 2 arguments ($url, $body)
<span>&#42;  Accepts 3 aruguments ($url, $body, $bodyFiles)</span>
</p>
<ul>
	<li>Get</li>
	<li>Delete</li>
	<li>Post&#42;</li>
	<li>Put&#42; </li>
	<li>Patch&#42; </li>
	<li>Go : General case... accepts the request method first: <br>
	<code>$sender->Go("POST",$url,$body,$bodyFiles)</code></li>
</ul>
<h3>An Example Request</h3>
<p>
<pre>use WAASender\HttpSender;

$config=[
	// associateive array of configs
];
$tokens=[
	// associative array of tokens,
	// basic uses 'user' & 'pass'
	// bearer uses 'bearer'
	// oauth1 uses 'consumerToken' 'consumerSecret' 'accessToken' 'accessSecret'
];


$sender= new HttpSender($config, $tokens);

$resp=$sender->None()->RetText()->Get('https://greggoad.net');</pre>

This example used no authorization, and would return text, and get the website!

</p>

<h2>Extending the Class</h2>
<p>
Of course you can just extend the class, and if your use case is simple that's probably the best option.
However, the class 'SenderBlocker' is provided to fascilitate a locking behavior.
</p>
<p>
For instance: if you need to send multiple requests in a process, and one fails,
you can call: <code>return $this->Eject('message');</code>, and it will return
the last failed response, with the special message added to the end.
Or, your can call
<code>$this->Eject('message');</code><br>
and let execution continue along. All requests will fail, and the functions
that return responses will just return the last failed or unacceptable response. 
</p>
<h3>When Extending SenderBlocker</h3>
<p>
Prepend your request functions with a single underscore, and push the 
method name (sans-underscore) onto <code>$this->retRespArray</code>
</p>
<h2>SocialPoster</h2>
<p>
	SocialPoster is an abstract class that requires the 3 basic blog operations (Upload, Update, Remove).
	It is meant to connect your blog articles to your social, so there 
	is a blogConfiguration static variable. It's a path to a json file that has the members:
	
</p>
<pre>"site"           - string - site root,
"siteBlog"       - string - blog lister,
"siteBlogViewer" - string - blog article viewer,	
"imageFolder"    - string - file-system path to blog image parent folder</pre>
<h3>Social Configurations</h3>
<p>
	The individual social configurations files (json) are in src/conf, along with the blog config.
</p>
<h2>Happy Posting</h2>