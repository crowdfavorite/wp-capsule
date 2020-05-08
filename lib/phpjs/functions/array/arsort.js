function arsort (inputArr, sort_flags) {
    // http://kevin.vanzonneveld.net
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // %        note 1: SORT_STRING (as well as natsort and natcasesort) might also be
    // %        note 1: integrated into all of these functions by adapting the code at
    // %        note 1: http://sourcefrog.net/projects/natsort/natcompare.js
    // %        note 2: The examples are correct, this is a new way
    // %        note 2: Credits to: http://javascript.internet.com/math-related/bubble-sort.html
    // %        note 3: This function deviates from PHP in returning a copy of the array instead
    // %        note 3: of acting by reference and returning true; this was necessary because
    // %        note 3: IE does not allow deleting and re-adding of properties without caching
    // %        note 3: of property position; you can set the ini of "phpjs.strictForIn" to true to
    // %        note 3: get the PHP behavior, but use this only if you are in an environment
    // %        note 3: such as Firefox extensions where for-in iteration order is fixed and true
    // %        note 3: property deletion is supported. Note that we intend to implement the PHP
    // %        note 3: behavior by default if IE ever does allow it; only gives shallow copy since
    // %        note 3: is by reference in PHP anyways
    // %        note 4: Since JS objects' keys are always strings, and (the
    // %        note 4: default) SORT_REGULAR flag distinguishes by key type,
    // %        note 4: if the content is a numeric string, we treat the
    // %        note 4: "original type" as numeric.
    // -    depends on: i18n_loc_get_default
    // *     example 1: data = {d: 'lemon', a: 'orange', b: 'banana', c: 'apple'};
    // *     example 1: data = arsort(data);
    // *     returns 1: data == {a: 'orange', d: 'lemon', b: 'banana', c: 'apple'}
    // *     example 2: ini_set('phpjs.strictForIn', true);
    // *     example 2: data = {d: 'lemon', a: 'orange', b: 'banana', c: 'apple'};
    // *     example 2: arsort(data);
    // *     results 2: data == {a: 'orange', d: 'lemon', b: 'banana', c: 'apple'}
    // *     returns 2: true
    var valArr = [], valArrLen = 0,
        k, i, ret, sorter, that = this,
        strictForIn = false,
        populateArr = {};

    switch (sort_flags) {
    case 'SORT_STRING':
        // compare items as strings
        sorter = function (a, b) {
            return that.strnatcmp(b, a);
        };
        break;
    case 'SORT_LOCALE_STRING':
        // compare items as strings, based on the current locale (set with i18n_loc_set_default() as of PHP6)
        var loc = this.i18n_loc_get_default();
        sorter = this.php_js.i18nLocales[loc].sorting;
        break;
    case 'SORT_NUMERIC':
        // compare items numerically
        sorter = function (a, b) {
            return (a - b);
        };
        break;
    case 'SORT_REGULAR':
        // compare items normally (don't change types)
    default:
        sorter = function (b, a) {
            var aFloat = parseFloat(a),
                bFloat = parseFloat(b),
                aNumeric = aFloat + '' === a,
                bNumeric = bFloat + '' === b;
            if (aNumeric && bNumeric) {
                return aFloat > bFloat ? 1 : aFloat < bFloat ? -1 : 0;
            } else if (aNumeric && !bNumeric) {
                return 1;
            } else if (!aNumeric && bNumeric) {
                return -1;
            }
            return a > b ? 1 : a < b ? -1 : 0;
        };
        break;
    }

    // BEGIN REDUNDANT
    this.php_js = this.php_js || {};
    this.php_js.ini = this.php_js.ini || {};
    // END REDUNDANT
    strictForIn = this.php_js.ini['phpjs.strictForIn'] && this.php_js.ini['phpjs.strictForIn'].local_value && this.php_js.ini['phpjs.strictForIn'].local_value !== 'off';
    populateArr = strictForIn ? inputArr : populateArr;


    // Get key and value arrays
    for (k in inputArr) {
        if (inputArr.hasOwnProperty(k)) {
            valArr.push([k, inputArr[k]]);
            if (strictForIn) {
                delete inputArr[k];
            }
        }
    }
    valArr.sort(function (a, b) {
        return sorter(a[1], b[1]);
    });
 
    // Repopulate the old array
    for (i = 0, valArrLen = valArr.length; i < valArrLen; i++) {
        populateArr[valArr[i][0]] = valArr[i][1];
    }

    return strictForIn || populateArr;
}
