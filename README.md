# X2Client

Convert HTML 5 related syntax into Table format supported by all email clients

One of the biggest pain ever encountered in coding is sending email. As advance as browsers are getting, the support for HTML 5 syntax is not versatile in email clients.

Rather that writing pure HTML code, you'll have to code using table. I think of it as a new revolution developing forced to embrace the coding mechanism of 1998.

A lot of email templates are available on the internet, the only problem is that they're just templates. I mean after downloading them, you'll still have to write your own custom code into the template. Which means, the email client will support the template but will likely not support your codes within the template.

#### These are several actions that makes email difficult to write

1. Most email client will remove internal CSS
2. Some email client will remove or override inline styles in elements such as **P**
3. You must build a series of tables that incorporate all the inline styling necessary
4. Email clients may require attribute declaration rather than style. E.g `width='100%'` instead of `style='width:100%'`
5. You have to do unlimited testing on multiple email client
6. There are no universal rules on how email clients should render emails
7. If you have to style all `<td>` element, internal CSS will fail. Therefore, you have to inline style each and every `<td>` element accordingly
8. In email the use of `<h1>` to `<h6>` and `<p>` tags leads to missing margins. Therefore, `<td>`  is used to style the text instead.
9. The `<ol>` and `<ul>` tags do not work on all email clients. Thus, ordered and unordered lists are coded with tables.

And Others! Although most of these problems can be resolved with a few techniques. The biggest frustration in coding email template still remain the use of nested tables and table rows.

## What is X2Client?

X2Client is a PHP library that converts HTML 5 related syntax into table format that is supported by most email client.

By HTML 5 related syntax, I mean it's not really HTML but an XML that follows a similar coding convention making it easier to understand.

## HOW TO USE

```php
<?php 

  require_once __DIR__ . "/X2Client.php";
  
  $EMAIL_STRING = "
  
    <x2:div>
    
      <x2:div class='my-class'>
      
        <x2:p>This is a paragraph</x2:p>
        
      </x2:div>
      
    </x2:div>
    
  ";
  
  $X2Client = new X2Client( $EMAIL_STRING )
  
  echo $X2Client->render();
  
```

X2Client uses the same syntax as HTML 5, except that it begins with a prefix `x2:`.

The `x2` prefix is what differentiates it from normal HTML 5 which enables it render into table set that are suitable for emails

#### What can X2Client help you do?

1. Convert Internal CSS into inline CSS 
2. Convert block tag such as `div` or `p` into `table` or `td` where applicable
3. Build a series of properly organized table with just a few line of code
4. Add necessary attribute and style for multiple email client support
5. Enable you code less, test less and achieve good result
6. Ease the frustration that comes from coding email

### HOW IT WORKS

You have to write you regular HTML 5 stynax with each tag following the prefix `x2`

```php

$EMAIL_HTML = "

  <x2:html>
  
    <x2:head>
    
      <x2:style>
        
        .main {
          margin: auto;
          color: #000;
          padding-top: 4rem;
        }
        
        /* 
          Applicable to all P Tags
          -------------------------
          You can either use p {} or x2:p {} in the css selector. both will work
       */
        
        p {
          font-size: 1rem;
          text-align: left;
        }
        
        x2:div.nested {
          margin-right: auto;
          display: block
        }
        
      </x2:style>
      
    </x2:head>
    
    <x2:body>
    
      <x2:div class='main'>
      
        <x2:p>This is is the first paragraph</x2:p>
        
        <x2:div class='nested'>
        
          <div>This is a nested block</div>
          
          <x2:p>And this is a nested <x2:span>paragraph</x2:span></x2:p>
          
        </x2:div>
        
      </x2:div>
      
    </x2:body>
    
  </x2:html>
  
 ";
```

Next, you have to render the output using the X2Client instance;

```php
  
  require_once "Path/To/X2Client.php";
  
  $X2Client = new X2Client( $EMAIL_HTML );
  
  echo $X2Client->render();
  
```

### The Output

```php



<html>
  <head>
    <style>
        .main {
          margin: auto;
          color: #000;
          padding-top: 4rem;
        }
        
        
        
        p {
          font-size: 1rem;
          text-align: left;
        }
        
        div.nested {
          margin-right: auto;
          display: block
        }
        
      </style>
  </head>
  <body>
    <table width="100%" align="left" border="0" cellspacing="0" cellpadding="0" style="max-width: 100%; table-layout: fixed; word-break: break-word;">
      <tr>
        <td class="main" style="margin: auto; color: #000; padding-top: 4rem" data-marker=".main">
          <table width="100%" align="left" border="0" cellspacing="0" cellpadding="0" style="max-width: 100%; table-layout: fixed; word-break: break-word;">
            <tr>
              <td style="font-size: 1rem; text-align: left" data-marker="p">This is is the first paragraph</td>
            </tr>
            <tr>
              <td class="nested" style="margin-right: auto; display: block" data-marker=".nested">
                <table width="100%" align="left" border="0" cellspacing="0" cellpadding="0" style="max-width: 100%; table-layout: fixed; word-break: break-word;">
                  <tr>
                    <td data-marker="div">This is a nested block</td>
                  </tr>
                  <tr>
                    <td style="font-size: 1rem; text-align: left" data-marker="p">And this is a nested <span>paragraph</span></td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>


```

It's that simple!

For the reference, you can make an element display in columns (as in flex) by adding `display='flex'` to the block element

```php

<x2:div display='flex'>

  <x2:div /> <!-- flex -->
  
  <x2:div /> <!-- flex -->
  
  <x2:div>  <!-- flex -->
  
    <x2:div /> <!-- not flex -->
    
  </x2:div> 
  
</x2:div>

```

