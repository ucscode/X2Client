<?php 

/*

	Project Name: X2Client
	
	Description: Convert HTML 5 related syntax into Table format supported by all email clients
	
	Difficulty: Easy to use
	
	Version: 1.0
	
	Production Date: 17th Feburary 2023
	
	Author: UCSCODE
	
	Author Website: https://ucscode.me
	
	Author Profile: https://github.com/ucscode
	
*/

class X2Client {
	
	protected $namespace = "x2";
	protected $domain = 'https://ucscode.me/x2client';
	protected $dom;
	protected $hashTag;
	protected $errors;
	protected $cssRules;
	
	protected $block = array(
		"div",
		"p"
	);
	
	public function __construct( $syntax ) {
		
		// Prevent Output Of XML Error;
		
		libxml_use_internal_errors(true);
		
		# I'M NOT PERFECT! BUT I'LL TRY MY BEST TO HANDLE SOME BASIC XML ERRORS:
		# LET'S DO THIS
		
		/* 
			ERROR 1: EntityRef: expecting ';'
			
			SOLUTION: Replace & with &amp; only if it is not a valid HTML Entity;
		*/
		
		$syntax = preg_replace("/&(?!([\w\n]{2,7}|#[\d]{1,4});)/","&amp;", $syntax);
		
		/*
			ERROR 2: Opening and ending tag mismatch: ...
			
			SOLUTION: Replace self-closing tags such as <br> with <br/>
		*/
		
		$syntax = $this->handleMismatchedTags( $syntax );
		
		/*
			ERROR 3: Entity 'x' not defined
			Where x can be nbsp, mdash etc;
			
			These entities are valid for HTML but not valid for XML.
			So! No fix available :(
			
			Wait!!! According to my research in:
			
			https://stackoverflow.com/questions/4645738/domdocument-appendxml-with-special-characters
			
			The only character entities that have an "actual name" defined (instead of using a numeric reference) are:
			
			- &amp; 
			- &lt; 
			- &gt; 
			- &quot
			- &apos;
			
			That means you have to use the numeric equivalent...
			
			---------------------------------------------------------
			
			SOLUTION: Let's get a list of all HTML entities and convert them to their numeric equivalence;
			
			Thanks to - https://github.com/w3c/html/blob/master/entities.json
			
		*/
		
		$entities = json_decode( file_get_contents( __DIR__ . "/entities.json" ), true );
		
		$syntax = preg_replace_callback( "/" . implode("|", array_keys($entities)) . "/i", function($match) use($entities) {
			$key = $match[0];
			$value = '&#' . $entities[$key]['codepoints'][0] . ";";
			return $value;
		}, $syntax );
		
		/*
			ERROR 4: Error parsing attribute name
			
			I discovered this error when a CSS Comment was found on a style tag.
			
			/* ERROR CAUSE! * /
			
			SOLUTION: Remove CSS Comment Tags
		*/
		
		$expression = "~\/\*(.*?)(?=\*\/)\*\/~s";
		
		$syntax = preg_replace( $expression, '', $syntax );
		
		/*
			
			Base on research, another problem came from using attribute value with a name
			
			<cardId ="01"> instead of <card Id="01">
			
			SOLUTION: NONE!
			
			WHY? 
				- Because We can't tell whether 'cardId' is tag on it's own
				- We also cannot tell whether the separation should be <car dId="01"> or <cardI d="01">
			
			It's left for the developer to monitor the syntax and correct such error!
			
		*/
		
		/*
			I'll try fixing more errors when I find them!
			Let Proceed...
		*/
		
		/* 
			Now! Let's create a random string tag that we can use as the root element
			On this root element, we declare our namespace
		*/
		
		$this->hashTag = "_" . sha1( mt_rand() );
		
		$xml = "
			<{$this->namespace}:{$this->hashTag} xmlns:{$this->namespace}='{$this->domain}'>
				{$syntax}
			</{$this->namespace}:{$this->hashTag}>
		";
		
		/*
			Now! Let's Create DOMDocument and load the XML String;
		*/
		
		$this->dom = new DOMDocument( "1.0", "utf-8" );
		
		$this->dom->preserveWhiteSpace = false;
		$this->dom->formatOutput = true;

		$this->dom->loadXML( trim($xml) );
		
		/*
			Now! Let's get ready to make some advance search using DOMXPath;
			Since DOMDocument doesn't use css ;)
		*/
		
		$this->xpath = new DOMXPath( $this->dom );
		$this->xpath->registerNamespace( $this->{"namespace"}, $this->domain );
		
		/*
			Now Let's import a CSS to XPath Translator;
			
			I found this online and I love it because of it's simplicity
			
			https://github.com/PhpGt/CssXPath
		*/
		require_once __DIR__ . "/Translator.php";
		
		/*
			USAGE: 
			
			$xPath = new Gt\CssXPath\Translator( "css selector" );
			DOMXPath::Query( $xPath );
			
			Very simple!
			
			Now! Let's store the error.
			
			So Incase the XML Doesn't Load, We can easily check what's causing the problem;
			
		*/
		
		$this->errors = libxml_get_errors();
		
		/*
			After the 
				
				H2Client::__construct( $XML_STRING )
				
			The next thing is to render 
			
				H2Client::render();
			
			
		*/
		
	}
	
