/**
 * k4 Bulletin Board, Geoffrey Checker
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Geoffrey Goodman
 * @version $Id$
 * @package k4bb
 */


var elements = [];
var matches = [];
var regexs = [];
var errors = [];
var messages = [];
var error_classes = [];
var base_classes = [];

function resetErrors() {
	for (var i = 0; i < FA.sizeOf(elements); i++)
	{
		if(typeof(errors[i]) != 'undefined') {
			var error = FA.getObj(errors[i]);
			if (error) {
				error.style.display = 'none';
			}
		}
		if(typeof(elements[i]) != 'undefined') {
			var element = FA.getObj(elements[i]);
			if (element) {
				element.className = base_classes[i];
			}
		}
		if(typeof(messages[i]) != 'undefined') {
			var message = FA.getObj(messages[i]);
			if (message) {
				message.style.display = 'block';
			}
		}
	}
}

function showError(num)
{
	var error = FA.getObj(errors[num]);
	if (error) {
		FA.show(error);
	}

	var element = FA.getObj(elements[num]);
	if (element) {
		element.className = error_classes[num];
	}

	var message = FA.getObj(messages[num]);
	if (message) {
		FA.hide(message);
	}
}
function checkForm(form)
{
	var valid = true;

	resetErrors();

	for (var i = 0; i < FA.sizeOf(form.elements); i++) {
		var element = form.elements[i];
		for (var j = 0; j < FA.sizeOf(elements); j++)
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
				if(typeof matches != 'undefined' && matches) {
					if (matches[j]) {
						var match = FA.getObj(matches[j]);
						if(typeof(match) != 'undefined' && match) {
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
		}
	}

	return valid;
}
function addMessage(id, message)
{
	for (var i = 0; i < FA.sizeOf(elements); i++) {
		if (elements[i] == id)
		{
			messages[i] = message;
		}
	}
}
function addVerification(id, regex, error, errorclassname)
{
	var num = FA.sizeOf(elements);

	elements[num] = id;
	regexs[num] = new RegExp('^'+regex+'$');
	matches[num] = '';
	errors[num] = error;

	element = FA.getObj(id);
	base_classes[num] = element.className;
	error_classes[num] = (errorclassname && errorclassname != '') ? errorclassname : element.className;
}
function addCompare(id, match, error, errorclassname)
{
	var num = elements.length;

	elements[num] = id;
	regexs[num] = '';
	matches[num] = match;
	errors[num] = error;

	element = FA.getObj(id);
	base_classes[num] = element.className;
	error_classes[num] = (errorclassname && errorclassname != '') ? errorclassname : element.className;
}