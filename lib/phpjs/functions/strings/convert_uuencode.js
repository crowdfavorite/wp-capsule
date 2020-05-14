function convert_uuencode (str) {
    // http://kevin.vanzonneveld.net
    // +   original by: Ole Vrijenhoek
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   reimplemented by: Ole Vrijenhoek
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // -    depends on: is_scalar
    // *     example 1: convert_uuencode("test\ntext text\r\n");
    // *     returns 1: '0=&5S=`IT97AT('1E>'0-"@``'
    // shortcut
    var chr = function (c) {
        return String.fromCharCode(c);
    };

    if (!str || str == "") {
        return chr(0);
    } else if (!this.is_scalar(str)) {
        return false;
    }

    var c = 0,
        u = 0,
        i = 0,
        a = 0;
    var encoded = "",
        tmp1 = "",
        tmp2 = "",
        bytes = {};

    // divide string into chunks of 45 characters
    var chunk = function () {
        bytes = str.substr(u, 45);
        for (i in bytes) {
            bytes[i] = bytes[i].charCodeAt(0);
        }
        if (bytes.length != 0) {
            return bytes.length;
        } else {
            return 0;
        }
    };

    while (chunk() !== 0) {
        c = chunk();
        u += 45;

        // New line encoded data starts with number of bytes encoded.
        encoded += chr(c + 32);

        // Convert each char in bytes[] to a byte
        for (i in bytes) {
            tmp1 = bytes[i].charCodeAt(0).toString(2);
            while (tmp1.length < 8) {
                tmp1 = "0" + tmp1;
            }
            tmp2 += tmp1;
        }

        while (tmp2.length % 6) {
            tmp2 = tmp2 + "0";
        }

        for (i = 0; i <= (tmp2.length / 6) - 1; i++) {
            tmp1 = tmp2.substr(a, 6);
            if (tmp1 == "000000") {
                encoded += chr(96);
            } else {
                encoded += chr(parseInt(tmp1, 2) + 32);
            }
            a += 6;
        }
        a = 0;
        tmp2 = "";
        encoded += "\n";
    }

    // Add termination characters
    encoded += chr(96) + "\n";

    return encoded;
}
