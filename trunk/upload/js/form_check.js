/**
* k4 Bulletin Board, form_check.js
*
* Copyright (c) 2005, Geoffrey Goodman
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Geoffrey Goodman
* @version $Id: form_check.js,v 1.1 2005/04/05 03:22:59 k4st Exp $
* @package k42
*/

var elements = new Array();
var matches = new Array();
var regexs = new Array();
var errors = new Array();
var messages = new Array();
var error_classes = new Array();
var base_classes = new Array();

function resetErrors() {
	for (var i = 0; i < elements.length; i++)
	{
		var error = document.getElementById(errors[i]);
		if (error) error.style.display = 'none';

		var element = document.getElementById(elements[i]);
		if (element) element.className = base_classes[i];

		var message = document.getElementById(messages[i]);
		if (message) message.style.display = 'block';
	}
}

function showError(num)
{
	var error = document.getElementById(errors[num]);
	if (error) error.style.display = 'block';

	var element = document.getElementById(elements[num]);
	if (element) element.className = error_classes[num];

	var message = document.getElementById(messages[num]);
	if (message) message.style.display = 'none';
}
function checkForm(form)
{
	var valid = true;

	resetErrors();

	for (var i = 0; i < form.elements.length; i++)
	{
		var element = form.elements[i];
		for (var j = 0; j < elements.length; j++)
		{
			if (elements[j] == element.id)
			{
				var value = (element.options) ? element[element.selectedIndex].value : element.value;
				if (regexs[j] != '' && !regexs[j].test(value))
				{
					showError(j);
					valid = false;
					break;
				}
				if (matches[j]) {
					var match = document.getElementById(matches[j]);

					if (element.value != match.value)
					{
						element.value = '';
						match.value = '';

						showError(j);
						valid = false;
						break;
					}
				}
			}
		}
	}

	return valid;
}
function addMessage(id, message)
{
	for (var i = 0; i < elements.length; i++) {
		if (elements[i] == id)
		{
			messages[i] = message;
		}
	}
}
function addVerification(id, regex, error, classname)
{
	var num = elements.length;

	elements[num] = id;
	regexs[num] = new RegExp('^'+regex+'$');
	matches[num] = '';
	errors[num] = error;

	element = document.getElementById(id);
	base_classes[num] = element.className;
	error_classes[num] = (classname) ? classname : element.className;
}
function addCompare(id, match, error, classname)
{
	var num = elements.length;

	elements[num] = id;
	regexs[num] = '';
	matches[num] = match;
	errors[num] = error;

	element = document.getElementById(id);
	base_classes[num] = element.className;
	error_classes[num] = (classname) ? classname : element.className;
}