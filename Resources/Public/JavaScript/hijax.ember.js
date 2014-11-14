/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Essential Dots d.o.o. Belgrade
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

ExtbaseHijax = {};
ExtbaseHijax.Application = Ember.Application.extend({
	$rootElement: null,
	$globalLoader: null,
	$globalTarget: null,
    state: Ember.Object.create({}),
	initialize: function(router) {
		$rootElement = jQuery(this.rootElement);
		if ($rootElement.hasClass('hijax-element')) {
			this.$rootElement = $rootElement;
		} else {
			$rootElement.wrap('<div class="hijax-element"/>').wrap('<div class="hijax-content"/>');
			$rootElement.parent().parent().append('<div class="hijax-loading"/>');
			this.$rootElement = $rootElement.parent().parent();
		}
		this.$globalTarget = this.$rootElement.find('> .hijax-content');
		this.$globalLoader = this.$rootElement.find('> .hijax-loading');
		this._super(router);
	},
	ajaxCall: function(fn, settings) {
		fn.apply(this, [settings, this.$globalTarget, this.$globalLoader]);
	}
});


ExtbaseHijax.View = Ember.View.extend({
	didInsertElement: function () {
		this._super();
		Ember.run.schedule('render', this, function() {
			this.$().find('.hijax-element').extbaseHijax(true);
			jQuery('body').trigger('layout-init', this.$());
		});
	}
});


ExtbaseHijax.DOMReference = ExtbaseHijax.View.extend({
	selector: '',
	reference: null,
	defaultTemplate: Ember.Handlebars.compile('<div class="dom-reference"></div>'),
	init: function() {
		this.set('context', Ember.Object.create({selector: ''}));
		this._super();
	},
	didInsertElement: function () {
		this._super();
		this.get('context').set('selector', this.selector);
		Ember.run.schedule('render', this, function() {
			this.reference = jQuery(_evalStr.call(this.$(), this.get('selector')));
			if (this.reference) {
				this.reference.detach().prependTo(this.$().find('> .dom-reference'));
			}
		});
	},
	willDestroyElement: function() {
		var $recycler = jQuery('#dom-reference-recycler');
		if ($recycler.length==0) {
			$('body').append('<div id="dom-reference-recycler" style="position: absolute; visibility: hidden; overflow: hidden; height: 1px; width: 1px;"></div>');
			$recycler = jQuery('#dom-reference-recycler');
		}
		if (this.reference) {
			this.reference.detach().prependTo($recycler);
		}
		this._super();
	}
});


ExtbaseHijax.CObjectViewIdCounter = 0;
ExtbaseHijax.CObjectView = ExtbaseHijax.View.extend({
	typoScriptObjectPath: '',
	loaders: '',
	defaultTemplate: Ember.Handlebars.compile('<div class="hijax-element" id="ember-cobject-' + (ExtbaseHijax.CObjectViewIdCounter++) + '" {{bindAttr data-hijax-loaders="loaders"}} {{bindAttr data-hijax-ajax-tssource="typoScriptObjectPath"}} data-hijax-result-wrap="false" data-hijax-result-target="jQuery(this)" data-hijax-element-type="ajax"><div class="hijax-content"><p>&nbsp;</p></div><div class="hijax-loading"></div></div>'),
	init: function() {
		this.set('context', Ember.Object.create({
			typoScriptObjectPath: '',
			loaders: ''
		}));
		this._super();
	},
	didInsertElement: function () {
		this._super();
		this.get('context').set('typoScriptObjectPath', this.typoScriptObjectPath);
		this.get('context').set('loaders', this.loaders);
		Ember.run.schedule('render', this, function() {
			this.$().find('.hijax-element').extbaseHijax(true, true);
		});
	},
	willDestroyElement: function () {
	},
	afterRender: function(buffer) {
		this._super(buffer);
	}
});

ExtbaseHijax.Checkbox = Ember.Checkbox.extend({
	_checkedChanged: function(event) {
		this.$().trigger('change', event);
	}.observes('checked')
});