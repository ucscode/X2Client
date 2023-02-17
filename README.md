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

By HTML 5 related syntax, I mean it's not really HTML but it follows a similar pattern making it easier to understand.

## HOW TO INCLUDE

1. Download the repo
