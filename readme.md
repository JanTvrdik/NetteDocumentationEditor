Editor for Nette Documentation
==============================


Usage
-----

**Opening a page**

* Accepted page identifiers are
	* Page URL on nette.org, e.g. `doc.nette.org/en/components`
	* Branch and path in repository in format `<branch>:<path>`, e.g. `doc-2.0:cs/homepage.texy`
* You can open non-existent page, it will be created when you save it for the first time.

**Saving a page**

* You need to open a page first to be able to save it.
* You need to have a push permission to target repository, pull requests are not yet supported.
* Your identity is verified using OAuth. The only required permission is access to your email which is required for
creating commits. You can [revoke the permission](https://github.com/settings/applications) at any time.


Installation
------------

1. Copy `app/config.local.example.neon` to `app/config.local.neon`.
2. Create a new GitHub application at https://github.com/settings/applications.
3. Set `clientId` and `clientSecret` in config.
4. Create a new Personal Access Token at https://github.com/settings/applications.
5. Set `accessToken` in config.
6. Set `repoOwner` and `repoName` in config.


Required Tools
--------------

**Composer**

* The dependencies are intentionally part of the repository.
* The dependencies should be updated with command<br>`composer.phar update --prefer-dist --optimize-autoloader`.


**TypeScript**

* Requires TypeScript compiler version 0.9.1 or newer.
* Run watch script `./www/static/js/watch.sh`
* or compile it manually with command<br>`tsc "www/static/js/init" --target ES5 --allowbool --out "www/static/js/main.js"`.

**LESS**

* Compile `www/static/css/screen.less` to `www/static/css/screen.css` using tool of your own choice.


License
-------

The MIT License (MIT)

Copyright (c) 2013 Jan Tvrdík

Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.
