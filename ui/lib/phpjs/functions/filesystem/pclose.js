function pclose (handle) {
    // http://kevin.vanzonneveld.net
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: var handle = popen('http://kevin.vanzonneveld.net/pj_test_supportfile_1.htm', 'r');
    // *     example 1: pclose(handle);
    // *     returns 1: true
    if (!handle || handle.opener !== 'popen') {
        return false;
    }

    try {
        delete this.php_js.resourceDataPointer[handle.id];
        delete this.php_js.resourceData[handle.id]; // Free up memory
    } catch (e) {
        return false;
    }
    return true;
}
