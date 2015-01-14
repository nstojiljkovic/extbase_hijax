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

; (function($) {
	var elements = [], eventsToListen = {}, listeners = [], startedTimer = false, timerInterval = 10000, currentTimerTime = 0, uniqueIDCounter = 0, ajaxCallback = false, baseAnimationSpeed = 600;

	var _evalStr = function(str){
		try {
			return eval(str);
		} catch (e) {
			return '';
		}
	};

	/*
	 * Private methods
	 */
	var _redirectToURL = function(url) {
		if (typeof window.History != 'undefined' && window.History.enabled) {
			var rootUrl = History.getRootUrl(),
				urlScheme = url.match(/^(.*?):\/\//),
				rootUrlScheme = rootUrl.match(/^(.*?):\/\//),
				isInternalLink = (typeof url !== 'undefined' && url !== null) && (url.substring(0,rootUrl.length) === rootUrl || url.indexOf(':') === -1);

			urlScheme = urlScheme ? urlScheme[1] : null;
			rootUrlScheme = rootUrlScheme ? rootUrlScheme[1] : null;

			// 1. check if the URL is internal or not (by comparing domains)
			// 2. check scheme, as well do plain redirect for HTTP -> HTTPS or vice versa redirects
			if (!isInternalLink || (urlScheme && rootUrlScheme && urlScheme != rootUrlScheme)) {
				window.location = url;
			} else {
				try {
					EXTBASE_HIJAX.url = url;
					History.pushState({hijax: false, custom: false, tstamp: new Date().getTime()}, null, url);
				} catch (e) {
					window.location = url;
				}
			}
		} else {
			window.location = url;
		}

	};

	var _addElements = function(newElements) {
			// add unique element ID if it already doesn't have it
		$.each(newElements, function(i, element) {
			var id = $(element).attr('id');
			if (!id) {
				$(element).attr('id', 'hijax-'+(uniqueIDCounter++));
			}
		});
		var addedElements = [];

			// filter out duplicates and join the new elements to the existing array
		$.each(newElements, function(i, newElement) {
			var newId = $(newElement).attr('id');
			var notDuplicate = true;
			$.each(elements, function(i, element) {
				var id = $(element).attr('id');
				if (id==newId) {
					notDuplicate = false;
					return false;
				}
			});
			if (notDuplicate) {
				//var content = $(newElement).find('> .'+EXTBASE_HIJAX.contentClass);
				//$(newElement).css('height', content.outerHeight());
				elements.push(newElement);
				addedElements.push(newElement);
			}
		});

		return addedElements;
	};

	var _clearElements = function () {
		var removedElements = [];

		elements = $.grep(elements, function(element) {
			if ($(element).parent().length==0) {
				removedElements.push(element);
			}
			return $(element).parent().length==1;
		});

		var updatedListenerIds = [];

		$.each(removedElements, function(i, element) {
			var el = $(element);
			if (el.attr('data-hijax-element-type')=='listener') {
				var listenerId = el.attr('data-hijax-listener-id');
				updatedListenerIds.push(listenerId);
				if (typeof listeners[listenerId] == 'undefined') {
					listeners[listenerId] = [];
				}
				listeners[listenerId] = $.grep(listeners[listenerId], function(lEl) {
					return el[0]!=lEl[0];
				})
			}
		});

		$.each(updatedListenerIds, function(i, listenerId) {
			eventsToListen[listenerId] = [];
			$.each(listeners[listenerId], function(i, element) {
				var el = $(element);
				if (el.attr('data-hijax-listener-events') && el.attr('data-hijax-listener-events').length > 0) {
					var eventNames = _parseCSV(el.attr('data-hijax-listener-events'));
					_addEvents(listenerId, eventNames[0]);
				}
			});
		});

		return removedElements;
	};

	var _addEvents = function(listenerId, newEvents) {
		var addedEvents = [];

		if (typeof eventsToListen[listenerId] == 'undefined') {
			eventsToListen[listenerId] = [];
		}

			// filter out duplicates and join the new elements to the existing array
		$.each(newEvents, function(i, newEvent) {
			var notDuplicate = true;
			$.each(eventsToListen[listenerId], function(i, event) {
				if (newEvent==event) {
					notDuplicate = false;
					return false;
				}
			});
			if (notDuplicate) {
				eventsToListen[listenerId].push(newEvent);
				addedEvents.push(newEvent);
			}
		});

		return addedEvents;
	};

	var _timer = function () {
		currentTimerTime += timerInterval/1000;
		processElements = [];
		$.each(elements, function(i, element) {
			var elementTiming = parseInt($(element).attr('data-hijax-timing'), 10);
			if (currentTimerTime % elementTiming == 0) {
				processElements.push(element);
			}
		});
		_process(processElements);
	};

	var _parseCSV = function( strData, strDelimiter ){
			// Check to see if the delimiter is defined. If not,
			// then default to comma.
		strDelimiter = (strDelimiter || ",");

		// Create a regular expression to parse the CSV values.
		var objPattern = new RegExp(
				(
						// Delimiters.
						"(\\" + strDelimiter + "|\\r?\\n|\\r|^)" +

						// Quoted fields.
						"(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|" +

						// Standard fields.
						"([^\"\\" + strDelimiter + "\\r\\n]*))"
				),
				"gi"
				);


			// Create an array to hold our data. Give the array
			// a default empty first row.
		var arrData = [[]];

			// Create an array to hold our individual pattern
			// matching groups.
		var arrMatches = null;

			// Keep looping over the regular expression matches
			// until we can no longer find a match.
		while (arrMatches = objPattern.exec( strData )){

					// Get the delimiter that was found.
				var strMatchedDelimiter = arrMatches[ 1 ];

					// Check to see if the given delimiter has a length
					// (is not the start of string) and if it matches
					// field delimiter. If id does not, then we know
					// that this delimiter is a row delimiter.
				if (
						strMatchedDelimiter.length &&
						(strMatchedDelimiter != strDelimiter)
						){
							// Since we have reached a new row of data,
							// add an empty row to our data array.
						arrData.push( [] );
				}

					// Now that we have our delimiter out of the way,
					// let's check to see which kind of value we
					// captured (quoted or unquoted).
				if (arrMatches[ 2 ]){

							// We found a quoted value. When we capture
							// this value, unescape any double quotes.
						var strMatchedValue = arrMatches[ 2 ].replace(
								new RegExp( "\"\"", "g" ),
								"\""
								);
				} else {
							// We found a non-quoted value.
						var strMatchedValue = arrMatches[ 3 ];
				}


				// Now that we have our value string, let's add
				// it to the data array.
				arrData[ arrData.length - 1 ].push( strMatchedValue );
		}

		// Return the parsed data.
		return( arrData );
	};

	var _ajaxRequest = function (requests, stateUrl, successCallback, errorCallback) {
		var pendingElements = [];
		var processedRequests = [];
		var fields = [];

		$.each(requests, function(i, r) {
			pendingElements[i] = {
				target: r['target'],
				loaders: r['loaders']
			};

			if (r['data']) {
				var pluginNameSpace = r['pluginNameSpace'];

				$.each(r['data'], function(j, field) {
					field['name'] = field['name'].replace(pluginNameSpace, 'r['+i+'][arguments]');
					fields.push(field);
				});
			}

			processedRequests[i] = {
				id: r['id'],
				extension: r['extension'],
				plugin: r['plugin'],
				controller: r['controller'],
				action: r['action'],
				arguments: r['arguments'],
				settingsHash: r['settingsHash'],
				chash: r['chash'],
				tsSource: r['tsSource']
			};
		});

		$data = $.hijaxParam({r: processedRequests, evts: eventsToListen})+'&'+$.hijaxParam(fields)+'&eID=extbase_hijax_dispatcher&L='+EXTBASE_HIJAX.sys_language_uid;

		if (typeof successCallback !== 'function') {
			successCallback = function (data, textStatus, jqXHR) {
				if (data['redirect'] && data['redirect'].url) {
					_redirectToURL(data['redirect'].url);
				} else {
					if (!data['validation-errors'] && typeof window.History != 'undefined' && window.History.enabled && typeof stateUrl != 'undefined' && stateUrl) {
						History.pushState({hijax: true, custom: true, tstamp: new Date().getTime()}, null, stateUrl);
					}

					$.each(EXTBASE_HIJAX.beforeLoadElement, function (i, f) {
						try {
							eval(f);
						} catch (err) {
						}
					});

					$.each(pendingElements, function (i, pendingElement) {
						try {
							var target = pendingElement['target'];
							var loaders = pendingElement['loaders'];
							$(target).hideHijaxLoader(loaders);
						} catch (err) {
						}
					});

					$.each(data['original'], function (i, r) {
						var element = $('#' + r['id']);
						if (element) {
							element.loadHijaxData(r['response'], r['preventMarkupUpdate']);
						}
					});

					$.each(data['affected'], function (i, r) {
						$.each(listeners[r['id']], function (i, element) {
							element = $(element);
							if (element) {
								element.loadHijaxData(r['response'], r['preventMarkupUpdate']);
							}
						});
					});

					$.each(EXTBASE_HIJAX.onLoadElement, function (i, f) {
						try {
							eval(f);
						} catch (err) {
						}
					});
				}
			};
		}

		if (typeof errorCallback !== 'function') {
			errorCallback = function(jqXHR, textStatus, errorThrown) {
				error = true;
				if (jqXHR.status == 401) {
					try {
						data = jQuery.parseJSON(jqXHR.responseText);
						error = false;
					} catch (err) {
						error = true;
					}
				}
				if (error) {
					$.each(pendingElements, function(i, pendingElement) {
						try {
							var target = pendingElement['target'];
							var loaders = pendingElement['loaders'];
							$(target).hideHijaxLoader(loaders);
							$(target).showMessage(EXTBASE_HIJAX.errorMessage);
						} catch (err) {
						}
					});
				} else {
					successCallback(data, textStatus, jqXHR);
				}
			};
		}

		var ajaxRequest = $.ajax({
			url: EXTBASE_HIJAX.url,
			type: "POST",
			//crossDomain: true,
			data: $data,
			fields: fields,
			dataType: "json",
			//pendingElements: pendingElements,
			success: successCallback,
			error: errorCallback
		});
	};

	var _processAnimation = function (animation) {
		var $animation = $(animation);

		$animation.find('> .hijax-content > .hijax-element').each(function(i, scene) {
			switch ($(scene).data('hijax-element-type')) {
				case 'scene':
					var $scene = $(scene);
					$scene.data('animation', $animation);

					var scenes = [$scene];
					if (!$animation.data('scenes')) {
						$animation.data('currentScene', $scene);
						$animation.data('scenes', scenes);
					} else {
						scenes = $animation.data('scenes');
						scenes.push($scene);
						$animation.data('scenes', scenes);
						$scene.css('display', 'none');
					}

					var sceneId = scenes.length;
					if ($scene.data('hijax-scene-id')) {
						sceneId = _evalStr.call($scene, $scene.data('hijax-scene-id'));
					}
					$animation.data('scene-'+sceneId, $scene);

					break;
				default:
					$(scene).css('display', 'none');
					break;
			}
		});

		if ($animation.data('currentScene')) {
			var $currentScene = $animation.data('currentScene');
			if ($currentScene.data('hijax-scene-duration')) {
				var currentSceneDuration = _evalStr.call($currentScene, $currentScene.data('hijax-scene-duration'));

				if (currentSceneDuration) {
					setTimeout(
						function() {
							var nextSceneId = _evalStr.call($currentScene, $currentScene.data('hijax-scene-next-scene-id'));
							var $animation = $currentScene.data('animation');
							var $nextScene = $animation.data('scene-'+nextSceneId);

								// TODO: implement nice fancy animation here
							$currentScene.css('display', 'none');
							$nextScene.css('display', 'block');
						}, currentSceneDuration
					);
				}
			}
		}
	};

	var _process = function (elements) {
		if (elements && elements.length > 0) {
			var requests = [];
			$.each(elements, function(i, element) {
				var el = $(element);
				if (el.data('hijax-processed')) {
					return true; //continue
				} else {
					el.data('hijax-processed', true);
				}
				switch (el.attr('data-hijax-element-type')) {
					case 'animation':
							_processAnimation(el);
						break;
					case 'listener':
						var listenerId = el.attr('data-hijax-listener-id');
						if (el.attr('data-hijax-listener-events') && el.attr('data-hijax-listener-events').length > 0) {
							var eventNames = _parseCSV(el.attr('data-hijax-listener-events'));
							if (eventNames.length && eventNames[0].length) {
								_addEvents(listenerId, eventNames[0]);
								if (!listeners[listenerId]) {
									listeners[listenerId] = [];
								}
								listeners[listenerId].push(el);
							}
						}
						break;
					case 'conditional':
						try {
							var prevValue = el.data('conditional-prevValue');
							var val = _evalStr.call(el, el.attr('data-hijax-condition'));
							var animate = _evalStr.call(el, el.attr('data-hijax-animate'));
							var thenTarget = el.find('> .hijax-content');
							var elseTarget = el.find('> .hijax-content-else');

							if (!val) {
								elseTarget.removeClass('hijax-display-none').css('display', 'block').css('visibility', 'visible');
								var targetHeight = elseTarget.outerHeight();
								var startingHeight = thenTarget.outerHeight();
								thenTarget.css('display', 'block').addClass('hijax-display-none');

									// animate only if this is a first eval call or if value has changed
								animate = animate && (typeof prevValue == 'undefined' || prevValue != val);

								if (!ajaxCallback && animate) {
									elseTarget.stop().css('height', startingHeight).animate({
										height: targetHeight
									}, baseAnimationSpeed / 2, 'linear', function() {
											// Animation complete.
										$(this).css('height', 'auto');
									});
//									if (parseInt(elseTarget.css('height'), 10) == targetHeight) {
//										$(this).css('height', 'auto');
//									}
								}
							} else {
								thenTarget.removeClass('hijax-display-none').css('display', 'block').css('visibility', 'visible');
								elseTarget.css('display', 'block').addClass('hijax-display-none');
							}
							el.data('conditional-prevValue', val);
						} catch (err) {
							el.css('display', 'none'); 
						}

						break;
					case 'form':
						el.bind('submit', function(e) {
							if (typeof e.isPropagationStopped === 'function' && e.isPropagationStopped()) {
								return false;
							}
							e.preventDefault(); // <-- important
							var requests = [];
							var target = $(this).parents('.hijax-element[data-hijax-listener-id="'+$(this).attr('data-hijax-settings')+'"]');
							var loaders = EXTBASE_HIJAX.defaultLoaderTarget ? EXTBASE_HIJAX.defaultLoaderTarget : null;
							if ($(this).attr('data-hijax-loaders')) {
								loaders = _evalStr.call($(this), $(this).attr('data-hijax-loaders'));
							}
							target.showHijaxLoader(loaders);
							var fields = $(this).formToArray();
							var pluginNameSpace = $(this).attr('data-hijax-namespace');

							var el = {
								id: $(this).attr('id'),
								extension: $(this).attr('data-hijax-extension'),
								plugin: $(this).attr('data-hijax-plugin'),
								controller: $(this).attr('data-hijax-controller'),
								action: $(this).attr('data-hijax-action'),
								arguments: $(this).attr('data-hijax-arguments'),
								settingsHash: $(this).attr('data-hijax-settings'),
								target: target,
								loaders: loaders,
								data: fields,
								tsSource: '',
								pluginNameSpace: pluginNameSpace
							};

							requests.push(el);

							_ajaxRequest(requests, $(this).attr('action'));
						});
						break;
					case 'ajax':
						var target = $(this);
						if (!$(this).attr('data-hijax-ajax-tssource')) {
							target = $(this).parents('.hijax-element[data-hijax-listener-id="'+$(this).attr('data-hijax-settings')+'"]');
						}
						var loaders = EXTBASE_HIJAX.defaultLoaderTarget ? EXTBASE_HIJAX.defaultLoaderTarget : null;
						if ($(this).attr('data-hijax-loaders')) {
							loaders = _evalStr.call($(this), $(this).attr('data-hijax-loaders'));
						}

						target.showHijaxLoader(loaders);

							// ajax request
						var el = {
							id: $(element).attr('id') ? $(element).attr('id') : '',
							extension: $(element).attr('data-hijax-extension') ? $(element).attr('data-hijax-extension') : '',
							plugin: $(element).attr('data-hijax-plugin') ? $(element).attr('data-hijax-plugin') : '',
							controller: $(element).attr('data-hijax-controller') ? $(element).attr('data-hijax-controller') : '',
							action: $(element).attr('data-hijax-action') ? $(element).attr('data-hijax-action') : '',
							arguments: $(element).attr('data-hijax-arguments') ? $(element).attr('data-hijax-arguments') : '',
							settingsHash: $(element).attr('data-hijax-settings') ? $(element).attr('data-hijax-settings') : '',
							tsSource: $(element).attr('data-hijax-ajax-tssource') ? $(element).attr('data-hijax-ajax-tssource') : '',
							target: target,
							loaders: loaders
						};

						requests.push(el);
						break;
					case 'link':
						el.bind('click', function(e) {
							e.preventDefault(); // <-- important
							var requests = [];
							var target = $(this).parents('.hijax-element[data-hijax-listener-id="'+$(this).attr('data-hijax-settings')+'"]');
							var loaders = EXTBASE_HIJAX.defaultLoaderTarget ? EXTBASE_HIJAX.defaultLoaderTarget : null;
							if ($(this).attr('data-hijax-loaders')) {
								loaders = _evalStr.call($(this), $(this).attr('data-hijax-loaders'));
							}
							target.showHijaxLoader(loaders);

							var el = {
								id: target.attr('id'),
								extension: $(this).attr('data-hijax-extension'),
								plugin: $(this).attr('data-hijax-plugin'),
								controller: $(this).attr('data-hijax-controller'),
								action: $(this).attr('data-hijax-action'),
								arguments: $(this).attr('data-hijax-arguments'),
								settingsHash: $(this).attr('data-hijax-settings'),
								chash: $(this).attr('data-hijax-chash'),
								target: target,
								tsSource: '',
								loaders: loaders
							};

							requests.push(el);

							_ajaxRequest(requests, $(this).attr('href'));
						});
						break;
					default: 
						break;
				}
			});

			if (requests.length>0) {
				_ajaxRequest(requests);
			}
		}
	};

	/*
	 * Public methods 
	 */

	$.fn.showMessage = function(msg) {
		var element = $(this);
		if (element.attr('data-hijax-result-target')) {
			element = _evalStr.call(element, element.attr('data-hijax-result-target'));
		}
		var content = element.find('> .'+EXTBASE_HIJAX.contentClass);

		var startingHeight = content.height();
		element.css('height', startingHeight);
		content.append('<p class="hijax-error">' + msg + '</p>');

		element.addClass('hijax-element-forced-visible-overflow');
		element.stop().animate({
			height: content.outerHeight()
		}, baseAnimationSpeed / 2, 'linear', function() {
				// Animation complete.
		});

		content.find('> .hijax-error').animate({
			opacity: 100
		}, baseAnimationSpeed);

		setTimeout(
			function() {
				var startingHeight = content.height();
				content.find('> .hijax-error:last').fadeOut(300, function() {
					var content = $(this).parent();
					var element = content.parent();
					$(this).remove();
					element.css('height', startingHeight);
					element.stop().animate({
						height: content.outerHeight()
					}, baseAnimationSpeed / 4, 'linear', function() {
							// Animation complete.
						element.removeClass('hijax-element-forced-visible-overflow');
						$(window).trigger('resize');
					});
				});
			}, 5000
		);

		return this;
	};

	$.fn.loadHijaxData = function(response, preventMarkupUpdate) {
		ajaxCallback = true;

		if (preventMarkupUpdate) {
			if ($('#extbase_hijax_js_callback').length>0) {
				$('#extbase_hijax_js_callback').html(response);
			} else {
				$('body').append('<div id="extbase_hijax_js_callback" style="display: none !important;">'+response+'</div>');
			}
		} else {
			var element = $(this);
			var content = element.find('> .'+EXTBASE_HIJAX.contentClass);

			if (element.attr('data-hijax-result-target')) {
				content = _evalStr.call(element, element.attr('data-hijax-result-target'));
				var wrapResult = false;
				if (element.attr('data-hijax-result-wrap')) {
					wrapResult = _evalStr.call(element, element.attr('data-hijax-result-wrap'));
				}
				if (wrapResult) {
					response = '<div class="hijax-element"><div class="'+EXTBASE_HIJAX.contentClass+'">'+response+'</div><div class="'+EXTBASE_HIJAX.loadingClass+'"></div></div>';
				}
			}

			if (content) {
				element.removeClass(EXTBASE_HIJAX.fallbackClass);
				var startingHeight = content.css('overflow', 'hidden').outerHeight();
				$.each(EXTBASE_HIJAX.unloadElement, function(i, f) {
					try {
						f(content);
					} catch (err) {
					}
				});
				element = jQuery(content.outer(response));
				if (startingHeight > 0) {
					element.css('height', startingHeight);
				}
				content = element.find('> .'+EXTBASE_HIJAX.contentClass);

				var newElements = element.find('.hijax-element');
				if (jQuery(element[0]).hasClass('hijax-element')) {
					newElements.push(element[0]);
				}
				newElements.extbaseHijax(true);

				$.each(EXTBASE_HIJAX.initElement, function(i, f) {
					try {
						f(element);
					} catch (err) {
					}
				});

				var contentStartingOverflow = content.css('overflow');
				var endingHeight = content.css('overflow', 'hidden').outerHeight();

				if (startingHeight != endingHeight && startingHeight > 0 && endingHeight > 0) {
					element.stop().animate({
						height: endingHeight
					}, baseAnimationSpeed, 'linear', function() {
							// Animation complete.
						$(this).css('height', 'auto');
						$(this).find('> .'+EXTBASE_HIJAX.contentClass).css('overflow', contentStartingOverflow);
					});
				} else {
					content.css('overflow', contentStartingOverflow);
					element.css('height', 'auto');
				}
			}
		}

		ajaxCallback = false;

		return this;
	};

	$.fn.showHijaxLoader = function(loaders) {
		if (!loaders) {
			var element = $(this);
			if (element.attr('data-hijax-result-target')) {
				element = _evalStr.call(element, element.attr('data-hijax-result-target'));
			}

			if (element.attr('data-hijax-loaders')) {
				loaders = _evalStr.call(element, element.attr('data-hijax-loaders'));
			} else {
				loaders = element.find('> .'+EXTBASE_HIJAX.loadingClass);
			}
		}

		$.each(loaders, function(i, loader) {
			try {
				loader = $(loader);
				if (!loader.data('targetOpacity')) {
					loader.data('targetOpacity', loader.css('opacity'));
					loader.css('opacity', 0);
				}
				loader.show();
				var afterShow = function() {
					// Animation complete.
				};
				loader.stop().animate(
					{
						opacity: loader.data('targetOpacity')
					}
					, baseAnimationSpeed
					, 'linear'
					, afterShow
				);
				if (loader.css('opacity')==loader.data('targetOpacity')) {
					afterShow();
				}
			} catch (err) {
			}
		});

		return this;
	};

	$.fn.hideHijaxLoader = function(loaders) {
		if (!loaders) {
			var element = $(this);
			if (element.attr('data-hijax-result-target')) {
				element = _evalStr.call(element, element.attr('data-hijax-result-target'));
			}

			if (element.attr('data-hijax-loaders')) {
				loaders = _evalStr.call(element, element.attr('data-hijax-loaders'));
			} else {
				loaders = element.find('> .'+EXTBASE_HIJAX.loadingClass);
			}
		}

		$.each(loaders, function(i, loader) {
			try {
				loader = $(loader);

				if (!loader.data('targetOpacity')) {
					loader.data('targetOpacity', loader.css('opacity'));
				}

				var afterHide = function() {
					// Animation complete.
					loader.hide();
				};
				loader.stop().animate(
					{
						opacity: 0
					}
					, baseAnimationSpeed / 2
					, 'linear'
					, afterHide
				);
				if (parseFloat(loader.css('opacity'), 10)==0.00) {
					afterHide();
				}
			} catch (err) {
			}
		});
	};

	$.fn.outer = function(val){
		if (val) {
			var content = $(val);
			content.insertBefore(this);
			$(this).remove();
			return content;
		} else {
			return $("<div>").append($(this).clone()).html(); 
		}
	};

	$.fn.extbaseHijax = function(process, force) {
		if (!$(this).length) {
			return this;
		}

		var addedElements = _addElements(this);
		var removedElements = _clearElements();

		if (process) {
			_process(force ? this : addedElements);
		}

		return this;
	};

	$.extbaseHijax = function(options) {
	};

	$.extbaseHijax.start = function() {
		if (!startedTimer) {
			startedTimer = true;
			window.setInterval(_timer, timerInterval);
			_process(elements);
		}
	};

	/**
	 * formToArray() gathers form element data into an array of objects that can
	 * be passed to any of the following ajax functions: $.get, $.post, or load.
	 * Each object in the array has both a 'name' and 'value' property.  An example of
	 * an array for a simple login form might be:
	 *
	 * [ { name: 'username', value: 'jresig' }, { name: 'password', value: 'secret' } ]
	 *
	 * It is this array that is passed to pre-submit callback functions provided to the
	 * ajaxSubmit() and ajaxForm() methods.
	 */
	$.fn.formToArray = function(semantic) {
		var a = [];
		if (this.length === 0) {
			return a;
		}

		var form = this[0];
		var els = semantic ? form.getElementsByTagName('*') : form.elements;
		if (!els) {
			return a;
		}

		var i,j,n,v,el,max,jmax;
		for(i=0, max=els.length; i < max; i++) {
			el = els[i];
			n = el.name;
			if (!n) {
				continue;
			}

			if (semantic && form.clk && el.type == "image") {
				// handle image inputs on the fly when semantic == true
				if(!el.disabled && form.clk == el) {
					a.push({name: n, value: $(el).val(), type: el.type });
					a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
				}
				continue;
			}

			v = $.fieldValue(el, true);
			if (v && v.constructor == Array) {
				for(j=0, jmax=v.length; j < jmax; j++) {
					a.push({name: n, value: v[j]});
				}
			}
			else if (v !== null && typeof v != 'undefined') {
				a.push({name: n, value: v, type: el.type});
			}
		}

		if (!semantic && form.clk) {
			// input type=='image' are not found in elements array! handle it here
			var $input = $(form.clk), input = $input[0];
			n = input.name;
			if (n && !input.disabled && input.type == 'image') {
				a.push({name: n, value: $input.val()});
				a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
			}
		}
		return a;
	};

	/**
	 * Serializes form data into a 'submittable' string. This method will return a string
	 * in the format: name1=value1&amp;name2=value2
	 */
	$.fn.formSerialize = function(semantic) {
		//hand off to jQuery.param for proper encoding
		return $.hijaxParam(this.formToArray(semantic));
	};

	/**
	 * Serializes all field elements in the jQuery object into a query string.
	 * This method will return a string in the format: name1=value1&amp;name2=value2
	 */
	$.fn.fieldSerialize = function(successful) {
		var a = [];
		this.each(function() {
			var n = this.name;
			if (!n) {
				return;
			}
			var v = $.fieldValue(this, successful);
			if (v && v.constructor == Array) {
				for (var i=0,max=v.length; i < max; i++) {
					a.push({name: n, value: v[i]});
				}
			}
			else if (v !== null && typeof v != 'undefined') {
				a.push({name: this.name, value: v});
			}
		});
		//hand off to jQuery.param for proper encoding
		return $.hijaxParam(a);
	};

	/**
	 * Returns the value(s) of the element in the matched set.  For example, consider the following form:
	 *
	 *  <form><fieldset>
	 *	  <input name="A" type="text" />
	 *	  <input name="A" type="text" />
	 *	  <input name="B" type="checkbox" value="B1" />
	 *	  <input name="B" type="checkbox" value="B2"/>
	 *	  <input name="C" type="radio" value="C1" />
	 *	  <input name="C" type="radio" value="C2" />
	 *  </fieldset></form>
	 *
	 *  var v = $(':text').fieldValue();
	 *  // if no values are entered into the text inputs
	 *  v == ['','']
	 *  // if values entered into the text inputs are 'foo' and 'bar'
	 *  v == ['foo','bar']
	 *
	 *  var v = $(':checkbox').fieldValue();
	 *  // if neither checkbox is checked
	 *  v === undefined
	 *  // if both checkboxes are checked
	 *  v == ['B1', 'B2']
	 *
	 *  var v = $(':radio').fieldValue();
	 *  // if neither radio is checked
	 *  v === undefined
	 *  // if first radio is checked
	 *  v == ['C1']
	 *
	 * The successful argument controls whether or not the field element must be 'successful'
	 * (per http://www.w3.org/TR/html4/interact/forms.html#successful-controls).
	 * The default value of the successful argument is true.  If this value is false the value(s)
	 * for each element is returned.
	 *
	 * Note: This method *always* returns an array.  If no valid value can be determined the
	 *	array will be empty, otherwise it will contain one or more values.
	 */
	$.fn.fieldValue = function(successful) {
		for (var val=[], i=0, max=this.length; i < max; i++) {
			var el = this[i];
			var v = $.fieldValue(el, successful);
			if (v === null || typeof v == 'undefined' || (v.constructor == Array && !v.length)) {
				continue;
			}
			v.constructor == Array ? $.merge(val, v) : val.push(v);
		}
		return val;
	};

	/**
	 * Returns the value of the field element.
	 */
	$.fieldValue = function(el, successful) {
		var n = el.name, t = el.type, tag = el.tagName.toLowerCase();
		if (successful === undefined) {
			successful = true;
		}

		if (successful && (!n || el.disabled || t == 'reset' || t == 'button' ||
			(t == 'checkbox' || t == 'radio') && !el.checked ||
			(t == 'submit' || t == 'image') && el.form && el.form.clk != el ||
			tag == 'select' && el.selectedIndex == -1)) {
				return null;
		}

		if (tag == 'select') {
			var index = el.selectedIndex;
			if (index < 0) {
				return null;
			}
			var a = [], ops = el.options;
			var one = (t == 'select-one');
			var max = (one ? index+1 : ops.length);
			for(var i=(one ? index : 0); i < max; i++) {
				var op = ops[i];
				if (op.selected) {
					var v = op.value;
					if (!v) { // extra pain for IE...
						v = (op.attributes && op.attributes['value'] && !(op.attributes['value'].specified)) ? op.text : op.value;
					}
					if (one) {
						return v;
					}
					a.push(v);
				}
			}
			return a;
		}
		return $(el).val();
	};

	$.hijaxParam = function ( a, traditional ) {
		var prefix,
			s = [],
			rbracket = /\[\]$/,
			r20 = /%20/g,
			add = function( key, value ) {
				// If value is a function, invoke it and return its value
				value = $.isFunction( value ) ? value() : ( value == null ? "" : value );
				s[ s.length ] = encodeURIComponent( key ) + "=" + encodeURIComponent( value );
			},
			buildParams = function( prefix, obj, traditional, add ) {
				var name;

				if ( $.isArray( obj ) ) {
					// Serialize array item.
					$.each( obj, function( i, v ) {
						if ( traditional || rbracket.test( prefix ) ) {
							// Treat each array item as a scalar.
							add( prefix, v );

						} else {
							// Item is non-scalar (array or object), encode its numeric index.
							buildParams( prefix + "[" + ( typeof v === "object" ? i : "" ) + "]", v, traditional, add );
						}
					});

				} else if ( !traditional && $.type( obj ) === "object" ) {
					// Serialize object item.
					for ( name in obj ) {
						buildParams( prefix + "[" + name + "]", obj[ name ], traditional, add );
					}

				} else {
					// Serialize scalar item.
					add( prefix, obj );
				}
			};

		// Set traditional to true for $ <= 1.3.2 behavior.
		if ( traditional === undefined ) {
			traditional = $.ajaxSettings && $.ajaxSettings.traditional;
		}

		// If an array was passed in, assume that it is an array of form elements.
		if ( $.isArray( a ) || ( a.$ && !$.isPlainObject( a ) ) ) {
			// Serialize the form elements
			$.each( a, function() {
				add( this.name, this.value );
			});

		} else {
			// If traditional, encode the "old" way (the way 1.3.2 or older
			// did it), otherwise encode params recursively.
			for ( prefix in a ) {
				buildParams( prefix, a[ prefix ], traditional, add );
			}
		}

		// Return the resulting serialization
		return s.join( "&" ).replace( r20, "+" );
	};

	$.fn.submitFormWithHijax = function(options) {
		var $this = $(this);
		if ($this.length !== 1) {
			return this;
		}

		options = options || {};
		var requests = [];
		var target = $(this).parents('.hijax-element[data-hijax-listener-id="'+$(this).attr('data-hijax-settings')+'"]');
		var loaders = null;
		var fields = $(this).formToArray();
		var pluginNameSpace = $(this).attr('data-hijax-namespace');

		var el = {
			id: $(this).attr('id'),
			extension: $(this).attr('data-hijax-extension'),
			plugin: $(this).attr('data-hijax-plugin'),
			controller: $(this).attr('data-hijax-controller'),
			action: $(this).attr('data-hijax-action'),
			arguments: $(this).attr('data-hijax-arguments'),
			settingsHash: $(this).attr('data-hijax-settings'),
			target: target,
			loaders: loaders,
			data: fields,
			tsSource: '',
			pluginNameSpace: pluginNameSpace
		};

		requests.push(el);

		_ajaxRequest(requests, $(this).attr('action'), options['success'], options['error']);

		return this;
	};


	$.hijax = function (settings, pendingElement, loaders) {
		var requests = [];
		var el = {
			id: settings.id,
			extension: settings.extension,
			plugin: settings.plugin,
			controller: settings.controller,
			action: settings.action,
			format: settings.format,
			arguments: settings.arguments,
			settingsHash: settings.settingsHash,
			chash: settings.chash
		};

		requests.push(el);
		var fields = [];
		var pluginNameSpace = settings.namespace;

		function hA(namespace, arr) {
			if (typeof arr !== 'undefined' && arr !== null) {
				$.each(arr, function(i, f) {
					if (typeof f != 'object') {
						fields.push({name: namespace+'['+i+']', value: f});
					} else {
						// object
						hA(namespace+'['+i+']', f);
					}
				});
			}
		}

		hA('r[0][arguments]', settings.data);

		$data = $.hijaxParam({r: requests, evts: eventsToListen})+'&'+$.hijaxParam(fields)+'&eID=extbase_hijax_dispatcher&L='+EXTBASE_HIJAX.sys_language_uid;

		if (pendingElement) {
			pendingElement.showHijaxLoader(loaders);
		}

		if (settings.type && settings.type=='DOWNLOAD') {
			function parseURL(url) {
				var o = {
					strictMode: false,
					key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
					parser: {
						strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
						loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/\/?)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/ // Added one optional slash to post-protocol to catch file:/// (should restrict this)
					}
				};

				var m   = o.parser[o.strictMode ? "strict" : "loose"].exec(url), uri = {}, i = 14;
				while (i--) {uri[o.key[i]] = m[i] || "";}

				return uri;
			}

			var downloadUrl = '';

			uri = parseURL(EXTBASE_HIJAX.url);

			if (uri.query) {
				downloadUrl = EXTBASE_HIJAX.url+'&'+$data;
			} else {
				downloadUrl = EXTBASE_HIJAX.url+'?'+$data;
			}

			if (uri.host=="") {
				downloadUrl = window.location.protocol + '//' + window.location.host + (window.location.port ? (':' + window.location.port) : '') + (uri.directory ? '' : '/') + downloadUrl;
			}

			var iframe = document.createElement("iframe");
			iframe.src = downloadUrl;
			iframe.style.display = "none";
			document.body.appendChild(iframe);
		} else {
			settings.url = EXTBASE_HIJAX.url;
			settings.type = "POST";
			//settings.crossDomain = true;
			settings.data = $data;
			settings.dataType = "json";
			settings.pendingElement = pendingElement;
			settings.loaderElements = loaders;
			settings.parentSuccessCallback = settings.success;
			settings.parentErrorCallback = settings.error;
			settings.success = function(data, textStatus, jqXHR) {
				if (this.parentSuccessCallback) {
					this.parentSuccessCallback(data, textStatus, jqXHR);
				}
				if (data['redirect'] && data['redirect'].url) {
					_redirectToURL(data['redirect'].url);
				} else {
					if (this.pendingElement) {
						$.each(EXTBASE_HIJAX.beforeLoadElement, function(i, f) {
							try {
								eval(f);
							} catch (err) {
							}
						});
						if (this.pendingElement) {
							this.pendingElement.hideHijaxLoader(this.loaderElements);
						}
						$.each(data['original'], function(i, r) {
							var element = $('#'+r['id']);
							if (element.length > 1) {
								element.loadHijaxData(r['response'], r['preventMarkupUpdate']);
							}
						});
						$.each(data['affected'], function(i, r) {
							$.each(listeners[r['id']], function(i, element) {
								element = $(element);
								if (element.length > 1) {
									element.loadHijaxData(r['response'], r['preventMarkupUpdate']);
								}
							});
						});
						$.each(EXTBASE_HIJAX.onLoadElement, function(i, f) {
							try {
								eval(f);
							} catch (err) {
							}
						});
					}
				}
			};
			settings.error = function(jqXHR, textStatus, errorThrown) {
				if (this.parentErrorCallback) {
					this.parentErrorCallback(jqXHR, textStatus, errorThrown);
				}
				if (this.pendingElement) {
					this.pendingElement.hideHijaxLoader(this.loaderElements);
					this.pendingElement.showMessage(EXTBASE_HIJAX.errorMessage);
				}
			};

			return $.ajax(settings);
		}
	};
})(jQuery);

jQuery(document).ready(function(){
	jQuery('.hijax-element').extbaseHijax();
	jQuery.extbaseHijax.start();
});