	public function __get($name) {
		return $this->{$name} ?? null;
	}
	
	protected function handleMismatchedTags( $syntax ) {
		/*
		
			With the method, we try to close self-closing tags that are not closed!
			such as using <br> instead of <br />
			
			Or better said:
			using <x2:br> instead of <x2:br />
			
		*/
		$tags = array(
			"area", 
			"base", 
			"br", 
			"col", 
			"embed", 
			"hr", 
			"img", 
			"input", 
			"link", 
			"meta", 
			"param", 
			"source", 
			"track", 
			"wbr"
		);
		foreach( $tags as $tagname ) {
			$expression = "~<({$tagname}|{$this->namespace}:{$tagname}[^>]*)~";
			$syntax = preg_replace_callback( $expression , function($matches) {
				$tag = $matches[0];
				if( substr($tag, -1) != "/" ) $tag .= "/";
				return $tag;
			}, $syntax );
		};
		return $syntax;
	}
	
	protected function transformNode( $element ) {
		
		/*
		
			This is where we convert namespace node into regular node such as
			
			<x2:a href=''> into <a href=''>
			
			This is also where we convert <x2:div /> or <x2:p />
			
			into <table /> or <td /> 
			
		*/
		
		if( !$element ) return;
		
		/*
			Let's search for the children of the element that should be transformed
			
			However, we cannot use a foreach loop!
			
			But Why :-/ ?
			
			Because we are going to be replacing the child nodes with lots of <table/>, </tr> and other regular non-namespace element.
			
			Since nodes are passed by reference, then, when we remove an element by replacing it with another one, 
			the element no longer exists in the node list, and the next one in line takes its position in the index. 
			Then when foreach hits the next iteration, and hence the next index, one will be effectively skipped
			
			And we definitely don't wanna skip any node!
			
			SOLUTION: Use for loop. :-)
			
		*/
		
		for( $x = 0; $x < $element->childNodes->length; $x++ ) {
			
			// Get the childNode;
			
			$node = $element->childNodes->item($x);
			
			/*
				Unfortunately, there are different kinds of node!
				But we want only element Nodes
			*/
			
			if( !$this->isElement( $node ) ) continue;
			
			/*
				Now let's get the original tagName;
				converting - x2:div into div
			*/
			
			$tag = $this->HTMLTag( $node->nodeName );
			
			/*
				If the tag is a block element such as DIV | P
				We convert it into a table.
				Otherwise, we replace it with an equivalent element that doesn't have namespace
			*/
			
			if( in_array($tag, $this->block) ) {
				$node = $this->convert2Table( $node );
			} else $node = $this->renameNode( $node, $tag );
			
			/*
				If the node has child Elements!
				Then that means we're not done yet.
				We just have to repeat the process again
			*/
			
			if( $node->childNodes->length ) $this->transformNode( $node );
			
		};
		
	}
	
	protected function HTMLTag( $nodeName ) {
		/*
			Get the original element name!
			We achieve this by removing the namespace from the tagName
		*/
		return str_replace( $this->{"namespace"} . ":", '', $nodeName );
	}
	
