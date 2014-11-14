function using(name, values, func){
	for (var i = 0, count = values.length; i < count; i++) {
		if (Object.prototype.toString.call(values[i]) !== '[object Array]') {
			values[i] = [values[i]];
		}
		func.apply(this, values[i]);
		jasmine.currentEnv_.currentSpec.description += ' (with "' + name + '" using ' + JSON.stringify(values[i]) + ')';
	}
}

if (!Function.prototype.bind) {
	Function.prototype.bind = function (oThis) {
		if (typeof this !== "function") {
			// closest thing possible to the ECMAScript 5 internal IsCallable function
			throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
		}

		var aArgs = Array.prototype.slice.call(arguments, 1),
			fToBind = this,
			fNOP = function () {},
			fBound = function () {
				return fToBind.apply(this instanceof fNOP && oThis
					? this
					: oThis,
					aArgs.concat(Array.prototype.slice.call(arguments)));
			};

		fNOP.prototype = this.prototype;
		fBound.prototype = new fNOP();

		return fBound;
	};
}

describe("LocalStorage", function() {

	beforeEach(function() {
		$('#hStorageFrame').remove();
		$.hStorage.testSetup.call(window);
		$.hStorage.initHTTPSLocalStorage.call(window);
	});

	afterEach(function() {
		$('#hStorageFrame').remove();
	});

	using("valid values", ['123', {x: 1, y: '2'}, 456, true, [1,2,3]], function(value){
		var key = 'sample';
		var valueRead = null;

		it("test setter / getter", function() {
			runs(function() {
				$.hStorage.set(key, value);
				$.hStorage.get(key, null, function(val) {
					valueRead = val;
				});
			});

			waitsFor(function() {
				return valueRead != null;
			}, "The value read from local storage should not be null", 3000);

			runs(function() {
				if (typeof valueRead !== 'string') {
					valueReadTest = JSON.stringify(valueRead);
				} else {
					valueReadTest = valueRead;
				}
				if (typeof value !== 'string') {
					valueTest = JSON.stringify(value);
				} else {
					valueTest = value;
				}
				expect(valueReadTest).toBe(valueTest);
			});
		});
	});

	it("test parallel setter / getter", function() {
		var values = ['123', {x: 1, y: '2'}, 456, true, [1,2,3]];
		var key = 'sample';
		var valuesRead = [];

		runs(function () {
			for (var i = 0, count = values.length; i < count; i++) {
				var value = values[i];
				$.hStorage.set(key + i, value);
				$.hStorage.get(key + i, null, function (val) {
					valuesRead[this] = val;
				}.bind(i));
			}
		});

		waitsFor(function() {
			var allValuesRead = true;
			for (var i = 0, count = values.length; i < count; i++) {
				allValuesRead = allValuesRead && valuesRead[i] != null;
				if (!allValuesRead) {
					break;
				}
			}
			return allValuesRead;

		}, "The value read from local storage should not be null", 3000);

		runs(function () {
			for (var i = 0, count = values.length; i < count; i++) {
				var value = values[i];
				if (typeof valuesRead[i] !== 'string') {
					valueReadTest = JSON.stringify(valuesRead[i]);
				} else {
					valueReadTest = valuesRead[i];
				}
				if (typeof value !== 'string') {
					valueTest = JSON.stringify(value);
				} else {
					valueTest = value;
				}
				expect(valueReadTest).toBe(valueTest);
			}
		});
	});
});