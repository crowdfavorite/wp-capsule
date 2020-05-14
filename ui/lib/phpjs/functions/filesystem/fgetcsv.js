function fgetcsv (handle, length, delimiter, enclosure, escape) {
    // http://kevin.vanzonneveld.net
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // -    depends on: str_getcsv
    // *     example 1: fopen('http://kevin.vanzonneveld.net/pj_test_supportfile_1.htm', 'r');
    // *     example 1: fgetcsv(handle, 1);
    // *     returns 1: '<'

    var start = 0,
        fullline = '';

    if (!this.php_js || !this.php_js.resourceData || !this.php_js.resourceDataPointer || length !== undefined && !length) {
        return false;
    }

    start = this.php_js.resourceDataPointer[handle.id];

    if (start === undefined || !this.php_js.resourceData[handle.id][start]) {
        return false; // Resource was already closed or already reached the end of the file
    }

    fullline = this.php_js.resourceData[handle.id].slice(start, this.php_js.resourceData[handle.id].indexOf('\n', start) + 1);
    if (fullline === '') {
        fullline = this.php_js.resourceData[handle.id].slice(start); // Get to rest of the file
    }

    length = (length === undefined || fullline.length < length) ? fullline.length : Math.floor(length / 2) || 1; // two bytes per character (or surrogate), but ensure at least one

    this.php_js.resourceDataPointer[handle.id] += length; // Leaves the pointer one higher apparently than in fgets/fgetss
    return this.str_getcsv(this.php_js.resourceData[handle.id].substr(start, length), delimiter, enclosure, escape);
}