	protected function convert2Table( $node ) {
		
		// Create Table Element;
		
		$table = $this->dom->createElement('table');
		
		/*
		
			You can add display='flex' to a div element : <div id='' display='flex'>
			
			An it's child elements will be arranged in columns;
			
			No rows! 
			Sorry! Columns...
			
			Whatever, the child elements will be grouped vertically like a flexbox
			Rather than horizontally like regular block element!
			
			Should the tables data be arranged in rows;
		*/
		
		if( $node->parentNode->getAttribute('display') == 'flex' ) {
			$parentTr = $this->dom->createElement('tr');
		} else $parentTr = null;
		
		// Let's Create Child Element;
		
		foreach( $node->childNodes as $childNode ) {
			
			/*
				Check if the node is an empty string
				Empty string create irrelevant table rows 
				Making the table extremely huge and ugly
			*/
			
			if( $childNode->nodeType == 3 ) {
				$nodeValue = trim($childNode->nodeValue);
				if( empty($nodeValue) ) continue;
			};
			
			// create <td/> and fill it with the element content;
			
			$td = $this->dom->createElement('td');
			
			$td->appendChild( $childNode->cloneNode(true) );
			
			/* 
				create a new <tr />
				or append to the 
			*/
			
			$tr = $parentTr ?? $this->dom->createElement('tr');
			$tr->appendChild( $td );
			
			$this->styleTd( $td, $childNode );
			
			// If a new row is created, append it to the table;
			
			if( !$parentTr ) $table->appendChild( $tr );
			
		};
		
		/*
			If no new row was created, append the parent <tr />
		*/
		
		if( $parentTr ) $table->append( $parentTr );
		
		/*
			add the required attribute and style on the table;
		*/
		
		$this->styleTable( $table, $node );
		
		/*
			Now! Replace the node with the table element;
		*/
		
		$node->parentNode->replaceChild( $table, $node );
		
		return $table;
		
	}
	
	protected function styleTable( $table, $node ) {
		
		/*
		
			This table attribute advice was from MailMunch
			
			You have a better one? Let us know now!
			
		*/
		
		if( !$this->isElement( $node ) ) return;
		
		$attributes = array(
			"width" => '100%',
			"align" => 'left',
			"border" => 0,
			"cellspacing" => 0,
			"cellpadding" => 0,
			"style" => "max-width: 100%; table-layout: fixed; word-break: break-word;"
		);
		
		foreach( $attributes as $name => $value ) {
			$table->setAttribute( $name, $value );
		}
		
		$this->setMarker( $table, $node );
		
	}
	
	protected function styleTd( $td, $node ) {
		
		/*
			Right here, we inherit the style of the element
			By passing it to the <td />
		*/
		
		if( !$this->isElement($node) ) return;
		
		$attributes = array();
		
		foreach( $attributes as $name => $value ) {
			$td->setAttribute( $name, $value );
		};
		
		foreach( $node->attributes as $attr ) {
			if( $attr->name == 'style' ) {
				if( !in_array( $this->HTMLTag( $node->nodeName ), $this->block ) ) continue;
			} else if( in_array( $attr->name, ['class', 'href', 'src'] ) ) continue;
			$td->setAttribute( $attr->name, $attr->value );
		}
		
		$this->setMarker( $td, $node );
		
	}
	
	protected function isElement( $node ) {
		return $node->nodeType === 1;
	}
	
	protected function setMarker( $el, $node ) {
		
		/*
			Seriously, writing this script was so confusing!
			The script was tested with a complete HTML page loaded with syntax
			
			Anyway! The marker helps us leave a trace so we can use to know which element was converted to 
			<table /> or <td /> element.
			
			As well as identity the element parents after the conversion!
			
		*/
		
		if( !$this->isElement($node) ) return;
		
		$markers = array(
			'id' => '#',
			'class' => '.'
		);
		
		foreach( $markers as $attr => $selector ) {
			$value = trim( $node->getAttribute( $attr ) );
			if( !empty($value) ) {
				$el->setAttribute( "data-marker", "{$selector}{$value}" );
				return;
			};
		};
		
		$el->setAttribute( "data-marker", $this->HTMLTag( $node->nodeName ) );
		
	}
	
	protected function renameNode( $node, $tag ) {
		
		/*
		
			DOMDocument Node cannot be renamed.
			Therefore, we need to create a new element, assign the new name to it and replace the old element
			
			If you ask me once more why we use for loop to transformNode, I'll punch your face!
			
		*/
		
		$newNode = $this->dom->createElement( $tag );
		
		// preserve attributes;
		foreach( $node->attributes as $attr )
			$newNode->setAttribute( $attr->name, $attr->value );
			
		// preserve children
		foreach( $node->childNodes as $childNode ) 
			$newNode->appendChild( $childNode->cloneNode(TRUE) );
		
		$node->parentNode->replaceChild( $newNode, $node );
		
		return $newNode;
		
	}
	
	public function render() {
		
		/*
		
			The only public method availabe in this library after the __constructor();
			It either gives you the result you need or false
			
			If it returns false, then you should consider using X2Client::$errors to check for errors
			
		*/
		
		if( empty($this->errors) ) {
			
			// get the root element;
			
			$element = $this->dom->getElementsByTagNameNS( $this->domain, $this->hashTag )->item(0);
			
			/*
				Now! Convert Internal CSS into Inline CSS
				Unless you want email clients to stripe your <style /> tag and make your email look like shit!
			*/
			
			$this->captureCss( $element );
			
			/*
				This is the first instance where we call the transformNode;
				Inside it, a recursion occurs until all nodes are completely transformed
			*/
			
			$this->transformNode( $element );
			
			/*
				Let's get the final result!
			*/
			
			$result = '';
			
			foreach( $element->childNodes as $node ) 
				$result .= $this->dom->saveXML( $node ) . "\n";
			
			/*
				Hurray! We made it!
			*/
			
			return $result;
			
		} else return false;
		
	}
	
	protected function captureCss( $node ) {
		
		$rules = array();
		
		/*
		
			I'd like you to know that your style tag must also start with the x2: namespace
			
			<x2:style> Your style here </x2:style>
			
		*/
		
		// get all available style tags;
		
		$styles = $this->xpath->query( ".//{$this->namespace}:style", $node );
		
		// Make them inline;
		
		foreach( $styles as $style ) {
			$this->parse_css( $style->nodeValue, $rules );
		};
		
		// return the rules as an array;
		
		return $rules;
		
	}
	
	protected function parse_css( string $css, &$css_array ) {
		
		/*
			This css parser, created by me :] works like magic;
		*/
		
		$elements = explode('}', $css);
		
		foreach ($elements as $element) {
			
			$rule_break = array_filter( array_map('trim', explode('{', $element) ) );
			
			if( count($rule_break) < 2 ) continue;
			
			// get the name of the CSS element
			
			$name = trim($rule_break[0]);
			$name = preg_replace( "/\s+/", ' ', $name );
			$name = preg_replace( "/{$this->namespace}:/i", '', $name );
			
			if( substr($name, 0,1) == '@' ) continue;
			
			$xPath = (string)(new Gt\CssXPath\Translator( $name ));
			$xPath = preg_replace( "/\/\/(\w+)/i", "//{$this->namespace}:$1", $xPath );
			
			// get all the key:value pair styles
			$rules = array_filter( array_map('trim', explode(';', $rule_break[1])) );
			
			$container = array();
			
			// remove element name from first property element
			foreach( $rules as $rule ) {
				$style_break = array_map('trim', explode( ":", $rule ));
				$container[ $style_break[0] ] = $style_break[1];
			};
			
			if( array_key_exists($name, $css_array) ) {
				$css_array[ $name ] = array_merge( $css_array[ $name ], $container );
			} $css_array[ $name ] = $container;
			
			// convert the internal css into inline css;
			
			$this->injectInlineCss( $this->xpath->query( $xPath ), $container );
			
		}
		
		return $css_array;
		
	}
	
	protected function injectInlineCss( $nodes, $style ) {
		
		/*
			Convert the style from an array to a string
		*/
		
		$inlineStyle = [];
		
		foreach( $style as $key => $value ) {
			$inlineStyle[] = "{$key}: $value";
		};
		
		$inlineStyle = implode( "; ", $inlineStyle );
		
		// Now push the string into the node;
		
		foreach( $nodes as $node ) {
			$node->setAttribute( 'style', $inlineStyle );
		}
		
	}
	
}